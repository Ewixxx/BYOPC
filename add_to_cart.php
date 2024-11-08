<?php
session_start();
require 'db.php';

// Get product ID and quantity from the request
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

// Check if the product ID is valid
if ($product_id > 0 && $quantity > 0) {
    // Fetch the product from the database
    $stmt = $conn->prepare("SELECT * FROM Products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if ($product) {
        // Store the product in session if not already present
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // Check if the product already exists in the cart session
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity,
            ];
        }

        $session_id = session_id(); 


        $cart_id = null;
        if (isset($_SESSION['user_id'])) {
   
            $stmt = $conn->prepare("SELECT id FROM Cart WHERE user_id = ? LIMIT 1");
            $stmt->bind_param("i", $_SESSION['user_id']);
        } else {
           
            $stmt = $conn->prepare("SELECT id FROM Cart WHERE session_id = ? LIMIT 1");
            $stmt->bind_param("s", $session_id);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $cart = $result->fetch_assoc();

        if ($cart) {
           
            $cart_id = $cart['id'];
        } else {
        
            $stmt = $conn->prepare("INSERT INTO Cart (user_id, session_id) VALUES (?, ?)");
            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            $stmt->bind_param("is", $user_id, $session_id);
            $stmt->execute();
            $cart_id = $stmt->insert_id; 
        }


        $stmt = $conn->prepare("SELECT id FROM Cart_Items WHERE cart_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $cart_id, $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $cart_item = $result->fetch_assoc();

        if ($cart_item) {
       
            $stmt = $conn->prepare("UPDATE Cart_Items SET quantity = quantity + ? WHERE cart_id = ? AND product_id = ?");
            $stmt->bind_param("iii", $quantity, $cart_id, $product_id);
        } else {
            
            $stmt = $conn->prepare("INSERT INTO Cart_Items (cart_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $cart_id, $product_id, $quantity, $product['price']);
        }

        $stmt->execute();

      
        if (isset($_SESSION['user_id'])) {
            
            $stmt = $conn->prepare("DELETE FROM Favorites WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $_SESSION['user_id'], $product_id);
            $stmt->execute();
        }
    }
}


$redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'favorites.php';
header("Location: " . $redirect_url);
exit;
