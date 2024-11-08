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

// Handle form submission for adding/updating products
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $performance_score = $_POST['performance_score'] ?? null;
    $socket = $_POST['socket'] ?? null;
    $ram_type = $_POST['ram_type'] ?? null;
    $wattage = $_POST['wattage'] ?? null;

    // Handle file upload
    if (isset($_FILES['image'])) {
        $image = $_FILES['image'];
        $image_name = basename($image['name']);
        $target_dir = "../uploads/";
        $target_file = $target_dir . $image_name;

        // Check if the file is an image
        $check = getimagesize($image['tmp_name']);
        if ($check !== false) {
            // Move the uploaded file to the target directory
            if (move_uploaded_file($image['tmp_name'], $target_file)) {
                if (isset($_POST['update'])) {
                    $id = $_POST['product_id'];
                    $stmt = $conn->prepare("UPDATE Products SET name=?, category=?, price=?, performance_score=?, socket=?, ram_type=?, wattage=?, image_url=? WHERE id=?");
                    $stmt->bind_param("ssdsisssi", $name, $category, $price, $performance_score, $socket, $ram_type, $wattage, $image_name, $id);
                    $stmt->execute();
                } elseif (isset($_POST['add'])) {
                    $stmt = $conn->prepare("INSERT INTO Products (name, category, price, performance_score, socket, ram_type, wattage, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssdsisss", $name, $category, $price, $performance_score, $socket, $ram_type, $wattage, $image_name);
                    $stmt->execute();
                }
                header("Location: admin.php");
                exit;
            } else {
                echo "Error uploading file.";
            }
        } else {
            echo "File is not an image.";
        }
    }
}

// Handle delete product logic
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM Products WHERE id = $id");
    header("Location: admin.php");
    exit;
}

// Fetch orders
$sql = "
    SELECT o.id as order_id, o.user_id, o.total_amount, o.order_date, 
           oi.product_id, SUM(oi.quantity) as total_quantity
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    GROUP BY o.id, oi.product_id
    ORDER BY o.order_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="apple-touch-icon" sizes="180x180" href="images/favicon_io/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../images/favicon_io/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../images/favicon_io/favicon-16x16.png">
    <link rel="manifest" href="images/favicon_io/site.webmanifest">
</head>

<body class="flex h-screen bg-gray-100">

    <!-- Sidebar -->
    <div id="sidebar" class="fixed inset-y-0 left-0 transform -translate-x-full lg:translate-x-0 transition-transform bg-blue-800 text-white w-64 z-30">
        <div class="p-4 flex justify-between items-center">
            <h2 class="text-2xl font-bold">Admin Dashboard</h2>
            <button id="closeSidebarBtn" class="lg:hidden text-white focus:outline-none">✕</button>
        </div>
        <nav class="mt-5">
            <button onclick="loadContent('products')" class="block w-full text-left py-2 px-4 text-gray-200 hover:bg-blue-700 rounded-md">Products</button>
            <button onclick="loadContent('orders')" class="block w-full text-left py-2 px-4 text-gray-200 hover:bg-blue-700 rounded-md">Orders</button>
            <button onclick="loadContent('users')" class="block w-full text-left py-2 px-4 text-gray-200 hover:bg-blue-700 rounded-md">Users</button>
            <button onclick="loadContent('admins')" class="block w-full text-left py-2 px-4 text-gray-200 hover:bg-blue-700 rounded-md">Admins</button>
            <a href="logout.php" class="block py-2 px-4 text-gray-200 hover:bg-blue-700 rounded-md">Logout</a>
        </nav>
    </div>

    <!-- Main content area -->
    <div class="flex flex-col flex-1 lg:ml-64 transition-all">
        <!-- Header -->
        <header class="bg-white shadow p-4 flex justify-between items-center">
            <button id="toggleSidebarBtn" class="lg:hidden text-blue-800 focus:outline-none">☰</button>
            <h1 class="text-xl font-semibold text-gray-800">Admin Dashboard</h1>
            <div class="flex items-center space-x-4">
                <img src="https://via.placeholder.com/32" alt="Profile Picture" class="w-8 h-8 rounded-full">
            </div>
        </header>

        <!-- Content area -->
        <main id="mainContent" class="p-6 bg-gray-100">
            <!-- Default content -->
            <h2 class="text-2xl font-semibold mb-4">Welcome to the Admin Dashboard</h2>
            <p>Select a section from the sidebar to manage different parts of the platform.</p>
        </main>
    </div>

    <script>
        let selectedContent = 'products'; // Default selected item

        function loadContent(contentId) {
            // Update the selected item
            selectedContent = contentId;

            // Clear the existing content
            const mainContent = document.getElementById('mainContent');
            mainContent.innerHTML = ''; // Clear previous content

            // Load the content based on contentId
            console.log('Loading content:', contentId);

            // Display content based on the selected section
            if (contentId === 'products') {
                mainContent.innerHTML = `
                    <h2 class="text-2xl font-semibold mb-4">Manage Products</h2>

                     <!-- Add Product Form -->
            <form method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-lg shadow-md mb-6">
                <h4 class="text-lg font-semibold mb-4">Add/Update Product</h4>
                <input type="hidden" name="product_id" value="">
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
                    <label for="price" class="block font-medium text-gray-700">Price</label>
                    <input type="number" id="price" name="price" class="mt-1 p-2 block w-full border-gray-300 rounded-md" required step="0.01">
                </div>
                <div class="mb-4">
                    <label for="performance_score" class="block font-medium text-gray-700">Performance Score</label>
                    <input type="number" id="performance_score" name="performance_score" class="mt-1 p-2 block w-full border-gray-300 rounded-md" step="0.01">
                </div>
                <div class="mb-4">
                    <label for="socket" class="block font-medium text-gray-700">Socket (for CPU/Motherboard)</label>
                    <input type="text" id="socket" name="socket" class="mt-1 p-2 block w-full border-gray-300 rounded-md">
                </div>
                <div class="mb-4">
                    <label for="ram_type" class="block font-medium text-gray-700">RAM Type (for Motherboard)</label>
                    <input type="text" id="ram_type" name="ram_type" class="mt-1 p-2 block w-full border-gray-300 rounded-md">
                </div>
                <div class="mb-4">
                    <label for="wattage" class="block font-medium text-gray-700">Wattage (for PSU)</label>
                    <input type="number" id="wattage" name="wattage" class="mt-1 p-2 block w-full border-gray-300 rounded-md">
                </div>
                <div class="mb-4">
                    <label for="image" class="block font-medium text-gray-700">Product Image</label>
                    <input type="file" id="image" name="image" class="mt-1 p-2 block w-full border-gray-300 rounded-md" accept="image/*" required>
                </div>
                <button type="submit" name="add" class="bg-green-500 text-white py-2 px-4 rounded-md hover:bg-green-600">Add Product</button>
                <button type="submit" name="update" class="bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600">Update Product</button>
            </form>

                    <table class="min-w-full bg-white border">
                        <thead>
                            <tr>
                                <th class="py-2 px-4 border-b">ID</th>
                                <th class="py-2 px-4 border-b">Name</th>
                                <th class="py-2 px-4 border-b">Category</th>
                                <th class="py-2 px-4 border-b">Price</th>
                                <th class="py-2 px-4 border-b">Image</th>
                                <th class="py-2 px-4 border-b">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr class="border-b">
                            <td class="py-2 px-4"><?= $product['id'] ?></td>
                            <td class="py-2 px-4"><?= $product['name'] ?></td>
                            <td class="py-2 px-4"><?= $product['category'] ?></td>
                            <td class="py-2 px-4">₱<?= number_format($product['price'], 2) ?></td>
                            <td class="py-2 px-4"><?= $product['performance_score'] ?></td>
                            <td class="py-2 px-4">
                                <img src="../uploads/<?= $product['image_url'] ?>" alt="<?= $product['name'] ?>" class="w-12 h-auto">
                            </td>
                            <td class="py-2 px-4">
                                <a href="?delete=<?= htmlspecialchars($product['id']) ?>" class="bg-red-500 text-white py-1 px-2 rounded hover:bg-red-600">Delete</a>
                                <button class="bg-yellow-500 text-white py-1 px-2 rounded hover:bg-yellow-600 edit-btn"
                                    data-id="<?= htmlspecialchars($product['id']) ?>"
                                    data-name="<?= htmlspecialchars($product['name']) ?>"
                                    data-category="<?= htmlspecialchars($product['category']) ?>"
                                    data-price="<?= htmlspecialchars($product['price']) ?>"
                                    data-performance_score="<?= htmlspecialchars($product['performance_score']) ?>"
                                    data-socket="<?= htmlspecialchars($product['socket']) ?>"
                                    data-ram_type="<?= htmlspecialchars($product['ram_type']) ?>"
                                    data-wattage="<?= htmlspecialchars($product['wattage']) ?>"
                                    data-image_url="<?= htmlspecialchars($product['image_url']) ?>">Edit</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                        </tbody>
                    </table>
                `;
            }

            if (contentId === 'orders') {
                mainContent.innerHTML = `
                    <h2 class="text-2xl font-semibold mb-4">Manage Orders</h2>
                    <table class="min-w-full bg-white border">
                        <thead>
                            <tr>
                                <th class="py-2 px-4 border-b">Order ID</th>
                                <th class="py-2 px-4 border-b">User ID</th>
                                <th class="py-2 px-4 border-b">Product ID</th>
                                <th class="py-2 px-4 border-b">Quantity</th>
                                <th class="py-2 px-4 border-b">Total Price</th>
                                <th class="py-2 px-4 border-b">Order Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($orders as $order): ?>
    <tr>
        <td class="py-2 px-4"><?= isset($order['order_id']) ? $order['order_id'] : 'N/A' ?></td>
        <td class="py-2 px-4"><?= isset($order['user_id']) ? $order['user_id'] : 'N/A' ?></td>
        <td class="py-2 px-4"><?= isset($order['product_id']) ? $order['product_id'] : 'N/A' ?></td>
        <td class="py-2 px-4"><?= isset($order['total_quantity']) ? $order['total_quantity'] : 'N/A' ?></td>
        <td class="py-2 px-4"><?= isset($order['total_amount']) ? '₱' . number_format($order['total_amount'], 2) : '₱0.00' ?></td>
        <td class="py-2 px-4"><?= isset($order['order_date']) ? $order['order_date'] : 'N/A' ?></td>
    </tr>
<?php endforeach; ?>


                        </tbody>
                    </table>
                `;
            }
        }

        // Close the sidebar
        const closeSidebarBtn = document.getElementById('closeSidebarBtn');
        closeSidebarBtn.addEventListener('click', () => {
            document.getElementById('sidebar').classList.add('-translate-x-full');
        });

        // Open the sidebar
        const toggleSidebarBtn = document.getElementById('toggleSidebarBtn');
        toggleSidebarBtn.addEventListener('click', () => {
            document.getElementById('sidebar').classList.remove('-translate-x-full');
        });
    </script>
</body>

</html>