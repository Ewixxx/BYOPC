<?php
session_start();
require 'db.php';


$category = isset($_GET['category']) ? $_GET['category'] : '';


$query = "SELECT * FROM Products";
if ($category) {
    $query .= " WHERE category = '" . $conn->real_escape_string($category) . "'";
}
$products = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <script src="https://cdn.lordicon.com/lordicon.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="styles.css?v=<?php echo filemtime('styles.css'); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="images/favicon_io/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon_io/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon_io/favicon-16x16.png">
    <link rel="manifest" href="images/favicon_io/site.webmanifest">
    <script src="script.js?v=<?php echo filemtime('script.js'); ?>"></script>
</head>
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
                <script src="https://cdn.lordicon.com/lordicon.js"></script>
                <lord-icon
                    src="https://cdn.lordicon.com/ggirntso.json"
                    trigger="hover"
                    colors="primary:cyan,secondary:#08a88a"
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

                        $cart_count = 0;
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
    <marquee behavior="" direction="">Welcome to RigMasters PC Hardware Shop. Opens Daily from 9:00AM to 6:00PM. Located at 181. E.Dela Paz Street. Sta. Elena Marikina City. Near Marikina Garlic Buns. Ships Nationwide via LBC and J&T Express. Same day delivery for selected areas in Metro Manila, Cavite and Bulacan. Accepts Gcash and Cash on Delivery Payment methods.</marquee>
    <div class="carousel-container">
        <div class="carousel-images" id="carousel-images">
            <img src="./HolidayImages/HolidayLogo.jpg" alt="Banner 1" class="carousel-image">
            <img src="./HolidayImages/banner2.jpg" alt="Banner 2" class="carousel-image">
            <img src="./HolidayImages/banner3.jpg" alt="Banner 3" class="carousel-image">
            <img src="./HolidayImages/banner4.jpg" alt="Banner 4" class="carousel-image">
            <img src="./HolidayImages/banner5.jpg" alt="Banner 5" class="carousel-image">
        </div>






        <div class="carousel-controls">
            <button class="prev" id="prevBtn">&#10094;</button>
            <button class="next" id="nextBtn">&#10095;</button>
        </div>
    </div>

    <div id="categories">

        <ul>
            <a href="">
                <li>Desktop <img src="./images/desktop.png" alt="desktop"></li>
            </a>
            <a href="">
                <li>Processors <img src="./images/cpu.png" alt="desktop"></li>
            </a>
            <a href="">
                <li>Peripherals <img src="./images/peripherals.png" alt="desktop"></li>
            </a>
            <a href="">
                <li>Case <img src="./images/Case.png" alt="desktop"></li>
            </a>
            <a href="">
                <li>Power Supply <img src="./images/white-psu.png" alt="desktop"></li>
            </a>
            <a href="">
                <li>Motherboard <img src="./images/white-mobo.png" alt="desktop"></li>
            </a>
        </ul>



    </div>






    <div class="home-wrapper">
        <div class="description">
            <h2 class="create">CREATE YOUR <br>
                DREAM PC</h2>
            <h2 class="build">BUILD</h2>
            <p>Your gateway to an immersive experience designed specifically for gamers and PC enthusiasts. Empowers you to craft the ultimate gaming machine tailored to your unique preferences and performance needs.This new world, you can carefully choose every component from cutting-edge GPUs to high-performance CPUs—to make sure your setup is optimized for stunning graphics and blazingly fast speeds. Our platform calculates every component's compatibility.</p>
            <button class="buildbtn"><a href="build_pc.php">Build Now</a></button>
        </div>
        <div class="BYOPC-banner">
            <img src="images/white-mobo.png" alt="Banner">
        </div>


    </div>


    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="loginModalLabel">Login</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <div id="loginAlert"></div>

                    <form id="loginForm" method="POST">
                        <div class="mb-3">
                            <label for="login-username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="login-username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="login-password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="login-password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>



    <div class="modal fade" id="signupModal" tabindex="-1" aria-labelledby="signupModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="signupModalLabel">Sign Up</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <div id="signupAlert"></div>

                    <form id="signupForm">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Sign Up</button>
                    </form>
                </div>
            </div>
        </div>
    </div>



    <span class="theme-icon"><img src="images/Dark-mode.png" alt="theme-icon"></span>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(event) {
            event.preventDefault();

            const formData = new FormData(this);

            fetch('login.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    const alertContainer = document.getElementById('loginAlert');
                    alertContainer.innerHTML = '';

                    if (data.status === 'success') {
                        alertContainer.innerHTML = `<div class="alert alert-success" role="alert">${data.message}</div>`;


                        setTimeout(() => {
                            window.location.href = 'home.php';
                        }, 2000);
                    } else if (data.status === 'error') {
                        alertContainer.innerHTML = `<div class="alert alert-danger" role="alert">${data.message}</div>`;
                    }
                })
                .catch(error => console.error('Error:', error));
        });





        document.getElementById('signupForm').addEventListener('submit', function(event) {
            event.preventDefault();

            const formData = new FormData(this);

            fetch('signup.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    const alertContainer = document.getElementById('signupAlert');
                    alertContainer.innerHTML = '';

                    if (data.status === 'success') {
                        alertContainer.innerHTML = `<div class="alert alert-success" role="alert">${data.message}</div>`;


                        setTimeout(() => {
                            window.location.href = 'home.php';
                        }, 2000);
                    } else if (data.status === 'warning') {
                        alertContainer.innerHTML = `<div class="alert alert-warning" role="alert">${data.message}</div>`;
                    } else if (data.status === 'error') {
                        alertContainer.innerHTML = `<div class="alert alert-danger" role="alert">${data.message}</div>`;
                    }
                })
                .catch(error => console.error('Error:', error));
        });






        let lastScrollTop = 0;

        window.addEventListener('scroll', function() {
            const homeWrapper = document.querySelector('.home-wrapper');
            const description = document.querySelector('.home-wrapper .description');
            const banner = document.querySelector('.BYOPC-banner');
            const windowHeight = window.innerHeight;
            const homeWrapperTop = homeWrapper.getBoundingClientRect().top;

            let scrollTop = window.pageYOffset || document.documentElement.scrollTop;


            if (homeWrapperTop < windowHeight - 100) {
                homeWrapper.style.opacity = '1';
                description.style.transform = 'translateX(0)';
                banner.style.transform = 'translateX(0)';
            }


            if (scrollTop < lastScrollTop && homeWrapperTop >= windowHeight - 100) {
                homeWrapper.style.opacity = '0';
                description.style.transform = 'translateX(-100%)';
                banner.style.transform = 'translateX(100%)';
            }

            lastScrollTop = scrollTop;
        });

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


        document.addEventListener('click', function(event) {
            const cartDropdown = document.getElementById('cart-dropdown');
            const cartIcon = document.getElementById('cart-icon');

            if (!cartIcon.contains(event.target) && !cartDropdown.contains(event.target)) {
                cartDropdown.style.display = 'none';
            }
        });
        const carouselImages = document.getElementById('carousel-images');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        let currentIndex = 0;
        const totalImages = 5;
        let autoScrollInterval;


        function updateCarousel() {
            const imageWidth = carouselImages.clientWidth;
            carouselImages.style.transform = `translateX(-${currentIndex * imageWidth}px)`;
        }


        nextBtn.addEventListener('click', () => {
            currentIndex = (currentIndex + 1) % totalImages;
            updateCarousel();
            resetAutoScroll();
        });


        prevBtn.addEventListener('click', () => {
            currentIndex = (currentIndex - 1 + totalImages) % totalImages;
            updateCarousel();
            resetAutoScroll();
        });


        function startAutoScroll() {
            autoScrollInterval = setInterval(() => {
                currentIndex = (currentIndex + 1) % totalImages;
                updateCarousel();
            }, 3000);
        }

        function resetAutoScroll() {
            clearInterval(autoScrollInterval);
            startAutoScroll();
        }


        window.addEventListener('load', () => {
            updateCarousel();
            startAutoScroll();
        });

        window.addEventListener('resize', updateCarousel);


        function toggleTheme() {
            document.body.classList.toggle('light-theme');


            const isLightTheme = document.body.classList.contains('light-theme');
            localStorage.setItem('theme', isLightTheme ? 'light' : 'dark');
        }


        window.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme');


            if (savedTheme === 'light') {
                document.body.classList.add('light-theme');
            }
        });


        document.querySelector('.theme-icon').addEventListener('click', function() {
            this.classList.toggle('rotated');
            toggleTheme();
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