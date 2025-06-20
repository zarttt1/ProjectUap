<?php
require_once 'config/database.php';
requireAdmin(); // Only admin can manage users

$database = new Database();
$db = $database->getConnection();

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $query = "INSERT INTO users (username, password, nama_lengkap, email, role, status) 
                         VALUES (:username, :password, :nama_lengkap, :email, :role, :status)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':username', $_POST['username']);
                $stmt->bindParam(':password', $password_hash);
                $stmt->bindParam(':nama_lengkap', $_POST['nama_lengkap']);
                $stmt->bindParam(':email', $_POST['email']);
                $stmt->bindParam(':role', $_POST['role']);
                $stmt->bindParam(':status', $_POST['status']);
                $stmt->execute();
                
                logActivity($_SESSION['user_id'], 'Menambah user baru: ' . $_POST['username'] . ' (' . $_POST['role'] . ')', 'users', $db->lastInsertId());
                break;
                
            case 'edit':
                if (!empty($_POST['password'])) {
                    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $query = "UPDATE users SET username = :username, password = :password, 
                             nama_lengkap = :nama_lengkap, email = :email, role = :role, status = :status WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':password', $password_hash);
                } else {
                    $query = "UPDATE users SET username = :username, nama_lengkap = :nama_lengkap, 
                             email = :email, role = :role, status = :status WHERE id = :id";
                    $stmt = $db->prepare($query);
                }
                $stmt->bindParam(':username', $_POST['username']);
                $stmt->bindParam(':nama_lengkap', $_POST['nama_lengkap']);
                $stmt->bindParam(':email', $_POST['email']);
                $stmt->bindParam(':role', $_POST['role']);
                $stmt->bindParam(':status', $_POST['status']);
                $stmt->bindParam(':id', $_POST['id']);
                $stmt->execute();
                
                logActivity($_SESSION['user_id'], 'Mengedit user: ' . $_POST['username'], 'users', $_POST['id']);
                break;
                
            case 'delete':
                if ($_POST['id'] != $_SESSION['user_id']) {
                    // Get user info for logging
                    $query = "SELECT username FROM users WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':id', $_POST['id']);
                    $stmt->execute();
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $query = "DELETE FROM users WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':id', $_POST['id']);
                    $stmt->execute();
                    
                    logActivity($_SESSION['user_id'], 'Menghapus user: ' . $user['username'], 'users', $_POST['id']);
                }
                break;
        }
        header("Location: users.php");
        exit();
    }
}

// Get all users
$query = "SELECT * FROM users ORDER BY nama_lengkap";
$stmt = $db->prepare($query);
$stmt->execute();
$users_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - INVESTO</title>
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
        
        .table-brown thead {
            background: linear-gradient(45deg, var(--brown-primary), var(--brown-secondary));
            color: white;
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
        
        .modal-header {
            background: linear-gradient(45deg, var(--brown-primary), var(--brown-secondary));
            color: white;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--brown-primary);
            box-shadow: 0 0 0 0.2rem rgba(139, 69, 19, 0.25);
        }

        select:disabled {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: none !important;
            background-color: #e9ecef; /* warna Bootstrap default untuk input nonaktif */
            cursor: not-allowed;
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
                    <h1 class="h2 page-title"><i class="fas fa-users"></i> Kelola User</h1>
                    <button type="button" class="btn btn-brown" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fas fa-plus"></i> Tambah User
                    </button>
                </div>
                
                <div class="card content-card shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-brown">
                                    <tr>
                                        <th>No</th>
                                        <th>Username</th>
                                        <th>Nama Lengkap</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Tanggal Dibuat</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($users_list as $user): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo $user['username']; ?></td>
                                        <td><?php echo $user['nama_lengkap']; ?></td>
                                        <td><?php echo $user['email'] ?: '-'; ?></td>
                                        <td><?php echo getRoleBadge($user['role']); ?></td>
                                        <td>
                                            <?php if ($user['status'] == 'aktif'): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Non-aktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo formatTanggal($user['created_at']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo $user['username']; ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php else: ?>
                                            <button class="btn btn-sm btn-secondary" disabled title="Tidak dapat menghapus akun sendiri">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah User Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" name="nama_lengkap" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" required>
                                <option value="">Pilih Role</option>
                                <option value="admin">Admin</option>
                                <option value="staff">Staff Gudang</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Non-aktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-brown">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" id="edit_username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password <small class="text-muted">(kosongkan jika tidak ingin mengubah)</small></label>
                            <input type="password" class="form-control" name="password" id="edit_password">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" name="nama_lengkap" id="edit_nama_lengkap" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" id="edit_role" required>
                                <option value="admin">Admin</option>
                                <option value="staff">Staff Gudang</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="edit_status" required>
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Non-aktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-brown">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editUser(user) {
        const sessionUserId = <?php echo $_SESSION['user_id']; ?>;
        const isTargetAdmin = user.role === 'admin';

        // Isi nilai form
        document.getElementById('edit_id').value = user.id;
        document.getElementById('edit_username').value = user.username;
        document.getElementById('edit_nama_lengkap').value = user.nama_lengkap;
        document.getElementById('edit_email').value = user.email || '';
        document.getElementById('edit_password').value = '';

        // Disable role input SELALU
        const roleSelect = document.getElementById('edit_role');
        roleSelect.value = user.role;
        roleSelect.disabled = true;

        // inject hidden input untuk role
        if (!document.getElementById('hidden_role')) {
            const hiddenRole = document.createElement('input');
            hiddenRole.type = 'hidden';
            hiddenRole.name = 'role';
            hiddenRole.value = user.role;
            hiddenRole.id = 'hidden_role';
            roleSelect.parentNode.appendChild(hiddenRole);
        } else {
            document.getElementById('hidden_role').value = user.role;
        }

        // Status: hanya disable kalau target adalah admin
        const statusSelect = document.getElementById('edit_status');
        statusSelect.value = user.status;
        statusSelect.disabled = isTargetAdmin;

        // inject hidden input untuk status jika admin
        if (isTargetAdmin) {
            if (!document.getElementById('hidden_status')) {
                const hiddenStatus = document.createElement('input');
                hiddenStatus.type = 'hidden';
                hiddenStatus.name = 'status';
                hiddenStatus.value = user.status;
                hiddenStatus.id = 'hidden_status';
                statusSelect.parentNode.appendChild(hiddenStatus);
            } else {
                document.getElementById('hidden_status').value = user.status;
            }
            } else {
                const hiddenStatus = document.getElementById('hidden_status');
                if (hiddenStatus) hiddenStatus.remove();
            }

            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
        
        function deleteUser(id, username) {
            if (confirm('Apakah Anda yakin ingin menghapus user "' + username + '"?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
