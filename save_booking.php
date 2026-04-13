<?php

$conn = new mysqli("localhost","root","","pangandaran_db");

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$nama = $_POST['nama'];
$paket = $_POST['paket'];
$tanggal = $_POST['tanggal'];
$peserta = $_POST['peserta'];

$sql = "INSERT INTO booking (nama,paket,tanggal,peserta)
VALUES ('$nama','$paket','$tanggal','$peserta')";

if ($conn->query($sql) === TRUE) {
    echo "Data booking berhasil disimpan";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();

?>