<?php
// FINDLY - Landing Page
session_start();

// Redirect authenticated users to dashboard if session exists
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: modules/dashboard/admin_dashboard.php');
            exit();
        case 'staff':
            header('Location: modules/dashboard/staff_dashboard.php');
            exit();
        case 'student':
            header('Location: modules/dashboard/student_dashboard.php');
            exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FINDLY – Campus Lost & Found</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- Header Section -->
    <header class="findly-header d-flex justify-content-between align-items-center">
        <!-- Logo & Branding -->
        <a href="index.php" class="d-flex align-items-center text-decoration-none">
            <img src="assets/images/logo.svg" alt="FINDLY Logo" class="brand-logo-img me-2">
            <div class="d-flex flex-column">
                <span class="brand-title">FINDLY</span>
                <span class="brand-subtitle">CAMPUS L&amp;F</span>
            </div>
        </a>

        <!-- Top Right Login | Register Button Pill -->
        <div>
            <div class="btn-pill-action">
                <a href="modules/auth/login.php" class="text-decoration-none text-dark fw-bold">Login</a>
                <span class="mx-1 text-muted">|</span>
                <a href="modules/auth/register.php" class="text-decoration-none text-dark fw-bold">Register</a>
            </div>
        </div>
    </header>

    <!-- Main Hero Section -->
    <main class="hero-container">
        <h1 class="hero-title">CAMPUS LOST AND FOUND</h1>
        <p class="hero-subtitle">
            A simple campus platform to report lost items and find items recovered on campus.
        </p>

        <!-- Centered Stacked Action Buttons -->
        <div class="hero-buttons">
            <a href="modules/auth/login.php" class="btn btn-landing-action">
                <i class="bi bi-exclamation-circle fs-5"></i>
                <span>REPORT LOST ITEMS</span>
            </a>

            <a href="modules/auth/login.php" class="btn btn-landing-action">
                <i class="bi bi-box-seam fs-5"></i>
                <span>BROWSE FOUND ITEMS</span>
            </a>
        </div>
    </main>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
