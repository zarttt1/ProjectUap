<?php
require 'db.php';

$result = $conn->query("SELECT * FROM barang");

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);
?>
