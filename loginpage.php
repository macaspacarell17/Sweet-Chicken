<?php
session_start();
require 'config/database.php';
require_once 'audit_log.php';

// Include PHPMailer files if needed for OTP handling
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (file_exists('PHPMailer/src/Exception.php')) {
    require 'PHPMailer/src/Exception.php';
    require 'PHPMailer/src/PHPMailer.php';
    require 'PHPMailer/src/SMTP.php';
}

/**
 * Sends a 6-digit OTP email. Returns true on success, false on failure.
 * Failures are logged via error_log() instead of being swallowed silently.
 */
function sendOtpEmail($email, $otp, $subject = 'Your verification code') {
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        error_log('PHPMailer classes not loaded - check PHPMailer/src files exist.');
        return false;
    }

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = "smtp.gmail.com";
        $mail->SMTPAuth = true;
        $mail->Username = "sweetchickenpos@gmail.com";
        $mail->Password = "nojgrdgymsdghkyp";
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Local/XAMPP dev environments often fail TLS certificate verification
        // against Gmail's SMTP server because the bundled CA cert store is
        // outdated. This relaxes verification so mail actually sends locally.
        // Remove this block once deployed on a server with a proper CA bundle.
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];

        $mail->setFrom("sweetchickenpos@gmail.com", "Sweet Chicken POS");
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = "<h2>Your verification code:</h2><h1 style='letter-spacing:4px;'>{$otp}</h1><p>This code expires in 5 minutes.</p>";
        $mail->send();
        return true;
    } catch (Exception $e) {
        // Check your PHP error log for this line to see the real SMTP failure reason.
        error_log('PHPMailer send failed: ' . $e->getMessage());
        return false;
    }
}

// --- BACKEND LOGIC HANDLERS ---

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    // 1. LOGIN HANDLER
    if ($action === 'login') {

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $_SESSION['flash_error'] = 'Please fill in all fields.';
            header("Location: ?view=login");
            exit();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'Please enter a valid email address.';
            header("Location: ?view=login");
            exit();
        }

        // Get the user's information including role
        $stmt = $conn->prepare("
            SELECT id, fullname, email, password, role, status
            FROM users
            WHERE email = ?
        ");

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {

                // Deactivated accounts (disabled by the Administrator) cannot log in.
                if (($user['status'] ?? 'Active') !== 'Active') {
                    createAuditLog($conn, 'Login Blocked (Deactivated)', $user['email'], 'Role: ' . $user['role'], (int) $user['id']);
                    $_SESSION['flash_error'] = 'This account has been deactivated. Please contact the administrator.';
                    header("Location: ?view=login");
                    exit();
                }

                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                createAuditLog($conn, 'User Login', $user['email'], 'Role: ' . $user['role'], (int) $user['id']);

                switch ($user['role']) {
                    case 'admin':
                        header("Location: admin_dashboard.php");
                        break;

                    case 'cashier':
                        header("Location: cashier_dashboard.php");
                        break;

                    case 'kitchen':
                        header("Location: kitchen_dashboard.php");
                        break;

                    default:
                        header("Location: customer_dashboard.php");
                }

                exit();
            }
        }

        createAuditLog($conn, 'Failed Login', $email, 'Invalid email or password');

        // Same message whether the email isn't registered or the password is
        // wrong, so the login form doesn't reveal which emails have accounts.
        $_SESSION['flash_error'] = 'Invalid email or password.';
        header("Location: ?view=login");
        exit();
    }

    // 2. REGISTER HANDLER - now sends an OTP instead of creating the account immediately
    if ($action === 'register') {
        $fullname = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($fullname) || empty($email) || empty($password)) {
            $_SESSION['flash_error'] = 'Please fill in all fields.';
            header("Location: ?view=register");
            exit();
        }

        if (strlen($fullname) < 2 || strlen($fullname) > 100) {
            $_SESSION['flash_error'] = 'Please enter your full name.';
            header("Location: ?view=register");
            exit();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'Please enter a valid email address.';
            header("Location: ?view=register");
            exit();
        }

        if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d).{9,}$/', $password)) {
            $_SESSION['flash_error'] = 'Password must be at least 9 characters and include a letter and a number.';
            header("Location: ?view=register");
            exit();
        }

        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $_SESSION['flash_error'] = 'An account with that email already exists.';
            header("Location: ?view=register");
            exit();
        }
        $check->close();

        // Don't create the account yet - stash the pending signup in the
        // session and only insert into `users` once the OTP is confirmed.
        $otp = rand(100000, 999999);
        $expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));

        $_SESSION['pending_registration'] = [
            'fullname' => $fullname,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'otp' => (string) $otp,
            'otp_expiry' => $expiry,
        ];

        $mailSent = sendOtpEmail($email, $otp, 'Verify your Sweet Chicken account');

        if (!$mailSent) {
            unset($_SESSION['pending_registration']);
            $_SESSION['flash_error'] = 'We could not send a verification email right now. Please try again in a moment.';
            header("Location: ?view=register");
            exit();
        }

        $_SESSION['flash_success'] = 'We sent a 6-digit code to your email. Enter it below to finish creating your account.';
        header("Location: ?view=verify_register");
        exit();
    }

    // 3. VERIFY REGISTRATION EMAIL HANDLER - creates the account once the OTP checks out
    if ($action === 'verify_register') {
        if (!isset($_SESSION['pending_registration'])) {
            header("Location: ?view=register");
            exit();
        }

        $pending = $_SESSION['pending_registration'];
        $otp = trim($_POST['otp'] ?? '');

        if (!preg_match('/^\d{6}$/', $otp)) {
            $_SESSION['flash_error'] = 'Enter the 6-digit code we sent you.';
            header("Location: ?view=verify_register");
            exit();
        }

        if ($otp !== $pending['otp'] || strtotime($pending['otp_expiry']) < time()) {
            $_SESSION['flash_error'] = 'That code is invalid or has expired.';
            header("Location: ?view=verify_register");
            exit();
        }

        // Re-check the email hasn't been taken in the meantime.
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $pending['email']);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            unset($_SESSION['pending_registration']);
            $_SESSION['flash_error'] = 'An account with that email already exists.';
            header("Location: ?view=login");
            exit();
        }
        $check->close();

        $stmt = $conn->prepare("
            INSERT INTO users (fullname, email, password, role)
            VALUES (?, ?, ?, 'customer')
        ");
        $stmt->bind_param("sss", $pending['fullname'], $pending['email'], $pending['password_hash']);

        if ($stmt->execute()) {
            createAuditLog($conn, 'Customer Registered', $pending['email'], 'Email verified by OTP', (int) $conn->insert_id);
            unset($_SESSION['pending_registration']);
            $_SESSION['flash_success'] = 'Email verified! Your account has been created - you can now log in.';
            header("Location: ?view=login");
        } else {
            $_SESSION['flash_error'] = 'Something went wrong creating your account. Please try again.';
            header("Location: ?view=register");
        }
        $stmt->close();
        exit();
    }

    // 3b. RESEND REGISTRATION OTP
    if ($action === 'resend_register_otp') {
        if (!isset($_SESSION['pending_registration'])) {
            header("Location: ?view=register");
            exit();
        }

        $pending = $_SESSION['pending_registration'];
        $otp = rand(100000, 999999);
        $pending['otp'] = (string) $otp;
        $pending['otp_expiry'] = date("Y-m-d H:i:s", strtotime("+5 minutes"));
        $_SESSION['pending_registration'] = $pending;

        $mailSent = sendOtpEmail($pending['email'], $otp, 'Verify your Sweet Chicken account');

        if ($mailSent) {
            $_SESSION['flash_success'] = 'We sent a new code to your email.';
        } else {
            $_SESSION['flash_error'] = 'We could not send a verification email right now. Please try again in a moment.';
        }
        header("Location: ?view=verify_register");
        exit();
    }

    // 4. SEND OTP HANDLER (forgot password)
    if ($action === 'send_otp') {
        $email = trim($_POST["email"] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'Please enter a valid email address.';
            header("Location: ?view=forgot");
            exit();
        }

        $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows == 0) {
            $_SESSION['flash_error'] = 'No account found with that email.';
            header("Location: ?view=forgot");
            exit();
        }

        $otp = rand(100000, 999999);
        $expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));

        $update = $conn->prepare("UPDATE users SET otp=?, otp_expiry=? WHERE email=?");
        $update->bind_param("sss", $otp, $expiry, $email);
        $update->execute();

        $mailSent = sendOtpEmail($email, $otp, 'Password Reset OTP');

        $_SESSION["reset_email"] = $email;

        if ($mailSent) {
            $_SESSION['flash_success'] = 'A verification code has been sent to your email.';
        } else {
            $_SESSION['flash_error'] = 'We could not send the email right now. Please try again in a moment.';
        }
        header("Location: ?view=verify_otp");
        exit();
    }

    // 5. VERIFY OTP HANDLER (forgot password)
    if ($action === 'verify_otp') {
        if (!isset($_SESSION['reset_email'])) {
            header("Location: ?view=login");
            exit();
        }

        $email = $_SESSION['reset_email'];
        $otp = trim($_POST['otp'] ?? '');

        if (!preg_match('/^\d{6}$/', $otp)) {
            $_SESSION['flash_error'] = 'Enter the 6-digit code we sent you.';
            header("Location: ?view=verify_otp");
            exit();
        }

        $stmt = $conn->prepare("SELECT otp, otp_expiry FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if ($user['otp'] === $otp && strtotime($user['otp_expiry']) > time()) {
                $_SESSION['otp_verified'] = true;
                header("Location: ?view=reset_password");
                exit();
            } else {
                $_SESSION['flash_error'] = 'That code is invalid or has expired.';
                header("Location: ?view=verify_otp");
                exit();
            }
        } else {
            $_SESSION['flash_error'] = 'Something went wrong. Please try again.';
            header("Location: ?view=login");
            exit();
        }
    }

    // 6. RESET PASSWORD HANDLER
    if ($action === 'reset_password') {
        if (!isset($_SESSION['reset_email']) || !isset($_SESSION['otp_verified'])) {
            header("Location: ?view=login");
            exit();
        }

        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d).{9,}$/', $password)) {
            $_SESSION['flash_error'] = 'Password must be at least 9 characters and include a letter and a number.';
            header("Location: ?view=reset_password");
            exit();
        }

        if ($password !== $confirm) {
            $_SESSION['flash_error'] = 'Passwords do not match.';
            header("Location: ?view=reset_password");
            exit();
        }

        $email = $_SESSION['reset_email'];
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password=?, otp=NULL, otp_expiry=NULL WHERE email=?");
        $stmt->bind_param("ss", $hash, $email);

        if ($stmt->execute()) {
            createAuditLog($conn, 'Password Reset', $email, 'Reset through email OTP');
            session_unset();
            session_destroy();
            // Start a fresh session so we can pass a one-time success message to the login page.
            session_start();
            $_SESSION['flash_success'] = 'Password updated! You can now log in.';
            header("Location: ?view=login");
        } else {
            $_SESSION['flash_error'] = 'Something went wrong. Please try again.';
            header("Location: ?view=reset_password");
        }
        exit();
    }
}

// Determine active view from URL parameter, default to 'login'
$view = $_GET['view'] ?? 'login';

// Don't allow landing on the OTP-verify screen with nothing pending to verify.
if ($view === 'verify_register' && !isset($_SESSION['pending_registration'])) {
    header("Location: ?view=register");
    exit();
}

// Pull any one-time flash message set by the handlers above, then clear it
// so it doesn't reappear on the next page load.
$flashError = $_SESSION['flash_error'] ?? null;
$flashSuccess = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_error'], $_SESSION['flash_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign In - Sweet Chicken POS</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
<link rel="stylesheet" href="css/loginpage.css">
</head>
<body>

<div class="auth-page">
    <!-- Left visual panel -->
    <div class="auth-visual">
        <a href="index.php" class="auth-logo">
            <img src="images/logo.png" alt="Sweet Chicken logo">
            <span>Sweet Chicken</span>
        </a>

        <div class="auth-visual-copy">
            <h1>Good food, sweet deal.</h1>
            <p>Sign in to order Dine-In, Take-Out, or Online, track it live from the kitchen, and pay with cash or GCash.</p>
        </div>

        <div class="auth-visual-footer">&copy; 2026 Sweet Chicken POS. All rights reserved.</div>
    </div>

    <!-- Right form panel -->
    <div class="auth-panel">
        <div class="auth-card">

            <?php if ($flashError): ?>
                <div class="form-alert form-alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?php echo htmlspecialchars($flashError); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($flashSuccess): ?>
                <div class="form-alert form-alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <span><?php echo htmlspecialchars($flashSuccess); ?></span>
                </div>
            <?php endif; ?>

            <!-- LOGIN -->
            <div id="loginBox" class="box <?php echo ($view === 'login') ? 'active' : ''; ?>">
                <span class="auth-eyebrow">Welcome back</span>
                <h2>Log in to your account</h2>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="login">
                    <div class="field">
                        <label class="field-label" for="loginEmail">Email</label>
                        <input id="loginEmail" type="email" name="email" placeholder="username@gmail.com" required>
                    </div>
                    <div class="field">
                        <label class="field-label" for="loginPassword">Password</label>
                        <div class="field-control">
                            <input id="loginPassword" type="password" name="password" placeholder="Password" required>
                            <button type="button" class="pw-toggle" aria-label="Show password"><i class="fa-regular fa-eye"></i></button>
                        </div>
                    </div>
                    <div class="field-meta">
                        <a href="?view=forgot">Forgot password?</a>
                    </div>
                    <button type="submit" class="auth-submit">Sign in</button>
                </form>
                <p class="auth-switch">Don't have an account? <a href="?view=register">Register</a></p>
            </div>

            <!-- REGISTER -->
            <div id="registerBox" class="box <?php echo ($view === 'register') ? 'active' : ''; ?>">
                <span class="auth-eyebrow">New here</span>
                <h2>Create your account</h2>
                <p class="auth-sub">Sign up to get started with your orders.</p>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="register">
                    <div class="field">
                        <label class="field-label" for="regFullname">Full Name</label>
                        <input id="regFullname" type="text" name="fullname" placeholder="John Doe" required>
                    </div>
                    <div class="field">
                        <label class="field-label" for="regEmail">Email</label>
                        <input id="regEmail" type="email" name="email" placeholder="username@gmail.com" required>
                    </div>
                    <div class="field">
                        <label class="field-label" for="regPassword">Password (min 9 chars, with a letter and a number)</label>
                        <div class="field-control">
                            <input id="regPassword" type="password" name="password" placeholder="Password" required>
                            <button type="button" class="pw-toggle" aria-label="Show password"><i class="fa-regular fa-eye"></i></button>
                        </div>
                    </div>
                    <button type="submit" class="auth-submit">Sign Up</button>
                </form>
                <p class="auth-switch">Already have an account? <a href="?view=login">Login</a></p>
            </div>

            <!-- VERIFY REGISTRATION EMAIL -->
            <div id="verifyRegisterBox" class="box <?php echo ($view === 'verify_register') ? 'active' : ''; ?>">
                <span class="auth-eyebrow">Almost there</span>
                <h2>Verify your email</h2>
                <p class="auth-sub">Enter the 6-digit code we sent to your email to finish creating your account.</p>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="verify_register">
                    <div class="field">
                        <label class="field-label" for="regOtpCode">OTP Code</label>
                        <input id="regOtpCode" class="otp-input" type="text" name="otp" maxlength="6" placeholder="123456" required>
                    </div>
                    <button type="submit" class="auth-submit">Verify &amp; Create Account</button>
                </form>
                <form action="" method="POST" class="resend-form">
                    <input type="hidden" name="action" value="resend_register_otp">
                    <p class="auth-switch">Didn't receive it? <button type="submit" class="link-btn">Resend code</button></p>
                </form>
            </div>

            <!-- FORGOT PASSWORD -->
            <div id="forgotBox" class="box <?php echo ($view === 'forgot') ? 'active' : ''; ?>">
                <span class="auth-eyebrow">Reset access</span>
                <h2>Forgot password</h2>
                <p class="auth-sub">Enter your registered email address to receive a verification OTP code.</p>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="send_otp">
                    <div class="field">
                        <label class="field-label" for="forgotEmail">Email</label>
                        <input id="forgotEmail" type="email" name="email" placeholder="username@gmail.com" required>
                    </div>
                    <button type="submit" class="auth-submit">Send OTP</button>
                </form>
                <p class="auth-switch"><a href="?view=login">Back to Login</a></p>
            </div>

            <!-- VERIFY OTP (password reset) -->
            <div id="verifyBox" class="box <?php echo ($view === 'verify_otp') ? 'active' : ''; ?>">
                <span class="auth-eyebrow">Check your inbox</span>
                <h2>Verify OTP</h2>
                <p class="auth-sub">Enter the 6-digit code sent to your email.</p>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="verify_otp">
                    <div class="field">
                        <label class="field-label" for="otpCode">OTP Code</label>
                        <input id="otpCode" class="otp-input" type="text" name="otp" maxlength="6" placeholder="123456" required>
                    </div>
                    <button type="submit" class="auth-submit">Verify OTP</button>
                </form>
                <p class="auth-switch"><a href="?view=forgot">Didn't receive code? Resend</a></p>
            </div>

            <!-- RESET PASSWORD -->
            <div id="resetBox" class="box <?php echo ($view === 'reset_password') ? 'active' : ''; ?>">
                <span class="auth-eyebrow">Almost done</span>
                <h2>Reset password</h2>
                <p class="auth-sub">Please choose a new secure password for your account.</p>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="reset_password">
                    <div class="field">
                        <label class="field-label" for="newPassword">New Password (min 9 chars, with a letter and a number)</label>
                        <div class="field-control">
                            <input id="newPassword" type="password" name="password" required>
                            <button type="button" class="pw-toggle" aria-label="Show password"><i class="fa-regular fa-eye"></i></button>
                        </div>
                    </div>
                    <div class="field">
                        <label class="field-label" for="confirmPassword">Confirm Password</label>
                        <div class="field-control">
                            <input id="confirmPassword" type="password" name="confirm_password" required>
                            <button type="button" class="pw-toggle" aria-label="Show password"><i class="fa-regular fa-eye"></i></button>
                        </div>
                    </div>
                    <button type="submit" class="auth-submit">Update Password</button>
                </form>
            </div>

        </div>
    </div>
</div>



<script src="js/loginpage.js"></script>
</body>
</html>