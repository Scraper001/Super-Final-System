<?php
session_start();

$action = isset($_GET['action']) ? $_GET['action'] : 'logout';

// Clear the current session regardless of action
session_unset();
session_destroy();

// Redirect based on action
if ($action === 'switch') {
    // Redirect to login page with switch user parameter
    header('Location: ../index.php?switch=true');
} else {
    // Regular logout - redirect to homepage or login page
    header('Location: ../index.php');
}
exit;
?>