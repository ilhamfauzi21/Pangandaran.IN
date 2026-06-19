<?php
header('Content-Type: application/json; charset=utf-8');

/*
  payment_callback.php

  FUNGSI:
  - Dipanggil oleh payment gateway setelah pembayaran sukses
  - Untuk testing localhost, bisa dipanggil manual pakai secret
  - Mengubah payment_status menjadi paid
  - Mengubah status_bayar menjadi Lunas / DP
*/

// ===== SECRET CALLBACK =====
// Ganti secret ini dengan kode rahasia kamu sendiri.
// Jangan ditaruh di JavaScript / detail.html.
$CALLBACK_SECRET = 'GANTI_SECRET_PANGANDARAN_2026';

// ===== AMBIL INPUT CALLBACK =====
$rawInput = file_get_contents('php://input');
$jsonData = json_decode($rawInput, true);

$input = [];

if (is_array($jsonData)) {
  $input = $jsonData;
} else {
  $input = $_POST;
}

// Support testing via URL GET
if (empty($input)) {
  $input = $_GET;
}

$secret = trim($input['secret'] ?? '');
$no_invoice = trim($input['no_invoice'] ?? '');
$payment_order_id = trim($input['payment_order_id'] ?? '');
$payment_ref = trim($input['payment_ref'] ?? '');
$gateway_status = strtolower(trim($input['payment_status'] ?? $input['status'] ?? 'paid'));

// ===== VALIDASI SECRET =====
if ($secret !== $CALLBACK_SECRET) {
  echo json_encode([
    'status' => 'error',
    'message' => 'Secret callback tidak valid.'
  ]);
  exit;
}

// Minimal harus ada no_invoice atau payment_order_id
if ($no_invoice === '' && $payment_order_id === '') {
  echo json_encode([
    'status' => 'error',
    'message' => 'no_invoice atau payment_order_id wajib dikirim.'
  ]);
  exit;
}

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

// ===== CARI BOOKING =====
if ($no_invoice !== '') {
  $sql = "SELECT * FROM booking WHERE no_invoice = ? LIMIT 1";
  $stmt = $db->prepare($sql);
  $stmt->bind_param("s", $no_invoice);
} else {
  $sql = "SELECT * FROM booking WHERE payment_order_id = ? LIMIT 1";
  $stmt = $db->prepare($sql);
  $stmt->bind_param("s", $payment_order_id);
}

if (!$stmt) {
  echo json_encode([
    'status' => 'error',
    'message' => 'Prepare SQL gagal: ' . $db->error
  ]);
  exit;
}

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

$id = (int)$row['id'];
$metode_bayar = $row['metode_bayar'] ?? 'full';
$total_invoice = (int)($row['total_invoice'] ?? $row['total_harga'] ?? 0);
$nominal_bayar = (int)($row['nominal_bayar'] ?? 0);
$dp_lama = (int)($row['dp'] ?? 0);

// ===== TENTUKAN STATUS DARI GATEWAY =====
if (in_array($gateway_status, ['paid', 'settlement', 'capture', 'success', 'sukses', 'berhasil'])) {
  $payment_status = 'paid';
} elseif (in_array($gateway_status, ['expired', 'expire', 'kadaluarsa'])) {
  $payment_status = 'expired';
} elseif (in_array($gateway_status, ['failed', 'failure', 'deny', 'cancel', 'cancelled', 'gagal'])) {
  $payment_status = 'failed';
} else {
  $payment_status = 'pending';
}

// ===== KALAU BELUM PAID, UPDATE STATUS SAJA =====
if ($payment_status !== 'paid') {
  $update = $db->prepare("
    UPDATE booking 
    SET payment_status = ?,
        payment_ref = IF(? != '', ?, payment_ref)
    WHERE id = ?
  ");

  if (!$update) {
    echo json_encode([
      'status' => 'error',
      'message' => 'Prepare update gagal: ' . $db->error
    ]);
    exit;
  }

  $update->bind_param("sssi", $payment_status, $payment_ref, $payment_ref, $id);
  $update->execute();

  echo json_encode([
    'status' => 'ok',
    'message' => 'Status pembayaran diperbarui.',
    'payment_status' => $payment_status,
    'no_invoice' => $row['no_invoice']
  ]);
  exit;
}

// ===== KALAU PAID, HITUNG STATUS BAYAR =====
// ===== KALAU PAID, HITUNG STATUS BAYAR =====
$metode = strtolower(trim($metode_bayar));

if ($metode === 'full' || $metode === 'full payment' || $nominal_bayar >= $total_invoice) {
  // Client bayar lunas lewat QRIS
  $status_bayar = 'Lunas';
  $dp = $total_invoice;
  $sisa_bayar = 0;
  $nominal_bayar_final = $total_invoice;
  $tanggal_pelunasan = date('Y-m-d');
} else {
  // Client baru bayar DP
  $status_bayar = 'DP';

  if ($nominal_bayar > 0) {
    $dp = $nominal_bayar;
  } else {
    $dp = $dp_lama;
  }

  $sisa_bayar = max(0, $total_invoice - $dp);
  $nominal_bayar_final = $dp;
  $tanggal_pelunasan = null;
}

// ===== UPDATE DATABASE JADI PAID =====
$update = $db->prepare("
  UPDATE booking
  SET payment_status = 'paid',
      status_bayar = ?,
      dp = ?,
      sisa_bayar = ?,
      nominal_bayar = ?,
      tanggal_pelunasan = ?,
      payment_ref = IF(? != '', ?, payment_ref),
      paid_at = NOW()
  WHERE id = ?
");

if (!$update) {
  echo json_encode([
    'status' => 'error',
    'message' => 'Prepare update paid gagal: ' . $db->error
  ]);
  exit;
}

$update->bind_param(
  "siiisssi",
  $status_bayar,
  $dp,
  $sisa_bayar,
  $nominal_bayar_final,
  $tanggal_pelunasan,
  $payment_ref,
  $payment_ref,
  $id
);

if (!$update->execute()) {
  echo json_encode([
    'status' => 'error',
    'message' => 'Gagal update pembayaran: ' . $update->error
  ]);
  exit;
}

// ===== AUTO-KIRIM INVOICE KE WHATSAPP ADMIN (FONNTE) =====
$FONNTE_TOKEN = 'TEMPEL_TOKEN_FONNTE_ANDA';   // <- ganti dengan token device Fonnte
$WA_ADMIN     = '6287793827592';              // nomor WhatsApp admin tujuan

// ambil rincian paket dari booking_items
$li = '';
$itq = $db->prepare("SELECT produk, qty, subtotal FROM booking_items WHERE booking_id = ?");
if($itq){
  $itq->bind_param("i", $id);
  $itq->execute();
  $ir = $itq->get_result();
  while($it = $ir->fetch_assoc()){
    $li .= "- ".$it['produk']." x".$it['qty']." = Rp ".number_format((int)$it['subtotal'],0,',','.')."\n";
  }
}
if($li === '') $li = "- ".($row['paket'] ?? '-')."\n";

$pesan =
  "*INVOICE - Pangandaran.in*\n".
  "No. Invoice : ".($row['no_invoice'] ?? '-')."\n".
  "Nama        : ".($row['nama'] ?? '-')."\n".
  "WhatsApp    : ".($row['whatsapp'] ?? '-')."\n".
  "Tanggal     : ".($row['tanggal'] ?? '-')."\n".
  "----------------------------------\n".
  $li.
  "----------------------------------\n".
  "Status      : ".$status_bayar."\n".
  "Total       : Rp ".number_format($total_invoice,0,',','.')."\n".
  "Terbayar    : Rp ".number_format($nominal_bayar_final,0,',','.')."\n".
  "Sisa        : Rp ".number_format($sisa_bayar,0,',','.');

if($FONNTE_TOKEN !== '' && $FONNTE_TOKEN !== 'TEMPEL_TOKEN_FONNTE_ANDA'){
  $ch = curl_init('https://api.fonnte.com/send');
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => ['target' => $WA_ADMIN, 'message' => $pesan],
    CURLOPT_HTTPHEADER => ['Authorization: '.$FONNTE_TOKEN],
    CURLOPT_TIMEOUT => 20,
  ]);
  @curl_exec($ch);  // diabaikan errornya supaya pembayaran tetap sukses meski WA gagal
  curl_close($ch);
}
// ===== END AUTO-KIRIM =====

echo json_encode([
  'status' => 'ok',
  'message' => 'Pembayaran berhasil dikonfirmasi.',
  'payment_status' => 'paid',
  'status_bayar' => $status_bayar,
  'no_invoice' => $row['no_invoice'],
  'nominal_bayar' => $nominal_bayar_final,
  'sisa_bayar' => $sisa_bayar,
  'tanggal_pelunasan' => $tanggal_pelunasan
]);
exit;
?>