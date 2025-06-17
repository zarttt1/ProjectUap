<?php
require_once 'config/database.php';
requireAdmin(); // Only admin can access

$database = new Database();
$db = $database->getConnection();

// Filter parameters
$tanggal_dari = $_GET['tanggal_dari'] ?? date('Y-m-01');
$tanggal_sampai = $_GET['tanggal_sampai'] ?? date('Y-m-d');
$user_id = $_GET['user_id'] ?? '';

// Build query with filters
$where_conditions = [];
$params = [];

$where_conditions[] = "DATE(l.created_at) BETWEEN :tanggal_dari AND :tanggal_sampai";
$params[':tanggal_dari'] = $tanggal_dari;
$params[':tanggal_sampai'] = $tanggal_sampai;

if (!empty($user_id)) {
    $where_conditions[] = "l.user_id = :user_id";
    $params[':user_id'] = $user_id;
}

$where_clause = implode(' AND ', $where_conditions);

// Get filtered logs
$query = "SELECT l.*, u.nama_lengkap, u.username, u.role 
          FROM log_aktivitas l 
          LEFT JOIN users u ON l.user_id = u.id 
          WHERE $where_clause
          ORDER BY l.created_at DESC 
          LIMIT 1000";
$stmt = $db->prepare($query);
$stmt->execute($params);
$log_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get users for filter
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
    <title>Log Aktivitas - INVESTO</title>
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
                    <h1 class="h2 page-title"><i class="fas fa-history"></i> Log Aktivitas</h1>
                </div>
                
                <!-- Filter Form -->
                <div class="card content-card shadow mb-4">
                    <div class="card-header" style="background: linear-gradient(45deg, var(--brown-primary), var(--brown-secondary)); color: white;">
                        <h6 class="m-0 font-weight-bold">Filter Log</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Tanggal Dari</label>
                                <input type="date" class="form-control" name="tanggal_dari" value="<?php echo $tanggal_dari; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tanggal Sampai</label>
                                <input type="date" class="form-control" name="tanggal_sampai" value="<?php echo $tanggal_sampai; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">User</label>
                                <select class="form-select" name="user_id">
                                    <option value="">Semua User</option>
                                    <?php foreach ($users_list as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo $user_id == $user['id'] ? 'selected' : ''; ?>>
                                        <?php echo $user['nama_lengkap'] . ' (' . ucfirst($user['role']) . ')'; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-brown">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                                <a href="log-aktivitas.php" class="btn btn-secondary">
                                    <i class="fas fa-refresh"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Log Table -->
                <div class="card content-card shadow">
                    <div class="card-header" style="background: linear-gradient(45deg, var(--brown-primary), var(--brown-secondary)); color: white;">
                        <h6 class="m-0 font-weight-bold">
                            Aktivitas Sistem (<?php echo count($log_list); ?> records)
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-sm">
                                <thead class="table-brown">
                                    <tr>
                                        <th>Waktu</th>
                                        <th>User</th>
                                        <th>Role</th>
                                        <th>Aktivitas</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($log_list as $log): ?>
                                    <tr>
                                        <td><?php echo formatTanggal($log['created_at']); ?></td>
                                        <td><?php echo $log['nama_lengkap'] ?? 'Unknown'; ?></td>
                                        <td><?php echo getRoleBadge($log['role'] ?? 'unknown'); ?></td>
                                        <td><?php echo $log['aktivitas']; ?></td>
                                        <td><?php echo $log['ip_address']; ?></td>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>