<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // Check if the email or username already exists
    $stmt = $conn->prepare("SELECT * FROM Users WHERE email = ? OR username = ?");
    $stmt->bind_param("ss", $email, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // User already exists
        echo json_encode(['status' => 'warning', 'message' => 'User already exists!']);
    } else {
        // Insert the new user into the database
        $stmt = $conn->prepare("INSERT INTO Users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $password);
        if ($stmt->execute()) {
            // Get the newly inserted user's ID
            $user_id = $stmt->insert_id;

            // Log the user in by setting the session
            $_SESSION['user_id'] = $user_id;

            // Success, return a success message
            echo json_encode(['status' => 'success', 'message' => 'Sign Up successful!']);
        } else {
            // Sign up failed
            echo json_encode(['status' => 'error', 'message' => 'Sign Up failed!']);
        }
    }
    exit; // Stop script execution
}
