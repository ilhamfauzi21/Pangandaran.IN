<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

/*
  Supaya setelah admin edit / simpan / hapus,
  website tidak mengambil data lama dari cache browser.
*/
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

$conn = new mysqli("localhost", "root", "", "pangandaran_db");

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Koneksi database gagal'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$conn->set_charset("utf8mb4");

$id = $_GET['id'] ?? '';

/*
  Kalau ada id:
  dipakai oleh detail.html, contoh:
  get_paket_admin.php?id=citumang

  Kalau tidak ada id:
  dipakai oleh index.html untuk menampilkan semua paket.
*/
if ($id !== '') {
    $stmt = $conn->prepare("SELECT * FROM paket WHERE id = ? LIMIT 1");

    if (!$stmt) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'Query gagal disiapkan'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt->bind_param("s", $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row) {
        echo json_encode($row, JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(404);
        echo json_encode([
            'error' => true,
            'message' => 'Paket tidak ditemukan',
            'id' => $id
        ], JSON_UNESCAPED_UNICODE);
    }

    $stmt->close();
} else {
    $result = $conn->query("SELECT * FROM paket ORDER BY kategori, nama");

    if (!$result) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'Gagal mengambil data paket'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>