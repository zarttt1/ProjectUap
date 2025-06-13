<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Ganti nama tabel jadi 'pengguna'
    $stmt = $conn->prepare("SELECT id, username, password FROM pengguna WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($id, $user, $hashed_password);
        $stmt->fetch();

        if ($password == $hashed_password) {
            $_SESSION['id'] = $id;
            $_SESSION['username'] = $username;

            header("Location: loadpage.html");
            exit();
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head><title>Login Gagal</title></head>
<body>
    <h1>Login Gagal</h1>
    <p><?php echo $error; ?></p>
    <a href="login.html">Kembali</a>
</body>
</html>
