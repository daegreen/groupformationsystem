<?php
session_start();
require 'conn.php';

// Protect admin page
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Determine which view to show (default: teachers)
$view = $_GET['view'] ?? 'teachers';

// ---------- Teachers ----------
$teachers = [];
$teacherSearch = '';
$teacherTotal = 0;
if ($view === 'teachers') {
    $teacherSearch = trim($_GET['teacher_search'] ?? '');
    $sql = "SELECT * FROM teachers WHERE 1=1";
    $params = [];
    if (!empty($teacherSearch)) {
        $sql .= " AND (id LIKE ? OR fullname LIKE ? OR username LIKE ?)";
        $like = "%$teacherSearch%";
        $params = [$like, $like, $like];
    }
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $teacherTotal = count($teachers);
}

// ---------- Students ----------
$students = [];
$studentSearch = '';
$studentTotal = 0;
if ($view === 'students') {
    $studentSearch = trim($_GET['student_search'] ?? '');
    $sql = "SELECT s.*, t.fullname AS teacher_name 
            FROM students s 
            LEFT JOIN teachers t ON s.teacher_id = t.id
            WHERE 1=1";
    $params = [];
    if (!empty($studentSearch)) {
        $sql .= " AND (s.id LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR s.reg_number LIKE ? OR t.fullname LIKE ?)";
        $like = "%$studentSearch%";
        $params = [$like, $like, $like, $like, $like];
    }
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $studentTotal = count($students);
}

// ---------- Admins ----------
$admins = [];
$adminSearch = '';
$adminTotal = 0;
if ($view === 'admins') {
    $adminSearch = trim($_GET['admin_search'] ?? '');
    $sql = "SELECT id, username, created_at FROM admins WHERE 1=1";
    $params = [];
    if (!empty($adminSearch)) {
        $sql .= " AND (id LIKE ? OR username LIKE ?)";
        $like = "%$adminSearch%";
        $params = [$like, $like];
    }
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $adminTotal = count($admins);
}

// For student search by teacher (legacy) - we keep it separate but integrated
$teacherIdSearch = '';
$studentByTeacher = [];
$byTeacherTotal = 0;
if (isset($_GET['by_teacher']) && $_GET['by_teacher'] !== '') {
    $teacherIdSearch = trim($_GET['by_teacher']);
    $sql = "SELECT s.*, t.fullname AS teacher_name
            FROM students s
            JOIN teachers t ON s.teacher_id = t.id
            WHERE t.id = ? OR t.fullname LIKE ?";
    $like = "%$teacherIdSearch%";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$teacherIdSearch, $like]);
    $studentByTeacher = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $byTeacherTotal = count($studentByTeacher);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>Admin Dashboard · Group Formation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Custom hover effects */
        .table-hover tbody tr:hover {
            background-color: #f9fafb;
        }
        .nav-link.active {
            background-color: #1e3a8a;
            color: white;
        }
        .nav-link {
            transition: all 0.2s ease;
        }
    </style>
</head>
<body class="bg-gray-100">

<div class="max-w-7xl mx-auto px-4 py-8">
    <!-- Header with logout -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-blue-800">
            <i class="fas fa-user-shield"></i> SEARCH DASHBOARD
        </h1>
        <div class="flex items-center gap-4">
            <span class="text-gray-700">Welcome, <?= htmlspecialchars($_SESSION['admin_username']) ?></span>
            <a href="admin_panel.php" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition">
                <i class="fas fa-sign-out-alt"></i> BACK
            </a>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-2">
        <a href="?view=teachers" class="nav-link px-4 py-2 rounded-t-lg <?= $view === 'teachers' ? 'bg-blue-700 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
            <i class="fas fa-chalkboard-user"></i> Teachers
        </a>
        <a href="?view=students" class="nav-link px-4 py-2 rounded-t-lg <?= $view === 'students' ? 'bg-blue-700 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
            <i class="fas fa-graduation-cap"></i> Students
        </a>
        <a href="?view=admins" class="nav-link px-4 py-2 rounded-t-lg <?= $view === 'admins' ? 'bg-blue-700 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
            <i class="fas fa-users"></i> Admins
        </a>
        <a href="?by_teacher=" class="nav-link px-4 py-2 rounded-t-lg <?= isset($_GET['by_teacher']) ? 'bg-blue-700 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
            <i class="fas fa-search"></i> Search by Teacher
        </a>
    </div>

    <!-- ==================== TEACHERS SECTION ==================== -->
    <?php if ($view === 'teachers'): ?>
        <div class="bg-white shadow rounded p-4 mb-6">
            <form method="GET" class="flex gap-3">
                <input type="hidden" name="view" value="teachers">
                <input type="text" name="teacher_search" value="<?= htmlspecialchars($teacherSearch) ?>" 
                       placeholder="Search by ID, name or username" 
                       class="flex-1 p-3 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500">
                <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700">
                    <i class="fas fa-search"></i> Search
                </button>
                <a href="?view=teachers" class="bg-gray-500 text-white px-6 py-3 rounded hover:bg-gray-600">
                    <i class="fas fa-sync-alt"></i> Reset
                </a>
            </form>
        </div>

        <div class="bg-white shadow rounded overflow-x-auto">
            <div class="p-4 border-b">
                <h2 class="text-lg font-semibold text-green-600">
                    <i class="fas fa-chalkboard-user"></i> Teachers Found: <?= $teacherTotal ?>
                </h2>
            </div>
            <?php if (!empty($teachers)): ?>
                <table class="min-w-full table-hover">
                    <thead class="bg-blue-700 text-white">
                        <tr>
                            <th class="p-3 text-left">ID</th>
                            <th class="p-3 text-left">Full Name</th>
                            <th class="p-3 text-left">Username</th>
                            <th class="p-3 text-left">Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teachers as $teacher): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-3"><?= $teacher['id'] ?></td>
                            <td class="p-3"><?= htmlspecialchars($teacher['fullname']) ?></td>
                            <td class="p-3"><?= htmlspecialchars($teacher['username']) ?></td>
                            <td class="p-3"><?= $teacher['created_at'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="p-4 text-red-600">No teachers found.</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ==================== STUDENTS SECTION ==================== -->
    <?php if ($view === 'students'): ?>
        <div class="bg-white shadow rounded p-4 mb-6">
            <form method="GET" class="flex gap-3">
                <input type="hidden" name="view" value="students">
                <input type="text" name="student_search" value="<?= htmlspecialchars($studentSearch) ?>" 
                       placeholder="Search by ID, name, reg number or teacher" 
                       class="flex-1 p-3 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500">
                <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700">
                    <i class="fas fa-search"></i> Search
                </button>
                <a href="?view=students" class="bg-gray-500 text-white px-6 py-3 rounded hover:bg-gray-600">
                    <i class="fas fa-sync-alt"></i> Reset
                </a>
            </form>
        </div>

        <div class="bg-white shadow rounded overflow-x-auto">
            <div class="p-4 border-b">
                <h2 class="text-lg font-semibold text-green-600">
                    <i class="fas fa-graduation-cap"></i> Students Found: <?= $studentTotal ?>
                </h2>
            </div>
            <?php if (!empty($students)): ?>
                <table class="min-w-full table-hover">
                    <thead class="bg-blue-700 text-white">
                        <tr>
                            <th class="p-3 text-left">ID</th>
                            <th class="p-3 text-left">First Name</th>
                            <th class="p-3 text-left">Last Name</th>
                            <th class="p-3 text-left">Reg Number</th>
                            <th class="p-3 text-left">Gender</th>
                            <th class="p-3 text-left">Interest</th>
                            <th class="p-3 text-left">Status</th>
                            <th class="p-3 text-left">Teacher</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $row): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-3"><?= $row['id'] ?></td>
                            <td class="p-3"><?= htmlspecialchars($row['first_name']) ?></td>
                            <td class="p-3"><?= htmlspecialchars($row['last_name']) ?></td>
                            <td class="p-3"><?= htmlspecialchars($row['reg_number']) ?></td>
                            <td class="p-3"><?= htmlspecialchars($row['gender']) ?></td>
                            <td class="p-3"><?= htmlspecialchars($row['interested']) ?></td>
                            <td class="p-3">
                                <?php if ($row['status'] === 'active'): ?>
                                    <span class="text-green-600 font-bold">🟢 Active</span>
                                <?php else: ?>
                                    <span class="text-red-600 font-bold">🔴 Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3">
                                <?= htmlspecialchars($row['teacher_name'] ?? 'Not assigned') ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="p-4 text-red-600">No students found.</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ==================== ADMINS SECTION ==================== -->
    <?php if ($view === 'admins'): ?>
        <div class="bg-white shadow rounded p-4 mb-6">
            <form method="GET" class="flex gap-3">
                <input type="hidden" name="view" value="admins">
                <input type="text" name="admin_search" value="<?= htmlspecialchars($adminSearch) ?>" 
                       placeholder="Search by ID or username" 
                       class="flex-1 p-3 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500">
                <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700">
                    <i class="fas fa-search"></i> Search
                </button>
                <a href="?view=admins" class="bg-gray-500 text-white px-6 py-3 rounded hover:bg-gray-600">
                    <i class="fas fa-sync-alt"></i> Reset
                </a>
            </form>
        </div>

        <div class="bg-white shadow rounded overflow-x-auto">
            <div class="p-4 border-b">
                <h2 class="text-lg font-semibold text-green-600">
                    <i class="fas fa-users"></i> Admins Found: <?= $adminTotal ?>
                </h2>
            </div>
            <?php if (!empty($admins)): ?>
                <table class="min-w-full table-hover">
                    <thead class="bg-blue-700 text-white">
                        <tr>
                            <th class="p-3 text-left">ID</th>
                            <th class="p-3 text-left">Username</th>
                            <th class="p-3 text-left">Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $admin): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-3"><?= $admin['id'] ?></td>
                            <td class="p-3"><?= htmlspecialchars($admin['username']) ?></td>
                            <td class="p-3"><?= $admin['created_at'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="p-4 text-red-600">No admins found.</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ==================== SEARCH STUDENTS BY TEACHER (Legacy) ==================== -->
    <?php if (isset($_GET['by_teacher'])): ?>
        <div class="bg-white shadow rounded p-4 mb-6">
            <form method="GET" class="flex gap-3">
                <input type="text" name="by_teacher" value="<?= htmlspecialchars($teacherIdSearch) ?>" 
                       placeholder="Enter Teacher ID or Name" 
                       class="flex-1 p-3 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500">
                <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700">
                    <i class="fas fa-search"></i> Search
                </button>
                <a href="?" class="bg-gray-500 text-white px-6 py-3 rounded hover:bg-gray-600">
                    <i class="fas fa-times"></i> Clear
                </a>
            </form>
        </div>

        <div class="bg-white shadow rounded overflow-x-auto">
            <div class="p-4 border-b">
                <h2 class="text-lg font-semibold text-green-600">
                    <i class="fas fa-graduation-cap"></i> Students Found for Teacher: <?= htmlspecialchars($teacherIdSearch) ?> (<?= $byTeacherTotal ?>)
                </h2>
            </div>
            <?php if (!empty($studentByTeacher)): ?>
                <table class="min-w-full table-hover">
                    <thead class="bg-blue-700 text-white">
                        <tr>
                            <th class="p-3 text-left">ID</th>
                            <th class="p-3 text-left">First Name</th>
                            <th class="p-3 text-left">Last Name</th>
                            <th class="p-3 text-left">Reg Number</th>
                            <th class="p-3 text-left">Gender</th>
                            <th class="p-3 text-left">Interest</th>
                            <th class="p-3 text-left">Status</th>
                            <th class="p-3 text-left">Teacher</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($studentByTeacher as $row): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-3"><?= $row['id'] ?></td>
                            <td class="p-3"><?= htmlspecialchars($row['first_name']) ?></td>
                            <td class="p-3"><?= htmlspecialchars($row['last_name']) ?></td>
                            <td class="p-3"><?= htmlspecialchars($row['reg_number']) ?></td>
                            <td class="p-3"><?= htmlspecialchars($row['gender']) ?></td>
                            <td class="p-3"><?= htmlspecialchars($row['interested']) ?></td>
                            <td class="p-3">
                                <?php if ($row['status'] === 'active'): ?>
                                    <span class="text-green-600 font-bold">🟢 Active</span>
                                <?php else: ?>
                                    <span class="text-red-600 font-bold">🔴 Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3"><?= htmlspecialchars($row['teacher_name']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="p-4 text-red-600">No students found for this teacher.</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>
</body>
</html>