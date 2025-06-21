<?php
include "connection/connection.php";
$conn = con();

if (isset($_POST['submit'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        header("Location: index.php?status=empty");
        exit();
    }

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $storedHashedPassword = $user['password'];

        if (password_verify($password, $storedHashedPassword)) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['level'] = $user['level'];
            header("Location: index.php?status=success");
            date_default_timezone_set('Asia/Manila');
            $date_created2 = date('Y-m-d H:i:s');
            echo $conn->query("INSERT INTO `logs`(`user_id`, `activity`, `dnt`) VALUES ('" . $_SESSION['user_id'] . "','login in', '$date_created2')");
            exit();
        } else {
            header("Location: index.php?status=wrongpass");
            exit();
        }
    } else {
        header("Location: index.php?status=notfound");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>TheCubeFactory - Login</title>
    <link href="src/output.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #10b981 0%, #059669 25%, #047857 50%, #064e3b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Animated background elements */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image:
                radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 70%, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
            pointer-events: none;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            50% {
                transform: translateY(-10px) rotate(1deg);
            }
        }

        /* Floating icons background */
        .bg-icons {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
            overflow: hidden;
        }

        .bg-icon {
            position: absolute;
            color: rgba(255, 255, 255, 0.1);
            animation: floatIcon 8s infinite ease-in-out;
        }

        .bg-icon:nth-child(1) {
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .bg-icon:nth-child(2) {
            top: 20%;
            right: 15%;
            animation-delay: 1s;
        }

        .bg-icon:nth-child(3) {
            top: 50%;
            left: 5%;
            animation-delay: 2s;
        }

        .bg-icon:nth-child(4) {
            bottom: 20%;
            right: 10%;
            animation-delay: 3s;
        }

        .bg-icon:nth-child(5) {
            bottom: 30%;
            left: 20%;
            animation-delay: 4s;
        }

        .bg-icon:nth-child(6) {
            top: 70%;
            right: 25%;
            animation-delay: 5s;
        }

        @keyframes floatIcon {

            0%,
            100% {
                transform: translateY(0px) scale(1);
                opacity: 0.1;
            }

            50% {
                transform: translateY(-20px) scale(1.1);
                opacity: 0.2;
            }
        }

        .container {
            display: flex;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            box-shadow:
                0 25px 50px rgba(0, 0, 0, 0.2),
                0 0 0 1px rgba(255, 255, 255, 0.1);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
            min-height: 600px;
            position: relative;
            animation: slideUp 0.8s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .left-panel {
            flex: 1;
            padding: 3rem 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: linear-gradient(145deg, #ffffff 0%, #f9fafb 100%);
        }

        .right-panel {
            flex: 1;
            background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Right panel decorative elements */
        .right-panel::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background:
                radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.1) 0%, transparent 30%),
                radial-gradient(circle at 70% 70%, rgba(255, 255, 255, 0.05) 0%, transparent 40%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .logo-section {
            margin-bottom: 2rem;
        }

        .logo {
            display: flex;
            align-items: center;
            font-size: 1.25rem;
            font-weight: 700;
            color: #059669;
            margin-bottom: 0.5rem;
        }

        .logo i {
            margin-right: 0.5rem;
            font-size: 1.5rem;
        }

        .welcome-title {
            font-size: 2.25rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
            line-height: 1.2;
        }

        .welcome-subtitle {
            color: #6b7280;
            font-size: 0.95rem;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 0.875rem;
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.9rem;
            color: #1f2937;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #10b981;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .form-input::placeholder {
            color: #9ca3af;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
        }

        .checkbox-wrapper input[type="checkbox"] {
            margin-right: 0.5rem;
            accent-color: #10b981;
        }

        .checkbox-label {
            font-size: 0.875rem;
            color: #6b7280;
        }

        .forgot-link {
            color: #10b981;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .forgot-link:hover {
            color: #059669;
        }

        .submit-button {
            width: 100%;
            padding: 0.875rem;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            border-radius: 8px;
            color: #ffffff;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            margin-bottom: 1.5rem;
        }

        .submit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
        }

        .google-button {
            width: 100%;
            padding: 0.875rem;
            background: #ffffff;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            color: #374151;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .google-button:hover {
            border-color: #d1d5db;
            background: #f9fafb;
        }

        .signup-link {
            text-align: center;
            font-size: 0.875rem;
            color: #6b7280;
        }

        .signup-link a {
            color: #10b981;
            text-decoration: none;
            font-weight: 600;
        }

        .signup-link a:hover {
            color: #059669;
        }

        .password-toggle {
            position: absolute;
            right: 0.875rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            font-size: 0.75rem;
            transition: color 0.3s ease;
            display: flex;
            align-items: center;
        }

        .password-toggle:hover {
            color: #374151;
        }

        .password-toggle input[type="checkbox"] {
            margin-right: 0.25rem;
            accent-color: #10b981;
            transform: scale(0.8);
        }

        /* Illustration styles */
        .illustration {
            position: relative;
            z-index: 2;
            max-width: 80%;
        }

        .person-illustration {
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.2);
            position: relative;
            animation: pulse 4s ease-in-out infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        .person-icon {
            font-size: 8rem;
            color: rgba(255, 255, 255, 0.9);
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* Decorative elements */
        .decorative-elements {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
        }

        .deco-element {
            position: absolute;
            color: rgba(255, 255, 255, 0.2);
            animation: float 6s ease-in-out infinite;
        }

        .deco-element:nth-child(1) {
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .deco-element:nth-child(2) {
            top: 20%;
            right: 15%;
            animation-delay: 1s;
        }

        .deco-element:nth-child(3) {
            bottom: 20%;
            left: 15%;
            animation-delay: 2s;
        }

        .deco-element:nth-child(4) {
            bottom: 30%;
            right: 20%;
            animation-delay: 3s;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                max-width: 400px;
                min-height: auto;
            }

            .right-panel {
                order: -1;
                min-height: 200px;
            }

            .left-panel {
                padding: 2rem 1.5rem;
            }

            .welcome-title {
                font-size: 1.875rem;
            }

            .person-illustration {
                width: 150px;
                height: 150px;
            }

            .person-icon {
                font-size: 4rem;
            }
        }

        /* Loading state */
        .loading {
            opacity: 0.7;
            cursor: not-allowed;
            pointer-events: none;
        }
    </style>
</head>

<body>
    <!-- Background floating icons -->
    <div class="bg-icons">
        <i class="fas fa-cube bg-icon" style="font-size: 2rem;"></i>
        <i class="fas fa-laptop-code bg-icon" style="font-size: 1.5rem;"></i>
        <i class="fas fa-cogs bg-icon" style="font-size: 1.8rem;"></i>
        <i class="fas fa-chart-line bg-icon" style="font-size: 1.6rem;"></i>
        <i class="fas fa-shield-alt bg-icon" style="font-size: 1.4rem;"></i>
        <i class="fas fa-rocket bg-icon" style="font-size: 1.7rem;"></i>
    </div>

    <div class="container">
        <!-- Left Panel - Login Form -->
        <div class="left-panel">
            <!-- Logo -->
            <div class="logo-section">
                <div class="logo">
                    <img src="images/logo.png" width="60px" height="60px" alt="">
                    Care Pro
                </div>
            </div>

            <!-- Welcome Message -->
            <div>
                <h1 class="welcome-title">Welcome back</h1>
                <p class="welcome-subtitle">Please enter your details</p>
            </div>

            <!-- Login Form -->
            <form method="POST" action="index.php" id="loginForm">
                <!-- Username Field -->
                <div class="form-group">
                    <label for="username" class="form-label">Email address</label>
                    <div class="input-wrapper">
                        <input id="username" name="username" type="text" required placeholder="Enter your email"
                            class="form-input" autocomplete="username" />
                    </div>
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-wrapper">
                        <input id="password" name="password" type="password" required placeholder="Enter your password"
                            class="form-input" autocomplete="current-password" />
                        <div class="password-toggle">
                            <input type="checkbox" id="togglePassword">
                            <label for="togglePassword">Show</label>
                        </div>
                    </div>
                </div>



                <!-- Submit Button -->
                <button type="submit" name="submit" class="submit-button" id="submitBtn">
                    Sign in
                </button>



            </form>
        </div>

        <!-- Right Panel - Illustration -->
        <div class="right-panel">
            <!-- Decorative Elements -->
            <div class="decorative-elements">
                <i class="fas fa-comments deco-element" style="font-size: 2rem;"></i>
                <i class="fas fa-envelope deco-element" style="font-size: 1.5rem;"></i>
                <i class="fas fa-bell deco-element" style="font-size: 1.8rem;"></i>
                <i class="fas fa-heart deco-element" style="font-size: 1.4rem;"></i>
            </div>

            <!-- Main Illustration -->
            <div class="illustration">
                <div class="person-illustration">
                    <i class="fas fa-user person-icon"></i>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('loginForm');
            const submitBtn = document.getElementById('submitBtn');
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');

            // Status handling
            if (status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Login Successful!',
                    text: 'Redirecting to dashboard...',
                    showConfirmButton: false,
                    timer: 2000,
                    background: 'rgba(255, 255, 255, 0.95)',
                    backdrop: 'rgba(16, 185, 129, 0.1)',
                    timerProgressBar: true,
                    customClass: {
                        popup: 'rounded-xl'
                    }
                }).then(() => {
                    window.location.href = 'admin/index.php';
                });
            } else if (status === 'wrongpass') {
                Swal.fire({
                    icon: 'error',
                    title: 'Authentication Failed',
                    text: 'Invalid password. Please try again.',
                    background: 'rgba(255, 255, 255, 0.95)',
                    backdrop: 'rgba(239, 68, 68, 0.1)',
                    confirmButtonColor: '#10b981',
                    customClass: {
                        popup: 'rounded-xl'
                    }
                });
            } else if (status === 'notfound') {
                Swal.fire({
                    icon: 'error',
                    title: 'User Not Found',
                    text: 'Please check your username and try again.',
                    background: 'rgba(255, 255, 255, 0.95)',
                    backdrop: 'rgba(239, 68, 68, 0.1)',
                    confirmButtonColor: '#10b981',
                    customClass: {
                        popup: 'rounded-xl'
                    }
                });
            } else if (status === 'empty') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Information',
                    text: 'Please fill in all required fields.',
                    background: 'rgba(255, 255, 255, 0.95)',
                    backdrop: 'rgba(245, 158, 11, 0.1)',
                    confirmButtonColor: '#10b981',
                    customClass: {
                        popup: 'rounded-xl'
                    }
                });
            }

            // Password toggle functionality
            document.getElementById('togglePassword').addEventListener('change', function () {
                const passwordField = document.getElementById('password');
                if (this.checked) {
                    passwordField.type = 'text';
                } else {
                    passwordField.type = 'password';
                }
            });

            // Form submission with loading state
            form.addEventListener('submit', function (e) {
                const username = document.getElementById('username').value.trim();
                const password = document.getElementById('password').value;

                if (!username || !password) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Missing Information',
                        text: 'Please fill in all required fields.',
                        confirmButtonColor: '#10b981',
                        customClass: {
                            popup: 'rounded-xl'
                        }
                    });
                    return;
                }

                // Add loading state
                submitBtn.classList.add('loading');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 0.5rem;"></i>Signing In...';
            });

            // Enhanced input interactions
            const inputs = document.querySelectorAll('.form-input');
            inputs.forEach(input => {
                input.addEventListener('focus', function () {
                    this.parentElement.style.transform = 'scale(1.01)';
                });

                input.addEventListener('blur', function () {
                    this.parentElement.style.transform = 'scale(1)';
                });
            });
        });
    </script>
</body>

</html>