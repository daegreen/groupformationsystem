<?php
session_start();
require 'conn.php'; // Must return a PDO object named $conn

$msg = '';
$reset_modal_open = false;      // Flag to open modal if needed
$reset_msg = '';                // Message inside modal
$reset_step = 1;                // Default to step 1 (fullname only)

// =====================
// Basic brute-force protection for login
// =====================
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}
if (!isset($_SESSION['last_attempt'])) {
    $_SESSION['last_attempt'] = time();
}
if ($_SESSION['login_attempts'] >= 5 && (time() - $_SESSION['last_attempt']) < 60) {
    $msg = "❌ Too many attempts. Try again after 1 minute.";
}

// =====================
// Reset password attempts protection (for both steps)
// =====================
if (!isset($_SESSION['reset_attempts'])) {
    $_SESSION['reset_attempts'] = 0;
}
if (!isset($_SESSION['last_reset_attempt'])) {
    $_SESSION['last_reset_attempt'] = time();
}
if ($_SESSION['reset_attempts'] >= 5 && (time() - $_SESSION['last_reset_attempt']) < 300) {
    $reset_msg = "⚠️ Too many password reset attempts. Please try again later.";
    $reset_modal_open = true;
}

// =====================
// Handle Step 1: Verify Full Name
// =====================
if (isset($_POST['verify_fullname']) && empty($reset_msg)) {
    // Check rate limit again
    if ($_SESSION['reset_attempts'] >= 5 && (time() - $_SESSION['last_reset_attempt']) < 300) {
        $reset_msg = "⚠️ Too many password reset attempts. Please try again later.";
        $reset_modal_open = true;
    } else {
        $fullname = trim($_POST['fullname'] ?? '');

        if (empty($fullname)) {
            $reset_msg = "❌ Please enter your full name.";
        } else {
            // Find teacher by full name (case‑insensitive)
            $stmt = $conn->prepare("SELECT id, status FROM teachers WHERE LOWER(fullname) = LOWER(?)");
            $stmt->execute([$fullname]);
            $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$teacher) {
                $reset_msg = "❌ No teacher found with that full name.";
                $_SESSION['reset_attempts']++;
                $_SESSION['last_reset_attempt'] = time();
            } elseif (strtolower($teacher['status']) != 'active') {
                $reset_msg = "❌ Account is inactive. Contact admin.";
                $_SESSION['reset_attempts']++;
                $_SESSION['last_reset_attempt'] = time();
            } else {
                // Verification successful – store teacher ID and proceed to step 2
                $_SESSION['reset_verified_teacher_id'] = $teacher['id'];
                $_SESSION['reset_step'] = 2;
                // Do not increase attempt counter on success
                header("Location: " . $_SERVER['PHP_SELF'] . "?step=2");
                exit();
            }
        }

        // If we reach here with an error, store it and reload
        if (!empty($reset_msg)) {
            $_SESSION['reset_error'] = $reset_msg;
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }
}

// =====================
// Handle Step 2: Reset Password
// =====================
if (isset($_POST['reset_password']) && empty($reset_msg)) {
    // Check rate limit
    if ($_SESSION['reset_attempts'] >= 5 && (time() - $_SESSION['last_reset_attempt']) < 300) {
        $reset_msg = "⚠️ Too many password reset attempts. Please try again later.";
        $reset_modal_open = true;
    } else {
        // Must have verified teacher ID from step 1
        if (!isset($_SESSION['reset_verified_teacher_id'])) {
            $reset_msg = "❌ Verification required. Please start over.";
            $_SESSION['reset_step'] = 1;
            unset($_SESSION['reset_verified_teacher_id']);
        } else {
            $new_password = trim($_POST['new_password'] ?? '');
            $confirm_password = trim($_POST['confirm_password'] ?? '');

            if (empty($new_password) || empty($confirm_password)) {
                $reset_msg = "❌ All fields are required.";
            } elseif ($new_password !== $confirm_password) {
                $reset_msg = "❌ Passwords do not match.";
            } elseif (strlen($new_password) < 6) {
                $reset_msg = "❌ Password must be at least 6 characters.";
            } else {
                // Update password for the verified teacher
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE teachers SET password = ? WHERE id = ?");
                if ($update->execute([$hashed, $_SESSION['reset_verified_teacher_id']])) {
                    // Success: clear session data, reset attempts, and show success message
                    $_SESSION['reset_attempts'] = 0;
                    $_SESSION['last_reset_attempt'] = time();
                    unset($_SESSION['reset_verified_teacher_id']);
                    unset($_SESSION['reset_step']);
                    $_SESSION['reset_success'] = "✅ Password reset successful! You can now login with your new password.";
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                } else {
                    $reset_msg = "❌ Database error. Please try again.";
                    $_SESSION['reset_attempts']++;
                    $_SESSION['last_reset_attempt'] = time();
                }
            }
        }

        // If we reach here with an error, store it and reload
        if (!empty($reset_msg)) {
            $_SESSION['reset_error'] = $reset_msg;
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }
}

// =====================
// Check for session messages and modal state
// =====================
if (isset($_SESSION['reset_success'])) {
    $msg = $_SESSION['reset_success'];
    unset($_SESSION['reset_success']);
}
if (isset($_SESSION['reset_error'])) {
    $reset_msg = $_SESSION['reset_error'];
    $reset_modal_open = true;
    unset($_SESSION['reset_error']);
}
// Determine which step of the modal to show
if (isset($_SESSION['reset_step']) && $_SESSION['reset_step'] == 2 && isset($_SESSION['reset_verified_teacher_id'])) {
    $reset_step = 2;
    $reset_modal_open = true; // Ensure modal opens
} else {
    // If step2 is not valid, reset any leftover verification data
    if (isset($_SESSION['reset_step'])) unset($_SESSION['reset_step']);
    if (isset($_SESSION['reset_verified_teacher_id'])) unset($_SESSION['reset_verified_teacher_id']);
    $reset_step = 1;
}
// If there is a message from step1 error and modal should be open
if (!empty($reset_msg)) {
    $reset_modal_open = true;
}

// =====================
// Login process (unchanged)
// =====================
if (isset($_POST['login']) && empty($msg)) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT id, password, status FROM teachers WHERE username=?");
        $stmt->execute([$username]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($teacher) {
            if (strtolower($teacher['status']) != 'active') {
                $msg = "❌ Teacher account is inactive";
            } elseif (password_verify($password, $teacher['password'])) {
                session_regenerate_id(true);
                $_SESSION['teacher_id'] = $teacher['id'];
                $_SESSION['teacher_username'] = $username;
                $_SESSION['login_attempts'] = 0;
                $_SESSION['last_attempt'] = time();
                header("Location: teacher.php");
                exit();
            } else {
                $_SESSION['login_attempts']++;
                $_SESSION['last_attempt'] = time();
                $msg = "❌ Wrong password";
            }
        } else {
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt'] = time();
            $msg = "❌ Invalid username";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
<title>Teacher Login · Secure Access</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: linear-gradient(135deg, #e3f0fc, #cbe4f5);
    font-family: Arial, sans-serif;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
}

.login-card {
    background: white;
    width: 100%;
    max-width: 430px;
    border-radius: 25px;
    box-shadow: 0 20px 35px rgba(0, 0, 0, 0.15);
    overflow: hidden;
}

.header {
    background: #0f2b3f;
    color: white;
    text-align: center;
    padding: 30px;
}

.header h1 {
    font-size: 28px;
}

.body {
    padding: 30px;
}

.message {
    background: #ffe8e8;
    color: red;
    padding: 12px;
    border-radius: 20px;
    margin-bottom: 20px;
    font-size: 14px;
}

.input-group {
    margin-bottom: 15px;
}

.input-group input {
    width: 100%;
    padding: 14px;
    border: 1px solid #ccc;
    border-radius: 30px;
    outline: none;
    font-size: 16px;
}

.input-group input:focus {
    border-color: #2a7f6b;
}

button {
    width: 100%;
    padding: 14px;
    background: #2a7f6b;
    color: white;
    border: none;
    border-radius: 30px;
    font-size: 17px;
    cursor: pointer;
    font-weight: bold;
}

button:hover {
    background: #1e9a80;
}

.forgot-btn {
    width: 100%;
    text-align: center;
    margin-top: 12px;
    margin-bottom: 15px;
}

.forgot-link {
    background: none;
    border: none;
    color: #1e4b6e;
    font-size: 14px;
    cursor: pointer;
    text-decoration: underline;
    padding: 5px;
}

.forgot-link:hover {
    color: #2a7f6b;
}

.footer {
    text-align: center;
    padding: 15px;
    background: #ecf5fd;
    font-size: 13px;
}

.back {
    text-align: center;
    margin-top: 15px;
}

.back a {
    text-decoration: none;
    color: #1e4b6e;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    justify-content: center;
    align-items: center;
}

.modal-content {
    background: white;
    margin: auto;
    padding: 25px;
    border-radius: 25px;
    width: 90%;
    max-width: 420px;
    box-shadow: 0 25px 40px rgba(0, 0, 0, 0.3);
    position: relative;
    animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}

.modal-content h2 {
    margin-bottom: 20px;
    color: #0f2b3f;
    font-size: 24px;
    text-align: center;
}

.modal-content .close {
    position: absolute;
    right: 20px;
    top: 15px;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: #aaa;
}

.modal-content .close:hover {
    color: black;
}

.modal-content .reset-message {
    background: #ffe8e8;
    color: #d32f2f;
    padding: 10px;
    border-radius: 20px;
    margin-bottom: 20px;
    font-size: 14px;
    text-align: center;
}

.modal-content .input-group {
    margin-bottom: 18px;
}

.modal-content button {
    margin-top: 10px;
}

.modal-content .cancel-btn {
    background: #6c757d;
    margin-top: 12px;
}

.modal-content .cancel-btn:hover {
    background: #5a6268;
}

/* Responsive */
@media (max-width: 480px) {
    .modal-content {
        padding: 20px;
        width: 95%;
    }
}
</style>
</head>
<body>

<div class="login-card">
    <div class="header">
        <h1>👩‍🏫 Teacher Login</h1>
        <p>Secure access to dashboard</p>
    </div>

    <div class="body">
        <?php if ($msg): ?>
            <div class="message"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <input type="text" name="username" placeholder="Username" required>
            </div>
            <div class="input-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit" name="login">Login</button>

            <div class="forgot-btn">
                <button type="button" class="forgot-link" id="forgotPasswordBtn">Forgot Password?</button>
            </div>
        </form>

        <div class="back">
            <a href="index.php">⬅ Back Home</a>
        </div>
    </div>

    <div class="footer">
        🔐 Secure Group Formation System
    </div>
</div>

<!-- Forgot Password Modal (two‑step) -->
<div id="resetModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>

        <!-- Step 1: Verify Full Name -->
        <div id="step1" style="<?= $reset_step == 2 ? 'display:none;' : '' ?>">
            <h2>Verify Identity</h2>
            <?php if (!empty($reset_msg) && $reset_step == 1): ?>
                <div class="reset-message"><?= htmlspecialchars($reset_msg) ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="input-group">
                    <input type="text" name="fullname" placeholder="Full Name (exactly as registered)" required>
                </div>
                <button type="submit" name="verify_fullname">Verify</button>
                <button type="button" class="cancel-btn" id="cancelResetBtn">Cancel</button>
            </form>
        </div>

        <!-- Step 2: Set New Password -->
        <div id="step2" style="<?= $reset_step == 2 ? '' : 'display:none;' ?>">
            <h2>Reset Password</h2>
            <?php if (!empty($reset_msg) && $reset_step == 2): ?>
                <div class="reset-message"><?= htmlspecialchars($reset_msg) ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="input-group">
                    <input type="password" name="new_password" placeholder="New Password (min. 6 chars)" required>
                </div>
                <div class="input-group">
                    <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
                </div>
                <button type="submit" name="reset_password">Reset Password</button>
                <button type="button" class="cancel-btn" id="cancelResetBtn2">Cancel</button>
            </form>
        </div>
    </div>
</div>

<script>
    // Get modal elements
    const modal = document.getElementById('resetModal');
    const forgotBtn = document.getElementById('forgotPasswordBtn');
    const closeSpan = document.querySelector('.close');
    const cancelBtn1 = document.getElementById('cancelResetBtn');
    const cancelBtn2 = document.getElementById('cancelResetBtn2');

    function openModal() {
        modal.style.display = 'flex';
    }
    function closeModal() {
        modal.style.display = 'none';
        // Optionally clear session by redirecting to reset state
        window.location.href = window.location.pathname;
    }

    forgotBtn.onclick = openModal;
    closeSpan.onclick = closeModal;

    if (cancelBtn1) cancelBtn1.onclick = closeModal;
    if (cancelBtn2) cancelBtn2.onclick = closeModal;

    window.onclick = function(event) {
        if (event.target === modal) closeModal();
    }

    // Auto‑open modal if there was an error or we are in step2
    <?php if ($reset_modal_open): ?>
        openModal();
    <?php endif; ?>
</script>

</body>
</html>
