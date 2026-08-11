<?php
// FINDLY - Account Registration Page (Student & Staff)
session_start();

$error = '';
$success = '';

// Handle PHP Registration Form Submission Scaffolding
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $identifier = isset($_POST['identifier']) ? trim($_POST['identifier']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $contact_number = isset($_POST['contact_number']) ? trim($_POST['contact_number']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $account_type = isset($_POST['account_type']) ? trim($_POST['account_type']) : 'student';

    // Disallow Admin registration attempt
    if ($account_type === 'admin') {
        $error = 'Administrator accounts cannot be self-registered.';
    } elseif (empty($full_name) || empty($identifier) || empty($email) || empty($contact_number) || empty($password)) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Registration Logic:
        // Student accounts -> active
        // Staff accounts -> inactive (requires admin activation)
        $status = ($account_type === 'staff') ? 'inactive' : 'active';

        /* 
         * BACKEND MYSQL REGISTRATION INTEGRATION PLACEHOLDER:
         * Replace with your database connection query:
         * 
         * $hashed_password = password_hash($password, PASSWORD_DEFAULT);
         * $stmt = $pdo->prepare("INSERT INTO users (full_name, identifier, email, contact_number, password, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
         * $stmt->execute([$full_name, $identifier, $email, $contact_number, $hashed_password, $account_type, $status]);
         * 
         * if ($status === 'inactive') {
         *     $success = 'Staff registration submitted successfully! Your account is pending Admin activation.';
         * } else {
         *     $success = 'Registration successful! You can now login to your FINDLY account.';
         * }
         */
        
        if ($account_type === 'staff') {
            $success = 'Staff account created successfully! It is pending approval by an Admin.';
        } else {
            $success = 'Account created successfully! You may now login.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register – FINDLY Campus Lost & Found</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

    <div class="auth-wrapper py-4">
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

            <!-- Title Section -->
            <div class="auth-title-section">
                <h2 class="auth-main-title">CREATE YOUR ACCOUNT</h2>
                <p class="auth-main-subtitle">Register to access FINDLY</p>
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

                <form action="register.php" method="POST" id="registerForm" novalidate>
                    
                    <!-- Full Name -->
                    <div class="form-group-custom">
                        <label for="full_name" class="form-label-custom">FULL NAME</label>
                        <input type="text" 
                               name="full_name" 
                               id="full_name" 
                               class="form-control-custom" 
                               placeholder="Enter your full name"
                               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                               required>
                        <div class="invalid-feedback-custom">Full name is required.</div>
                    </div>

                    <!-- Enrollment / Employee Number -->
                    <div class="form-group-custom">
                        <label for="identifier" class="form-label-custom">ENROLLMENT / EMPLOYEE NUMBER</label>
                        <input type="text" 
                               name="identifier" 
                               id="identifier" 
                               class="form-control-custom" 
                               placeholder="Enter enrollment / employee number"
                               value="<?php echo isset($_POST['identifier']) ? htmlspecialchars($_POST['identifier']) : ''; ?>"
                               required>
                        <div class="invalid-feedback-custom">Enrollment or Employee number is required.</div>
                    </div>

                    <!-- Email Address -->
                    <div class="form-group-custom">
                        <label for="email" class="form-label-custom">EMAIL ADDRESS</label>
                        <input type="email" 
                               name="email" 
                               id="email" 
                               class="form-control-custom" 
                               placeholder="Enter email address"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               required>
                        <div class="invalid-feedback-custom">Valid email address is required.</div>
                    </div>

                    <!-- Contact Number -->
                    <div class="form-group-custom">
                        <label for="contact_number" class="form-label-custom">CONTACT NUMBER</label>
                        <input type="tel" 
                               name="contact_number" 
                               id="contact_number" 
                               class="form-control-custom" 
                               placeholder="Enter phone number"
                               value="<?php echo isset($_POST['contact_number']) ? htmlspecialchars($_POST['contact_number']) : ''; ?>"
                               required>
                        <div class="invalid-feedback-custom">Valid contact phone number is required.</div>
                    </div>

                    <!-- Password -->
                    <div class="form-group-custom">
                        <label for="password" class="form-label-custom">PASSWORD</label>
                        <input type="password" 
                               name="password" 
                               id="password" 
                               class="form-control-custom" 
                               placeholder="Enter your password"
                               required>
                        <div class="invalid-feedback-custom">Password must be at least 6 characters.</div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group-custom">
                        <label for="confirm_password" class="form-label-custom">CONFIRM PASSWORD</label>
                        <input type="password" 
                               name="confirm_password" 
                               id="confirm_password" 
                               class="form-control-custom" 
                               placeholder="Re-enter your password"
                               required>
                        <div class="invalid-feedback-custom">Passwords do not match.</div>
                    </div>

                    <!-- Account Type Selection -->
                    <div class="form-group-custom" id="account_type_container">
                        <label class="form-label-custom">ACCOUNT TYPE</label>
                        <div class="account-type-group">
                            <div class="account-type-option">
                                <input type="radio" 
                                       name="account_type" 
                                       id="account_type_student" 
                                       value="student" 
                                       <?php echo (!isset($_POST['account_type']) || $_POST['account_type'] === 'student') ? 'checked' : ''; ?>>
                                <label for="account_type_student" class="account-type-label">
                                    <i class="bi bi-mortarboard-fill me-1"></i> Student
                                </label>
                            </div>
                            <div class="account-type-option">
                                <input type="radio" 
                                       name="account_type" 
                                       id="account_type_staff" 
                                       value="staff" 
                                       <?php echo (isset($_POST['account_type']) && $_POST['account_type'] === 'staff') ? 'checked' : ''; ?>>
                                <label for="account_type_staff" class="account-type-label">
                                    <i class="bi bi-person-badge-fill me-1"></i> Staff
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Primary Create Account Button -->
                    <div class="text-center mt-4 mb-3">
                        <button type="submit" class="btn btn-primary-auth px-4">Create Account</button>
                    </div>

                    <!-- Login Link & Back Link -->
                    <div class="d-flex justify-content-between align-items-center mt-3 fs-7">
                        <div class="auth-bottom-links m-0">
                            Already have account?
                            <a href="login.php" class="btn-secondary-pill">Login</a>
                        </div>
                        <a href="../../index.php" class="back-to-home m-0">&larr; Back to Home</a>
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
