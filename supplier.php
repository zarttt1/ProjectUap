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
                $query = "INSERT INTO supplier (nama_supplier, alamat, telepon, email, kontak_person) 
                         VALUES (:nama_supplier, :alamat, :telepon, :email, :kontak_person)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':nama_supplier', $_POST['nama_supplier']);
                $stmt->bindParam(':alamat', $_POST['alamat']);
                $stmt->bindParam(':telepon', $_POST['telepon']);
                $stmt->bindParam(':email', $_POST['email']);
                $stmt->bindParam(':kontak_person', $_POST['kontak_person']);
                $stmt->execute();
                break;
                
            case 'edit':
                $query = "UPDATE supplier SET nama_supplier = :nama_supplier, alamat = :alamat, 
                         telepon = :telepon, email = :email, kontak_person = :kontak_person WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':nama_supplier', $_POST['nama_supplier']);
                $stmt->bindParam(':alamat', $_POST['alamat']);
                $stmt->bindParam(':telepon', $_POST['telepon']);
                $stmt->bindParam(':email', $_POST['email']);
                $stmt->bindParam(':kontak_person', $_POST['kontak_person']);
                $stmt->bindParam(':id', $_POST['id']);
                $stmt->execute();
                break;
                
            case 'delete':
                $query = "DELETE FROM supplier WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $_POST['id']);
                $stmt->execute();
                break;
        }
        header("Location: supplier.php");
        exit();
    }
}

// Get all suppliers
$query = "SELECT s.*, COUNT(b.id) as jumlah_barang 
          FROM supplier s 
          LEFT JOIN barang b ON s.id = b.supplier_id 
          GROUP BY s.id 
          ORDER BY s.nama_supplier";
$stmt = $db->prepare($query);
$stmt->execute();
$supplier_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier - INVESTO</title>
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
                    <h1 class="h2"><i class="fas fa-truck"></i> Data Supplier</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fas fa-plus"></i> Tambah Supplier
                    </button>
                </div>
                
                <div class="card shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Supplier</th>
                                        <th>Alamat</th>
                                        <th>Telepon</th>
                                        <th>Email</th>
                                        <th>Kontak Person</th>
                                        <th>Jumlah Barang</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($supplier_list as $supplier): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo $supplier['nama_supplier']; ?></td>
                                        <td><?php echo $supplier['alamat'] ?: '-'; ?></td>
                                        <td><?php echo $supplier['telepon'] ?: '-'; ?></td>
                                        <td><?php echo $supplier['email'] ?: '-'; ?></td>
                                        <td><?php echo $supplier['kontak_person'] ?: '-'; ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $supplier['jumlah_barang']; ?> barang</span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" onclick="editSupplier(<?php echo htmlspecialchars(json_encode($supplier)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($supplier['jumlah_barang'] == 0): ?>
                                            <button class="btn btn-sm btn-danger" onclick="deleteSupplier(<?php echo $supplier['id']; ?>, '<?php echo $supplier['nama_supplier']; ?>')">
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
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Supplier Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nama Supplier</label>
                                    <input type="text" class="form-control" name="nama_supplier" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Kontak Person</label>
                                    <input type="text" class="form-control" name="kontak_person">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control" name="alamat" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Telepon</label>
                                    <input type="text" class="form-control" name="telepon">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email">
                                </div>
                            </div>
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
                    <h5 class="modal-title">Edit Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nama Supplier</label>
                                    <input type="text" class="form-control" name="nama_supplier" id="edit_nama_supplier" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Kontak Person</label>
                                    <input type="text" class="form-control" name="kontak_person" id="edit_kontak_person">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control" name="alamat" id="edit_alamat" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Telepon</label>
                                    <input type="text" class="form-control" name="telepon" id="edit_telepon">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" id="edit_email">
                                </div>
                            </div>
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
        function editSupplier(supplier) {
            document.getElementById('edit_id').value = supplier.id;
            document.getElementById('edit_nama_supplier').value = supplier.nama_supplier;
            document.getElementById('edit_kontak_person').value = supplier.kontak_person || '';
            document.getElementById('edit_alamat').value = supplier.alamat || '';
            document.getElementById('edit_telepon').value = supplier.telepon || '';
            document.getElementById('edit_email').value = supplier.email || '';
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
        
        function deleteSupplier(id, nama) {
            if (confirm('Apakah Anda yakin ingin menghapus supplier "' + nama + '"?')) {
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