<?php
require_once 'config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Get statistics
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

// Barang stok minimum
$query = "SELECT COUNT(*) as total FROM barang WHERE stok_saat_ini <= stok_minimum";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['stok_minimum'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Recent transactions
$query = "SELECT t.*, b.nama_barang, b.kode_barang 
          FROM transaksi t 
          JOIN barang b ON t.barang_id = b.id 
          ORDER BY t.created_at DESC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - INVESTO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Barang</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_barang']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-boxes fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Kategori</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_kategori']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-tags fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Supplier</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_supplier']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-truck fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Stok Minimum</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['stok_minimum']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Transactions -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Transaksi Terbaru</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Kode Transaksi</th>
                                        <th>Jenis</th>
                                        <th>Barang</th>
                                        <th>Jumlah</th>
                                        <th>Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_transactions as $trans): ?>
                                    <tr>
                                        <td><?php echo $trans['kode_transaksi']; ?></td>
                                        <td>
                                            <span class="badge <?php echo $trans['jenis_transaksi'] == 'masuk' ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo ucfirst($trans['jenis_transaksi']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $trans['nama_barang']; ?></td>
                                        <td><?php echo $trans['jumlah']; ?></td>
                                        <td><?php echo formatTanggal($trans['tanggal_transaksi']); ?></td>
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