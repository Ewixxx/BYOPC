<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Update user profile information
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $email = $_POST['email'];
    $shipping_street = $_POST['shipping_street'];
    $shipping_city = $_POST['shipping_city'];
    $shipping_state = $_POST['shipping_state'];
    $shipping_postal_code = $_POST['shipping_postal_code'];
    $shipping_country = $_POST['shipping_country'];

    // Profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $target_dir = "uploads/"; // Ensure this directory exists and is writable
        $target_file = $target_dir . basename($_FILES["profile_picture"]["name"]);
        move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file);

        $stmt = $conn->prepare("UPDATE Users SET email = ?, shipping_street = ?, shipping_city = ?, shipping_state = ?, shipping_postal_code = ?, shipping_country = ?, profile_picture = ? WHERE id = ?");
        $stmt->bind_param("sssssssi", $email, $shipping_street, $shipping_city, $shipping_state, $shipping_postal_code, $shipping_country, $target_file, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE Users SET email = ?, shipping_street = ?, shipping_city = ?, shipping_state = ?, shipping_postal_code = ?, shipping_country = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $email, $shipping_street, $shipping_city, $shipping_state, $shipping_postal_code, $shipping_country, $user_id);
    }

    if ($stmt->execute()) {
        echo "Profile updated successfully!";
    } else {
        echo "Error updating profile.";
    }
}

// Change password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];

    // Fetch the user's current password
    $stmt = $conn->prepare("SELECT password FROM Users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Verify the old password
    if (password_verify($old_password, $user['password'])) {
        $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE Users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_new_password, $user_id);
        if ($stmt->execute()) {
            echo "Password changed successfully!";
        } else {
            echo "Error changing password.";
        }
    } else {
        echo "Old password is incorrect!";
    }
}

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM Users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <script src="https://cdn.lordicon.com/lordicon.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="styles.css?v=<?php echo filemtime('styles.css'); ?>">
    <script src="script.js?v=<?php echo filemtime('script.js'); ?>"></script>
</head>
<style>
    * {
        color: cyan;
    }
</style>
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
                                <input type="number" class="quantity-input" data-cart-item-id="<?= $item['cart_item_id'] ?>" value="<?= $item['quantity'] ?>" min="0" style="width: 50px;">
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

<body>
    <div class="container">
        <h2>Edit Profile</h2>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="profile_picture" class="form-label">Profile Picture</label>
                <input type="file" class="form-control" id="profile_picture" name="profile_picture">
                <?php if (!empty($user['profile_picture'])): ?>
                    <img src="<?= $user['profile_picture'] ?>" alt="Profile Picture" class="img-thumbnail mt-2" style="width: 150px; height: auto;">
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required>
            </div>

            <h3>Shipping Address</h3>
            <div class="mb-3">
                <label for="shipping_street" class="form-label">Street</label>
                <input type="text" class="form-control" id="shipping_street" name="shipping_street" value="<?= htmlspecialchars($user['shipping_street']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="shipping_city" class="form-label">City</label>
                <input type="text" class="form-control" id="shipping_city" name="shipping_city" value="<?= htmlspecialchars($user['shipping_city']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="shipping_state" class="form-label">Municipality/Region</label>
                <input type="text" class="form-control" id="shipping_state" name="shipping_state" value="<?= htmlspecialchars($user['shipping_state']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="shipping_postal_code" class="form-label">Postal Code</label>
                <input type="text" class="form-control" id="shipping_postal_code" name="shipping_postal_code" value="<?= htmlspecialchars($user['shipping_postal_code']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="shipping_country" class="form-label">Country</label>
                <input type="text" class="form-control" id="shipping_country" name="shipping_country" value="<?= htmlspecialchars($user['shipping_country']); ?>" required>
            </div>

            <button type="submit" class="btn btn-primary" name="update_profile">Save Changes</button>
        </form>
    </div>
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