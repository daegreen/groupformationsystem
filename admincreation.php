<?php
session_start();
include 'conn.php'; // Must return a PDO object named $conn

$message = "";
$messageType = "";

// ----------------------
// LOGIN LOGIC (only for MUCYO)
// ----------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login_mucyo'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        // Check only for username "MUCYO"
        if ($username !== 'MUCYO') {
            $message = "❌ Access denied. Only MUCYO can manage admins.";
            $messageType = "error";
        } else {
            $stmt = $conn->prepare("SELECT id, password FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $username;
                // Redirect to avoid resubmission
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $message = "❌ Invalid password for MUCYO.";
                $messageType = "error";
            }
        }
    } else {
        $message = "⚠️ Username and password are required.";
        $messageType = "error";
    }
}

// ----------------------
// LOGOUT
// ----------------------
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// ----------------------
// CHECK ACCESS: only MUCYO is allowed
// ----------------------
$isAuthorized = (isset($_SESSION['admin_username']) && $_SESSION['admin_username'] === 'MUCYO');

// If not authorized, show login form
if (!$isAuthorized) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
        <title>Restricted Access · Admin Management</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    </head>
    <body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
        <div class="w-full max-w-md">
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <div class="bg-gradient-to-r from-red-600 to-red-700 px-6 py-5">
                    <h2 class="text-2xl font-bold text-white text-center flex items-center justify-center gap-2">
                        <i class="fas fa-lock"></i> Restricted Access
                    </h2>
                    <p class="text-red-100 text-center text-sm mt-1">Only MUCYO can manage administrators</p>
                </div>
                <div class="p-6">
                    <?php if ($message): ?>
                        <div class="mb-4 p-3 rounded-lg text-sm <?php echo $messageType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-4">
                            <label class="block text-gray-700 font-medium mb-2">Username</label>
                            <input type="text" name="username" placeholder="Enter username" required
                                   class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-400">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 font-medium mb-2">Password</label>
                            <input type="password" name="password" placeholder="Enter password" required
                                   class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-400">
                        </div>
                        <button type="submit" name="login_mucyo"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>
                    </form>
                    <div class="mt-4 text-center text-sm text-gray-500">
                        <a href="admin_login.php" class="text-blue-600 hover:underline">Back to general login</a>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit(); // Stop further execution
}

// ----------------------
// AFTER AUTHENTICATION – ADMIN MANAGEMENT CODE (unchanged)
// ----------------------

// CREATE NEW ADMIN
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_admin'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $checkStmt = $conn->prepare("SELECT id FROM admins WHERE username = ?");
            $checkStmt->execute([$username]);
            if ($checkStmt->rowCount() > 0) {
                $message = "❌ Username already exists!";
                $messageType = "error";
            } else {
                $stmt = $conn->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
                $stmt->execute([$username, $hash]);
                $message = "✅ Admin created successfully!";
                $messageType = "success";
            }
        } catch (PDOException $e) {
            $message = "❌ Error: " . $e->getMessage();
            $messageType = "error";
        }
    } else {
        $message = "⚠️ All fields are required!";
        $messageType = "error";
    }
}

// UPDATE ADMIN
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_admin'])) {
    $admin_id = intval($_POST['admin_id']);
    $new_username = trim($_POST['new_username']);
    $new_password = trim($_POST['new_password']);

    if (empty($new_username)) {
        $message = "⚠️ Username cannot be empty!";
        $messageType = "error";
    } else {
        try {
            $checkStmt = $conn->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
            $checkStmt->execute([$new_username, $admin_id]);
            if ($checkStmt->rowCount() > 0) {
                $message = "❌ Username already exists!";
                $messageType = "error";
            } else {
                if (!empty($new_password)) {
                    $hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE admins SET username = ?, password = ? WHERE id = ?");
                    $stmt->execute([$new_username, $hash, $admin_id]);
                    $message = "✅ Admin updated successfully!";
                } else {
                    $stmt = $conn->prepare("UPDATE admins SET username = ? WHERE id = ?");
                    $stmt->execute([$new_username, $admin_id]);
                    $message = "✅ Username updated successfully!";
                }
                $messageType = "success";
            }
        } catch (PDOException $e) {
            $message = "❌ Error: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

// DELETE ADMIN
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_admin'])) {
    $admin_id = intval($_POST['admin_id']);
    // Prevent deleting yourself (MUCYO)
    if (isset($_SESSION['admin_id']) && $admin_id == $_SESSION['admin_id']) {
        $message = "⚠️ You cannot delete your own account!";
        $messageType = "error";
    } else {
        try {
            $stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
            $stmt->execute([$admin_id]);
            if ($stmt->rowCount() > 0) {
                $message = "✅ Admin deleted successfully!";
                $messageType = "success";
            } else {
                $message = "❌ Admin not found.";
                $messageType = "error";
            }
        } catch (PDOException $e) {
            $message = "❌ Error: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

// RESET PASSWORD
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    $admin_id = intval($_POST['admin_id']);
    $default_password = "default123";
    $hash = password_hash($default_password, PASSWORD_DEFAULT);
    try {
        $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
        $stmt->execute([$hash, $admin_id]);
        $message = "✅ Password reset to '$default_password'. Please change it after login.";
        $messageType = "success";
    } catch (PDOException $e) {
        $message = "❌ Error: " . $e->getMessage();
        $messageType = "error";
    }
}

// FETCH ALL ADMINS
$admins = [];
try {
    $stmt = $conn->query("SELECT id, username, created_at FROM admins ORDER BY id DESC");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // ignore
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    <title>Admin Management | MUCYO Only</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Same styles as before */
        body { background: linear-gradient(135deg, #f0f9ff 0%, #e6f0fa 100%); min-height: 100vh; padding: 1rem; }
        .card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .card:hover { transform: translateY(-2px); box-shadow: 0 20px 25px -12px rgba(0,0,0,0.1); }
        @media (max-width: 640px) {
            .admin-table th, .admin-table td { padding: 0.75rem 0.5rem; }
            .action-buttons { display: flex; flex-direction: column; gap: 0.5rem; }
            .action-buttons button, .action-buttons a { width: 100%; justify-content: center; }
        }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; }
        .modal-content { background: white; border-radius: 1.5rem; padding: 1.5rem; max-width: 400px; width: 90%; }
    </style>
</head>
<body class="flex flex-col items-center justify-center min-h-screen">

<div class="w-full max-w-5xl mx-auto">
    <!-- Logout Button -->
    <div class="flex justify-end mb-4">
        <a href="?logout=1" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-full text-sm flex items-center gap-2 transition">
            <i class="fas fa-sign-out-alt"></i> Logout (MUCYO)
        </a>
    </div>

    <!-- Create Admin Card -->
    <div class="card bg-white rounded-2xl shadow-xl overflow-hidden mb-8">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-5">
            <h2 class="text-2xl font-bold text-white text-center flex items-center justify-center gap-2">
                <i class="fas fa-user-shield"></i> Create Admin
            </h2>
            <p class="text-blue-100 text-center text-sm mt-1">Add new administrator account</p>
        </div>

        <div class="p-6 md:p-8">
            <?php if ($message): ?>
                <div class="mb-6 p-3 rounded-lg text-center text-sm font-medium flex items-center justify-center gap-2 
                    <?php echo $messageType === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'; ?>">
                    <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-gray-700 font-medium mb-2">
                        <i class="fas fa-user mr-2 text-blue-500"></i> Username
                    </label>
                    <input type="text" name="username" class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-400" placeholder="Enter username" required>
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-2">
                        <i class="fas fa-lock mr-2 text-blue-500"></i> Password
                    </label>
                    <input type="password" name="password" class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-400" placeholder="Enter password" required>
                </div>
                <button type="submit" name="create_admin" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition flex items-center justify-center gap-2 shadow-md">
                    <i class="fas fa-plus-circle"></i> Create Admin
                </button>
            </form>
        </div>
    </div>

    <!-- Manage Existing Admins Card -->
    <?php if (!empty($admins)): ?>
    <div class="card bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="bg-gradient-to-r from-gray-700 to-gray-800 px-6 py-5">
            <h2 class="text-2xl font-bold text-white text-center flex items-center justify-center gap-2">
                <i class="fas fa-users"></i> Existing Admins
            </h2>
            <p class="text-gray-300 text-center text-sm mt-1">Manage administrator accounts</p>
        </div>

        <div class="p-6 md:p-8 overflow-x-auto">
            <table class="admin-table w-full border-collapse">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50">
                        <th class="text-left p-3 font-semibold text-gray-700">ID</th>
                        <th class="text-left p-3 font-semibold text-gray-700">Username</th>
                        <th class="text-left p-3 font-semibold text-gray-700">Created At</th>
                        <th class="text-left p-3 font-semibold text-gray-700">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $admin): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                        <td class="p-3"><?= $admin['id'] ?></td>
                        <td class="p-3"><?= htmlspecialchars($admin['username']) ?></td>
                        <td class="p-3"><?= $admin['created_at'] ?? 'N/A' ?></td>
                        <td class="p-3 action-buttons">
                            <div class="flex flex-wrap gap-2">
                                <button onclick="openEditModal(<?= $admin['id'] ?>, '<?= htmlspecialchars($admin['username']) ?>')" 
                                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1.5 rounded-lg text-sm flex items-center gap-1 transition">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="POST" class="inline" onsubmit="return confirm('Reset password for this admin? The new password will be \'default123\'.')">
                                    <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
                                    <button type="submit" name="reset_password" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded-lg text-sm flex items-center gap-1 transition">
                                        <i class="fas fa-key"></i> Reset
                                    </button>
                                </form>
                                <form method="POST" class="inline" onsubmit="return confirm('Delete this admin permanently? This action cannot be undone.')">
                                    <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
                                    <button type="submit" name="delete_admin" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-lg text-sm flex items-center gap-1 transition">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Links -->
    <div class="text-center text-sm text-gray-500 mt-6">
        <a href="admin_login.php" class="text-blue-600 hover:text-blue-800 inline-flex items-center gap-1 transition">
            <i class="fas fa-arrow-left"></i> Back to Login
        </a>
        <span class="mx-2">•</span>
        <a href="admin_panel.php" class="text-blue-600 hover:text-blue-800 inline-flex items-center gap-1 transition">
            <i class="fas fa-tachometer-alt"></i> Admin Panel
        </a>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <h3 class="text-xl font-bold mb-4 flex items-center gap-2"><i class="fas fa-user-edit"></i> Edit Admin</h3>
        <form method="POST" id="editForm">
            <input type="hidden" name="admin_id" id="edit_admin_id">
            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-1">Username</label>
                <input type="text" name="new_username" id="edit_username" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-1">New Password (optional)</label>
                <input type="password" name="new_password" id="edit_password" placeholder="Leave blank to keep current"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400">
                <p class="text-xs text-gray-500 mt-1">If provided, password will be updated.</p>
            </div>
            <div class="flex gap-3">
                <button type="submit" name="update_admin" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex-1">Update</button>
                <button type="button" onclick="closeModal()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal(id, username) {
        document.getElementById('edit_admin_id').value = id;
        document.getElementById('edit_username').value = username;
        document.getElementById('editModal').style.display = 'flex';
    }
    function closeModal() {
        document.getElementById('editModal').style.display = 'none';
    }
    window.onclick = function(event) {
        const modal = document.getElementById('editModal');
        if (event.target === modal) closeModal();
    }
</script>

</body>
</html>