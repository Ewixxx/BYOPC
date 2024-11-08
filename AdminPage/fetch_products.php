<?php
session_start();
require '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? null;

if ($action === 'fetch') {
    $products = $conn->query("SELECT * FROM Products")->fetch_all(MYSQLI_ASSOC);
    echo json_encode($products);
} elseif ($action === 'add' || $action === 'update') {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $performance_score = $_POST['performance_score'] ?? null;
    $socket = $_POST['socket'] ?? null;
    $ram_type = $_POST['ram_type'] ?? null;
    $wattage = $_POST['wattage'] ?? null;

    if (!empty($_FILES['image']['name'])) {
        $image_name = time() . '_' . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/" . $image_name);
    }

    if ($action === 'add') {
        $stmt = $conn->prepare("INSERT INTO Products (name, category, price, performance_score, socket, ram_type, wattage, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdsisss", $name, $category, $price, $performance_score, $socket, $ram_type, $wattage, $image_name);
        $stmt->execute();
        echo json_encode(['success' => 'Product added successfully']);
    } else if ($action === 'update') {
        $id = $_POST['id'];
        $stmt = $conn->prepare("UPDATE Products SET name=?, category=?, price=?, performance_score=?, socket=?, ram_type=?, wattage=?, image_url=? WHERE id=?");
        $stmt->bind_param("ssdsisssi", $name, $category, $price, $performance_score, $socket, $ram_type, $wattage, $image_name, $id);
        $stmt->execute();
        echo json_encode(['success' => 'Product updated successfully']);
    }
} elseif ($action === 'delete') {
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM Products WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo json_encode(['success' => 'Product deleted successfully']);
} else {
    echo json_encode(['error' => 'Invalid action']);
}
