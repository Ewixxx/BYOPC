<?php
session_start();
require 'db.php';

// Fetch product ID from URL
if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit;
}

$product_id = intval($_GET['id']);

// Fetch product details from the database
$stmt = $conn->prepare("SELECT * FROM Products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    header("Location: products.php");
    exit;
}
if (isset($_SESSION['user_id'])) {
    // Logged-in user cart
    $stmt = $conn->prepare("SELECT p.name, p.price, ci.quantity 
                            FROM Cart_Items ci 
                            JOIN Products p ON ci.product_id = p.id 
                            JOIN Cart c ON ci.cart_id = c.id 
                            WHERE c.user_id = ?");
    if ($stmt === false) {
        die('Prepare failed: ' . $conn->error); // Error handling for query preparation
    }
    $stmt->bind_param("i", $_SESSION['user_id']);
} else {
    // Guest user cart by session ID
    $session_id = session_id();
    $stmt = $conn->prepare("SELECT p.name, p.price, ci.quantity 
                            FROM Cart_Items ci 
                            JOIN Products p ON ci.product_id = p.id 
                            JOIN Cart c ON ci.cart_id = c.id 
                            WHERE c.session_id = ?");
    if ($stmt === false) {
        die('Prepare failed: ' . $conn->error); // Error handling for query preparation
    }
    $stmt->bind_param("s", $session_id);
}

if (!$stmt->execute()) {
    die('Execute failed: ' . $stmt->error); // Error handling for query execution
}

$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if ($cart_items === false) {
    die('Fetch failed: ' . $stmt->error); // Error handling for fetching results
}
$cart_count = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0; // Calculate total quantity of items in cart

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?></title>
    <script src="https://cdn.lordicon.com/lordicon.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="styles.css?v=<?php echo filemtime('styles.css'); ?>">
    <script src="script.js?v=<?php echo filemtime('script.js'); ?>"></script>
    <link rel="apple-touch-icon" sizes="180x180" href="images/favicon_io/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon_io/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon_io/favicon-16x16.png">
    <link rel="manifest" href="images/favicon_io/site.webmanifest">
</head>
<style>
    body {
        color: white;
        margin-bottom: 20px;
    }
</style>

<body>
    <nav>
        <div class="navbar">
            <a href="home.php">
                <div class="navbar-brand">RigMasters</div>
            </a>
            <div class="search-container">
                <input type="text" placeholder="Search" class="search-bar">
                <button class="searchbtn"><i class="fa fa-search search-icon"></i></button>
            </div>
            <div class="cart">
                <button id="cart-icon" class="cart-icon">
                    <lord-icon
                        src="https://cdn.lordicon.com/ggirntso.json"
                        trigger="hover"
                        stroke="bold"
                        colors="primary:cyan,secondary:#16c79e"
                        style="width:30px;height:30px">
                    </lord-icon>
                    <span id="cart-counter" class="cart-counter">
                        <?php
                        // Fetch the cart count from the database for logged-in users
                        if (isset($_SESSION['user_id'])) {
                            $stmt = $conn->prepare("SELECT SUM(ci.quantity) AS total_quantity 
                                FROM Cart_Items ci 
                                JOIN Cart c ON ci.cart_id = c.id 
                                WHERE c.user_id = ?");
                            $stmt->bind_param("i", $_SESSION['user_id']);
                            $stmt->execute();
                            $result = $stmt->get_result()->fetch_assoc();
                            $cart_count = $result['total_quantity'] ? $result['total_quantity'] : 0;
                        } else {
                            // For guests or if user is not logged in
                            $cart_count = 0; // Or handle as needed
                        }

                        echo htmlspecialchars($cart_count);
                        ?>
                    </span>

                </button>


                <div id="cart-dropdown" class="cart-dropdown position-absolute p-3 shadow" style="display: none;">
                    <center>
                        <h6>Your Cart</h6>
                    </center>
                    <ul class="list-unstyled">
                        <?php
                        $cart_items = [];

                        if (isset($_SESSION['user_id'])) {
                            $stmt = $conn->prepare("SELECT ci.id as cart_item_id, p.name, p.price, ci.quantity 
                                    FROM Cart_Items ci 
                                    JOIN Products p ON ci.product_id = p.id 
                                    JOIN Cart c ON ci.cart_id = c.id 
                                    WHERE c.user_id = ?");
                            $stmt->bind_param("i", $_SESSION['user_id']);
                            $stmt->execute();
                            $cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                        } elseif (isset($_SESSION['guest_cart'])) {
                            $session_id = session_id();
                            $stmt = $conn->prepare("SELECT ci.id as cart_item_id, p.name, p.price, ci.quantity 
                                    FROM Cart_Items ci 
                                    JOIN Products p ON ci.product_id = p.id 
                                    JOIN Cart c ON ci.cart_id = c.id 
                                    WHERE c.session_id = ?");
                            $stmt->bind_param("s", $session_id);
                            $stmt->execute();
                            $cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                        }

                        if (!empty($cart_items)):
                            $total = 0;
                            foreach ($cart_items as $item):
                                $total += $item['price'] * $item['quantity'];
                        ?>
                                <li>
                                    <?= htmlspecialchars($item['name']) ?> - ₱<?= number_format($item['price'], 2) ?> x
                                    <input type="number" class="quantity-input" data-cart-item-id="<?= $item['cart_item_id'] ?>" value="<?= $item['quantity'] ?>" min="0" style="width: 30px;">
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <center>
                                <li><img src="images/empty_cart.png" alt="Empty Cart" style="width:50px; height:50px; "><br>Your Cart is Empty</li>
                            </center>
                        <?php endif; ?>
                    </ul>

                    <?php if (isset($total) && $total > 0): ?>
                        <div class="mt-2">
                            <strong>Total: ₱<?= number_format($total, 2) ?></strong>
                        </div>
                    <?php endif; ?>

                    <center> <a href="checkout.php" class="btn btn-primary btn-sm mt-2">Checkout</a></center>
                </div>


                <div class="favorite">
                    <a href="favorites.php">
                        <lord-icon
                            src="https://cdn.lordicon.com/aydxrkfl.json"
                            trigger="morph"
                            state="morph-slider"
                            colors="primary:cyan,secondary:#08a88a"
                            style="width:30px;height:30px">
                        </lord-icon>
                    </a>
                </div>
                <div class="profile-section">
                    <button class="profilebtn"><a href="profile.php">
                            <lord-icon
                                src="https://cdn.lordicon.com/kdduutaw.json"
                                trigger="hover"
                                stroke="bold"
                                colors="primary:cyan,secondary:#16c79e"
                                style="width:30px;height:30px">
                            </lord-icon>
                        </a></button>
                </div>
                <div class="menu-icon" id="menu-icon"><i class="fa-solid fa-bars"></i></div>
                <div class="navbar-list" id="navbar-list">
                    <ul>
                        <li><a href="build_pc.php">
                                <lord-icon
                                    src="https://cdn.lordicon.com/fwkrbvja.json"
                                    trigger="hover"
                                    stroke="bold"
                                    colors="primary:cyan,secondary:#16c79e"
                                    style="width:20px;height:20px">
                                </lord-icon>
                                Build Your Own PC</a></li>
                        <li><a href="products.php">
                                <lord-icon
                                    src="https://cdn.lordicon.com/dkobpcrm.json"
                                    trigger="hover"
                                    colors="primary:#30e8bd,secondary:#08a88a"
                                    style="width:20px;height:20px">
                                </lord-icon>
                                Products</a></li>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li><a href="my_purchases.php">
                                    <lord-icon
                                        src="https://cdn.lordicon.com/jprtoagx.json"
                                        trigger="morph"
                                        state="morph-fill"
                                        colors="primary:#30e8bd,secondary:#08a88a"
                                        style="width:20px;height:20px">
                                    </lord-icon> My Purchases
                                </a></li>
                        <?php endif; ?>



                        <li><a href="#"><lord-icon
                                    src="https://cdn.lordicon.com/rzgcaxjz.json"
                                    trigger="hover"
                                    stroke="bold"
                                    colors="primary:cyan,secondary:#16c79e"
                                    style="width:20px;height:20px">
                                </lord-icon> Contact Us</a></li>
                        <li>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="logout.php" class="logout">
                                    <lord-icon
                                        src="https://cdn.lordicon.com/gwvmctbb.json"
                                        trigger="hover"
                                        stroke="bold"
                                        colors="primary:cyan,secondary:#16c79e"
                                        style="width:20px;height:20px">
                                    </lord-icon> Log Out
                                </a>
                            <?php else: ?>
                                <a href="#" class="login" data-bs-toggle="modal" data-bs-target="#loginModal">
                                    <lord-icon
                                        src="https://cdn.lordicon.com/hrjifpbq.json"
                                        trigger="hover"
                                        colors="primary:cyan"
                                        style="width:20px;height:20px">
                                    </lord-icon>
                                    Login
                                </a>
                                <a href="#" class="signup" data-bs-toggle="modal" data-bs-target="#signupModal">
                                    <lord-icon
                                        src="https://cdn.lordicon.com/exymduqj.json"
                                        trigger="hover"
                                        state="hover-line"
                                        colors="primary:cyan,secondary:#30e8bd"
                                        style="width:20px;height:20px">
                                    </lord-icon>
                                    Sign Up
                                </a>

                            <?php endif; ?>

                        </li>
                    </ul>
                </div>

            </div>
    </nav>
    <div class="container mt-5">
        <h2 class="mb-4"><?= htmlspecialchars($product['name']) ?></h2>
        <div class="row">
            <div class="col-md-6">
                <div class="product-image-container">
                    <img src="uploads/<?= $product['image_url'] ?>" class="img-fluid" alt="<?= htmlspecialchars($product['name']) ?>">
                </div>
            </div>
            <div class="col-md-6">
                <h5>Product Details</h5>
                <p><strong>Category:</strong> <?= htmlspecialchars($product['category']) ?></p>
                <p><strong>Price:</strong> ₱<?= number_format($product['price'], 2) ?></p>
                <p><strong>Performance Score:</strong> <?= htmlspecialchars($product['performance_score']) ?></p>
                <p><strong>Socket:</strong> <?= htmlspecialchars($product['socket'] ?? 'N/A') ?></p>
                <p><strong>RAM Type:</strong> <?= htmlspecialchars($product['ram_type'] ?? 'N/A') ?></p>
                <p><strong>Wattage:</strong> <?= htmlspecialchars($product['wattage'] ?? 'N/A') ?> W</p>
                <p><strong>Description:</strong> <?= htmlspecialchars($product['description'] ?? 'No description available') ?></p>

                <div class="d-flex align-items-center">
                    <form action="add_to_cart.php" method="POST" class="me-2">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <input type="number" name="quantity" value="1" min="1" class="form-control" style="width: 70px; display: inline;">
                        <button type="submit" class="btn btn-success">
                            <i class="fa-solid fa-cart-plus"></i> Add to Cart
                        </button>
                    </form>

                    <form action="add_to_favorites.php" method="POST">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <button type="submit" class="btn btn-danger">
                            <i class="fa-regular fa-heart"></i> Add to Favorites
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <span class="theme-icon"><img src="images/Dark-mode.png" alt="theme-icon"></span>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const menuIcon = document.getElementById('menu-icon');
        const navbarList = document.getElementById('navbar-list');

        menuIcon.addEventListener('click', function() {
            navbarList.classList.toggle('show');
        });

        document.addEventListener('click', function(event) {
            if (!navbarList.contains(event.target) && !menuIcon.contains(event.target)) {
                navbarList.classList.remove('show');
            }
        });
        document.getElementById('cart-icon').addEventListener('click', function() {
            const cartDropdown = document.getElementById('cart-dropdown');
            cartDropdown.style.display = (cartDropdown.style.display === 'none' || cartDropdown.style.display === '') ? 'block' : 'none';
        });

        // Close the cart dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const cartDropdown = document.getElementById('cart-dropdown');
            const cartIcon = document.getElementById('cart-icon');

            if (!cartIcon.contains(event.target) && !cartDropdown.contains(event.target)) {
                cartDropdown.style.display = 'none';
            }
        });

        const imageContainer = document.querySelector('.product-image-container');
        const image = imageContainer.querySelector('img');
        let scale = 1; // Initial zoom level
        let isDragging = false;
        let startX, startY, currentX, currentY;
        let translateX = 0,
            translateY = 0; // Initial translation values for dragging
        let dragTimeout; // Timeout for delayed drag
        let isLongPress = false; // To check if the mouse is pressed for a long time

        // Mouse scroll zoom handler
        imageContainer.addEventListener('wheel', function(e) {
            e.preventDefault();

            // Adjust zoom level
            if (e.deltaY < 0) {
                // Scrolling up (zoom in)
                scale += 0.1;
            } else {
                // Scrolling down (zoom out)
                scale -= 0.1;
            }

            // Limit the zoom scale
            if (scale < 1) scale = 1; // Minimum zoom level
            if (scale > 3) scale = 3; // Maximum zoom level

            image.style.transform = `scale(${scale}) translate(${translateX}px, ${translateY}px)`;

            // Adjust the origin of the zoom to be where the mouse is
            const rect = image.getBoundingClientRect();
            const xPos = ((e.clientX - rect.left) / rect.width) * 100;
            const yPos = ((e.clientY - rect.top) / rect.height) * 100;
            image.style.transformOrigin = `${xPos}% ${yPos}%`;
        });

        // Mouse down handler to start the long press for dragging
        imageContainer.addEventListener('mousedown', function(e) {
            if (scale > 1) { // Allow dragging only when zoomed in
                isLongPress = false;
                dragTimeout = setTimeout(() => {
                    isDragging = true;
                    startX = e.clientX - translateX;
                    startY = e.clientY - translateY;
                    imageContainer.classList.add('dragging');
                    isLongPress = true;
                }, 500); // 500ms long press to enable dragging
            }
        });

        // Mouse move handler to drag the image
        imageContainer.addEventListener('mousemove', function(e) {
            if (isDragging) {
                currentX = e.clientX;
                currentY = e.clientY;

                // Calculate translation based on drag distance
                translateX = currentX - startX;
                translateY = currentY - startY;

                // Apply the translation along with the zoom
                image.style.transform = `scale(${scale}) translate(${translateX}px, ${translateY}px)`;
            }
        });

        // Mouse up handler to stop dragging
        imageContainer.addEventListener('mouseup', function() {
            clearTimeout(dragTimeout); // Cancel the long press if mouse is released early
            if (isDragging) {
                isDragging = false;
                imageContainer.classList.remove('dragging');
            }
        });

        // Mouse leave handler to stop dragging when mouse leaves container
        imageContainer.addEventListener('mouseleave', function() {
            clearTimeout(dragTimeout); // Cancel the long press if mouse leaves early
            if (isDragging) {
                isDragging = false;
                imageContainer.classList.remove('dragging');
            }
        });

        // Optional: Handle touch events for touch devices
        imageContainer.addEventListener('touchstart', function(e) {
            if (scale > 1) {
                isLongPress = false;
                dragTimeout = setTimeout(() => {
                    isDragging = true;
                    startX = e.touches[0].clientX - translateX;
                    startY = e.touches[0].clientY - translateY;
                    imageContainer.classList.add('dragging');
                    isLongPress = true;
                }, 500); // 500ms long press to enable dragging
            }
        });

        imageContainer.addEventListener('touchmove', function(e) {
            if (isDragging) {
                currentX = e.touches[0].clientX;
                currentY = e.touches[0].clientY;

                // Calculate translation based on drag distance
                translateX = currentX - startX;
                translateY = currentY - startY;

                // Apply the translation along with the zoom
                image.style.transform = `scale(${scale}) translate(${translateX}px, ${translateY}px)`;
            }
        });

        imageContainer.addEventListener('touchend', function() {
            clearTimeout(dragTimeout); // Cancel the long press if touch ends early
            if (isDragging) {
                isDragging = false;
                imageContainer.classList.remove('dragging');
            }
        });


        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                let cartItemId = this.dataset.cartItemId;
                let newQuantity = this.value;

                if (newQuantity == 0) {
                    if (confirm("Do you really want to remove this item from the cart?")) {
                        // Send request to remove item
                        window.location.href = `remove_from_cart.php?cart_item_id=${cartItemId}`;
                    } else {
                        this.value = 1; // Reset to 1 if user cancels
                    }
                } else {
                    // Send request to update quantity
                    window.location.href = `update_cart.php?cart_item_id=${cartItemId}&quantity=${newQuantity}`;
                }
            });
        });
    </script>
</body>

</html>