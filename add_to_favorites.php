<?php
session_start();
require 'db.php';

// Get product ID from the request
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

// Check if product ID is valid
if ($product_id > 0) {
    // Fetch the product from the database
    $stmt = $conn->prepare("SELECT id, name, price, image_url FROM Products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if ($product) {
        // Store in session for guest users
        if (!isset($_SESSION['favorites'])) {
            $_SESSION['favorites'] = [];
        }

        // Check if the product is already in the favorites session
        if (!isset($_SESSION['favorites'][$product_id])) {
            $_SESSION['favorites'][$product_id] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'image_url' => $product['image_url'], // Correct column name
            ];
        }

        // Store in the database if the user is logged in
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];

            // Check if the product is already in the favorites table for this user
            $stmt = $conn->prepare("SELECT id FROM Favorites WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $favorite_item = $result->fetch_assoc();

            if (!$favorite_item) {
                // Insert the product into the Favorites table with the image URL
                $stmt = $conn->prepare("INSERT INTO Favorites (user_id, product_id, image_url) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $user_id, $product_id, $product['image_url']);
                $stmt->execute();
            }
        }
    }
}

// Redirect back to the product details page or products list
$redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'products.php';
header("Location: " . $redirect_url);
exit;
?>
