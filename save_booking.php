<?php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "pangandaran_db");
if ($conn->connect_error) {
    echo json_encode(['status'=>'error','message'=>'DB connection failed']);
    exit;
}
$conn->set_charset("utf8mb4");

// ── Auto-create tables
$conn->query("CREATE TABLE IF NOT EXISTS `booking` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `no_invoice`      VARCHAR(30),
  `nama`            VARCHAR(255),
  `email`           VARCHAR(100),
  `whatsapp`        VARCHAR(30),
  `medsos`          VARCHAR(100),
  `tanggal`         DATE,
  `waktu_kegiatan`  VARCHAR(100),
  `catatan`         TEXT,
  `sumber`          VARCHAR(30) DEFAULT 'booking',
  `metode_bayar`    VARCHAR(20) DEFAULT 'full',
  `total_harga`     INT DEFAULT 0,
  `discount`        INT DEFAULT 0,
  `total_invoice`   INT DEFAULT 0,
  `dp`              INT DEFAULT 0,
  `sisa_bayar`      INT DEFAULT 0,
  `status_bayar`    VARCHAR(30) DEFAULT 'Pending',
  `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS `booking_items` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT,
  `produk`     VARCHAR(255),
  `harga`      INT DEFAULT 0,
  `qty`        INT DEFAULT 1,
  `subtotal`   INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Pastikan kolom metode_bayar ada (untuk DB lama)
@$conn->query("ALTER TABLE booking ADD COLUMN IF NOT EXISTS metode_bayar VARCHAR(20) DEFAULT 'full'");
@$conn->query("ALTER TABLE booking ADD COLUMN IF NOT EXISTS discount INT DEFAULT 0");
@$conn->query("ALTER TABLE booking ADD COLUMN IF NOT EXISTS total_invoice INT DEFAULT 0");
@$conn->query("ALTER TABLE booking ADD COLUMN IF NOT EXISTS dp INT DEFAULT 0");
@$conn->query("ALTER TABLE booking ADD COLUMN IF NOT EXISTS sisa_bayar INT DEFAULT 0");

// ── Ambil input dari client
$nama           = trim($_POST['nama']           ?? '');
$email          = trim($_POST['email']          ?? '');
$whatsapp       = trim($_POST['whatsapp']       ?? '');
$medsos         = trim($_POST['medsos']         ?? '');
$tanggal        = $_POST['tanggal']             ?? date('Y-m-d');
$waktu_kegiatan = trim($_POST['waktu_kegiatan'] ?? '');
$catatan        = trim($_POST['catatan']        ?? '');
$sumber         = $_POST['sumber']              ?? 'booking';
$items_json     = $_POST['items']               ?? '[]';

// ── Metode bayar yang dipilih client (info saja untuk admin)
// Nilai: 'full' | 'dp' | 'tempo'
$metode_bayar = $conn->real_escape_string($_POST['metode_bayar'] ?? 'full');

// ── Validasi
if (!$nama || !$email) {
    echo json_encode(['status'=>'error','message'=>'Nama dan email wajib']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status'=>'error','message'=>'Format email tidak valid']);
    exit;
}

$items = json_decode($items_json, true) ?: [];
if (empty($items)) {
    echo json_encode(['status'=>'error','message'=>'Minimal 1 item']);
    exit;
}

// ── Hitung total
$total = 0;
foreach ($items as &$it) {
    $it['harga']    = intval($it['harga']   ?? 0);
    $it['qty']      = max(1, intval($it['qty'] ?? 1));
    $it['subtotal'] = $it['harga'] * $it['qty'];
    $total += $it['subtotal'];
}

$no_invoice    = 'PNT' . date('ymd') . str_pad(rand(1,9999), 4, '0', STR_PAD_LEFT);
$total_invoice = $total; // discount bisa admin tambahkan nanti

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// STATUS SELALU "Pending" SAAT CLIENT SUBMIT
// Seperti Shopee/Tokopedia: order masuk dulu → 
// admin konfirmasi pembayaran → baru status berubah
// 
// Metode bayar yang dipilih client hanya disimpan sebagai
// INFORMASI untuk admin, bukan penentu status otomatis
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
$status_bayar = 'Pending';
$dp_awal      = 0;
$sisa_awal    = $total_invoice;

// ── Simpan ke database
$stmt = $conn->prepare(
    "INSERT INTO booking
     (no_invoice, nama, email, whatsapp, medsos, tanggal, waktu_kegiatan, catatan,
      sumber, metode_bayar, total_harga, discount, total_invoice, dp, sisa_bayar, status_bayar)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
);
$discount = 0;
$stmt->bind_param(
    "ssssssssssiiiiis",
    $no_invoice, $nama, $email, $whatsapp, $medsos, $tanggal, $waktu_kegiatan, $catatan,
    $sumber, $metode_bayar, $total, $discount, $total_invoice, $dp_awal, $sisa_awal, $status_bayar
);

if (!$stmt->execute()) {
    echo json_encode(['status'=>'error','message'=>$stmt->error]);
    exit;
}
$bid = $conn->insert_id;

// ── Simpan item
$si = $conn->prepare(
    "INSERT INTO booking_items (booking_id, produk, harga, qty, subtotal)
     VALUES (?,?,?,?,?)"
);
foreach ($items as $it) {
    $si->bind_param("isiii", $bid, $it['produk'], $it['harga'], $it['qty'], $it['subtotal']);
    $si->execute();
}

// ── Kirim ke Google Sheets (opsional, isi URL jika sudah setup)
$gsUrl = ''; // Paste URL Google Apps Script di sini
if ($gsUrl) {
    $gsData = http_build_query([
        'no_invoice'    => $no_invoice,
        'nama'          => $nama,
        'email'         => $email,
        'whatsapp'      => $whatsapp,
        'tanggal'       => $tanggal,
        'metode_bayar'  => $metode_bayar,
        'total_invoice' => $total_invoice,
        'status'        => $status_bayar,
        'items'         => implode(', ', array_map(fn($it) => $it['produk'].' x'.$it['qty'], $items)),
    ]);
    @file_get_contents($gsUrl . '?' . $gsData);
}

// ── Response ke client
echo json_encode([
    'status'          => 'ok',
    'no_invoice'      => $no_invoice,
    'booking_id'      => $bid,
    'nama'            => $nama,
    'email'           => $email,
    'whatsapp'        => $whatsapp,
    'medsos'          => $medsos,
    'tanggal'         => $tanggal,
    'waktu_kegiatan'  => $waktu_kegiatan ?: '—',
    'metode_bayar'    => $metode_bayar,
    'items'           => $items,
    'total_harga'     => $total,
    'discount'        => $discount,
    'total_invoice'   => $total_invoice,
    'ammount_paid'    => $dp_awal,
    'outstanding'     => $sisa_awal,
    'status_bayar'    => $status_bayar,
    'tanggal_booking' => date('d M Y'),
]);

$conn->close();
?>