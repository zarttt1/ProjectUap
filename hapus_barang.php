<?php
require 'db.php';

if (isset($_GET['kode'])) {
    $kode = $_GET['kode'];

    $stmt = $conn->prepare("DELETE FROM barang WHERE kode=?");
    $stmt->bind_param("s", $kode);

    if ($stmt->execute()) {
        header("Location: barang.html");
    } else {
        echo "Gagal: " . $stmt->error;
    }
}
?>
