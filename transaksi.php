<?php
require_once 'config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                try {
                    $db->beginTransaction();
                    
                    // Generate kode transaksi
                    $kode_transaksi = generateKode('TRX');
                    
                    // Insert transaksi
                    $query = "INSERT INTO transaksi (kode_transaksi, jenis_transaksi, barang_id, jumlah, harga_satuan, total_harga, keterangan, tanggal_transaksi, user_id) 
                             VALUES (:kode_transaksi, :jenis_transaksi, :barang_id, :jumlah, :harga_satuan, :total_harga, :keterangan, :tanggal_transaksi, :user_id)";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':kode_transaksi', $kode_transaksi);
                    $stmt->bindParam(':jenis_transaksi', $_POST['jenis_transaksi']);
                    $stmt->bindParam(':barang_id', $_POST['barang_id']);
                    $stmt->bindParam(':jumlah', $_POST['jumlah']);
                    $stmt->bindParam(':harga_satuan', $_POST['harga_satuan']);
                    
                    $total_harga = $_POST['jumlah'] * $_POST['harga_satuan'];
                    $stmt->bindParam(':total_harga', $total_harga);
                    $stmt->bindParam(':keterangan', $_POST['keterangan']);
                    $stmt->bindParam(':tanggal_transaksi', $_POST['tanggal_transaksi']);
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
                    
                    $db->commit();
                } catch (Exception $e) {
                    $db->rollback();
                    echo "Error: " . $e->getMessage();
                }
                break;
                
            case 'delete':
                try {
                    $db->beginTransaction();
                    
                    // Get transaksi data
                    $query = "SELECT * FROM transaksi WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':id', $_POST['id']);
                    $stmt->execute();
                    $transaksi = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Reverse stok update
                    if ($transaksi['jenis_transaksi'] == 'masuk') {
                        $query = "UPDATE barang SET stok_saat_ini = stok_saat_ini - :jumlah WHERE id = :barang_id";
                    } else {
                        $query = "UPDATE barang SET stok_saat_ini = stok_saat_ini + :jumlah WHERE id = :barang_id";
                    }
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':jumlah', $transaksi['jumlah']);
                    $stmt->bindParam(':barang_id', $transaksi['barang_id']);
                    $stmt->execute();
                    
                    // Delete transaksi
                    $query = "DELETE FROM transaksi WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':id', $_POST['id']);
                    $stmt->execute();
                    
                    $db->commit();
                } catch (Exception $e) {
                    $db->rollback();
                    echo "Error: " . $e->getMessage();
                }
                break;
        }
        header("Location: transaksi.php");
        exit();
    }
}

// Get all transactions
$query = "SELECT t.*, b.nama_barang, b.kode_barang, b.satuan, u.nama_lengkap 
          FROM transaksi t 
          JOIN barang b ON t.barang_id = b.id 
          LEFT JOIN users u ON t.user_id = u.id 
          ORDER BY t.tanggal_transaksi DESC";
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
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-exchange-alt"></i> Transaksi Barang</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fas fa-plus"></i> Tambah Transaksi
                    </button>
                </div>
                
                <div class="card shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Kode Transaksi</th>
                                        <th>Tanggal</th>
                                        <th>Jenis</th>
                                        <th>Barang</th>
                                        <th>Jumlah</th>
                                        <th>Harga Satuan</th>
                                        <th>Total</th>
                                        <th>User</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transaksi_list as $transaksi): ?>
                                    <tr>
                                        <td><?php echo $transaksi['kode_transaksi']; ?></td>
                                        <td><?php echo formatTanggal($transaksi['tanggal_transaksi']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $transaksi['jenis_transaksi'] == 'masuk' ? 'bg-success' : 'bg-danger'; ?>">
                                                <i class="fas <?php echo $transaksi['jenis_transaksi'] == 'masuk' ? 'fa-arrow-down' : 'fa-arrow-up'; ?>"></i>
                                                <?php echo ucfirst($transaksi['jenis_transaksi']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?php echo $transaksi['nama_barang']; ?></strong><br>
                                            <small class="text-muted"><?php echo $transaksi['kode_barang']; ?></small>
                                        </td>
                                        <td><?php echo $transaksi['jumlah'] . ' ' . $transaksi['satuan']; ?></td>
                                        <td><?php echo formatRupiah($transaksi['harga_satuan']); ?></td>
                                        <td><?php echo formatRupiah($transaksi['total_harga']); ?></td>
                                        <td><?php echo $transaksi['nama_lengkap']; ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="viewDetail(<?php echo htmlspecialchars(json_encode($transaksi)); ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteTransaksi(<?php echo $transaksi['id']; ?>, '<?php echo $transaksi['kode_transaksi']; ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
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
                                    <label class="form-label">Tanggal Transaksi</label>
                                    <input type="datetime-local" class="form-control" name="tanggal_transaksi" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Barang</label>
                            <select class="form-select" name="barang_id" id="barang_id" required onchange="updateHarga()">
                                <option value="">Pilih Barang</option>
                                <?php foreach ($barang_list as $barang): ?>
                                <option value="<?php echo $barang['id']; ?>" 
                                        data-harga-beli="<?php echo $barang['harga_beli']; ?>"
                                        data-harga-jual="<?php echo $barang['harga_jual']; ?>"
                                        data-stok="<?php echo $barang['stok_saat_ini']; ?>"
                                        data-satuan="<?php echo $barang['satuan']; ?>">
                                    <?php echo $barang['nama_barang'] . ' (' . $barang['kode_barang'] . ') - Stok: ' . $barang['stok_saat_ini']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Jumlah</label>
                                    <input type="number" class="form-control" name="jumlah" id="jumlah" min="1" required onchange="hitungTotal()">
                                    <small class="text-muted" id="stok_info"></small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Harga Satuan</label>
                                    <input type="number" class="form-control" name="harga_satuan" id="harga_satuan" step="0.01" required onchange="hitungTotal()">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Total Harga</label>
                                    <input type="text" class="form-control" id="total_display" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control" name="keterangan" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Detail Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detail_content">
                    <!-- Content will be filled by JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateHarga() {
            const select = document.getElementById('barang_id');
            const option = select.options[select.selectedIndex];
            const jenisTransaksi = document.querySelector('select[name="jenis_transaksi"]').value;
            
            if (option.value) {
                const hargaBeli = parseFloat(option.dataset.hargaBeli);
                const hargaJual = parseFloat(option.dataset.hargaJual);
                const stok = parseInt(option.dataset.stok);
                const satuan = option.dataset.satuan;
                
                // Set harga berdasarkan jenis transaksi
                if (jenisTransaksi === 'masuk') {
                    document.getElementById('harga_satuan').value = hargaBeli;
                } else if (jenisTransaksi === 'keluar') {
                    document.getElementById('harga_satuan').value = hargaJual;
                }
                
                document.getElementById('stok_info').textContent = `Stok tersedia: ${stok} ${satuan}`;
                hitungTotal();
            }
        }
        
        function hitungTotal() {
            const jumlah = parseFloat(document.getElementById('jumlah').value) || 0;
            const harga = parseFloat(document.getElementById('harga_satuan').value) || 0;
            const total = jumlah * harga;
            
            document.getElementById('total_display').value = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR'
            }).format(total);
        }
        
        function viewDetail(transaksi) {
            const content = `
                <table class="table table-borderless">
                    <tr><td><strong>Kode Transaksi:</strong></td><td>${transaksi.kode_transaksi}</td></tr>
                    <tr><td><strong>Tanggal:</strong></td><td>${new Date(transaksi.tanggal_transaksi).toLocaleString('id-ID')}</td></tr>
                    <tr><td><strong>Jenis:</strong></td><td><span class="badge ${transaksi.jenis_transaksi === 'masuk' ? 'bg-success' : 'bg-danger'}">${transaksi.jenis_transaksi.toUpperCase()}</span></td></tr>
                    <tr><td><strong>Barang:</strong></td><td>${transaksi.nama_barang} (${transaksi.kode_barang})</td></tr>
                    <tr><td><strong>Jumlah:</strong></td><td>${transaksi.jumlah} ${transaksi.satuan}</td></tr>
                    <tr><td><strong>Harga Satuan:</strong></td><td>${new Intl.NumberFormat('id-ID', {style: 'currency', currency: 'IDR'}).format(transaksi.harga_satuan)}</td></tr>
                    <tr><td><strong>Total Harga:</strong></td><td>${new Intl.NumberFormat('id-ID', {style: 'currency', currency: 'IDR'}).format(transaksi.total_harga)}</td></tr>
                    <tr><td><strong>Keterangan:</strong></td><td>${transaksi.keterangan || '-'}</td></tr>
                    <tr><td><strong>User:</strong></td><td>${transaksi.nama_lengkap}</td></tr>
                </table>
            `;
            document.getElementById('detail_content').innerHTML = content;
            new bootstrap.Modal(document.getElementById('detailModal')).show();
        }
        
        function deleteTransaksi(id, kode) {
            if (confirm('Apakah Anda yakin ingin menghapus transaksi "' + kode + '"?\nStok barang akan dikembalikan ke kondisi sebelumnya.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Update harga when jenis transaksi changes
        document.querySelector('select[name="jenis_transaksi"]').addEventListener('change', updateHarga);
    </script>
</body>
</html>