<?php
header('Content-Type: application/json; charset=utf-8');

// ===== KONEKSI DATABASE =====
// Sesuaikan kalau file koneksi kamu beda nama
$koneksiFile = null;

foreach (['koneksi.php', 'config.php', 'db.php', 'database.php'] as $file) {
  if (file_exists($file)) {
    $koneksiFile = $file;
    break;
  }
}

if (!$koneksiFile) {
  echo json_encode([
    'status' => 'error',
    'message' => 'File koneksi database tidak ditemukan.'
  ]);
  exit;
}

require_once $koneksiFile;

// Support nama variabel koneksi yang berbeda
if (isset($conn)) {
  $db = $conn;
} elseif (isset($koneksi)) {
  $db = $koneksi;
} elseif (isset($mysqli)) {
  $db = $mysqli;
} else {
  echo json_encode([
    'status' => 'error',
    'message' => 'Variabel koneksi database tidak ditemukan.'
  ]);
  exit;
}

if (!$db) {
  echo json_encode([
    'status' => 'error',
    'message' => 'Koneksi database gagal.'
  ]);
  exit;
}

// ===== AMBIL DATA DARI DETAIL.HTML =====
$nama          = trim($_POST['nama'] ?? '');
$email         = trim($_POST['email'] ?? '');
$whatsapp      = trim($_POST['whatsapp'] ?? '');
$medsos        = trim($_POST['medsos'] ?? '');
$paket         = trim($_POST['paket'] ?? '');
$tanggal       = trim($_POST['tanggal'] ?? '');
$waktu         = trim($_POST['waktu'] ?? ($_POST['waktu_kegiatan'] ?? ''));
$peserta       = (int)($_POST['peserta'] ?? 1);
$sumber        = trim($_POST['sumber'] ?? 'detail');

$harga_satuan  = (int)($_POST['harga_satuan'] ?? 0);
$total_harga   = (int)($_POST['total_harga'] ?? 0);
$total_invoice = (int)($_POST['total_invoice'] ?? $total_harga);

$metode_bayar  = trim($_POST['metode_bayar'] ?? 'full');
$dp            = (int)($_POST['dp'] ?? 0);
$sisa_bayar    = (int)($_POST['sisa_bayar'] ?? 0);
$nominal_bayar = (int)($_POST['nominal_bayar'] ?? 0);

if ($peserta < 1) {
  $peserta = 1;
}

if ($total_harga <= 0) {
  $total_harga = $harga_satuan * $peserta;
}

if ($total_invoice <= 0) {
  $total_invoice = $total_harga;
}

// ===== VALIDASI SERVER =====
if ($nama === '') {
  echo json_encode(['status' => 'error', 'message' => 'Nama wajib diisi.']);
  exit;
}

if ($whatsapp === '') {
  echo json_encode(['status' => 'error', 'message' => 'WhatsApp wajib diisi.']);
  exit;
}

if ($medsos === '') {
  echo json_encode(['status' => 'error', 'message' => 'Media sosial wajib diisi.']);
  exit;
}

if ($paket === '') {
  echo json_encode(['status' => 'error', 'message' => 'Paket tidak ditemukan.']);
  exit;
}

if ($tanggal === '') {
  echo json_encode(['status' => 'error', 'message' => 'Tanggal wajib diisi.']);
  exit;
}

if ($waktu === '') {
  echo json_encode(['status' => 'error', 'message' => 'Perkiraan waktu wajib diisi.']);
  exit;
}

if (!preg_match('/^\d{2}\.\d{2}$/', $waktu)) {
  echo json_encode(['status' => 'error', 'message' => 'Format waktu harus 10.00 atau 09.30.']);
  exit;
}

if ($metode_bayar !== 'full' && $metode_bayar !== 'dp') {
  echo json_encode(['status' => 'error', 'message' => 'Metode pembayaran tidak valid.']);
  exit;
}

// ===== LOGIKA PEMBAYARAN =====
if ($metode_bayar === 'full') {
  $dp = $total_invoice;
  $sisa_bayar = 0;
  $nominal_bayar = $total_invoice;
  $status_bayar = 'Menunggu Pembayaran';
} else {
  $min_dp = (int)ceil($total_invoice * 0.3);

  if ($dp < $min_dp) {
    echo json_encode([
      'status' => 'error',
      'message' => 'DP minimal 30%: Rp ' . number_format($min_dp, 0, ',', '.')
    ]);
    exit;
  }

  if ($dp >= $total_invoice) {
    echo json_encode([
      'status' => 'error',
      'message' => 'DP tidak boleh sama atau lebih besar dari total. Pilih Full Payment.'
    ]);
    exit;
  }

  $sisa_bayar = $total_invoice - $dp;
  $nominal_bayar = $dp;
  $status_bayar = 'Menunggu Pembayaran';
}

// ===== BUAT NOMOR INVOICE DAN PAYMENT ORDER =====
$no_invoice = 'PNT' . date('ymdHis') . rand(100, 999);
$payment_order_id = 'PAY-' . $no_invoice;
$payment_ref = 'REF-' . strtoupper(substr(md5($payment_order_id . time()), 0, 10));
$payment_status = 'pending';

// Untuk sekarang masih pakai QRIS statis di detail.html
// Kalau nanti pakai payment gateway asli, payment_url ini diisi dari gateway.
$payment_url = 'assets/qris.png';

$discount = 0;
$catatan = '';
$paid_at = null;

// ===== SIMPAN KE DATABASE =====
$sql = "INSERT INTO booking 
(
  nama,
  paket,
  tanggal,
  peserta,
  sumber,
  no_invoice,
  harga_satuan,
  total_harga,
  whatsapp,
  status_bayar,
  catatan,
  email,
  medsos,
  waktu_kegiatan,
  total_invoice,
  dp,
  sisa_bayar,
  discount,
  metode_bayar,
  payment_order_id,
  payment_ref,
  payment_status,
  payment_url,
  nominal_bayar,
  paid_at,
  created_at
)
VALUES
(
  ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
)";

$stmt = $db->prepare($sql);

if (!$stmt) {
  echo json_encode([
    'status' => 'error',
    'message' => 'Prepare SQL gagal: ' . $db->error
  ]);
  exit;
}

$stmt->bind_param(
  "sssissiissssssiiiisssssis",
  $nama,
  $paket,
  $tanggal,
  $peserta,
  $sumber,
  $no_invoice,
  $harga_satuan,
  $total_harga,
  $whatsapp,
  $status_bayar,
  $catatan,
  $email,
  $medsos,
  $waktu,
  $total_invoice,
  $dp,
  $sisa_bayar,
  $discount,
  $metode_bayar,
  $payment_order_id,
  $payment_ref,
  $payment_status,
  $payment_url,
  $nominal_bayar,
  $paid_at
);

if (!$stmt->execute()) {
  echo json_encode([
    'status' => 'error',
    'message' => 'Gagal menyimpan booking: ' . $stmt->error
  ]);
  exit;
}

$booking_id = $stmt->insert_id;

// ===== SIMPAN ITEM PAKET KE booking_items =====
$items_json = $_POST['items'] ?? '';
$items = json_decode($items_json, true);

if(!is_array($items) || empty($items)){
  $items = [[
    'produk' => $paket,
    'harga' => $harga_satuan,
    'qty' => $peserta,
    'subtotal' => $total_harga
  ]];
}

$stmtItem = $db->prepare("
  INSERT INTO booking_items 
  (booking_id, produk, harga, qty, subtotal)
  VALUES (?, ?, ?, ?, ?)
");

if(!$stmtItem){
  echo json_encode([
    'status' => 'error',
    'message' => 'Prepare item gagal: ' . $db->error
  ]);
  exit;
}

foreach($items as $it){
  $produk = trim($it['produk'] ?? $it['nama'] ?? '');
  $harga = intval($it['harga'] ?? 0);
  $qty = intval($it['qty'] ?? 1);

  if($qty < 1){
    $qty = 1;
  }

  $subtotal = intval($it['subtotal'] ?? ($harga * $qty));

  if($produk !== ''){
    $stmtItem->bind_param(
      "isiii",
      $booking_id,
      $produk,
      $harga,
      $qty,
      $subtotal
    );

    if(!$stmtItem->execute()){
      echo json_encode([
        'status' => 'error',
        'message' => 'Gagal menyimpan item booking: ' . $stmtItem->error
      ]);
      exit;
    }
  }
}

// ===== RESPONSE KE DETAIL.HTML =====
echo json_encode([
  'status' => 'ok',
  'message' => 'Pembayaran berhasil dibuat.',
  'booking_id' => $booking_id,
  'no_invoice' => $no_invoice,
  'payment_order_id' => $payment_order_id,
  'payment_ref' => $payment_ref,
  'payment_status' => $payment_status,
  'payment_url' => $payment_url,
  'nominal_bayar' => $nominal_bayar,
  'metode_bayar' => $metode_bayar
]);
exit;
?>