<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-6 rounded-lg shadow-md w-full max-w-lg">
        <div class="flex flex-col items-center">
            <!-- Green Check Icon -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-green-500" viewBox="0 0 24 24" fill="currentColor">
                <circle cx="12" cy="12" r="10" fill="#4CAF50" />
                <path d="M10 15.5l6-6-1.5-1.5L10 12.5l-2-2L6.5 12l3.5 3.5z" fill="white" />
            </svg>

            <h1 class="text-2xl font-semibold text-gray-800">Payment Successful!</h1>
            <p class="text-gray-600 mt-2">Thank you for your purchase! Here are your order details:</p>

            <!-- Order Details -->
            <div class="mt-6 w-full">
                <h2 class="text-xl font-semibold text-gray-800">Order Summary</h2>
                <div class="bg-gray-50 p-4 rounded-lg mt-4">
                    <!-- Loop through order items and display -->
                    <?php
                    require 'db.php';
                    session_start();
                    $user_id = $_SESSION['user_id'];
                    
                    // Fetch latest order for the user
                    $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT 1");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $order = $stmt->get_result()->fetch_assoc();
                    $order_id = $order['id'];

                    echo "<p><strong>Order ID:</strong> " . htmlspecialchars($order_id) . "</p>";
                    echo "<p><strong>Total Amount:</strong> ₱" . number_format($order['total_amount'], 2) . "</p>";

                    // Fetch order items
                    $stmt = $conn->prepare("SELECT oi.quantity, oi.price, p.name 
                                            FROM order_items oi 
                                            JOIN products p ON oi.product_id = p.id 
                                            WHERE oi.order_id = ?");
                    $stmt->bind_param("i", $order_id);
                    $stmt->execute();
                    $items = $stmt->get_result();
                    ?>

                    <ul class="mt-4">
                        <?php while ($item = $items->fetch_assoc()): ?>
                            <li class="flex justify-between text-gray-700">
                                <span><?php echo htmlspecialchars($item['name']); ?> (x<?php echo $item['quantity']; ?>)</span>
                                <span>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>

                <!-- Clear cart items for the user -->
                <?php
                // Remove items from the cart
                $stmt = $conn->prepare("DELETE ci FROM Cart_Items ci 
                                        JOIN Cart c ON ci.cart_id = c.id 
                                        WHERE c.user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                ?>

                <!-- Shipping Information -->
                <h2 class="text-xl font-semibold text-gray-800 mt-6">Shipping Information</h2>
                <div class="bg-gray-50 p-4 rounded-lg mt-4">
                    <?php
                    echo "<p><strong>Street:</strong> " . htmlspecialchars($order['shipping_street']) . "</p>";
                    echo "<p><strong>City:</strong> " . htmlspecialchars($order['shipping_city']) . "</p>";
                    echo "<p><strong>State:</strong> " . htmlspecialchars($order['shipping_state']) . "</p>";
                    echo "<p><strong>Postal Code:</strong> " . htmlspecialchars($order['shipping_postal_code']) . "</p>";
                    echo "<p><strong>Country:</strong> " . htmlspecialchars($order['shipping_country']) . "</p>";
                    ?>
                </div>
            </div>

            <a href="home.php" class="mt-8 bg-green-500 text-white py-2 px-4 rounded-lg hover:bg-green-600">Back to Home</a>
        </div>
    </div>
</body>

</html>
