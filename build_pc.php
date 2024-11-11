    <?php
    session_start();
    require 'db.php';

    // Check if the user is logged in
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }

    $user_id = $_SESSION['user_id'];

// Fetch available products for each category
$cpus = $conn->query("SELECT * FROM Products WHERE category = 'CPU'")->fetch_all(MYSQLI_ASSOC);
$motherboards = $conn->query("SELECT * FROM Products WHERE category = 'Motherboard'")->fetch_all(MYSQLI_ASSOC);
$gpus = $conn->query("SELECT * FROM Products WHERE category = 'GPU'")->fetch_all(MYSQLI_ASSOC);
$memory = $conn->query("SELECT * FROM Products WHERE category = 'Memory'")->fetch_all(MYSQLI_ASSOC);
$storage = $conn->query("SELECT * FROM Products WHERE category = 'Storage'")->fetch_all(MYSQLI_ASSOC);
$psus = $conn->query("SELECT * FROM Products WHERE category = 'PSU'")->fetch_all(MYSQLI_ASSOC);

// Initialize performance data as empty
$performance_data = [
    'CPU' => 0,
    'Motherboard' => 0,
    'GPU' => 0,
    'Memory' => 0,
    'PSU' => 0,
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if IDs are valid integers greater than 0
    $cpu_id = (int)$_POST['cpu'];
    $motherboard_id = (int)$_POST['motherboard'];
    $gpu_id = (int)$_POST['gpu'];
    $memory_id = (int)$_POST['memory'];
    $storage_id = (int)$_POST['storage'];
    $psu_id = (int)$_POST['psu'];

    // Validate component IDs
    if ($cpu_id <= 0 || $motherboard_id <= 0 || $gpu_id <= 0 || $memory_id <= 0 || $storage_id <= 0 || $psu_id <= 0) {
        die("Error: One or more component IDs are missing.");
    }

    // Fetch compatibility details for selected components
    $cpu = $conn->query("SELECT * FROM Products WHERE id = $cpu_id")->fetch_assoc();
    $ram = $conn->query("SELECT * FROM Products WHERE id = $memory_id")->fetch_assoc();
    $motherboard = $conn->query("SELECT * FROM Products WHERE id = $motherboard_id")->fetch_assoc();
    $gpu = $conn->query("SELECT * FROM Products WHERE id = $gpu_id")->fetch_assoc();
    $psu = $conn->query("SELECT * FROM Products WHERE id = $psu_id")->fetch_assoc();

    // Initialize bottleneck_message with a default value
    $bottleneck_message = 'All components are compatible!';

    // Bottleneck logic for CPU and GPU
    $performance_diff = abs($cpu['performance_score'] - $gpu['performance_score']);
    if ($performance_diff >= 1000) {
        $bottleneck_message = "<span style='color: red;'>Alert: CPU is fully bottlenecked by the GPU!</span>";
    } elseif ($performance_diff >= 500) {
        $bottleneck_message = "Warning: CPU is slightly bottlenecked by the GPU.";
    }



    // Store the build if there are no compatibility errors
    if (empty($compatibility_errors)) {
        $stmt = $conn->prepare("INSERT INTO PC_Builds (user_id, cpu_id, motherboard_id, gpu_id, memory_id, storage_id, psu_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiiiii", $user_id, $cpu_id, $motherboard_id, $gpu_id, $memory_id, $storage_id, $psu_id);

        if ($stmt->execute()) {
            $success = "PC Build saved successfully! " . $bottleneck_message;
        } else {
            die("Error saving the build: " . $stmt->error);
        }
    }

        // Prepare performance data
        $performance_data = [
            'CPU' => $cpu['performance_score'],
            'Motherboard' => $motherboard['performance_score'],
            'GPU' => $gpu['performance_score'],
            'Memory' => $ram['performance_score'],
            'PSU' => $psu['performance_score'],
        ];
    }
    ?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Build Your Own PC</title>
    <script src="https://cdn.lordicon.com/lordicon.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="styles.css?v=<?php echo filemtime('styles.css'); ?>">
    <script src="script.js?v=<?php echo filemtime('script.js'); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="apple-touch-icon" sizes="180x180" href="images/favicon_io/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon_io/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon_io/favicon-16x16.png">
    <link rel="manifest" href="images/favicon_io/site.webmanifest">

</head>
<style>
    h2,
    label {
        color: cyan;
    }

    body {
        margin-bottom: 20px;

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

<body>

<div class="container mt-5">
    <div class="row">
        <!-- Form Section -->
        <div class="col-md-8">
            <h2>Build Your Own PC</h2>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (!empty($compatibility_errors)): ?>
                <div class="alert alert-warning">
                    <ul>
                        <?php foreach ($compatibility_errors as $comp_error): ?>
                            <li><?php echo $comp_error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" class="mt-4">
                <!-- CPU Selection -->
                <div class="mb-3">
                    <label for="cpu" class="form-label">Select CPU</label>
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button" id="cpuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            -- Choose CPU --
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="cpuDropdown">
                            <?php foreach ($cpus as $cpu): ?>
                                <li>
                                    <a class="dropdown-item" href="#" data-value="<?= $cpu['id'] ?>" onclick="selectComponent('cpu', <?= $cpu['id'] ?>)">
                                        <img src="uploads/<?= $cpu['image_url'] ?>" alt="<?= $cpu['name'] ?>" class="dropdown-item-img" style="width: 40px; margin-right: 10px;">
                                        <?= $cpu['name'] ?> - ₱<?= number_format($cpu['price'], 2) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <input type="hidden" id="cpu" name="cpu" value="0" required>
                </div>

                <!-- Motherboard Selection -->
                <div class="mb-3">
                    <label for="motherboard" class="form-label">Select Motherboard</label>
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button" id="motherboardDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            -- Choose Motherboard --
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="motherboardDropdown">
                            <?php foreach ($motherboards as $motherboard): ?>
                                <li>
                                    <a class="dropdown-item" href="#" data-value="<?= $motherboard['id'] ?>" onclick="selectComponent('motherboard', <?= $motherboard['id'] ?>)">
                                        <img src="uploads/<?= htmlspecialchars($motherboard['image_url']) ?>" alt="<?= htmlspecialchars($motherboard['name']) ?>" class="product-image" style="width: 40px; margin-right: 10px;">
                                        <?= htmlspecialchars($motherboard['name']) ?> - ₱<?= number_format($motherboard['price'], 2) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <input type="hidden" name="motherboard" id="motherboard" required>
                </div>

                <!-- GPU Selection -->
                <div class="mb-3">
                    <label for="gpu" class="form-label">Select GPU</label>
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button" id="gpuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            -- Choose GPU --
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="gpuDropdown">
                            <?php foreach ($gpus as $gpu): ?>
                                <li>
                                    <a class="dropdown-item" href="#" data-value="<?= $gpu['id'] ?>" onclick="selectComponent('gpu', <?= $gpu['id'] ?>)">
                                        <img src="uploads/<?= $gpu['image_url'] ?>" alt="<?= $gpu['name'] ?>" class="dropdown-item-img" style="width: 40px; margin-right: 10px;">
                                        <?= $gpu['name'] ?> - ₱<?= number_format($gpu['price'], 2) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <input type="hidden" id="gpu" name="gpu" required>
                </div>

                <!-- Memory Selection -->
                <div class="mb-3">
                    <label for="memory" class="form-label">Select Memory</label>
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button" id="memoryDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            -- Choose Memory --
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="memoryDropdown">
                            <?php foreach ($memory as $mem): ?>
                                <li>
                                    <a class="dropdown-item" href="#" data-value="<?= $mem['id'] ?>" onclick="selectComponent('memory', <?= $mem['id'] ?>)">
                                        <img src="uploads/<?= $mem['image_url'] ?>" alt="<?= $mem['name'] ?>" class="dropdown-item-img">
                                        <?= $mem['name'] ?> - ₱<?= number_format($mem['price'], 2) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <input type="hidden" id="memory" name="memory" required>
                </div>

                <!-- Storage Selection -->
                <div class="mb-3">
                    <label for="storage" class="form-label">Select Storage</label>
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button" id="storageDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            -- Choose Storage --
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="storageDropdown">
                            <?php foreach ($storage as $stor): ?>
                                <li>
                                    <a class="dropdown-item" href="#" data-value="<?= $stor['id'] ?>" onclick="selectComponent('storage', <?= $stor['id'] ?>)">
                                        <img src="uploads/<?= $stor['image_url'] ?>" alt="<?= $stor['name'] ?>" class="dropdown-item-img">
                                        <?= $stor['name'] ?> - ₱<?= number_format($stor['price'], 2) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <input type="hidden" id="storage" name="storage" required>
                </div>

                <!-- PSU Selection -->
                <div class="mb-3">
                    <label for="psu" class="form-label">Select PSU</label>
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button" id="psuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            -- Choose PSU --
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="psuDropdown">
                            <?php foreach ($psus as $psu): ?>
                                <li>
                                    <a class="dropdown-item" href="#" data-value="<?= $psu['id'] ?>" onclick="selectComponent('psu', <?= $psu['id'] ?>)">
                                        <img src="uploads/<?= $psu['image_url'] ?>" alt="<?= $psu['name'] ?>" class="dropdown-item-img">
                                        <?= $psu['name'] ?> - ₱<?= number_format($psu['price'], 2) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <input type="hidden" id="psu" name="psu" required>
                </div>

                <button type="submit" class="btn btn-primary">Save PC Build</button>
            </form>
        </div>

        <div class="col-md-4 d-flex align-items-center justify-content-center">
            <div id="chartContainer" style="width: 100%; height: 500px; margin-top: 20px;">
                <canvas id="performanceRadarChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="text-center mt-3" id="levelRange">
        <h4 style="color:cyan;">BUILD RANGE</h4>
        <div style="display: inline-block; width: 300px; height: 20px; background: linear-gradient(to right, red, yellow, green);"></div>
        <div id="range-label">
            <span style="color: red; font-weight: bold;">ENTRY LEVEL</span>
            <span style="margin-left: 60px; margin-right:60px; font-weight: bold; color:yellow;">MID RANGE</span>
            <span style=" color: green; font-weight: bold;">HIGH END</span>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Pass PHP performance data to JavaScript
    const componentPerformanceData = <?= json_encode($performance_data); ?>;
    
    var ctx = document.getElementById('performanceRadarChart').getContext('2d');
    var performanceRadarChart = new Chart(ctx, {
        type: 'radar',
        data: {
            labels: [
                'CPU', 
                'Motherboard', 
                'GPU', 
                'RAM', 
                'PSU'
            ],
            datasets: [{
                label: 'Performance',
                data: Object.values(componentPerformanceData),
                backgroundColor: 'rgba(0, 255, 0, 0.3)',
                borderColor: 'rgba(0, 200, 0, 1)',
                pointBackgroundColor: 'rgba(0, 200, 0, 1)',
                borderWidth: 2,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    beginAtZero: true,
                    min: 0,
                    max: 5000,
                    ticks: {
                        stepSize: 500,
                        display: false
                    },
                    grid: {
                        color: "rgba(255, 255, 255, 0.1)"
                    },
                    angleLines: {
                        color: "rgba(255, 255, 255, 0.3)"
                    },
                    pointLabels: {
                        color: '#FFFFFF',
                        font: {
                            size: 12
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
</script>


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

        document.querySelectorAll('.dropdown-item').forEach(item => {
            item.addEventListener('click', function() {
                const value = this.getAttribute('data-value');
                const imgSrc = this.querySelector('img').src; // Get the image source
                const imgAlt = this.querySelector('img').alt; // Get the image alt text
                const dropdownButton = this.closest('.dropdown').querySelector('.dropdown-toggle');

                // Extract only the text from the current selection
                const itemText = this.textContent.trim(); // Get the text only

                // Create an image element
                const imgElement = `<img src="${imgSrc}" alt="${imgAlt}" class="dropdown-selected-img" style="width: 20px; margin-right: 10px;">`;

                // Update the button content with image + text, avoiding adding the image twice
                dropdownButton.innerHTML = imgElement + itemText;

                // Set the hidden input value


                // Optional: Close the dropdown after selection
                const bsDropdown = bootstrap.Dropdown.getOrCreateInstance(dropdownButton); // Bootstrap 5
                bsDropdown.hide();
            });
        });

        function selectComponent(componentType, componentId) {
            // Update the hidden input field with the selected component ID
            document.getElementById(componentType).value = componentId;
        }

        function selectComponent(componentType, componentId) {
            // Update the hidden input field with the selected component ID
            var inputField = document.getElementById(componentType);
            if (inputField) {
                inputField.value = componentId;
            } else {
                console.error(`Element with ID '${componentType}' not found.`);
            }
        }

        // Form submission validation
        document.querySelector('form').addEventListener('submit', function(event) {
            const componentTypes = ['cpu', 'motherboard', 'gpu', 'memory', 'storage', 'psu'];
            let allSelected = true;

            componentTypes.forEach(type => {
                const value = document.getElementById(type).value;
                console.log(`${type} value: ${value}`); // Debugging output to check values
                if (value == 0) {
                    allSelected = false;
                    alert(`Please select a ${type}.`);
                }
            });

            if (!allSelected) {
                event.preventDefault(); // Prevent form submission
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