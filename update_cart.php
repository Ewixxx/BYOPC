<?php
session_start();
require 'db.php';

if (isset($_GET['cart_item_id']) && isset($_GET['quantity'])) {
    $cart_item_id = $_GET['cart_item_id'];
    $quantity = (int)$_GET['quantity'];
    
    if ($quantity > 0) {
        $stmt = $conn->prepare("UPDATE Cart_Items SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $quantity, $cart_item_id);
        $stmt->execute();
    }
}

header("Location: home.php"); 
exit;
