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
                $query = "INSERT INTO kategori (nama_kategori, deskripsi) VALUES (:nama_kategori, :deskripsi)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':nama_kategori', $_POST['nama_kategori']);
                $stmt->bindParam(':deskripsi', $_POST['deskripsi']);
                $stmt->execute();
                break;
                
            case 'edit':
                $query = "UPDATE kategori SET nama_kategori = :nama_kategori, deskripsi = :deskripsi WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':nama_kategori', $_POST['nama_kategori']);
                $stmt->bindParam(':deskripsi', $_POST['deskripsi']);
                $stmt->bindParam(':id', $_POST['id']);
                $stmt->execute();
                break;
                
            case 'delete':
                $query = "DELETE FROM kategori WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $_POST['id']);
                $stmt->execute();
                break;
        }
        header("Location: kategori.php");
        exit();
    }
}

// Get all categories
$query = "SELECT k.*, COUNT(b.id) as jumlah_barang 
          FROM kategori k 
          LEFT JOIN barang b ON k.id = b.kategori_id 
          GROUP BY k.id 
          ORDER BY k.nama_kategori";
$stmt = $db->prepare($query);
$stmt->execute();
$kategori_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori - INVESTO</title>
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
                    <h1 class="h2"><i class="fas fa-tags"></i> Kategori Barang</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fas fa-plus"></i> Tambah Kategori
                    </button>
                </div>
                
                <div class="card shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Kategori</th>
                                        <th>Deskripsi</th>
                                        <th>Jumlah Barang</th>
                                        <th>Tanggal Dibuat</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($kategori_list as $kategori): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo $kategori['nama_kategori']; ?></td>
                                        <td><?php echo $kategori['deskripsi'] ?: '-'; ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $kategori['jumlah_barang']; ?> barang</span>
                                        </td>
                                        <td><?php echo formatTanggal($kategori['created_at']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" onclick="editKategori(<?php echo htmlspecialchars(json_encode($kategori)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($kategori['jumlah_barang'] == 0): ?>
                                            <button class="btn btn-sm btn-danger" onclick="deleteKategori(<?php echo $kategori['id']; ?>, '<?php echo $kategori['nama_kategori']; ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php else: ?>
                                            <button class="btn btn-sm btn-secondary" disabled title="Tidak dapat dihapus karena masih ada barang">
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
                    <h5 class="modal-title">Tambah Kategori Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Nama Kategori</label>
                            <input type="text" class="form-control" name="nama_kategori" required>
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
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Nama Kategori</label>
                            <input type="text" class="form-control" name="nama_kategori" id="edit_nama_kategori" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="deskripsi" id="edit_deskripsi" rows="3"></textarea>
                        </div>
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
        function editKategori(kategori) {
            document.getElementById('edit_id').value = kategori.id;
            document.getElementById('edit_nama_kategori').value = kategori.nama_kategori;
            document.getElementById('edit_deskripsi').value = kategori.deskripsi || '';
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
        
        function deleteKategori(id, nama) {
            if (confirm('Apakah Anda yakin ingin menghapus kategori "' + nama + '"?')) {
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