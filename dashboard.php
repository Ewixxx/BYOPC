<?php
session_start();
require 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch user information
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username FROM Users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 50px;
            background-color: #f8f9fa;
        }
        .card {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .navbar {
            margin-bottom: 30px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="#">PC Hardware Store</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="profile.php">Profile</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <h2>Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h2>
            <p class="lead">What would you like to do today?</p>

            <div class="row mt-4">
                <!-- Build PC Card -->
                <div class="col-md-4">
                    <div class="card">
                        <img src="images/build_pc.jpg" class="card-img-top" alt="Build Your PC">
                        <div class="card-body">
                            <h5 class="card-title">Build Your Own PC</h5>
                            <p class="card-text">Start assembling your custom PC with compatible hardware components.</p>
                            <a href="build_pc.php" class="btn btn-primary">Start Building</a>
                        </div>
                    </div>
                </div>
                
                <!-- View Products Card -->
                <div class="col-md-4">
                    <div class="card">
                        <img src="images/products.jpg" class="card-img-top" alt="View Products">
                        <div class="card-body">
                            <h5 class="card-title">View Products</h5>
                            <p class="card-text">Explore our wide range of PC components and accessories.</p>
                            <a href="products.php" class="btn btn-success">Browse Products</a>
                        </div>
                    </div>
                </div>

                <!-- Profile Card -->
                <div class="col-md-4">
                    <div class="card">
                        <img src="images/profile.jpg" class="card-img-top" alt="Profile">
                        <div class="card-body">
                            <h5 class="card-title">Edit Profile</h5>
                            <p class="card-text">Update your account information and shipping address.</p>
                            <a href="profile.php" class="btn btn-warning">Edit Profile</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
