<?php
// Error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

class Database {
    private $host = "localhost";
    private $db_name = "investo_db";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8", 
                $this->username, 
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                )
            );
        } catch(PDOException $exception) {
            die("Connection error: " . $exception->getMessage());
        }
        return $this->conn;
    }
}

// Session configuration
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isStaff() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'staff';
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: dashboard.php?error=access_denied");
        exit();
    }
}

function hasAccess($required_role) {
    if ($required_role === 'admin') {
        return isAdmin();
    } elseif ($required_role === 'staff') {
        return isStaff() || isAdmin();
    }
    return false;
}

function generateKode($prefix, $length = 6) {
    return $prefix . str_pad(rand(1, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

function formatTanggal($tanggal) {
    return date('d/m/Y H:i', strtotime($tanggal));
}

function logActivity($user_id, $aktivitas, $tabel_terkait = null, $id_terkait = null, $data_lama = null, $data_baru = null) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "INSERT INTO log_aktivitas (user_id, aktivitas, tabel_terkait, id_terkait, data_lama, data_baru, ip_address, user_agent) 
                  VALUES (:user_id, :aktivitas, :tabel_terkait, :id_terkait, :data_lama, :data_baru, :ip_address, :user_agent)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':aktivitas', $aktivitas);
        $stmt->bindParam(':tabel_terkait', $tabel_terkait);
        $stmt->bindParam(':id_terkait', $id_terkait);
        $stmt->bindParam(':data_lama', $data_lama);
        $stmt->bindParam(':data_baru', $data_baru);
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $stmt->bindParam(':ip_address', $ip_address);
        $stmt->bindParam(':user_agent', $user_agent);
        $stmt->execute();
    } catch (Exception $e) {
        // Log error but don't break the application
        error_log("Log activity error: " . $e->getMessage());
    }
}

function getRoleBadge($role) {
    switch($role) {
        case 'admin':
            return '<span class="badge bg-warning text-dark">Admin</span>';
        case 'staff':
            return '<span class="badge" style="background-color: #8B4513; color: white;">Staff Gudang</span>';
        default:
            return '<span class="badge bg-secondary">Unknown</span>';
    }
}

function uploadImage($file, $target_dir = 'uploads/barang/') {
    // Create directory if not exists
    if (!file_exists($target_dir)) {
        if (!mkdir($target_dir, 0777, true)) {
            return false;
        }
    }
    
    // Check if file was uploaded
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return false;
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    $imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    
    // Check if image file is actual image
    $check = getimagesize($file["tmp_name"]);
    if($check === false) {
        return false;
    }
    
    // Check file size (5MB max)
    if ($file["size"] > 5000000) {
        return false;
    }
    
    // Allow certain file formats
    if(!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
        return false;
    }
    
    // Generate unique filename
    $unique_name = time() . '_' . uniqid() . '.' . $imageFileType;
    $target_file = $target_dir . $unique_name;
    
    // Move uploaded file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $unique_name;
    } else {
        return false;
    }
}
?>