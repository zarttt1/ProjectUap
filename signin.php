<?php
require 'koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_lengkap = trim($_POST['fullname']);
    $username     = trim($_POST['username']);
    $email        = trim($_POST['email']);
    $password     = $_POST['password'];
    $konfirmasi   = $_POST['confirm_password'];

    if ($password !== $konfirmasi) {
        echo "Konfirmasi password tidak cocok.";
        exit;
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Cek username/email
    $cek = $conn->prepare("SELECT id FROM pengguna WHERE username = ? OR email = ?");
    $cek->bind_param("ss", $username, $email);
    $cek->execute();
    $cek->store_result();

    if ($cek->num_rows > 0) {
        echo "Username atau email sudah terdaftar.";
        exit;
    }

    // Simpan pengguna baru
    $stmt = $conn->prepare("INSERT INTO pengguna (nama_lengkap, username, email, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $nama_lengkap, $username, $email, $hashed_password);

    if ($stmt->execute()) {
        header("Location: login.html");
        exit;
    } else {
        echo "Gagal mendaftar: " . $stmt->error;
    }

    $stmt->close();
    $cek->close();
    $conn->close();
}
?>
