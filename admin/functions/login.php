<?php

include "../../connection/connection.php";
$conn = con();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Query the database to get the user info
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if user exists
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            echo 'Login successful!';
            // Redirect to dashboard or home page
            date_default_timezone_set('Asia/Manila');
            $date_created2 = date('Y-m-d H:i:s');
            $conn->query("INSERT INTO `logs`(`user_id`, `activity`, `dnt`) VALUES ('" . $_SESSION['user_id'] . "','login in', '$date_created2' )");
            // header("Location: dashboard.php");
            exit();
        } else {
            echo 'Incorrect password!';
        }
    } else {
        echo 'User not found!';
    }
}
?>

<!-- Form (can be the same as your original one) -->
<form method="POST" action="login.php">
    <div class="space-y-6">
        <div>
            <label class="text-sm font-medium block mb-2" for="username">Username</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-user text-gray-400"></i>
                </div>
                <input id="username" name="username" type="text" required
                    class="input-field block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg bg-white bg-opacity-80 text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-0 transition duration-200 text-sm"
                    placeholder="Juan Dela Cruz">
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between mb-2">
                <label class="text-sm font-medium" for="password">Password</label>
                <a href="#" class="text-sm hover: transition">Forgot password?</a>
            </div>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-lock text-gray-400"></i>
                </div>
                <input id="password" name="password" type="password" autocomplete="current-password" required
                    class="input-field block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg bg-white bg-opacity-80 text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-0 transition duration-200 text-sm"
                    placeholder="••••••••">
            </div>
        </div>

        <div>
            <button type="submit"
                class="btn-login w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium  bg-green-600 hover:bg-green-700 focus:outline-none">
                Sign in
            </button>
        </div>
    </div>
</form>