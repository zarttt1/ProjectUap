<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kode = $_POST['kode'];
    $nama = $_POST['nama'];
    $kategori = $_POST['kategori'];
    $stok = $_POST['stok'];
    $harga = $_POST['harga'];

    $stmt = $conn->prepare("INSERT INTO barang (kode, nama, kategori, stok, harga) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssii", $kode, $nama, $kategori, $stok, $harga);

    if ($stmt->execute()) {
        header("Location: barang.html");
    } else {
        echo "Gagal: " . $stmt->error;
    }
}
?>
