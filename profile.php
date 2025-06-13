<?php
require_once 'config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// Handle form submission
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $query = "UPDATE users SET nama_lengkap = :nama_lengkap, email = :email WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':nama_lengkap', $_POST['nama_lengkap']);
                $stmt->bindParam(':email', $_POST['email']);
                $stmt->bindParam(':id', $_SESSION['user_id']);
                
                if ($stmt->execute()) {
                    $_SESSION['nama_lengkap'] = $_POST['nama_lengkap'];
                    $message = "Profile berhasil diupdate!";
                    logActivity($_SESSION['user_id'], 'Mengupdate profile');
                } else {
                    $error = "Gagal mengupdate profile!";
                }
                break;
                
            case 'change_password':
                // Verify current password
                $query = "SELECT password FROM users WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $_SESSION['user_id']);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($_POST['current_password'], $user['password'])) {
                    if ($_POST['new_password'] === $_POST['confirm_password']) {
                        $new_password_hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                        $query = "UPDATE users SET password = :password WHERE id = :id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':password', $new_password_hash);
                        $stmt->bindParam(':id', $_SESSION['user_id']);
                        
                        if ($stmt->execute()) {
                            $message = "Password berhasil diubah!";
                            logActivity($_SESSION['user_id'], 'Mengubah password');
                        } else {
                            $error = "Gagal mengubah password!";
                        }
                    } else {
                        $error = "Konfirmasi password tidak cocok!";
                    }
                } else {
                    $error = "Password lama tidak benar!";
                }
                break;
        }
    }
}

// Get current user data
$query = "SELECT * FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $_SESSION['user_id']);
$stmt->execute();
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - INVESTO</title>
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
        
        .btn-brown {
            background: linear-gradient(45deg, var(--brown-primary), var(--brown-secondary));
            border: none;
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-brown:hover {
            background: linear-gradient(45deg, var(--brown-dark), var(--brown-primary));
            color: white;
            transform: translateY(-2px);
        }
        
        .content-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--brown-light);
        }
        
        .page-title {
            color: var(--brown-dark);
            font-weight: bold;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(45deg, var(--brown-primary), var(--brown-secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
            margin: 0 auto 20px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--brown-primary);
            box-shadow: 0 0 0 0.2rem rgba(139, 69, 19, 0.25);
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2 page-title"><i class="fas fa-user-edit"></i> Profile Saya</h1>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <!-- Profile Info -->
                    <div class="col-lg-4 mb-4">
                        <div class="card content-card">
                            <div class="card-body text-center">
                                <div class="profile-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <h4><?php echo $user_data['nama_lengkap']; ?></h4>
                                <p class="text-muted">@<?php echo $user_data['username']; ?></p>
                                <?php echo getRoleBadge($user_data['role']); ?>
                                <hr>
                                <div class="row text-center">
                                    <div class="col-6">
                                        <strong>Status</strong><br>
                                        <?php if ($user_data['status'] == 'aktif'): ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Non-aktif</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-6">
                                        <strong>Bergabung</strong><br>
                                        <small><?php echo date('d/m/Y', strtotime($user_data['created_at'])); ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Profile Form -->
                    <div class="col-lg-8">
                        <div class="card content-card mb-4">
                            <div class="card-header" style="background: linear-gradient(45deg, var(--brown-primary), var(--brown-secondary)); color: white;">
                                <h6 class="m-0"><i class="fas fa-edit"></i> Edit Profile</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_profile">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Username</label>
                                                <input type="text" class="form-control" value="<?php echo $user_data['username']; ?>" readonly>
                                                <small class="text-muted">Username tidak dapat diubah</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Role</label>
                                                <input type="text" class="form-control" value="<?php echo ucfirst($user_data['role']); ?>" readonly>
                                                <small class="text-muted">Role tidak dapat diubah</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Nama Lengkap</label>
                                        <input type="text" class="form-control" name="nama_lengkap" value="<?php echo $user_data['nama_lengkap']; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo $user_data['email']; ?>">
                                    </div>
                                    <button type="submit" class="btn btn-brown">
                                        <i class="fas fa-save"></i> Update Profile
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Change Password -->
                        <div class="card content-card">
                            <div class="card-header" style="background: linear-gradient(45deg, var(--brown-primary), var(--brown-secondary)); color: white;">
                                <h6 class="m-0"><i class="fas fa-key"></i> Ubah Password</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="change_password">
                                    <div class="mb-3">
                                        <label class="form-label">Password Lama</label>
                                        <input type="password" class="form-control" name="current_password" required>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Password Baru</label>
                                                <input type="password" class="form-control" name="new_password" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Konfirmasi Password Baru</label>
                                                <input type="password" class="form-control" name="confirm_password" required>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-brown">
                                        <i class="fas fa-key"></i> Ubah Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>