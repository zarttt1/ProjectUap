<?php
require_once 'config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $kode_transaksi = generateKode('TRX');
                $total_harga = $_POST['jumlah'] * $_POST['harga_satuan'];
                
                // Insert transaksi
                $query = "INSERT INTO transaksi (kode_transaksi, jenis_transaksi, barang_id, jumlah, harga_satuan, total_harga, keterangan, user_id) 
                         VALUES (:kode_transaksi, :jenis_transaksi, :barang_id, :jumlah, :harga_satuan, :total_harga, :keterangan, :user_id)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':kode_transaksi', $kode_transaksi);
                $stmt->bindParam(':jenis_transaksi', $_POST['jenis_transaksi']);
                $stmt->bindParam(':barang_id', $_POST['barang_id']);
                $stmt->bindParam(':jumlah', $_POST['jumlah']);
                $stmt->bindParam(':harga_satuan', $_POST['harga_satuan']);
                $stmt->bindParam(':total_harga', $total_harga);
                $stmt->bindParam(':keterangan', $_POST['keterangan']);
                $stmt->bindParam(':user_id', $_SESSION['user_id']);
                $stmt->execute();
                
                // Update stok barang
                if ($_POST['jenis_transaksi'] == 'masuk') {
                    $query = "UPDATE barang SET stok_saat_ini = stok_saat_ini + :jumlah WHERE id = :barang_id";
                } else {
                    $query = "UPDATE barang SET stok_saat_ini = stok_saat_ini - :jumlah WHERE id = :barang_id";
                }
                $stmt = $db->prepare($query);
                $stmt->bindParam(':jumlah', $_POST['jumlah']);
                $stmt->bindParam(':barang_id', $_POST['barang_id']);
                $stmt->execute();
                
                // Get barang name for logging
                $query = "SELECT nama_barang FROM barang WHERE id = :barang_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':barang_id', $_POST['barang_id']);
                $stmt->execute();
                $barang = $stmt->fetch(PDO::FETCH_ASSOC);
                
                logActivity($_SESSION['user_id'], 'Menambah transaksi ' . $_POST['jenis_transaksi'] . ': ' . $barang['nama_barang'] . ' (' . $_POST['jumlah'] . ')', 'transaksi', $db->lastInsertId());
                break;
        }
        header("Location: transaksi.php");
        exit();
    }
}

// Get all transactions with barang and user info
$query = "SELECT t.*, b.nama_barang, b.satuan, u.nama_lengkap 
          FROM transaksi t 
          LEFT JOIN barang b ON t.barang_id = b.id 
          LEFT JOIN users u ON t.user_id = u.id 
          ORDER BY t.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$transaksi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get barang for dropdown
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
    <title>Transaksi - INVESTO</title>
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
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2 page-title"><i class="fas fa-exchange-alt"></i> Transaksi Barang</h1>
                    <button type="button" class="btn btn-brown" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fas fa-plus"></i> Tambah Transaksi
                    </button>
                </div>
                
                <div class="card content-card shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-brown">
                                    <tr>
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
                                    <?php foreach ($transaksi_list as $transaksi): ?>
                                    <tr>
                                        <td><?php echo $transaksi['kode_transaksi']; ?></td>
                                        <td><?php echo formatTanggal($transaksi['tanggal_transaksi']); ?></td>
                                        <td>
                                            <?php if ($transaksi['jenis_transaksi'] == 'masuk'): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-arrow-down"></i> Masuk
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-arrow-up"></i> Keluar
                                                </span>
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
    
    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Transaksi Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Jenis Transaksi</label>
                                    <select class="form-select" name="jenis_transaksi" required>
                                        <option value="">Pilih Jenis</option>
                                        <option value="masuk">Barang Masuk</option>
                                        <option value="keluar">Barang Keluar</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Barang</label>
                                    <select class="form-select" name="barang_id" id="barang_id" required onchange="updateHarga()">
                                        <option value="">Pilih Barang</option>
                                        <?php foreach ($barang_list as $barang): ?>
                                        <option value="<?php echo $barang['id']; ?>" 
                                                data-harga-beli="<?php echo $barang['harga_beli']; ?>"
                                                data-harga-jual="<?php echo $barang['harga_jual']; ?>"
                                                data-stok="<?php echo $barang['stok_saat_ini']; ?>">
                                            <?php echo $barang['nama_barang'] . ' (Stok: ' . $barang['stok_saat_ini'] . ')'; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Jumlah</label>
                                    <input type="number" class="form-control" name="jumlah" id="jumlah" required min="1" onchange="hitungTotal()">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Harga Satuan</label>
                                    <input type="number" class="form-control" name="harga_satuan" id="harga_satuan" required step="0.01" onchange="hitungTotal()">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Total Harga</label>
                            <input type="text" class="form-control" id="total_harga_display" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control" name="keterangan" rows="3"></textarea>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateHarga() {
            const barangSelect = document.getElementById('barang_id');
            const jenisTransaksi = document.querySelector('select[name="jenis_transaksi"]').value;
            const hargaSatuanInput = document.getElementById('harga_satuan');
            
            if (barangSelect.value && jenisTransaksi) {
                const selectedOption = barangSelect.options[barangSelect.selectedIndex];
                const hargaBeli = selectedOption.getAttribute('data-harga-beli');
                const hargaJual = selectedOption.getAttribute('data-harga-jual');
                
                if (jenisTransaksi === 'masuk') {
                    hargaSatuanInput.value = hargaBeli;
                } else {
                    hargaSatuanInput.value = hargaJual;
                }
                
                hitungTotal();
            }
        }
        
        function hitungTotal() {
            const jumlah = document.getElementById('jumlah').value;
            const hargaSatuan = document.getElementById('harga_satuan').value;
            const totalDisplay = document.getElementById('total_harga_display');
            
            if (jumlah && hargaSatuan) {
                const total = jumlah * hargaSatuan;
                totalDisplay.value = 'Rp ' + total.toLocaleString('id-ID');
            }
        }
        
        // Update harga when jenis transaksi changes
        document.querySelector('select[name="jenis_transaksi"]').addEventListener('change', updateHarga);
    </script>
</body>
</html>
