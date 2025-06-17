<?php
require_once 'config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

$stats = [];

// Total barang
$query = "SELECT COUNT(*) as total FROM barang";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_barang'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total kategori
$query = "SELECT COUNT(*) as total FROM kategori";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_kategori'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total supplier
$query = "SELECT COUNT(*) as total FROM supplier";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_supplier'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total users
$query = "SELECT COUNT(*) as total FROM users WHERE status = 'aktif'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Barang stok minimum
$query = "SELECT * FROM barang WHERE stok_saat_ini <= stok_minimum ORDER BY stok_saat_ini ASC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$stok_minimum = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Transaksi terbaru
$query = "SELECT t.*, b.nama_barang, u.nama_lengkap 
          FROM transaksi t 
          LEFT JOIN barang b ON t.barang_id = b.id 
          LEFT JOIN users u ON t.user_id = u.id 
          ORDER BY t.created_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$transaksi_terbaru = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - INVESTO</title>
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
        
        .stat-card {
            background: linear-gradient(135deg, white 0%, var(--brown-light) 100%);
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin-bottom: 15px;
        }
        
        .icon-brown { background: linear-gradient(45deg, var(--brown-primary), var(--brown-secondary)); }
        .icon-black { background: linear-gradient(45deg, var(--black-primary), var(--black-secondary)); }
        .icon-warning { background: linear-gradient(45deg, #ff6b35, #f7931e); }
        .icon-success { background: linear-gradient(45deg, #28a745, #20c997); }
        
        .content-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--brown-light);
        }
        
        .table-brown thead {
            background: linear-gradient(45deg, var(--brown-primary), var(--brown-secondary));
            color: white;
        }
        
        .badge-brown {
            background: var(--brown-primary);
            color: white;
        }
        
        .alert-brown {
            background: var(--brown-light);
            border-color: var(--brown-secondary);
            color: var(--brown-dark);
        }
        
        .page-title {
            color: var(--brown-dark);
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
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
                    <h1 class="h2 page-title"><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                    <div class="text-muted">
                        <i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i'); ?>
                    </div>
                </div>
                
                <!-- Welcome Message -->
                <div class="alert alert-brown mb-4">
                    <h5><i class="fas fa-hand-wave"></i> Selamat datang, <?php echo $_SESSION['nama_lengkap']; ?>!</h5>
                    <p class="mb-0">Anda login sebagai <?php echo getRoleBadge($_SESSION['role']); ?></p>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card h-100">
                            <div class="card-body text-center">
                                <div class="stat-icon icon-brown mx-auto">
                                    <i class="fas fa-boxes"></i>
                                </div>
                                <h3 class="text-brown-dark"><?php echo $stats['total_barang']; ?></h3>
                                <p class="text-muted mb-0">Total Barang</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card h-100">
                            <div class="card-body text-center">
                                <div class="stat-icon icon-black mx-auto">
                                    <i class="fas fa-tags"></i>
                                </div>
                                <h3 class="text-brown-dark"><?php echo $stats['total_kategori']; ?></h3>
                                <p class="text-muted mb-0">Kategori</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card h-100">
                            <div class="card-body text-center">
                                <div class="stat-icon icon-warning mx-auto">
                                    <i class="fas fa-truck"></i>
                                </div>
                                <h3 class="text-brown-dark"><?php echo $stats['total_supplier']; ?></h3>
                                <p class="text-muted mb-0">Supplier</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card h-100">
                            <div class="card-body text-center">
                                <div class="stat-icon icon-success mx-auto">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h3 class="text-brown-dark"><?php echo $stats['total_users']; ?></h3>
                                <p class="text-muted mb-0">Users Aktif</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Stok Minimum Alert -->
                    <div class="col-lg-6 mb-4">
                        <div class="card content-card">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-brown-dark">
                                    <i class="fas fa-exclamation-triangle text-warning"></i> 
                                    Alert Stok Minimum
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if (count($stok_minimum) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead class="table-brown">
                                                <tr>
                                                    <th>Barang</th>
                                                    <th>Stok</th>
                                                    <th>Min</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($stok_minimum as $item): ?>
                                                <tr>
                                                    <td><?php echo $item['nama_barang']; ?></td>
                                                    <td>
                                                        <span class="badge bg-danger">
                                                            <?php echo $item['stok_saat_ini']; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $item['stok_minimum']; ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-success mb-0">
                                        <i class="fas fa-check-circle"></i> 
                                        Semua stok barang dalam kondisi aman
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Transaksi Terbaru -->
                    <div class="col-lg-6 mb-4">
                        <div class="card content-card">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-brown-dark">
                                    <i class="fas fa-history"></i> 
                                    Transaksi Terbaru
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if (count($transaksi_terbaru) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead class="table-brown">
                                                <tr>
                                                    <th>Barang</th>
                                                    <th>Jenis</th>
                                                    <th>Jumlah</th>
                                                    <th>User</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($transaksi_terbaru as $transaksi): ?>
                                                <tr>
                                                    <td><?php echo $transaksi['nama_barang']; ?></td>
                                                    <td>
                                                        <?php if ($transaksi['jenis_transaksi'] == 'masuk'): ?>
                                                            <span class="badge bg-success">Masuk</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Keluar</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo $transaksi['jumlah']; ?></td>
                                                    <td><?php echo $transaksi['nama_lengkap']; ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted mb-0">Belum ada transaksi</p>
                                <?php endif; ?>
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
