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
                $kode_barang = generateKode('BRG');
                $query = "INSERT INTO barang (kode_barang, nama_barang, kategori_id, supplier_id, harga_beli, harga_jual, stok_minimum, stok_saat_ini, satuan, deskripsi) 
                         VALUES (:kode_barang, :nama_barang, :kategori_id, :supplier_id, :harga_beli, :harga_jual, :stok_minimum, :stok_saat_ini, :satuan, :deskripsi)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':kode_barang', $kode_barang);
                $stmt->bindParam(':nama_barang', $_POST['nama_barang']);
                $stmt->bindParam(':kategori_id', $_POST['kategori_id']);
                $stmt->bindParam(':supplier_id', $_POST['supplier_id']);
                $stmt->bindParam(':harga_beli', $_POST['harga_beli']);
                $stmt->bindParam(':harga_jual', $_POST['harga_jual']);
                $stmt->bindParam(':stok_minimum', $_POST['stok_minimum']);
                $stmt->bindParam(':stok_saat_ini', $_POST['stok_saat_ini']);
                $stmt->bindParam(':satuan', $_POST['satuan']);
                $stmt->bindParam(':deskripsi', $_POST['deskripsi']);
                $stmt->execute();
                break;
                
            case 'edit':
                $query = "UPDATE barang SET nama_barang = :nama_barang, kategori_id = :kategori_id, supplier_id = :supplier_id, 
                         harga_beli = :harga_beli, harga_jual = :harga_jual, stok_minimum = :stok_minimum, 
                         satuan = :satuan, deskripsi = :deskripsi WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':nama_barang', $_POST['nama_barang']);
                $stmt->bindParam(':kategori_id', $_POST['kategori_id']);
                $stmt->bindParam(':supplier_id', $_POST['supplier_id']);
                $stmt->bindParam(':harga_beli', $_POST['harga_beli']);
                $stmt->bindParam(':harga_jual', $_POST['harga_jual']);
                $stmt->bindParam(':stok_minimum', $_POST['stok_minimum']);
                $stmt->bindParam(':satuan', $_POST['satuan']);
                $stmt->bindParam(':deskripsi', $_POST['deskripsi']);
                $stmt->bindParam(':id', $_POST['id']);
                $stmt->execute();
                break;
                
            case 'delete':
                $query = "DELETE FROM barang WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $_POST['id']);
                $stmt->execute();
                break;
        }
        header("Location: barang.php");
        exit();
    }
}

// Get all items with category and supplier info
$query = "SELECT b.*, k.nama_kategori, s.nama_supplier 
          FROM barang b 
          LEFT JOIN kategori k ON b.kategori_id = k.id 
          LEFT JOIN supplier s ON b.supplier_id = s.id 
          ORDER BY b.nama_barang";
$stmt = $db->prepare($query);
$stmt->execute();
$barang_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for dropdown
$query = "SELECT * FROM kategori ORDER BY nama_kategori";
$stmt = $db->prepare($query);
$stmt->execute();
$kategori_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get suppliers for dropdown
$query = "SELECT * FROM supplier ORDER BY nama_supplier";
$stmt = $db->prepare($query);
$stmt->execute();
$supplier_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Barang - INVESTO</title>
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
                    <h1 class="h2"><i class="fas fa-boxes"></i> Data Barang</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fas fa-plus"></i> Tambah Barang
                    </button>
                </div>
                
                <div class="card shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Kode</th>
                                        <th>Nama Barang</th>
                                        <th>Kategori</th>
                                        <th>Supplier</th>
                                        <th>Stok</th>
                                        <th>Harga Beli</th>
                                        <th>Harga Jual</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($barang_list as $barang): ?>
                                    <tr>
                                        <td><?php echo $barang['kode_barang']; ?></td>
                                        <td><?php echo $barang['nama_barang']; ?></td>
                                        <td><?php echo $barang['nama_kategori'] ?? '-'; ?></td>
                                        <td><?php echo $barang['nama_supplier'] ?? '-'; ?></td>
                                        <td>
                                            <?php echo $barang['stok_saat_ini'] . ' ' . $barang['satuan']; ?>
                                            <?php if ($barang['stok_saat_ini'] <= $barang['stok_minimum']): ?>
                                                <span class="badge bg-warning">Min</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo formatRupiah($barang['harga_beli']); ?></td>
                                        <td><?php echo formatRupiah($barang['harga_jual']); ?></td>
                                        <td>
                                            <?php if ($barang['stok_saat_ini'] > $barang['stok_minimum']): ?>
                                                <span class="badge bg-success">Aman</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Stok Minimum</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" onclick="editBarang(<?php echo htmlspecialchars(json_encode($barang)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteBarang(<?php echo $barang['id']; ?>, '<?php echo $barang['nama_barang']; ?>')">
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
                    <h5 class="modal-title">Tambah Barang Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nama Barang</label>
                                    <input type="text" class="form-control" name="nama_barang" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Kategori</label>
                                    <select class="form-select" name="kategori_id">
                                        <option value="">Pilih Kategori</option>
                                        <?php foreach ($kategori_list as $kategori): ?>
                                        <option value="<?php echo $kategori['id']; ?>"><?php echo $kategori['nama_kategori']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Supplier</label>
                                    <select class="form-select" name="supplier_id">
                                        <option value="">Pilih Supplier</option>
                                        <?php foreach ($supplier_list as $supplier): ?>
                                        <option value="<?php echo $supplier['id']; ?>"><?php echo $supplier['nama_supplier']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Satuan</label>
                                    <input type="text" class="form-control" name="satuan" value="pcs">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Harga Beli</label>
                                    <input type="number" class="form-control" name="harga_beli" step="0.01">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Harga Jual</label>
                                    <input type="number" class="form-control" name="harga_jual" step="0.01">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Stok Awal</label>
                                    <input type="number" class="form-control" name="stok_saat_ini" value="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Stok Minimum</label>
                                    <input type="number" class="form-control" name="stok_minimum" value="5">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="deskripsi" rows="3"></textarea>
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
    
    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <!-- Same form fields as add modal -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nama Barang</label>
                                    <input type="text" class="form-control" name="nama_barang" id="edit_nama_barang" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Kategori</label>
                                    <select class="form-select" name="kategori_id" id="edit_kategori_id">
                                        <option value="">Pilih Kategori</option>
                                        <?php foreach ($kategori_list as $kategori): ?>
                                        <option value="<?php echo $kategori['id']; ?>"><?php echo $kategori['nama_kategori']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <!-- Add other fields similar to add modal -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editBarang(barang) {
            document.getElementById('edit_id').value = barang.id;
            document.getElementById('edit_nama_barang').value = barang.nama_barang;
            // Set other fields...
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
        
        function deleteBarang(id, nama) {
            if (confirm('Apakah Anda yakin ingin menghapus barang "' + nama + '"?')) {
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