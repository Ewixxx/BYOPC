<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user orders
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Purchases</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <h2 class="text-2xl font-semibold mb-4">My Purchases</h2>
    
    <?php if ($orders->num_rows > 0): ?>
        <?php while ($order = $orders->fetch_assoc()): ?>
            <div class="bg-white p-4 rounded-lg shadow-md mb-4">
                <p><strong>Order ID:</strong> <?= htmlspecialchars($order['id']) ?></p>
                <p><strong>Date:</strong> <?= htmlspecialchars($order['order_date']) ?></p>
                <p><strong>Total:</strong> ₱<?= number_format($order['total_amount'], 2) ?></p>

                <h3 class="text-lg font-semibold mt-2">Items:</h3>
                <ul>
                    <?php
                    $order_id = $order['id'];
                    $stmt_items = $conn->prepare("SELECT oi.quantity, oi.price, p.name 
                                                  FROM order_items oi 
                                                  JOIN products p ON oi.product_id = p.id 
                                                  WHERE oi.order_id = ?");
                    $stmt_items->bind_param("i", $order_id);
                    $stmt_items->execute();
                    $items = $stmt_items->get_result();

                    while ($item = $items->fetch_assoc()):
                    ?>
                        <li>
                            <?= htmlspecialchars($item['name']) ?> - Quantity: <?= $item['quantity'] ?> - Price: ₱<?= number_format($item['price'], 2) ?>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>
            
        <?php endwhile; ?>
    <?php else: ?>
        <p>No purchases found.</p>
    <?php endif; ?>
    <a href="home.php" class="mt-8 bg-green-500 text-white py-2 px-4 rounded-lg hover:bg-cyan-600">Back to Home</a>

</body>
</html>
