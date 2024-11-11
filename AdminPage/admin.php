<?php
session_start();
require '../db.php';

// Ensure the user is logged in as an admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Fetch all products for display
$products = $conn->query("SELECT * FROM Products")->fetch_all(MYSQLI_ASSOC);

// Fetch all orders for "My Orders" section
$orders = $conn->query("SELECT * FROM Orders")->fetch_all(MYSQLI_ASSOC);
$completedOrders = $conn->query("SELECT * FROM Orders WHERE status = 'Order Complete'")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['product_id'] ?? null;
    $name = $_POST['name'];
    $category = $_POST['category'];
    $compatibility = $_POST['compatibility'] ?? null;
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $performance_score = $_POST['performance_score'] ?? null;
    $socket = $_POST['socket'] ?? null;
    $ram_type = $_POST['ram_type'] ?? null;
    $wattage = $_POST['wattage'] ?? null;
    $image_folder = $_POST['image_folder'] ?? null;

    // Initialize image_url as null to be updated if there's an image upload
    $image_url = null;

    // Handle file upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image = $_FILES['image'];
        $target_dir = "../uploads/";
        $image_name = basename($image['name']);
        $target_file = $target_dir . $image_name;

        // Check if the file is an image
        if (getimagesize($image['tmp_name']) !== false) {
            // Move the uploaded file to the target directory
            if (move_uploaded_file($image['tmp_name'], $target_file)) {
                // If file upload was successful, set the image URL
                $image_url = $image_name;
            } else {
                echo "Error uploading file.";
                exit;
            }
        } else {
            echo "File is not an image.";
            exit;
        }
    } else {
        // Use current image URL if updating and no new file was uploaded
        $image_url = $_POST['current_image_url'] ?? null;
    }

    // Prepare the SQL query
    if (isset($_POST['add'])) {
        // Insert new product
        $stmt = $conn->prepare("INSERT INTO Products (name, category, compatibility, price, stock, image_url, performance_score, socket, ram_type, wattage, image_folder) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }
        $stmt->bind_param("sssdissssss", $name, $category, $compatibility, $price, $stock, $image_url, $performance_score, $socket, $ram_type, $wattage, $image_folder);
    } elseif (isset($_POST['update']) && $id) {
        // Update existing product
        $stmt = $conn->prepare("UPDATE Products SET name=?, category=?, compatibility=?, price=?, stock=?, image_url=?, performance_score=?, socket=?, ram_type=?, wattage=?, image_folder=? WHERE id=?");
        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }
        // Updated bind_param with correct type specifiers
        $stmt->bind_param("sssdisssssii", $name, $category, $compatibility, $price, $stock, $image_url, $performance_score, $socket, $ram_type, $wattage, $image_folder, $id);
    }


    // Execute query and check for success
    if ($stmt->execute()) {
        header("Location: admin.php");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="apple-touch-icon" sizes="180x180" href="../images/favicon_io/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../images/favicon_io/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../images/favicon_io/favicon-16x16.png">
    <link rel="manifest" href="../images/favicon_io/site.webmanifest">
</head>

<body class="flex h-screen bg-gray-100">

    <!-- Sidebar -->
    <div id="sidebar" class="fixed inset-y-0 left-0 transform -translate-x-full lg:translate-x-0 transition-transform bg-blue-800 text-white w-64 z-30 flex flex-col">
        <div class="p-4 flex justify-between items-center">
            <h2 class="text-2xl font-bold">Admin Dashboard</h2>
            <button id="closeSidebarBtn" class="lg:hidden text-white focus:outline-none">✕</button>
        </div>
        <nav class="mt-5 flex-1">
            <button onclick="loadContent('products')" class="block w-full text-left py-2 px-4 text-gray-200 hover:bg-blue-700 rounded-md">Products</button>
            <button onclick="loadContent('orders')" class="block w-full text-left py-2 px-4 text-gray-200 hover:bg-blue-700 rounded-md">Orders</button>

            <button onclick="loadContent('completedOrders')" class="block w-full text-left py-2 px-4 text-gray-200 hover:bg-blue-700 rounded-md">Completed Orders</button>
        </nav>
    
        <a href="logout.php" class="block py-2 px-4 text-gray-200 hover:bg-red-700 rounded-md mt-auto mb-4">Logout</a>
    </div>


    <!-- Main content area -->
    <div class="flex flex-col flex-1 lg:ml-64 transition-all">
        <header class="bg-white shadow p-4 flex justify-between items-center">
            <button id="toggleSidebarBtn" class="lg:hidden text-blue-800 focus:outline-none">☰</button>
            <h1 class="text-xl font-semibold text-gray-800">Admin Dashboard</h1>
        </header>

        <main id="mainContent" class="p-6 bg-gray-100">
            <div id="productsSection">
                <h2 class="text-2xl font-semibold mb-4">Manage Products</h2>

                <!-- Add/Update Product Form -->
                <form method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <h4 class="text-lg font-semibold mb-4">Add/Update Product</h4>
                    <input type="hidden" id="product_id" name="product_id">
                    <div class="mb-4">
                        <label for="name" class="block font-medium text-gray-700">Product Name</label>
                        <input type="text" id="name" name="name" class="mt-1 p-2 block w-full border-gray-300 rounded-md" required>
                    </div>
                    <div class="mb-4">
                        <label for="category" class="block font-medium text-gray-700">Category</label>
                        <select id="category" name="category" class="mt-1 p-2 block w-full border-gray-300 rounded-md" required>
                            <option value="CPU">CPU</option>
                            <option value="GPU">GPU</option>
                            <option value="Motherboard">Motherboard</option>
                            <option value="Memory">Memory</option>
                            <option value="Storage">Storage</option>
                            <option value="PSU">PSU</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="compatibility" class="block font-medium text-gray-700">Compatibility</label>
                        <input type="text" id="compatibility" name="compatibility" class="mt-1 p-2 block w-full border-gray-300 rounded-md">
                    </div>
                    <div class="mb-4">
                        <label for="price" class="block font-medium text-gray-700">Price</label>
                        <input type="number" id="price" name="price" class="mt-1 p-2 block w-full border-gray-300 rounded-md" required step="0.01">
                    </div>
                    <div class="mb-4">
                        <label for="stock" class="block font-medium text-gray-700">Stock</label>
                        <input type="number" id="stock" name="stock" class="mt-1 p-2 block w-full border-gray-300 rounded-md" required>
                    </div>
                    <div class="mb-4">
                        <label for="performance_score" class="block font-medium text-gray-700">Performance Score</label>
                        <input type="number" id="performance_score" name="performance_score" class="mt-1 p-2 block w-full border-gray-300 rounded-md">
                    </div>
                    <div class="mb-4">
                        <label for="socket" class="block font-medium text-gray-700">Socket</label>
                        <input type="text" id="socket" name="socket" class="mt-1 p-2 block w-full border-gray-300 rounded-md">
                    </div>
                    <div class="mb-4">
                        <label for="ram_type" class="block font-medium text-gray-700">RAM Type</label>
                        <input type="text" id="ram_type" name="ram_type" class="mt-1 p-2 block w-full border-gray-300 rounded-md">
                    </div>
                    <div class="mb-4">
                        <label for="wattage" class="block font-medium text-gray-700">Wattage</label>
                        <input type="number" id="wattage" name="wattage" class="mt-1 p-2 block w-full border-gray-300 rounded-md">
                    </div>
                    <div class="mb-4">
                        <label for="image_folder" class="block font-medium text-gray-700">Image Folder</label>
                        <input type="text" id="image_folder" name="image_folder" class="mt-1 p-2 block w-full border-gray-300 rounded-md">
                    </div>
                    <div class="mb-4">
                        <label for="image" class="block font-medium text-gray-700">Product Image</label>
                        <input type="file" id="image" name="image" class="mt-1 p-2 block w-full border-gray-300 rounded-md">
                    </div>
                    <div class="mb-4">
                        <button type="submit" name="add" class="py-2 px-4 bg-blue-600 text-white rounded-md hover:bg-blue-700">Add Product</button>
                        <button type="submit" name="update" class="py-2 px-4 bg-green-600 text-white rounded-md hover:bg-green-700">Update Product</button>
                    </div>
                </form>

                <!-- Product List Table -->
                <table class="w-full table-auto bg-white shadow-md rounded-lg mb-4">
                    <thead>
                        <tr class="bg-blue-500 text-white">
                            <th class="p-2">Product Name</th>
                            <th class="p-2">Category</th>
                            <th class="p-2">Price</th>
                            <th class="p-2">Stock</th>
                            <th class="p-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr class="border-b">
                                <td class="p-2"><?php echo htmlspecialchars($product['name']); ?></td>
                                <td class="p-2"><?php echo htmlspecialchars($product['category']); ?></td>
                                <td class="p-2"><?php echo number_format($product['price'], 2); ?></td>
                                <td class="p-2"><?php echo $product['stock']; ?></td>
                                <td class="p-2">
                                    <button class="edit-btn py-1 px-3 bg-yellow-500 text-white rounded-md" data-id="<?php echo $product['id']; ?>">Edit</button>
                                    <a href="?delete=<?php echo $product['id']; ?>" class="py-1 px-3 bg-red-500 text-white rounded-md">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="ordersSection">
                <h2 class="text-2xl font-semibold mb-4">All Orders</h2>
                <table class="min-w-full bg-white border">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b">Order ID</th>
                            <th class="py-2 px-4 border-b">User ID</th>
                            <th class="py-2 px-4 border-b">Total Price</th>
                            <th class="py-2 px-4 border-b">Order Date</th>
                            <th class="py-2 px-4 border-b">Shipping Address</th>
                            <th class="py-2 px-4 border-b">Status</th>
                            <th class="py-2 px-4 border-b">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td class="py-2 px-4"><?= $order['id'] ?></td>
                                <td class="py-2 px-4"><?= $order['user_id'] ?></td>
                                <td class="py-2 px-4">₱<?= number_format($order['total_amount'], 2) ?></td>
                                <td class="py-2 px-4"><?= $order['order_date'] ?></td>
                                <td class="py-2 px-4"><?= $order['shipping_street'] . ', ' . $order['shipping_city'] . ', ' . $order['shipping_state'] . ', ' . $order['shipping_postal_code'] . ', ' . $order['shipping_country'] ?></td>
                                <td class="py-2 px-4"><?= $order['status'] ?></td>
                                <td class="py-2 px-4">
                                    <form action="update_order_status.php" method="POST">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <select name="status" class="border rounded p-1">
                                            <option value="To-Ship" <?= $order['status'] == 'To-Ship' ? 'selected' : '' ?>>To-Ship</option>
                                            <option value="To-Receive" <?= $order['status'] == 'To-Receive' ? 'selected' : '' ?>>To-Receive</option>
                                            <option value="Order Complete" <?= $order['status'] == 'Order Complete' ? 'selected' : '' ?>>Order Complete</option>
                                        </select>
                                        <button type="submit" class="bg-blue-500 text-white px-2 py-1 rounded">Update</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Completed Orders Section on Sidebar -->
            <div id="completedOrders">
                <h2 class="text-2xl font-semibold mb-4">Completed Orders</h2>
                <table class="min-w-full bg-white border">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b">Order ID</th>
                            <th class="py-2 px-4 border-b">User ID</th>
                            <th class="py-2 px-4 border-b">Total Price</th>
                            <th class="py-2 px-4 border-b">Order Date</th>
                            <th class="py-2 px-4 border-b">Shipping Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($completedOrders as $order): ?>
                            <tr>
                                <td class="py-2 px-4"><?= $order['id'] ?></td>
                                <td class="py-2 px-4"><?= $order['user_id'] ?></td>
                                <td class="py-2 px-4">₱<?= number_format($order['total_amount'], 2) ?></td>
                                <td class="py-2 px-4"><?= $order['order_date'] ?></td>
                                <td class="py-2 px-4"><?= $order['shipping_street'] . ', ' . $order['shipping_city'] . ', ' . $order['shipping_state'] . ', ' . $order['shipping_postal_code'] . ', ' . $order['shipping_country'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>

    <script>
        document.getElementById('toggleSidebarBtn').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
        });

        function loadContent(section) {
            document.getElementById('productsSection').style.display = section === 'products' ? 'block' : 'none';
            document.getElementById('ordersSection').style.display = section === 'orders' ? 'block' : 'none';
            document.getElementById('completedOrders').style.display = section === 'completedOrders'?'block' :'none';
        }

        // Edit product logic
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');
                fetch('get_product.php?id=' + productId)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('product_id').value = data.id;
                        document.getElementById('name').value = data.name;
                        document.getElementById('category').value = data.category;
                        document.getElementById('compatibility').value = data.compatibility;
                        document.getElementById('price').value = data.price;
                        document.getElementById('stock').value = data.stock;
                        document.getElementById('performance_score').value = data.performance_score;
                        document.getElementById('socket').value = data.socket;
                        document.getElementById('ram_type').value = data.ram_type;
                        document.getElementById('wattage').value = data.wattage;
                        document.getElementById('image_folder').value = data.image_folder;
                    })
                    .catch(error => console.error('Error fetching product data:', error));
            });
        });
    </script>
</body>

</html>