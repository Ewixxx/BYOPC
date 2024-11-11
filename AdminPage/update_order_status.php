<?php
// Include your database connection file
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve the order ID and new status from the POST request
    $orderId = $_POST['order_id'];
    $status = $_POST['status'];

    // Prepare an SQL statement to update the order status
    $stmt = $conn->prepare("UPDATE Orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $orderId);

    // Execute the update and check if it was successful
    if ($stmt->execute()) {
        echo "Order status updated successfully.";
    } else {
        echo "Failed to update order status. Please try again.";
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();

    // Redirect back to the admin dashboard or orders page
    header("Location: admin.php"); // Adjust this path as needed
    exit();
} else {
    // If accessed directly, redirect to the admin dashboard
    header("Location: admin.php");
    exit();
}
?>
