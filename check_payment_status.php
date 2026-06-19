<?php
header('Content-Type: application/json; charset=utf-8');

// ===== KONEKSI DATABASE =====
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

// Support beberapa nama variabel koneksi
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

// ===== AMBIL NO INVOICE =====
$no_invoice = trim($_GET['no_invoice'] ?? '');

if ($no_invoice === '') {
  echo json_encode([
    'status' => 'error',
    'message' => 'No invoice kosong.'
  ]);
  exit;
}

// ===== CEK DATA BOOKING =====
$sql = "SELECT * FROM booking WHERE no_invoice = ? LIMIT 1";
$stmt = $db->prepare($sql);

if (!$stmt) {
  echo json_encode([
    'status' => 'error',
    'message' => 'Prepare SQL gagal: ' . $db->error
  ]);
  exit;
}

$stmt->bind_param("s", $no_invoice);
$stmt->execute();

$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
  echo json_encode([
    'status' => 'error',
    'message' => 'Data booking tidak ditemukan.'
  ]);
  exit;
}

$row = $result->fetch_assoc();

$payment_status = $row['payment_status'] ?? 'pending';

// ===== KALAU BELUM BAYAR, JANGAN KIRIM INVOICE =====
if ($payment_status !== 'paid') {
  echo json_encode([
    'status' => 'ok',
    'payment_status' => $payment_status,
    'message' => 'Pembayaran belum berhasil.'
  ]);
  exit;
}

// ===== KALAU SUDAH PAID, KIRIM DATA INVOICE =====
$peserta = (int)($row['peserta'] ?? 1);
if ($peserta < 1) {
  $peserta = 1;
}

$harga_satuan = (int)($row['harga_satuan'] ?? 0);
$total_harga = (int)($row['total_harga'] ?? 0);
$total_invoice = (int)($row['total_invoice'] ?? $total_harga);

if ($total_harga <= 0) {
  $total_harga = $harga_satuan * $peserta;
}

if ($total_invoice <= 0) {
  $total_invoice = $total_harga;
}

$dp = (int)($row['dp'] ?? 0);
$sisa_bayar = (int)($row['sisa_bayar'] ?? 0);
$metode_bayar = $row['metode_bayar'] ?? 'full';

$waktu = '';
if (isset($row['waktu_kegiatan'])) {
  $waktu = $row['waktu_kegiatan'];
} elseif (isset($row['waktu'])) {
  $waktu = $row['waktu'];
}

$invoice = [
  'status' => 'ok',
  'id' => $row['id'] ?? null,
  'no_invoice' => $row['no_invoice'] ?? '',
  'nama' => $row['nama'] ?? '',
  'email' => $row['email'] ?? '',
  'whatsapp' => $row['whatsapp'] ?? '',
  'medsos' => $row['medsos'] ?? '',
  'paket' => $row['paket'] ?? '',
  'tanggal' => $row['tanggal'] ?? '',
  'waktu' => $waktu,
  'waktu_kegiatan' => $waktu,
  'peserta' => $peserta,
  'harga_satuan' => $harga_satuan,
  'total_harga' => $total_harga,
  'total_invoice' => $total_invoice,
  'metode_bayar' => $metode_bayar,
  'dp' => $dp,
  'sisa_bayar' => $sisa_bayar,
  'status_bayar' => $row['status_bayar'] ?? '',
  'payment_status' => $payment_status,
  'paid_at' => $row['paid_at'] ?? '',
  'items' => [
    [
      'produk' => $row['paket'] ?? '',
      'harga' => $harga_satuan,
      'qty' => $peserta,
      'subtotal' => $total_harga
    ]
  ]
];

echo json_encode([
  'status' => 'ok',
  'payment_status' => 'paid',
  'message' => 'Pembayaran berhasil.',
  'invoice' => $invoice
]);
exit;
?>