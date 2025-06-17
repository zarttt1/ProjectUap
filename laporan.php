<?php
require_once 'config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Filter parameters
$tanggal_dari = $_GET['tanggal_dari'] ?? date('Y-m-01');
$tanggal_sampai = $_GET['tanggal_sampai'] ?? date('Y-m-d');
$jenis_transaksi = $_GET['jenis_transaksi'] ?? '';
$barang_id = $_GET['barang_id'] ?? '';

// Build query with filters
$where_conditions = [];
$params = [];

$where_conditions[] = "DATE(t.tanggal_transaksi) BETWEEN :tanggal_dari AND :tanggal_sampai";
$params[':tanggal_dari'] = $tanggal_dari;
$params[':tanggal_sampai'] = $tanggal_sampai;

if (!empty($jenis_transaksi)) {
    $where_conditions[] = "t.jenis_transaksi = :jenis_transaksi";
    $params[':jenis_transaksi'] = $jenis_transaksi;
}

if (!empty($barang_id)) {
    $where_conditions[] = "t.barang_id = :barang_id";
    $params[':barang_id'] = $barang_id;
}

$where_clause = implode(' AND ', $where_conditions);

// Get filtered transactions
$query = "SELECT t.*, b.nama_barang, b.satuan, u.nama_lengkap 
          FROM transaksi t 
          LEFT JOIN barang b ON t.barang_id = b.id 
          LEFT JOIN users u ON t.user_id = u.id 
          WHERE $where_clause
          ORDER BY t.tanggal_transaksi DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$transaksi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get summary statistics
$query = "SELECT 
            COUNT(*) as total_transaksi,
            SUM(CASE WHEN jenis_transaksi = 'masuk' THEN total_harga ELSE 0 END) as total_masuk,
            SUM(CASE WHEN jenis_transaksi = 'keluar' THEN total_harga ELSE 0 END) as total_keluar,
            SUM(CASE WHEN jenis_transaksi = 'masuk' THEN jumlah ELSE 0 END) as qty_masuk,
            SUM(CASE WHEN jenis_transaksi = 'keluar' THEN jumlah ELSE 0 END) as qty_keluar
          FROM transaksi t 
          WHERE $where_clause";
$stmt = $db->prepare($query);
$stmt->execute($params);
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

// Get barang for filter
$query = "SELECT * FROM barang ORDER BY nama_barang";
$stmt = $db->prepare($query);
$stmt->execute();
$barang_filter = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - INVESTO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
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
        
        .summary-card {
            background: linear-gradient(135deg, white 0%, var(--brown-light) 100%);
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .summary-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
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
                    <h1 class="h2 page-title"><i class="fas fa-chart-bar"></i> Laporan Transaksi</h1>
                    <button type="button" class="btn btn-brown" onclick="exportToExcel()">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </button>
                </div>
                
                <!-- Filter Form -->
                <div class="card content-card shadow mb-4">
                    <div class="card-header" style="background: linear-gradient(45deg, var(--brown-primary), var(--brown-secondary)); color: white;">
                        <h6 class="m-0 font-weight-bold">Filter Laporan</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Tanggal Dari</label>
                                <input type="date" class="form-control" name="tanggal_dari" value="<?php echo $tanggal_dari; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tanggal Sampai</label>
                                <input type="date" class="form-control" name="tanggal_sampai" value="<?php echo $tanggal_sampai; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jenis Transaksi</label>
                                <select class="form-select" name="jenis_transaksi">
                                    <option value="">Semua Jenis</option>
                                    <option value="masuk" <?php echo $jenis_transaksi == 'masuk' ? 'selected' : ''; ?>>Barang Masuk</option>
                                    <option value="keluar" <?php echo $jenis_transaksi == 'keluar' ? 'selected' : ''; ?>>Barang Keluar</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Barang</label>
                                <select class="form-select" name="barang_id">
                                    <option value="">Semua Barang</option>
                                    <?php foreach ($barang_filter as $barang): ?>
                                    <option value="<?php echo $barang['id']; ?>" <?php echo $barang_id == $barang['id'] ? 'selected' : ''; ?>>
                                        <?php echo $barang['nama_barang']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-brown">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                                <a href="laporan.php" class="btn btn-secondary">
                                    <i class="fas fa-refresh"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card summary-card h-100">
                            <div class="card-body text-center">
                                <div class="text-brown-dark">
                                    <i class="fas fa-list fa-2x mb-2"></i>
                                    <h4><?php echo $summary['total_transaksi']; ?></h4>
                                    <p class="mb-0">Total Transaksi</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card summary-card h-100">
                            <div class="card-body text-center">
                                <div class="text-success">
                                    <i class="fas fa-arrow-down fa-2x mb-2"></i>
                                    <h4><?php echo $summary['qty_masuk']; ?></h4>
                                    <p class="mb-0">Qty Masuk</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card summary-card h-100">
                            <div class="card-body text-center">
                                <div class="text-danger">
                                    <i class="fas fa-arrow-up fa-2x mb-2"></i>
                                    <h4><?php echo $summary['qty_keluar']; ?></h4>
                                    <p class="mb-0">Qty Keluar</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card summary-card h-100">
                            <div class="card-body text-center">
                                <div class="text-brown-dark">
                                    <i class="fas fa-money-bill fa-2x mb-2"></i>
                                    <h6><?php echo formatRupiah($summary['total_masuk'] + $summary['total_keluar']); ?></h6>
                                    <p class="mb-0">Total Nilai</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Transactions Table -->
                <div class="card content-card shadow">
                    <div class="card-header" style="background: linear-gradient(45deg, var(--brown-primary), var(--brown-secondary)); color: white;">
                        <h6 class="m-0 font-weight-bold">
                            Data Transaksi (<?php echo count($transaksi_list); ?> records)
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="laporanTable">
                                <thead class="table-brown">
                                    <tr>
                                        <th>No</th>
                                        <th>Kode Transaksi</th>
                                        <th>Tanggal</th>
                                        <th>Jenis</th>
                                        <th>Barang</th>
                                        <th>Jumlah</th>
                                        <th>Harga Satuan</th>
                                        <th>Total</th>
                                        <th>User</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($transaksi_list as $transaksi): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo $transaksi['kode_transaksi']; ?></td>
                                        <td><?php echo formatTanggal($transaksi['tanggal_transaksi']); ?></td>
                                        <td>
                                            <?php if ($transaksi['jenis_transaksi'] == 'masuk'): ?>
                                                <span class="badge bg-success">Masuk</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Keluar</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $transaksi['nama_barang']; ?></td>
                                        <td><?php echo $transaksi['jumlah'] . ' ' . $transaksi['satuan']; ?></td>
                                        <td><?php echo formatRupiah($transaksi['harga_satuan']); ?></td>
                                        <td><?php echo formatRupiah($transaksi['total_harga']); ?></td>
                                        <td><?php echo $transaksi['nama_lengkap']; ?></td>
                                        <td><?php echo $transaksi['keterangan'] ?: '-'; ?></td>
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
    <script>
        function exportToExcel() {
            const table = document.getElementById('laporanTable');
            const wb = XLSX.utils.table_to_book(table, {sheet: "Laporan Transaksi"});
            const filename = 'Laporan_Transaksi_' + new Date().toISOString().slice(0,10) + '.xlsx';
            XLSX.writeFile(wb, filename);
        }
    </script>
</body>
</html>