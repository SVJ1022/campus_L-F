<?php
// FINDLY - Shared Login Page (Student, Staff, Admin)
session_start();

$error = '';
$success = '';

// Handle PHP Login Form Submission Scaffolding
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email address and password.';
    } else {
        /* 
         * BACKEND MYSQL AUTHENTICATION INTEGRATION PLACEHOLDER:
         * Replace with your existing database connection & authentication query:
         * 
         * $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
         * $stmt->execute([$email]);
         * $user = $stmt->fetch();
         * 
         * if ($user && password_verify($password, $user['password'])) {
         *     $_SESSION['user_id'] = $user['id'];
         *     $_SESSION['role'] = $user['role']; // 'student', 'staff', 'admin'
         *     
         *     // Redirect based on role
         *     if ($user['role'] === 'admin') {
         *         header('Location: ../dashboard/admin_dashboard.php');
         *     } else if ($user['role'] === 'staff') {
         *         header('Location: ../dashboard/staff_dashboard.php');
         *     } else {
         *         header('Location: ../dashboard/student_dashboard.php');
         *     }
         *     exit();
         * } else {
         *     $error = 'Invalid email or password.';
         * }
         */
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – FINDLY Campus Lost & Found</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

    <div class="auth-wrapper">
        <div class="auth-outer-card">
            
            <!-- Card Header: Branding Left & Right -->
            <div class="auth-header">
                <a href="../../index.php" class="d-flex align-items-center text-decoration-none">
                    <img src="../../assets/images/logo.svg" alt="FINDLY Logo" class="brand-logo-img me-2">
                    <div class="d-flex flex-column">
                        <span class="brand-title">FINDLY</span>
                    </div>
                </a>
                <div class="brand-subtitle fs-5 fw-extrabold text-dark">
                    CAMPUS L&amp;F
                </div>
            </div>

            <!-- Welcome Title Section -->
            <div class="auth-title-section">
                <h2 class="auth-main-title">WELCOME BACK</h2>
                <p class="auth-main-subtitle">Login to Access your FINDLY account.</p>
            </div>

            <!-- Inner Form Card -->
            <div class="auth-inner-card">

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-custom py-2" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-custom py-2" role="alert">
                        <i class="bi bi-check-circle-fill"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST" id="loginForm" novalidate>
                    
                    <!-- Email Field -->
                    <div class="form-group-custom">
                        <label for="email" class="form-label-custom">
                            <i class="bi bi-envelope"></i> Email Address
                        </label>
                        <input type="email" 
                               name="email" 
                               id="email" 
                               class="form-control-custom" 
                               placeholder="Enter your email address"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               required>
                        <div class="invalid-feedback-custom">Email address is required.</div>
                    </div>

                    <!-- Password Field -->
                    <div class="form-group-custom mb-1">
                        <label for="password" class="form-label-custom">
                            <i class="bi bi-lock"></i> Password
                        </label>
                        <input type="password" 
                               name="password" 
                               id="password" 
                               class="form-control-custom" 
                               placeholder="Enter your password"
                               required>
                        <div class="invalid-feedback-custom">Password is required.</div>
                    </div>

                    <!-- Forgot Password Link -->
                    <div class="text-end mb-4">
                        <a href="#" class="forgot-password-link">Forgot Password?</a>
                    </div>

                    <!-- Primary Submit Button -->
                    <div class="text-center mb-3">
                        <button type="submit" class="btn btn-primary-auth w-auto px-4">Login</button>
                    </div>

                    <!-- Register Link -->
                    <div class="auth-bottom-links">
                        Don't have an account? 
                        <a href="register.php" class="btn-secondary-pill">REGISTER</a>
                    </div>

                    <!-- Back to Home -->
                    <div class="text-center mt-2">
                        <a href="../../index.php" class="back-to-home">&larr; Back to Home</a>
                    </div>

                </form>
            </div>

        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Auth Validation JS -->
    <script src="../../assets/js/auth.js"></script>
</body>
</html>
