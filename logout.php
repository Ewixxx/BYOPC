<?php
session_start();

// Clear the cart session to reset cart items upon logout
if (isset($_SESSION['cart'])) {
    unset($_SESSION['cart']); // Remove the cart data from the session
}

// Optionally, reset the cart counter
if (isset($_SESSION['cart_count'])) {
    unset($_SESSION['cart_count']); // Reset the cart count as well
}

// Destroy the session (this will also log out the user)
session_destroy();

// Start a new session to generate a new session ID for the next user
session_start();
session_regenerate_id(true); // Regenerate a new session ID

// Redirect to the login page or homepage after logout
header("Location: home.php");
exit();
