<?php
// Test file untuk debugging
echo "<h1>PHP Test</h1>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Current Directory: " . getcwd() . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";

// Test database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=investo_db", "root", "");
    echo "<span style='color: green;'>✓ Database connection successful</span><br>";
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "Users in database: " . $result['count'] . "<br>";
    
} catch(PDOException $e) {
    echo "<span style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</span><br>";
}

// Test file permissions
$upload_dir = 'uploads/barang/';
if (!file_exists($upload_dir)) {
    if (mkdir($upload_dir, 0777, true)) {
        echo "<span style='color: green;'>✓ Upload directory created</span><br>";
    } else {
        echo "<span style='color: red;'>✗ Failed to create upload directory</span><br>";
    }
} else {
    echo "<span style='color: green;'>✓ Upload directory exists</span><br>";
}

if (is_writable($upload_dir)) {
    echo "<span style='color: green;'>✓ Upload directory is writable</span><br>";
} else {
    echo "<span style='color: red;'>✗ Upload directory is not writable</span><br>";
}

// Test session
session_start();
echo "<span style='color: green;'>✓ Session started</span><br>";

echo "<h2>PHP Extensions</h2>";
$extensions = ['pdo', 'pdo_mysql', 'gd', 'fileinfo'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<span style='color: green;'>✓ $ext</span><br>";
    } else {
        echo "<span style='color: red;'>✗ $ext (missing)</span><br>";
    }
}

phpinfo();
?>