<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kode = $_POST['kode'];
    $nama = $_POST['nama'];
    $kategori = $_POST['kategori'];
    $stok = $_POST['stok'];
    $harga = $_POST['harga'];

    $stmt = $conn->prepare("UPDATE barang SET nama=?, kategori=?, stok=?, harga=? WHERE kode=?");
    $stmt->bind_param("ssiis", $nama, $kategori, $stok, $harga, $kode);

    if ($stmt->execute()) {
        header("Location: barang.html");
    } else {
        echo "Gagal: " . $stmt->error;
    }
}
?>
