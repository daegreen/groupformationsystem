<?php
session_start();
include 'conn.php'; // Must return a PDO object named $conn

// --- Authentication (adjust as needed) ---
// Uncomment and modify according to your admin login system
// if (!isset($_SESSION['admin_id'])) {
//     header("Location: admin_login.php");
//     exit();
// }

// --- Helper: get total students across all tables except teachers/admins ---
function getTotalStudents($conn) {
    $tables = [];
    $stmt = $conn->query("SHOW TABLES");
    if ($stmt) {
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    $total = 0;
    foreach ($tables as $table) {
        if ($table != 'teachers' && $table != 'admins') {
            $countStmt = $conn->query("SELECT COUNT(*) AS cnt FROM `$table`");
            if ($countStmt && $cntRow = $countStmt->fetch(PDO::FETCH_ASSOC)) {
                $total += $cntRow['cnt'];
            }
        }
    }
    return $total;
}

// --- Fetch statistics ---
$totalTeachers = 0;
$teacherResult = $conn->query("SELECT COUNT(*) AS total FROM teachers");
if ($teacherResult && $row = $teacherResult->fetch(PDO::FETCH_ASSOC)) {
    $totalTeachers = $row['total'];
}

$totalStudents = getTotalStudents($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    <title>ADMIN DASHBOARD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Custom overrides for better responsiveness and touch */
        .card-hover {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
        }
        /* Ensure touch targets are large enough on mobile */
        button, a {
            touch-action: manipulation;
        }
        /* Fix for very small screens (<= 360px) */
        @media (max-width: 360px) {
            .stats-card .text-3xl {
                font-size: 1.75rem;
            }
            .stats-card p.text-sm {
                font-size: 0.7rem;
            }
            .action-card h3 {
                font-size: 1rem;
            }
            .action-card p {
                font-size: 0.75rem;
            }
        }
        /* Sticky header with safe padding for notched phones */
        .sticky-header {
            padding-top: env(safe-area-inset-top);
        }
        /* Profile image fallback */
        .profile-img {
            object-fit: cover;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased">

    <!-- Header with light background and profile section -->
    <header class="bg-white shadow-md sticky top-0 z-10 sticky-header border-b border-gray-200">
        <div class="container mx-auto px-4 py-3 flex flex-wrap justify-between items-center gap-3">
            <div class="flex items-center gap-2">
                <i class="fas fa-chalkboard-user text-2xl text-blue-600"></i>
                <h1 class="text-xl font-bold text-gray-800 hidden xs:inline">ADMIN DASHBOARD</h1>
                <h1 class="text-lg font-bold text-gray-800 xs:hidden">ADMIN DASHBOARD</h1>
            </div>
            <div class="flex items-center gap-3">
                <!-- Profile Section (with fallback icon if image missing) -->
                <div class="flex items-center gap-2 bg-gray-100 rounded-full px-3 py-1.5 shadow-sm">
                    <img src="profile.jpg" 
                         alt="Profile" 
                         class="w-10 h-10 rounded-full object-cover border-2 border-blue-500"
                         onerror="this.onerror=null; this.src='https://via.placeholder.com/40?text=👤';">
                    <span class="text-sm font-medium text-gray-700 hidden sm:inline">
                        <?php
                            $adminName = isset($_SESSION['MUCYO']) 
                                ? htmlspecialchars($_SESSION['MUCYO']) 
                                : 'Administrator';
                            echo $adminName;
                        ?>
                    </span>
                </div>
                <a href="logout.php" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded-full text-sm font-medium transition flex items-center gap-1 active:scale-95 text-white">
                    <i class="fas fa-sign-out-alt"></i> <span class="hidden xs:inline">Logout</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-6 md:py-8">
        
        <!-- Stats Cards (Responsive grid with improved spacing) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 md:gap-6 mb-8 md:mb-10">
            <!-- Total Teachers -->
            <div class="bg-white rounded-2xl shadow-md p-5 md:p-6 border-l-8 border-blue-500 card-hover stats-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-xs md:text-sm uppercase tracking-wide">Total Teachers</p>
                        <p class="text-2xl md:text-3xl font-bold text-gray-800"><?= $totalTeachers ?></p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-chalkboard-user text-blue-600 text-xl md:text-2xl"></i>
                    </div>
                </div>
            </div>
            <!-- Total Students -->
            <div class="bg-white rounded-2xl shadow-md p-5 md:p-6 border-l-8 border-green-500 card-hover stats-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-xs md:text-sm uppercase tracking-wide">Total Students</p>
                        <p class="text-2xl md:text-3xl font-bold text-gray-800"><?= $totalStudents ?></p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-users text-green-600 text-xl md:text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Management Tools -->
        <h2 class="text-xl md:text-2xl font-semibold text-gray-700 mb-5 flex items-center gap-2">
            <i class="fas fa-tools"></i> Management Tools
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5 md:gap-6">
            
            <!-- Teacher Management Card -->
            <a href="admincreatet.php" class="block focus:outline-none">
                <div class="bg-white rounded-2xl shadow-md p-5 flex flex-col items-center text-center hover:bg-blue-50 transition-all card-hover action-card h-full">
                    <div class="bg-blue-100 p-4 rounded-full mb-4">
                        <i class="fas fa-chalkboard-user text-blue-600 text-2xl md:text-3xl"></i>
                    </div>
                    <h3 class="font-bold text-base md:text-lg text-gray-800">Teacher Management</h3>
                    <p class="text-gray-500 text-xs md:text-sm mt-2">Add, edit, or remove teachers</p>
                </div>
            </a>

            <!-- Student Activities -->
            <a href="view_student.php" class="block focus:outline-none">
                <div class="bg-white rounded-2xl shadow-md p-5 flex flex-col items-center text-center hover:bg-green-50 transition-all card-hover action-card h-full">
                    <div class="bg-green-100 p-4 rounded-full mb-4">
                        <i class="fas fa-user-graduate text-green-600 text-2xl md:text-3xl"></i>
                    </div>
                    <h3 class="font-bold text-base md:text-lg text-gray-800">Student Activities</h3>
                    <p class="text-gray-500 text-xs md:text-sm mt-2">Monitor student registrations & groups</p>
                </div>
            </a>

            <!-- Create Admins -->
            <a href="admincreation.php" class="block focus:outline-none">
                <div class="bg-white rounded-2xl shadow-md p-5 flex flex-col items-center text-center hover:bg-green-50 transition-all card-hover action-card h-full">
                    <div class="bg-green-100 p-4 rounded-full mb-4">
                        <i class="fas fa-user-shield text-green-600 text-2xl md:text-3xl"></i>
                    </div>
                    <h3 class="font-bold text-base md:text-lg text-gray-800">Create Admins</h3>
                    <p class="text-gray-500 text-xs md:text-sm mt-2">Add other administrators</p>
                </div>
            </a>

            <!-- Teacher Logs -->
            <a href="view_teacher.php" class="block focus:outline-none">
                <div class="bg-white rounded-2xl shadow-md p-5 flex flex-col items-center text-center hover:bg-purple-50 transition-all card-hover action-card h-full">
                    <div class="bg-purple-100 p-4 rounded-full mb-4">
                        <i class="fas fa-clipboard-list text-purple-600 text-2xl md:text-3xl"></i>
                    </div>
                    <h3 class="font-bold text-base md:text-lg text-gray-800">Teacher activities</h3>
                    <p class="text-gray-500 text-xs md:text-sm mt-2">View teacher activities & performance</p>
                </div>
            </a>

            <!-- Search Engine -->
            <a href="search_enginer.php" class="block focus:outline-none">
                <div class="bg-white rounded-2xl shadow-md p-5 flex flex-col items-center text-center hover:bg-purple-50 transition-all card-hover action-card h-full">
                    <div class="bg-purple-100 p-4 rounded-full mb-4">
                        <i class="fas fa-search text-purple-600 text-2xl md:text-3xl"></i>
                    </div>
                    <h3 class="font-bold text-base md:text-lg text-gray-800">SEARCH ENGINE</h3>
                    <p class="text-gray-500 text-xs md:text-sm mt-2">✨ live search<br>✨ activate/deactivate teacher</p>
                </div>
            </a>

            <!-- Activate / Deactivate -->
            <a href="activate.php" class="block focus:outline-none">
                <div class="bg-white rounded-2xl shadow-md p-5 flex flex-col items-center text-center hover:bg-yellow-50 transition-all card-hover action-card h-full">
                    <div class="bg-yellow-100 p-4 rounded-full mb-4">
                        <i class="fas fa-toggle-on text-yellow-600 text-2xl md:text-3xl"></i>
                    </div>
                    <h3 class="font-bold text-base md:text-lg text-gray-800">Activate / Deactivate</h3>
                    <p class="text-gray-500 text-xs md:text-sm mt-2">Manage user access rights</p>
                </div>
            </a>

            <!-- Recent Activities -->
            <a href="recent_activite.php" class="block focus:outline-none">
                <div class="bg-white rounded-2xl shadow-md p-5 flex flex-col items-center text-center hover:bg-indigo-50 transition-all card-hover action-card h-full">
                    <div class="bg-indigo-100 p-4 rounded-full mb-4">
                        <i class="fas fa-clock text-indigo-600 text-2xl md:text-3xl"></i>
                    </div>
                    <h3 class="font-bold text-base md:text-lg text-gray-800">RECENT ACTIVITIES</h3>
                    <p class="text-gray-500 text-xs md:text-sm mt-2">Recently activities in 1h</p>
                </div>
            </a>
        </div>

    </main>

    <!-- Footer -->
    <footer class="bg-gray-200 text-center text-gray-600 py-4 mt-8 text-xs md:text-sm">
        <p>&copy; <?= date('Y') ?> Group Formation System. All rights reserved.</p>
    </footer>
</body>
</html>
