<?php
// Database connection
include "connection/connection.php";
$conn = con();

// User details
$username = "rohan";
$password = "123";
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// SQL to insert user
$sql = "INSERT INTO users (username, password) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $username, $hashedPassword);

// Execute the statement
if ($stmt->execute()) {
    echo "User 'rohan' successfully added to database!";
} else {
    echo "Error: " . $stmt->error;
}

// Close connection
$stmt->close();
$conn->close();

// Show the hashed password (remove this in production)
echo "<br>For verification, the hashed password is: " . $hashedPassword;
?>