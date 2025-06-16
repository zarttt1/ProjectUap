<?php
require_once 'config/database.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_POST) {
    $database = new Database();
    $db = $database->getConnection();
    
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $query = "SELECT id, username, password, nama_lengkap, role, status FROM users WHERE username = :username AND status = 'aktif'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];
            
            // Log login activity
            logActivity($user['id'], 'Login ke sistem');
            
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak ditemukan atau akun tidak aktif!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - INVESTO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --brown-primary: #8B4513;
            --brown-secondary: #A0522D;
            --brown-light: #D2B48C;
            --brown-dark: #654321;
            --black-primary: #1a1a1a;
            --black-secondary: #2d2d2d;
            --black-light: #404040;
        }
        
        body {
            background: linear-gradient(135deg, var(--black-primary) 0%, var(--brown-dark) 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            overflow: hidden;
        }

        .bg-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('background.jpg') no-repeat center center fixed;
            background-size: cover;
            opacity: 0.4; /* Ubah nilai ini untuk transparansi */
            z-index: -1;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid var(--brown-light);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        }
        
        .brand-title {
            color: var(--brown-primary);
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .btn-brown {
            background: linear-gradient(45deg, var(--brown-primary), var(--brown-secondary));
            border: none;
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-brown:hover {
            background: linear-gradient(45deg, var(--brown-dark), var(--brown-primary));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 69, 19, 0.4);
            color: white;
        }
        
        .input-group-text {
            background: var(--brown-light);
            border-color: var(--brown-secondary);
            color: var(--brown-dark);
        }
        
        .form-control {
            border-color: var(--brown-secondary);
        }
        
        .form-control:focus {
            border-color: var(--brown-primary);
            box-shadow: 0 0 0 0.2rem rgba(139, 69, 19, 0.25);
        }
        
        .demo-accounts {
            background: var(--black-secondary);
            color: var(--brown-light);
            border-radius: 8px;
            padding: 10px;
        }
    </style>
</head>
<body>
    <div class="bg-overlay"></div>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="card login-card shadow-lg">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h2 class="brand-title"><i class="fas fa-boxes"></i> INVESTO</h2>
                            <p class="text-muted">Sistem Inventaris & Stok Barang</p>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['error']) && $_GET['error'] == 'access_denied'): ?>
                            <div class="alert alert-warning">Anda tidak memiliki akses ke halaman tersebut!</div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-brown w-100">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
