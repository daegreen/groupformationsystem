<?php
session_start();
include 'conn.php'; // Must return a PDO object named $conn

$success = $error = '';

// ---------- Create Teacher ----------
if (isset($_POST['create_teacher'])) {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if ($fullname && $username && $password) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        
        // Make sure admin is logged in and has ID
        if (!isset($_SESSION['admin_id'])) {
            $error = "Admin not logged in!";
        } else {
            $admin_id = $_SESSION['admin_id']; // current logged-in admin

            $stmt = $conn->prepare("INSERT INTO teachers (admin_id, fullname, username, password) VALUES (?, ?, ?, ?)");

            try {
                $stmt->execute([$admin_id, $fullname, $username, $hashed_password]);
                $success = "Teacher created successfully!";
            } catch (PDOException $e) {
                $error = "Error: " . $e->getMessage();
            }

            $stmt = null;
        }
    } else {
        $error = "All fields are required!";
    }
}

// ---------- Delete Teacher ----------
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM teachers WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ---------- Edit Teacher ----------
$edit_teacher = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT id, fullname, username FROM teachers WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt = null;
}

// ---------- Update Teacher ----------
if (isset($_POST['update_teacher'])) {
    $id = intval($_POST['id']);
    $fullname = trim($_POST['fullname']);
    $password = trim($_POST['password']);

    if ($fullname) {
        try {
            if ($password) {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE teachers SET fullname = ?, password = ? WHERE id = ?");
                $stmt->execute([$fullname, $hashed_password, $id]);
            } else {
                $stmt = $conn->prepare("UPDATE teachers SET fullname = ? WHERE id = ?");
                $stmt->execute([$fullname, $id]);
            }
            $success = "Teacher updated successfully!";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
        $stmt = null;
    } else {
        $error = "Full Name is required!";
    }
}

// ---------- Fetch All Teachers ----------
$teachers = $conn->query("SELECT id, fullname, username, created_at FROM teachers ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Admin Panel - Teacher Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Your previous CSS unchanged */
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background: linear-gradient(145deg,#f0f4fa 0%,#e2eaf2 100%); font-family:'Inter',system-ui,-apple-system,'Segoe UI',Roboto,sans-serif; padding:2rem 1.5rem; min-height:100vh; }
        .container { max-width:1400px; margin:0 auto; }
        .header { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:2rem; background:white; padding:1rem 1.5rem; border-radius:1.5rem; box-shadow:0 8px 20px rgba(0,0,0,0.05);}
        .header h1 { font-size:1.5rem; display:flex; align-items:center; gap:0.5rem; color:#1e3a5f;}
        .logout-btn { background:#e74c3c; color:white; text-decoration:none; padding:0.5rem 1.2rem; border-radius:2rem; display:inline-flex; align-items:center; gap:0.5rem; font-weight:500; transition:0.2s; }
        .logout-btn:hover { background:#c0392b; transform:translateY(-2px);}
        .teachers-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:1.5rem; margin-top:1.5rem; }
        .teacher-card { background:white; border-radius:1.25rem; box-shadow:0 8px 20px rgba(0,0,0,0.08); transition:transform 0.2s, box-shadow 0.2s; overflow:hidden; }
        .teacher-card:hover { transform:translateY(-4px); box-shadow:0 15px 30px rgba(0,0,0,0.12);}
        .card-header { background:#2c3e66; color:white; padding:1rem 1.2rem; display:flex; justify-content:space-between; align-items:center; }
        .card-header h3 { font-size:1.1rem; font-weight:600; display:flex; align-items:center; gap:0.5rem;}
        .teacher-details { padding:1.2rem; }
        .detail-row { display:flex; align-items:center; gap:0.8rem; margin-bottom:0.8rem; color:#2c3e50; font-size:0.9rem;}
        .detail-row i { width:1.5rem; color:#3b6e8f;}
        .card-actions { padding:0 1.2rem 1.2rem 1.2rem; display:flex; gap:1rem; border-top:1px solid #edf2f7; margin-top:0.5rem; padding-top:1rem;}
        .btn-edit, .btn-delete { flex:1; text-align:center; padding:0.5rem; border-radius:2rem; text-decoration:none; font-weight:500; display:inline-flex; align-items:center; justify-content:center; gap:0.5rem; transition:0.2s; }
        .btn-edit { background:#2c7da0; color:white; } .btn-edit:hover { background:#1f5e7a; }
        .btn-delete { background:#e67e22; color:white; } .btn-delete:hover { background:#d35400; }
        .form-card { background:white; border-radius:1.5rem; box-shadow:0 8px 20px rgba(0,0,0,0.08); margin-bottom:2rem; overflow:hidden;}
        .form-card .card-header { background:#1e4b6e; }
        .form-body { padding:1.5rem; }
        .form-group { margin-bottom:1rem; }
        input { width:100%; padding:0.75rem 1rem; border:1px solid #dce5ec; border-radius:2rem; font-size:0.9rem; transition:0.2s; }
        input:focus { outline:none; border-color:#2c7da0; box-shadow:0 0 0 3px rgba(44,125,160,0.2);}
        .btn-submit { background:#2c8c7a; color:white; border:none; padding:0.75rem 1.5rem; border-radius:2rem; font-weight:600; cursor:pointer; width:100%; display:inline-flex; align-items:center; justify-content:center; gap:0.5rem; transition:0.2s;}
        .btn-submit:hover { background:#1e6f5c; transform:translateY(-2px);}
        .cancel-link { display:inline-block; margin-top:1rem; text-align:center; width:100%; color:#e67e22; text-decoration:none; }
        .alert { padding:0.8rem 1rem; border-radius:1rem; margin-bottom:1rem; font-weight:500;}
        .alert-success { background:#dff0e8; color:#1d6f5c; border-left:4px solid #2c8c7a; }
        .alert-error { background:#fee9e6; color:#bc4e2c; border-left:4px solid #e67e22; }
        .empty-message { text-align:center; padding:2rem; background:white; border-radius:1rem; color:#5f7f9a; }
        @media(max-width:768px){ body{padding:1rem;} .teachers-grid{grid-template-columns:1fr;} .header{flex-direction:column; align-items:stretch; text-align:center;} .logout-btn{align-self:center;} }
    </style>
</head>
<body>
<div class="container">
    <!-- Header with Logout -->
    <div class="header">
        <h1><i class="fas fa-chalkboard-user"></i> Teacher Management</h1>
        <a href="admin_panel.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> BACK</a>
    </div>

    <!-- Messages -->
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Form Card (Create/Edit) -->
    <div class="form-card">
        <div class="card-header">
            <h3><i class="fas <?= $edit_teacher ? 'fa-edit' : 'fa-plus-circle' ?>"></i> <?= $edit_teacher ? 'Edit Teacher' : 'Create New Teacher' ?></h3>
        </div>
        <div class="form-body">
            <form method="POST">
                <?php if ($edit_teacher): ?>
                    <input type="hidden" name="id" value="<?= $edit_teacher['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <input type="text" name="fullname" placeholder="Full Name *" value="<?= $edit_teacher ? htmlspecialchars($edit_teacher['fullname']) : '' ?>" required>
                </div>

                <?php if (!$edit_teacher): ?>
                    <div class="form-group">
                        <input type="text" name="username" placeholder="Username *" required>
                    </div>
                    <div class="form-group">
                        <input type="password" name="password" placeholder="Password *" required>
                    </div>
                <?php else: ?>
                    <div class="form-group">
                        <input type="password" name="password" placeholder="New Password (leave blank to keep current)">
                    </div>
                <?php endif; ?>

                <button type="submit" name="<?= $edit_teacher ? 'update_teacher' : 'create_teacher' ?>" class="btn-submit">
                    <i class="fas <?= $edit_teacher ? 'fa-save' : 'fa-user-plus' ?>"></i>
                    <?= $edit_teacher ? 'Update Teacher' : 'Create Teacher' ?>
                </button>

                <?php if ($edit_teacher): ?>
                    <a href="<?= $_SERVER['PHP_SELF'] ?>" class="cancel-link"><i class="fas fa-times-circle"></i> Cancel Edit</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Teachers List (Cards) -->
    <h2 style="margin:1.5rem 0 0.5rem 0; display:flex; align-items:center; gap:0.5rem;"><i class="fas fa-users"></i> All Teachers</h2>
    
    <?php if ($teachers && $teachers->rowCount() > 0): ?>
        <div class="teachers-grid">
            <?php while ($row = $teachers->fetch(PDO::FETCH_ASSOC)): ?>
                <div class="teacher-card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-circle"></i> <?= htmlspecialchars($row['fullname']) ?></h3>
                        <span style="font-size:0.8rem; background:rgba(255,255,255,0.2); padding:0.2rem 0.6rem; border-radius:2rem;">ID: <?= $row['id'] ?></span>
                    </div>
                    <div class="teacher-details">
                        <div class="detail-row">
                            <i class="fas fa-user"></i>
                            <span><strong>Username:</strong> <?= htmlspecialchars($row['username']) ?></span>
                        </div>
                        <div class="detail-row">
                            <i class="fas fa-calendar-alt"></i>
                            <span><strong>Created:</strong> <?= date('d M Y, H:i', strtotime($row['created_at'])) ?></span>
                        </div>
                    </div>
                    <div class="card-actions">
                        <a href="?edit=<?= $row['id'] ?>" class="btn-edit"><i class="fas fa-edit"></i> Edit</a>
                        <a href="?delete=<?= $row['id'] ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this teacher?');"><i class="fas fa-trash-alt"></i> Delete</a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-message">
            <i class="fas fa-info-circle"></i> No teachers found. Create one using the form above.
        </div>
    <?php endif; ?>
</div>
</body>
</html>