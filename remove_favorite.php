<?php
session_start();
require 'db.php';

// Get the favorite item ID from the request
$favorite_id = isset($_POST['favorite_id']) ? intval($_POST['favorite_id']) : 0;

// Remove from Favorites if a valid favorite ID is provided
if ($favorite_id > 0) {
    $stmt = $conn->prepare("DELETE FROM Favorites WHERE id = ?");
    $stmt->bind_param("i", $favorite_id);
    $stmt->execute();
}

// Redirect back to the favorites page
header("Location: favorites.php");
exit;
