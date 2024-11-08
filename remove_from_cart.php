<?php
session_start();
require 'db.php';

if (isset($_GET['cart_item_id'])) {
    $cart_item_id = $_GET['cart_item_id'];
    
    $stmt = $conn->prepare("DELETE FROM Cart_Items WHERE id = ?");
    $stmt->bind_param("i", $cart_item_id);
    $stmt->execute();
}

header("Location: home.php"); // Redirect back to the cart page
exit;
