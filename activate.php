<?php
include 'conn.php'; // Must return a PDO object named $conn

function safeTable($name){
    return preg_replace('/[^a-zA-Z0-9_]/', '', $name);
}

/* =========================
   AJAX ACTION
========================= */
if(isset($_POST['action_type'])){
    $type = $_POST['type']; // 'teacher' or 'student'
    $id = intval($_POST['id']);
    $action = $_POST['action']; // 'activate' or 'deactivate'

    // Map action to actual status value
    $new_status = ($action == 'activate') ? 'active' : 'inactive';

    if($type == 'teacher'){
        $stmt = $conn->prepare("UPDATE teachers SET status=? WHERE id=?");
        $stmt->execute([$new_status, $id]);
    }

    if($type == 'student' && isset($_POST['table'])){
        $table = safeTable($_POST['table']);
        $stmt = $conn->prepare("UPDATE `$table` SET status=? WHERE id=?");
        $stmt->execute([$new_status, $id]);
    }

    // Return the new status (active/inactive) to update the frontend
    echo $new_status;
    exit;
}

/* =========================
   GET TABLES
========================= */
$tables = [];
$stmt = $conn->query("SHOW TABLES");
if ($stmt) {
    $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($allTables as $table) {
        if ($table != 'teachers' && $table != 'admins') {
            $tables[] = $table;
        }
    }
}

/* =========================
   GET TEACHERS
========================= */
$teachersStmt = $conn->query("SELECT * FROM teachers ORDER BY id DESC");
$teachers = $teachersStmt ? $teachersStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// Helper function to get student display name
function getStudentName($row) {
    if (isset($row['first_name']) && isset($row['last_name'])) {
        return htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
    } elseif (isset($row['student_name'])) {
        return htmlspecialchars($row['student_name']);
    } else {
        return '—';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title>User Management Dashboard | Responsive</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* ---------- GLOBAL RESET & VARIABLES ---------- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        :root {
            --primary-dark: #0f2b3f;
            --primary: #1a4b6e;
            --accent: #2c8b70;
            --accent-light: #54c0a1;
            --bg-light: #f4f9ff;
            --card-bg: #ffffff;
            --border: #e2edf7;
            --text-dark: #1e2f3a;
            --text-muted: #5c6f7e;
            --success: #2c8b70;
            --danger: #c25d5d;
            --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
        }

        body {
            background: linear-gradient(145deg, #e6f0f9 0%, #d9e6f2 100%);
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, sans-serif;
            padding: 1.5rem;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        /* ---------- HEADER SECTION ---------- */
        .header {
            background: var(--primary-dark);
            color: white;
            padding: 1.5rem 2rem;
            border-radius: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            box-shadow: var(--shadow);
        }

        .header-left {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 1rem;
        }

        .back-link {
            background: rgba(255,255,255,0.12);
            padding: 0.6rem 1.2rem;
            border-radius: 3rem;
            color: white;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: 0.2s;
            font-size: 0.9rem;
        }

        .back-link:hover {
            background: rgba(255,255,255,0.25);
            transform: translateX(-2px);
        }

        .header h1 {
            font-size: clamp(1.3rem, 5vw, 1.8rem);
            display: flex;
            align-items: center;
            gap: 0.7rem;
            flex-wrap: wrap;
        }

        .badge {
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1.2rem;
            border-radius: 3rem;
            font-size: 0.85rem;
            font-weight: 500;
            backdrop-filter: blur(4px);
        }

        /* ---------- CARDS ---------- */
        .card {
            background: var(--card-bg);
            border-radius: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .card-header {
            padding: 1.2rem 1.8rem;
            background: #fafeff;
            border-bottom: 1px solid var(--border);
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 0.8rem;
        }

        .card-header h2 {
            font-size: clamp(1.2rem, 4vw, 1.6rem);
            display: flex;
            align-items: center;
            gap: 0.6rem;
            color: var(--primary-dark);
        }

        .table-count {
            background: #eef3fc;
            padding: 0.3rem 1rem;
            border-radius: 2rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--primary);
        }

        /* ---------- TABLE RESPONSIVE WRAPPER ---------- */
        .table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 500px; /* ensures horizontal scroll on small screens */
        }

        th, td {
            padding: 1rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        th {
            background: #f8fbfe;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.9rem;
        }

        td {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        /* status badges */
        .status-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
            letter-spacing: 0.3px;
        }

        .status-badge.active {
            background: #e0f3ec;
            color: var(--success);
        }

        .status-badge.inactive {
            background: #ffe8e8;
            color: var(--danger);
        }

        /* action buttons */
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
        }

        .btn {
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-weight: 600;
            font-size: 0.8rem;
            cursor: pointer;
            transition: 0.15s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #f0f4f9;
            color: #1e4663;
        }

        .btn-activate {
            background: #e1f5ee;
            color: #1f6e58;
        }
        .btn-activate:hover {
            background: #c8ede2;
            transform: translateY(-1px);
        }

        .btn-deactivate {
            background: #ffe6e6;
            color: #b13e3e;
        }
        .btn-deactivate:hover {
            background: #ffd6d6;
            transform: translateY(-1px);
        }

        .empty-state {
            text-align: center;
            padding: 2rem !important;
            color: #8aa0b0;
            font-style: italic;
        }

        /* ---------- RESPONSIVE MEDIA QUERIES ---------- */
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .header {
                padding: 1.2rem 1.5rem;
                flex-direction: column;
                align-items: flex-start;
            }

            .header-left {
                width: 100%;
                justify-content: space-between;
            }

            .card-header {
                padding: 1rem 1.2rem;
            }

            th, td {
                padding: 0.8rem 0.8rem;
            }

            .btn {
                padding: 0.4rem 0.8rem;
                font-size: 0.75rem;
            }

            .action-buttons {
                gap: 0.4rem;
            }
        }

        @media (max-width: 480px) {
            .header-left {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .back-link {
                align-self: flex-start;
            }

            .btn {
                padding: 0.45rem 0.9rem;
            }

            .action-buttons {
                flex-direction: column;
                gap: 0.4rem;
            }

            .action-buttons .btn {
                width: 100%;
                justify-content: center;
            }

            .status-badge {
                padding: 0.25rem 0.6rem;
                font-size: 0.7rem;
            }
        }

        /* touch-friendly active states */
        .btn:active {
            transform: scale(0.96);
        }

        /* loading spinner */
        .fa-spinner {
            margin-right: 0.3rem;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="header-left">
            <a href="admin_panel.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Admin Panel</a>
            <h1><i class="fas fa-user-shield"></i> User Management Dashboard</h1>
        </div>
        <div class="badge"><i class="fas fa-database"></i> Manage activation status</div>
    </div>

    <!-- TEACHERS SECTION -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-chalkboard-user"></i> Teachers</h2>
            <span class="table-count"><?= count($teachers) ?> registered</span>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($teachers) > 0): ?>
                        <?php foreach($teachers as $teacher): ?>
                            <tr id="teacher-<?= $teacher['id'] ?>">
                                <td><?= htmlspecialchars($teacher['id']) ?></td>
                                <td><?= htmlspecialchars($teacher['fullname']) ?></td>
                                <td><?= htmlspecialchars($teacher['username']) ?></td>
                                <td class="status-cell">
                                    <span class="status-badge <?= $teacher['status'] == 'active' ? 'active' : 'inactive' ?>">
                                        <?= htmlspecialchars($teacher['status']) ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <button class="btn btn-activate" onclick="changeStatus('teacher', <?= $teacher['id'] ?>, 'activate')">
                                        <i class="fas fa-check-circle"></i> Activate
                                    </button>
                                    <button class="btn btn-deactivate" onclick="changeStatus('teacher', <?= $teacher['id'] ?>, 'deactivate')">
                                        <i class="fas fa-ban"></i> Deactivate
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="empty-state">No teachers found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- STUDENTS SECTION (Dynamic Tables) -->
    <?php foreach($tables as $table): ?>
        <?php
        $studentsStmt = $conn->query("SELECT * FROM `$table` ORDER BY id DESC");
        $students = $studentsStmt ? $studentsStmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $studentCount = count($students);
        ?>
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-users"></i> <?= htmlspecialchars(ucfirst($table)) ?></h2>
                <span class="table-count"><?= $studentCount ?> students</span>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student Name</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($studentCount > 0): ?>
                            <?php foreach($students as $student): ?>
                                <tr id="student-<?= $table.'-'.$student['id'] ?>">
                                    <td><?= htmlspecialchars($student['id']) ?></td>
                                    <td><?= getStudentName($student) ?></td>
                                    <td class="status-cell">
                                        <span class="status-badge <?= ($student['status'] ?? 'inactive') == 'active' ? 'active' : 'inactive' ?>">
                                            <?= htmlspecialchars($student['status'] ?? 'inactive') ?>
                                        </span>
                                    </td>
                                    <td class="action-buttons">
                                        <button class="btn btn-activate" onclick="changeStatus('student', <?= $student['id'] ?>, 'activate', '<?= $table ?>')">
                                            <i class="fas fa-check-circle"></i> Activate
                                        </button>
                                        <button class="btn btn-deactivate" onclick="changeStatus('student', <?= $student['id'] ?>, 'deactivate', '<?= $table ?>')">
                                            <i class="fas fa-ban"></i> Deactivate
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="empty-state">No students in this table.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
function changeStatus(type, id, action, table = '') {
    let button = event.target.closest('button');
    if (!button) return;
    let originalHtml = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-pulse"></i> Loading...';
    button.disabled = true;

    $.post('', {
        action_type: 'update',
        type: type,
        id: id,
        action: action,
        table: table
    }, function(response) {
        let rowId = (type === 'teacher') ? '#teacher-' + id : '#student-' + table + '-' + id;
        let statusCell = $(rowId + ' .status-cell');
        let newStatus = response.trim(); // 'active' or 'inactive'

        statusCell.html('<span class="status-badge ' + newStatus + '">' + newStatus + '</span>');

        button.innerHTML = originalHtml;
        button.disabled = false;
    }).fail(function() {
        alert('Error updating status. Please try again.');
        button.innerHTML = originalHtml;
        button.disabled = false;
    });
}
</script>
</body>
</html>