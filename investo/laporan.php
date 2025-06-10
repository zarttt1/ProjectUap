<?php
require_once 'config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Default filter values
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
$query = "SELECT t.*, b.nama_barang, b.kode_barang, b.satuan, u.nama_lengkap 
          FROM transaksi t 
          JOIN barang b ON t.barang_id = b.id 
          LEFT JOIN users u ON t.user_id = u.id 
          WHERE $where_clause
          ORDER BY t.tanggal_transaksi DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$transaksi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get summary statistics
$query_summary = "SELECT 
                    jenis_transaksi,
                    COUNT(*) as jumlah_transaksi,
                    SUM(jumlah) as total_qty,
                    SUM(total_harga) as total_nilai
                  FROM transaksi t 
                  WHERE $where_clause
                  GROUP BY jenis_transaksi";
$stmt = $db->prepare($query_summary);
$stmt->execute($params);
$summary = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get barang for filter dropdown
$query = "SELECT * FROM barang ORDER BY nama_barang";
$stmt = $db->prepare($query);
$stmt->execute();
$barang_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - INVESTO</title>
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
                    <h1 class="h2"><i class="fas fa-chart-bar"></i> Laporan Transaksi</h1>
                    <button type="button" class="btn btn-success" onclick="exportToExcel()">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </button>
                </div>
                
                <!-- Filter Form -->
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">Filter Laporan</h6>
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
                                    <option value="">Semua</option>
                                    <option value="masuk" <?php echo $jenis_transaksi == 'masuk' ? 'selected' : ''; ?>>Barang Masuk</option>
                                    <option value="keluar" <?php echo $jenis_transaksi == 'keluar' ? 'selected' : ''; ?>>Barang Keluar</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Barang</label>
                                <select class="form-select" name="barang_id">
                                    <option value="">Semua Barang</option>
                                    <?php foreach ($barang_list as $barang): ?>
                                    <option value="<?php echo $barang['id']; ?>" <?php echo $barang_id == $barang['id'] ? 'selected' : ''; ?>>
                                        <?php echo $barang['nama_barang']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
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
                    <?php 
                    $total_masuk = 0;
                    $total_keluar = 0;
                    $nilai_masuk = 0;
                    $nilai_keluar = 0;
                    
                    foreach ($summary as $sum) {
                        if ($sum['jenis_transaksi'] == 'masuk') {
                            $total_masuk = $sum['total_qty'];
                            $nilai_masuk = $sum['total_nilai'];
                        } else {
                            $total_keluar = $sum['total_qty'];
                            $nilai_keluar = $sum['total_nilai'];
                        }
                    }
                    ?>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Barang Masuk</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($total_masuk); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-arrow-down fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-danger shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Barang Keluar</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($total_keluar); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-arrow-up fa-2x text-gray-300"></i>
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
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Nilai Masuk</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo formatRupiah($nilai_masuk); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
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
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Nilai Keluar</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo formatRupiah($nilai_keluar); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Transactions Table -->
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            Detail Transaksi (<?php echo count($transaksi_list); ?> transaksi)
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="dataTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>Kode</th>
                                        <th>Jenis</th>
                                        <th>Barang</th>
                                        <th>Jumlah</th>
                                        <th>Harga Satuan</th>
                                        <th>Total</th>
                                        <th>User</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($transaksi_list as $transaksi): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($transaksi['tanggal_transaksi'])); ?></td>
                                        <td><?php echo $transaksi['kode_transaksi']; ?></td>
                                        <td>
                                            <span class="badge <?php echo $transaksi['jenis_transaksi'] == 'masuk' ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo ucfirst($transaksi['jenis_transaksi']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo $transaksi['nama_barang']; ?><br>
                                            <small class="text-muted"><?php echo $transaksi['kode_barang']; ?></small>
                                        </td>
                                        <td><?php echo $transaksi['jumlah'] . ' ' . $transaksi['satuan']; ?></td>
                                        <td><?php echo formatRupiah($transaksi['harga_satuan']); ?></td>
                                        <td><?php echo formatRupiah($transaksi['total_harga']); ?></td>
                                        <td><?php echo $transaksi['nama_lengkap']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-info">
                                        <th colspan="7">Total</th>
                                        <th><?php echo formatRupiah(array_sum(array_column($transaksi_list, 'total_harga'))); ?></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        function exportToExcel() {
            const table = document.getElementById('dataTable');
            const wb = XLSX.utils.table_to_book(table, {sheet: "Laporan Transaksi"});
            const filename = `Laporan_Transaksi_${new Date().toISOString().slice(0,10)}.xlsx`;
            XLSX.writeFile(wb, filename);
        }
    </script>
</body>
</html>