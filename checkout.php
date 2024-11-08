<?php

require __DIR__ . "/vendor/autoload.php";
require 'db.php';

$stripe_secret_key = "sk_test_51QG9z7ECrxyGgUlBGsw4azHwgBskSKsjy2I4SffTtcKQM3RWQgsYkYiPL0bBCCdXNjI2ztG3VfuSwYNtkhgfmyU200iYA1mm7W";
\Stripe\Stripe::setApiKey($stripe_secret_key);

session_start();
$user_id = $_SESSION['user_id'] ?? null;
$session_id = session_id();

$line_items = [];
$total_amount = 0; // Track total amount for the order

// Fetch cart items based on session or user ID
if ($user_id) {
    $stmt = $conn->prepare("SELECT p.id as product_id, p.name, p.price, ci.quantity 
                            FROM Cart_Items ci 
                            JOIN Products p ON ci.product_id = p.id 
                            JOIN Cart c ON ci.cart_id = c.id 
                            WHERE c.user_id = ?");
    $stmt->bind_param("i", $user_id);
} else {
    $stmt = $conn->prepare("SELECT p.id as product_id, p.name, p.price, ci.quantity 
                            FROM Cart_Items ci 
                            JOIN Products p ON ci.product_id = p.id 
                            JOIN Cart c ON ci.cart_id = c.id 
                            WHERE c.session_id = ?");
    $stmt->bind_param("s", $session_id);
}

$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($cart_items as $item) {
    $line_items[] = [
        'quantity' => $item['quantity'],
        'price_data' => [
            'currency' => 'php',
            'unit_amount' => $item['price'] * 100,
            'product_data' => [
                'name' => $item['name'],
            ],
        ],
    ];

    // Calculate the total amount for order storage
    $total_amount += $item['price'] * $item['quantity'];
}

// Insert order into the `orders` table
if ($user_id) {
    $stmt = $conn->prepare("SELECT email, shipping_street, shipping_city, shipping_state, shipping_postal_code, shipping_country FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_info = $stmt->get_result()->fetch_assoc();

    // Record the order in the database
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, shipping_street, shipping_city, shipping_state, shipping_postal_code, shipping_country) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("idsssss", $user_id, $total_amount, $user_info['shipping_street'], $user_info['shipping_city'], $user_info['shipping_state'], $user_info['shipping_postal_code'], $user_info['shipping_country']);
    $stmt->execute();
    $order_id = $stmt->insert_id;

    // Insert each item into the `order_items` table
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($cart_items as $item) {
        $stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
        $stmt->execute();
    }

    // Create or retrieve the Stripe customer
    $customer = \Stripe\Customer::create([
        'email' => $user_info['email'],
        'shipping' => [
            'name' => $_SESSION['username'] ?? 'Customer',
            'address' => [
                'line1' => $user_info['shipping_street'],
                'city' => $user_info['shipping_city'],
                'state' => $user_info['shipping_state'],
                'postal_code' => $user_info['shipping_postal_code'],
                'country' => $user_info['shipping_country'],
            ],
        ],
    ]);
    $customer_id = $customer->id;
} else {
    $customer_id = null;
}

// Create the checkout session
$checkout_session = \Stripe\Checkout\Session::create([
    "mode" => "payment",
    "success_url" => "http://localhost/BYOPC/success.php?order_id=" . $order_id,
    "cancel_url" => "http://localhost/BYOPC/home.php",
    "locale" => "auto",
    "line_items" => $line_items,
    "customer" => $customer_id,
    "shipping_address_collection" => [
        "allowed_countries" => ['PH'],
    ],
]);

// Redirect to Stripe Checkout
http_response_code(303);
header("Location: " . $checkout_session->url);
?>
