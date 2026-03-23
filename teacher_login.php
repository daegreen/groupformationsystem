<?php
session_start();
require 'conn.php'; // Must return a PDO object named $conn

$msg = '';

// =====================
// Basic brute-force protection
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
// Login process
// =====================
if(isset($_POST['login']) && empty($msg)){

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if(!empty($username) && !empty($password)){

        // PDO prepared statement
        $stmt = $conn->prepare("SELECT id, password, status FROM teachers WHERE username=?");
        $stmt->execute([$username]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

      if($teacher){

    // STATUS CHECK
    if(strtolower($teacher['status']) != 'active'){
        $msg = "❌ Teacher account is inactive";
    }

    // PASSWORD CHECK
    elseif(password_verify($password, $teacher['password'])){

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
*{
margin:0;
padding:0;
box-sizing:border-box;
}

body{
background:linear-gradient(135deg,#e3f0fc,#cbe4f5);
font-family:Arial,sans-serif;
min-height:100vh;
display:flex;
justify-content:center;
align-items:center;
padding:20px;
}

.login-card{
background:white;
width:100%;
max-width:430px;
border-radius:25px;
box-shadow:0 20px 35px rgba(0,0,0,0.15);
overflow:hidden;
}

.header{
background:#0f2b3f;
color:white;
text-align:center;
padding:30px;
}

.header h1{
font-size:28px;
}

.body{
padding:30px;
}

.message{
background:#ffe8e8;
color:red;
padding:12px;
border-radius:20px;
margin-bottom:20px;
font-size:14px;
}

.input-group{
margin-bottom:15px;
}

.input-group input{
width:100%;
padding:14px;
border:1px solid #ccc;
border-radius:30px;
outline:none;
font-size:16px;
}

.input-group input:focus{
border-color:#2a7f6b;
}

button{
width:100%;
padding:14px;
background:#2a7f6b;
color:white;
border:none;
border-radius:30px;
font-size:17px;
cursor:pointer;
font-weight:bold;
}

button:hover{
background:#1e9a80;
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

.footer{
text-align:center;
padding:15px;
background:#ecf5fd;
font-size:13px;
}

.back{
text-align:center;
margin-top:15px;
}

.back a{
text-decoration:none;
color:#1e4b6e;
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

<?php if($msg): ?>
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
    <button type="button" class="forgot-link" onclick="alert('📞 Please contact admin for password reset: +250789503716')">Forgot Password?</button>
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

</body>
</html>