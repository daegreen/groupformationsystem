<?php
session_start();
include 'conn.php'; // Must return a PDO object named $conn

$login_error = '';
$max_attempts = 5; // max login attempts
$lock_time = 300; // lock time in seconds (5 minutes)

if(!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

if(isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // check if user is locked out
    if($_SESSION['login_attempts'] >= $max_attempts && (time() - $_SESSION['last_attempt_time']) < $lock_time){
        $login_error = "Too many login attempts. Try again later.";
    } else {
        if(!empty($username) && !empty($password)) {
            $stmt = $conn->prepare("SELECT id, password FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if($admin) {
                if(password_verify($password, $admin['password'])) {
                    // Login success: reset attempts
                    $_SESSION['login_attempts'] = 0;

                    // Secure session
                    session_regenerate_id(true);
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $username;

                    header("Location: admin_panel.php");
                    exit();
                } else {
                    $login_error = "Invalid username or password.";
                    $_SESSION['login_attempts']++;
                    $_SESSION['last_attempt_time'] = time();
                }
            } else {
                $login_error = "Invalid username or password.";
                $_SESSION['login_attempts']++;
                $_SESSION['last_attempt_time'] = time();
            }
            $stmt = null;
        } else {
            $login_error = "Please fill all fields.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
<title>Secure Admin Login · Group Formation</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<style>
/* ----- RESET & GLOBAL ----- */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    -webkit-tap-highlight-color: transparent;
}

:root {
    --primary-dark: #0f2b3f;
    --primary: #1e4b6e;
    --accent: #2a7f6b;
    --accent-hover: #1e9a80;
    --bg-light: #f0f4fa;
    --card-bg: #ffffff;
    --border-light: #dce5ef;
    --text-dark: #2c3e4e;
    --shadow-md: 0 20px 35px -12px rgba(0, 0, 0, 0.15);
    --radius-card: 2rem;
    --radius-input: 3rem;
    --transition: all 0.2s ease;
}

body {
    background: linear-gradient(135deg, #e3f0fc 0%, #cbe4f5 100%);
    font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, sans-serif;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

/* Main container */
.login-wrapper {
    width: 100%;
    max-width: 450px;
    margin: 0 auto;
}

/* Card */
.login-card {
    background: var(--card-bg);
    border-radius: var(--radius-card);
    box-shadow: var(--shadow-md);
    overflow: hidden;
    transition: var(--transition);
    border: 1px solid rgba(255,255,255,0.5);
}

/* Header */
.card-header {
    background: var(--primary-dark);
    color: white;
    padding: 2rem 1.8rem;
    text-align: center;
}

.card-header h1 {
    font-size: clamp(1.6rem, 6vw, 2.2rem);
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    flex-wrap: wrap;
}

.card-header p {
    margin-top: 0.5rem;
    opacity: 0.85;
    font-size: clamp(0.85rem, 3.5vw, 1rem);
}

/* Body */
.card-body {
    padding: clamp(1.8rem, 5vw, 2.5rem);
}

/* Message */
.message {
    background: #ffe8e8;
    border-left: 5px solid #d9534f;
    padding: 0.9rem 1.2rem;
    border-radius: 2rem;
    margin-bottom: 1.8rem;
    font-size: 0.9rem;
    color: #bc4747;
    font-weight: 500;
    word-break: break-word;
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

.message i {
    font-size: 1.1rem;
}

/* Input groups */
.input-group {
    display: flex;
    align-items: center;
    background: white;
    border-radius: var(--radius-input);
    margin-bottom: 1.2rem;
    border: 1.5px solid var(--border-light);
    transition: var(--transition);
    padding: 0.2rem 1.2rem;
}

.input-group:focus-within {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(42, 127, 107, 0.2);
}

.input-group i {
    color: var(--primary);
    width: 28px;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.input-group input {
    width: 100%;
    padding: 0.9rem 0.4rem;
    border: none;
    background: transparent;
    outline: none;
    font-size: 1rem;
    font-family: inherit;
}

/* Button */
.login-btn {
    background: var(--accent);
    color: white;
    border: none;
    padding: 1rem;
    border-radius: 4rem;
    font-weight: 700;
    font-size: 1.1rem;
    cursor: pointer;
    width: 100%;
    margin-top: 1rem;
    transition: 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    box-shadow: 0 6px 14px -8px #094334;
}

.login-btn:hover {
    background: var(--accent-hover);
    transform: scale(1.01);
}

/* Back link */
.back-link {
    text-align: center;
    margin-top: 1.5rem;
}

.back-link a {
    color: var(--primary);
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: color 0.2s;
}

.back-link a:hover {
    color: var(--accent);
    text-decoration: underline;
}

/* Footer */
.card-footer {
    background: #ecf5fd;
    padding: 0.9rem 1rem;
    text-align: center;
    font-size: 0.8rem;
    color: #1e4b6e;
    border-top: 1px solid #cde2f2;
}

/* Responsive */
@media (max-width: 550px) {
    body {
        padding: 0.75rem;
    }
    .card-body {
        padding: 1.5rem;
    }
    .input-group {
        padding: 0.1rem 1rem;
    }
    .input-group input {
        padding: 0.75rem 0.3rem;
        font-size: 0.95rem;
    }
    .login-btn {
        font-size: 1rem;
        padding: 0.9rem;
    }
}

@media (max-width: 420px) {
    .login-card {
        border-radius: 1.5rem;
    }
    .card-header {
        padding: 1.5rem 1rem;
    }
    .card-body {
        padding: 1.2rem;
    }
    .message {
        font-size: 0.85rem;
        padding: 0.7rem 1rem;
    }
}

/* Touch optimization */
input, button {
    touch-action: manipulation;
}
</style>
</head>
<body>
<div class="login-wrapper">
    <div class="login-card">
        <div class="card-header">
            <h1><i class="fas fa-user-shield"></i> Admin Login</h1>
            <p>Secure access to management panel</p>
        </div>
        <div class="card-body">
            <?php if ($login_error != ''): ?>
                <div class="message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($login_error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" placeholder="Username" required autofocus>
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" name="login" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="back-link">
                <a href="index.php"><i class="fas fa-home"></i> Back to Home</a>
            </div>
        </div>
        <div class="card-footer">
            <i class="fas fa-shield-alt"></i> Secure authentication · Group Formation System
        </div>
    </div>
</div>
</body>
</html>