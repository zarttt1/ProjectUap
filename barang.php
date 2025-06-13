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
                // Only admin can add new items
                if (!isAdmin()) {
                    header("Location: barang.php?error=access_denied");
                    exit();
                }
                
                $kode_barang = generateKode('BRG');
                $gambar = null;
                
                // Handle image upload
                if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
                    $gambar = uploadImage($_FILES['gambar']);
                }
                
                $query = "INSERT INTO barang (kode_barang, nama_barang, kategori_id, supplier_id, harga_beli, harga_jual, stok_minimum, stok_saat_ini, satuan, deskripsi, gambar) 
                         VALUES (:kode_barang, :nama_barang, :kategori_id, :supplier_id, :harga_beli, :harga_jual, :stok_minimum, :stok_saat_ini, :satuan, :deskripsi, :gambar)";
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
                $stmt->bindParam(':gambar', $gambar);
                $stmt->execute();
                
                logActivity($_SESSION['user_id'], 'Menambah barang baru: ' . $_POST['nama_barang'], 'barang', $db->lastInsertId());
                break;
                
            case 'edit':
                // Only admin can edit items
                if (!isAdmin()) {
                    header("Location: barang.php?error=access_denied");
                    exit();
                }
                
                $gambar = $_POST['gambar_lama'];
                
                // Handle new image upload
                if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
                    $new_gambar = uploadImage($_FILES['gambar']);
                    if ($new_gambar) {
                        // Delete old image
                        if ($gambar && file_exists('uploads/barang/' . $gambar)) {
                            unlink('uploads/barang/' . $gambar);
                        }
                        $gambar = $new_gambar;
                    }
                }
                
                $query = "UPDATE barang SET nama_barang = :nama_barang, kategori_id = :kategori_id, supplier_id = :supplier_id, 
                         harga_beli = :harga_beli, harga_jual = :harga_jual, stok_minimum = :stok_minimum, 
                         satuan = :satuan, deskripsi = :deskripsi, gambar = :gambar WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':nama_barang', $_POST['nama_barang']);
                $stmt->bindParam(':kategori_id', $_POST['kategori_id']);
                $stmt->bindParam(':supplier_id', $_POST['supplier_id']);
                $stmt->bindParam(':harga_beli', $_POST['harga_beli']);
                $stmt->bindParam(':harga_jual', $_POST['harga_jual']);
                $stmt->bindParam(':stok_minimum', $_POST['stok_minimum']);
                $stmt->bindParam(':satuan', $_POST['satuan']);
                $stmt->bindParam(':deskripsi', $_POST['deskripsi']);
                $stmt->bindParam(':gambar', $gambar);
                $stmt->bindParam(':id', $_POST['id']);
                $stmt->execute();
                
                logActivity($_SESSION['user_id'], 'Mengedit barang: ' . $_POST['nama_barang'], 'barang', $_POST['id']);
                break;
                
            case 'delete':
                // Only admin can delete items
                if (!isAdmin()) {
                    header("Location: barang.php?error=access_denied");
                    exit();
                }
                
                // Get item info for logging and delete image
                $query = "SELECT nama_barang, gambar FROM barang WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $_POST['id']);
                $stmt->execute();
                $barang = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Delete image file
                if ($barang['gambar'] && file_exists('uploads/barang/' . $barang['gambar'])) {
                    unlink('uploads/barang/' . $barang['gambar']);
                }
                
                $query = "DELETE FROM barang WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $_POST['id']);
                $stmt->execute();
                
                logActivity($_SESSION['user_id'], 'Menghapus barang: ' . $barang['nama_barang'], 'barang', $_POST['id']);
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

// Get categories for dropdown (only if admin)
if (isAdmin()) {
    $query = "SELECT * FROM kategori ORDER BY nama_kategori";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $kategori_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get suppliers for dropdown
    $query = "SELECT * FROM supplier ORDER BY nama_supplier";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $supplier_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Barang - INVESTO</title>
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
        
        .image-preview {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid var(--brown-light);
        }
        
        .image-upload-area {
            border: 2px dashed var(--brown-light);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .image-upload-area:hover {
            border-color: var(--brown-primary);
            background: rgba(139, 69, 19, 0.05);
        }
        
        .modal-header {
            background: linear-gradient(45deg, var(--brown-primary), var(--brown-secondary));
            color: white;
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
                    <h1 class="h2 page-title"><i class="fas fa-boxes"></i> Data Barang</h1>
                    <?php if (isAdmin()): ?>
                    <button type="button" class="btn btn-brown" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fas fa-plus"></i> Tambah Barang
                    </button>
                    <?php endif; ?>
                </div>
                
                <?php if (isset($_GET['error']) && $_GET['error'] == 'access_denied'): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Anda tidak memiliki akses untuk melakukan operasi ini. Hanya Admin yang dapat mengelola data barang.
                    </div>
                <?php endif; ?>
                
                <div class="card content-card shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-brown">
                                    <tr>
                                        <th>Gambar</th>
                                        <th>Kode</th>
                                        <th>Nama Barang</th>
                                        <th>Kategori</th>
                                        <th>Supplier</th>
                                        <th>Stok</th>
                                        <?php if (isAdmin()): ?>
                                        <th>Harga Beli</th>
                                        <th>Harga Jual</th>
                                        <?php endif; ?>
                                        <th>Status</th>
                                        <?php if (isAdmin()): ?>
                                        <th>Aksi</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($barang_list as $barang): ?>
                                    <tr>
                                        <td>
                                            <?php if ($barang['gambar']): ?>
                                                <img src="uploads/barang/<?php echo $barang['gambar']; ?>" 
                                                     class="image-preview" alt="<?php echo $barang['nama_barang']; ?>">
                                            <?php else: ?>
                                                <div class="image-preview d-flex align-items-center justify-content-center bg-light">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
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
                                        <?php if (isAdmin()): ?>
                                        <td><?php echo formatRupiah($barang['harga_beli']); ?></td>
                                        <td><?php echo formatRupiah($barang['harga_jual']); ?></td>
                                        <?php endif; ?>
                                        <td>
                                            <?php if ($barang['stok_saat_ini'] > $barang['stok_minimum']): ?>
                                                <span class="badge bg-success">Aman</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Stok Minimum</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php if (isAdmin()): ?>
                                        <td>
                                            <button class="btn btn-sm btn-warning" onclick="editBarang(<?php echo htmlspecialchars(json_encode($barang)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteBarang(<?php echo $barang['id']; ?>, '<?php echo $barang['nama_barang']; ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                        <?php endif; ?>
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
    
    <?php if (isAdmin()): ?>
    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Barang Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <!-- Image Upload -->
                        <div class="mb-3">
                            <label class="form-label">Gambar Barang</label>
                            <div class="image-upload-area">
                                <input type="file" class="form-control" name="gambar" accept="image/*" onchange="previewImage(this, 'add-preview')">
                                <div class="mt-2">
                                    <i class="fas fa-cloud-upload-alt fa-2x text-muted"></i>
                                    <p class="text-muted">Klik untuk upload gambar (JPG, PNG, GIF - Max 5MB)</p>
                                </div>
                                <img id="add-preview" class="image-preview mt-2" style="display: none;">
                            </div>
                        </div>
                        
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
                        <button type="submit" class="btn btn-brown">Simpan</button>
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
                <form method="POST" enctype="multipart/form-data" id="editForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <input type="hidden" name="gambar_lama" id="edit_gambar_lama">
                        
                        <!-- Current Image Display -->
                        <div class="mb-3">
                            <label class="form-label">Gambar Saat Ini</label>
                            <div id="current-image-container">
                                <img id="current-image" class="image-preview" style="display: none;">
                                <p id="no-image" class="text-muted">Tidak ada gambar</p>
                            </div>
                        </div>
                        
                        <!-- New Image Upload -->
                        <div class="mb-3">
                            <label class="form-label">Ganti Gambar (Opsional)</label>
                            <div class="image-upload-area">
                                <input type="file" class="form-control" name="gambar" accept="image/*" onchange="previewImage(this, 'edit-preview')">
                                <div class="mt-2">
                                    <i class="fas fa-cloud-upload-alt fa-2x text-muted"></i>
                                    <p class="text-muted">Klik untuk upload gambar baru (JPG, PNG, GIF - Max 5MB)</p>
                                </div>
                                <img id="edit-preview" class="image-preview mt-2" style="display: none;">
                            </div>
                        </div>
                        
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
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Supplier</label>
                                    <select class="form-select" name="supplier_id" id="edit_supplier_id">
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
                                    <input type="text" class="form-control" name="satuan" id="edit_satuan">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Harga Beli</label>
                                    <input type="number" class="form-control" name="harga_beli" id="edit_harga_beli" step="0.01">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Harga Jual</label>
                                    <input type="number" class="form-control" name="harga_jual" id="edit_harga_jual" step="0.01">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Stok Minimum</label>
                                    <input type="number" class="form-control" name="stok_minimum" id="edit_stok_minimum">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="deskripsi" id="edit_deskripsi" rows="3"></textarea>
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
    <?php endif; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        <?php if (isAdmin()): ?>
        function editBarang(barang) {
            document.getElementById('edit_id').value = barang.id;
            document.getElementById('edit_nama_barang').value = barang.nama_barang;
            document.getElementById('edit_kategori_id').value = barang.kategori_id || '';
            document.getElementById('edit_supplier_id').value = barang.supplier_id || '';
            document.getElementById('edit_satuan').value = barang.satuan;
            document.getElementById('edit_harga_beli').value = barang.harga_beli;
            document.getElementById('edit_harga_jual').value = barang.harga_jual;
            document.getElementById('edit_stok_minimum').value = barang.stok_minimum;
            document.getElementById('edit_deskripsi').value = barang.deskripsi || '';
            document.getElementById('edit_gambar_lama').value = barang.gambar || '';
            
            // Show current image
            const currentImage = document.getElementById('current-image');
            const noImage = document.getElementById('no-image');
            if (barang.gambar) {
                currentImage.src = 'uploads/barang/' + barang.gambar;
                currentImage.style.display = 'block';
                noImage.style.display = 'none';
            } else {
                currentImage.style.display = 'none';
                noImage.style.display = 'block';
            }
            
            // Reset preview
            document.getElementById('edit-preview').style.display = 'none';
            
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
        <?php endif; ?>
    </script>
</body>
</html>