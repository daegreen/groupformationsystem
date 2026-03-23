<?php
session_start();
include 'conn.php'; // Must return a PDO object named $conn

// ===================== Helper Functions =====================
function safeTable($name){
    return preg_replace('/[^a-zA-Z0-9_]/', '', strtolower(trim($name)));
}

function columnExists($conn, $table, $column) {
    $stmt = $conn->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$column]);
    return $stmt->rowCount() > 0;
}

// ===================== PDF Export Handling =====================
if (isset($_GET['export_pdf'])) {
    // Retrieve groups and teacher name from session
    $groups = $_SESSION['groups'] ?? [];
    $teacher_name = $_SESSION['groups_teacher_name'] ?? 'Teacher';

    if (empty($groups)) {
        die("<h3>No groups found. Please generate groups first.</h3>");
    }

    // Try to use TCPDF if available
    if (class_exists('TCPDF')) {
        // Define TCPDF constants if they are not already defined
        if (!defined('PDF_PAGE_ORIENTATION')) define('PDF_PAGE_ORIENTATION', 'P');
        if (!defined('PDF_UNIT')) define('PDF_UNIT', 'mm');
        if (!defined('PDF_PAGE_FORMAT')) define('PDF_PAGE_FORMAT', 'A4');
        if (!defined('PDF_CREATOR')) define('PDF_CREATOR', 'Group Management System');

        // ----- PDF generation with TCPDF -----
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Group Management System');
        $pdf->SetTitle('Generated Groups');
        $pdf->SetSubject('Groups for ' . $teacher_name);

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Add a page
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 12);

        // Title
        $html = '<h1>Generated Groups for ' . htmlspecialchars($teacher_name) . '</h1>';
        $html .= '<p>Total groups: ' . count($groups) . '</p>';

        // Build table of groups
        foreach ($groups as $idx => $group) {
            $html .= '<h3>Group ' . ($idx + 1) . ' (' . count($group) . ' members)</h3>';

            // Find chief (first 'interested' student)
            $chief = null;
            foreach ($group as $member) {
                if (strtolower(trim($member['interested'])) == 'yes') {
                    $chief = $member;
                    break;
                }
            }
            if (!$chief && !empty($group)) $chief = $group[0];

            if ($chief) {
                $html .= '<p><strong>👑 Chief:</strong> ' . htmlspecialchars($chief['first_name'] . ' ' . $chief['last_name']) . '</p>';
            }

            $html .= '<ul>';
            foreach ($group as $member) {
                $html .= '<li>👤 ' . htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) . '</li>';
            }
            $html .= '</ul><br>';
        }

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('groups.pdf', 'D'); // Force download
        exit;
    } else {
        // ----- Fallback: print‑friendly HTML -----
        header('Content-Type: text/html; charset=utf-8');
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Groups for <?= htmlspecialchars($teacher_name) ?></title>
            <style>
                @media print {
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .group-card { page-break-inside: avoid; margin-bottom: 20px; border: 1px solid #ccc; padding: 10px; border-radius: 8px; }
                    .chief-badge { font-weight: bold; color: #b45f1b; }
                    .member-list { margin-top: 10px; }
                    .member { margin-left: 15px; }
                }
                body { font-family: Arial, sans-serif; margin: 20px; }
                .group-card { margin-bottom: 20px; border: 1px solid #ddd; padding: 10px; border-radius: 8px; background: #f9f9f9; }
                .chief-badge { font-weight: bold; color: #b45f1b; }
                .member-list { margin-top: 10px; }
                .member { margin-left: 15px; }
                button { margin-top: 20px; padding: 8px 16px; cursor: pointer; }
            </style>
        </head>
        <body>
            <h1>Generated Groups for <?= htmlspecialchars($teacher_name) ?></h1>
            <p>Total groups: <?= count($groups) ?></p>

            <?php foreach ($groups as $idx => $group): ?>
                <div class="group-card">
                    <h3>Group <?= $idx + 1 ?> (<?= count($group) ?> members)</h3>
                    <?php
                        $chief = null;
                        foreach ($group as $member) {
                            if (strtolower(trim($member['interested'])) == 'yes') {
                                $chief = $member;
                                break;
                            }
                        }
                        if (!$chief && !empty($group)) $chief = $group[0];
                    ?>
                    <?php if ($chief): ?>
                        <div class="chief-badge">👑 Chief: <?= htmlspecialchars($chief['first_name'] . ' ' . $chief['last_name']) ?></div>
                    <?php endif; ?>
                    <div class="member-list">
                        <?php foreach ($group as $member): ?>
                            <div class="member">👤 <?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

                       <script>
                // Automatically open the print dialog after page loads
                window.onload = function() {
                    window.print();
                };
            </script>
        </body>
        </html>
        <?php
        exit;
    }
}

// ===================== Determine User Role =====================
$is_admin = isset($_SESSION['admin_id']);
$is_teacher = isset($_SESSION['teacher_id']);

// Default variables
$selected_teacher_id = null;
$selected_teacher_name = '';
$teachers_list = [];

if (!$is_admin && !$is_teacher) {
    header('Location: login.php');
    exit();
}

// If admin, fetch all teachers for dropdown
if ($is_admin) {
    $stmt = $conn->prepare("SELECT id, fullname, username FROM teachers ORDER BY fullname");
    $stmt->execute();
    $teachers_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get selected teacher from POST or default to first (for initial page load)
    $selected_teacher_id = isset($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : ($teachers_list[0]['id'] ?? null);
    if ($selected_teacher_id) {
        $stmt = $conn->prepare("SELECT fullname FROM teachers WHERE id = ?");
        $stmt->execute([$selected_teacher_id]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
        $selected_teacher_name = $teacher['fullname'] ?? 'Selected Teacher';
    }
} else {
    // Teacher is logged in
    $selected_teacher_id = $_SESSION['teacher_id'];
    $selected_teacher_name = $_SESSION['teacher_username'] ?? 'Teacher';
}

$selected_table = 'students';

// ===================== AJAX Handling =====================
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($is_ajax) {
    // Handle AJAX requests
    if (isset($_POST['view_table'])) {
        // Determine the teacher ID from POST (for admin) or session
        $teacher_id = $is_admin ? (int)$_POST['teacher_id'] : $_SESSION['teacher_id'];
        // Fetch teacher name if needed for display
        if ($is_admin) {
            $stmt = $conn->prepare("SELECT fullname FROM teachers WHERE id = ?");
            $stmt->execute([$teacher_id]);
            $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
            $teacher_name = $teacher['fullname'] ?? 'Selected Teacher';
        } else {
            $teacher_name = $_SESSION['teacher_username'] ?? 'Teacher';
        }
        echo getTableDataHTML($conn, $selected_table, $teacher_id, $teacher_name);
        exit;
    } elseif (isset($_POST['generate'])) {
        // For group generation, we call the same function; it will use POST data
        echo getGroupsHTML($conn, $selected_table, $is_admin);
        exit;
    }
}

// ===================== Helper Functions for HTML generation =====================
function getTableDataHTML($conn, $table, $teacher_id, $teacher_name) {
    if (!in_array($table, $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN))) {
        return "<div class='alert alert-error'>❌ Table '$table' does not exist.</div>";
    }
    $hasTeacherId = columnExists($conn, $table, 'teacher_id');
    if (!$hasTeacherId) {
        return "<div class='alert alert-error'>❌ Table '$table' does not have a teacher_id column. Cannot filter by teacher.</div>";
    }
    $hasStatus = columnExists($conn, $table, 'status');
    if ($hasStatus) {
        $sql = "SELECT * FROM `$table` WHERE teacher_id = ? AND status = 'Active'";
    } else {
        $sql = "SELECT * FROM `$table` WHERE teacher_id = ?";
    }
    $stmt = $conn->prepare($sql);
    $stmt->execute([$teacher_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($rows) > 0) {
        $html = "<div class='form-card'>";
        $html .= "<h3>📋 Students of <b>" . htmlspecialchars($teacher_name) . "</b> in table: <b>$table</b></h3>";
        $html .= "<div class='table-wrapper'>";
        $html .= "<table class='data-table'>";
        $html .= "<thead>
                      <tr>
                        <th>ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Reg Number</th>
                        <th>Gender</th>
                        <th>Interested</th>
                      </tr>
                  </thead><tbody>";
        foreach ($rows as $row) {
            $html .= "<tr>
                        <td>" . htmlspecialchars($row['id']) . "</td>
                        <td>" . htmlspecialchars($row['first_name']) . "</td>
                        <td>" . htmlspecialchars($row['last_name']) . "</td>
                        <td>" . htmlspecialchars($row['reg_number']) . "</td>
                        <td>" . htmlspecialchars($row['gender']) . "</td>
                        <td>" . htmlspecialchars($row['interested']) . "</td>
                      </tr>";
        }
        $html .= "</tbody></table></div></div>";
        return $html;
    } else {
        return "<div class='alert alert-info'>ℹ️ No students found for this teacher.</div>";
    }
}

function getGroupsHTML($conn, $table, $is_admin) {
    // Validate required POST data
    if (!isset($_POST['group_size']) || !is_numeric($_POST['group_size'])) {
        return "<div class='alert alert-error'>❌ Group size is required and must be a number.</div>";
    }
    $size = (int)$_POST['group_size'];

    if ($is_admin) {
        if (!isset($_POST['teacher_id']) || !is_numeric($_POST['teacher_id'])) {
            return "<div class='alert alert-error'>❌ Teacher selection is required.</div>";
        }
        $teacher_id = (int)$_POST['teacher_id'];
        // Fetch teacher name for display
        $stmt = $conn->prepare("SELECT fullname FROM teachers WHERE id = ?");
        $stmt->execute([$teacher_id]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
        $teacher_name = $teacher['fullname'] ?? 'Selected Teacher';
    } else {
        $teacher_id = $_SESSION['teacher_id'];
        $teacher_name = $_SESSION['teacher_username'] ?? 'Teacher';
    }

    if ($size < 2 || $size > 10) {
        return "<div class='alert alert-error'>❌ Group size must be between 2 and 10.</div>";
    }
    if (!in_array($table, $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN))) {
        return "<div class='alert alert-error'>❌ Table '$table' does not exist.</div>";
    }
    $hasTeacherId = columnExists($conn, $table, 'teacher_id');
    if (!$hasTeacherId) {
        return "<div class='alert alert-error'>❌ Table '$table' does not have a teacher_id column. Cannot filter by teacher.</div>";
    }
    $hasStatus = columnExists($conn, $table, 'status');
    if ($hasStatus) {
        $stmt = $conn->prepare("SELECT * FROM `$table` WHERE teacher_id = ? AND status = 'Active'");
    } else {
        $stmt = $conn->prepare("SELECT * FROM `$table` WHERE teacher_id = ?");
    }
    $stmt->execute([$teacher_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($rows) == 0) {
        return "<div class='alert alert-info'>ℹ️ No students found for this teacher to generate groups.</div>";
    }

    // Separate interested vs others
    $interested = [];
    $others = [];
    foreach ($rows as $row) {
        if (strtolower(trim($row['interested'])) == 'yes') {
            $interested[] = $row;
        } else {
            $others[] = $row;
        }
    }

    shuffle($interested);
    shuffle($others);

    $total = count($interested) + count($others);
    $group_count = ceil($total / $size);
    $groups = array_fill(0, $group_count, []);

    $i = 0;
    foreach ($interested as $student) {
        $groups[$i % $group_count][] = $student;
        $i++;
    }
    $i = 0;
    foreach ($others as $student) {
        $groups[$i % $group_count][] = $student;
        $i++;
    }

    // Store groups and teacher name in session for PDF export
    $_SESSION['groups'] = $groups;
    $_SESSION['groups_teacher_name'] = $teacher_name;

    $output = "<div class='groups-container'>";
    $output .= "<h2>🎯 Generated Groups for " . htmlspecialchars($teacher_name) . "</h2>";
    $output .= "<div class='groups-grid'>";

    foreach ($groups as $index => $group) {
        $output .= "<div class='group-card'>";
        $output .= "<div class='group-header'>Group " . ($index + 1) . " <span class='member-count'>(" . count($group) . " members)</span></div>";

        $chief = null;
        foreach ($group as $member) {
            if (strtolower(trim($member['interested'])) == 'yes') {
                $chief = $member;
                break;
            }
        }
        if (!$chief && !empty($group)) $chief = $group[0];

        if ($chief) {
            $output .= "<div class='chief-badge'>👑 Chief: " . htmlspecialchars($chief['first_name'] . ' ' . $chief['last_name']) . "</div>";
        }

        $output .= "<div class='member-list'>";
        foreach ($group as $member) {
            $output .= "<div class='member'>👤 " . htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) . "</div>";
        }
        $output .= "</div></div>";
    }

    $output .= "</div>"; // groups-grid
    // Updated PDF link: now points to this same file with a GET parameter
    $output .= "<div class='export-pdf'><a href='?export_pdf=1' class='export-btn'>📄 Export Groups to PDF</a></div>";
    $output .= "</div>"; // groups-container

    return $output;
}

// ===================== Non-AJAX page load: generate initial content =====================
$table_data = '';
$groups_html = '';

// Only generate on initial page load (non-AJAX)
if (isset($_POST['view_table']) && !$is_ajax) {
    $table_data = getTableDataHTML($conn, $selected_table, $selected_teacher_id, $selected_teacher_name);
}
if (isset($_POST['generate']) && !$is_ajax) {
    $groups_html = getGroupsHTML($conn, $selected_table, $is_admin);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    <title><?= $is_admin ? 'Admin · Group Management' : 'Teacher Dashboard · Group Formation' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* (CSS unchanged) */
        * { margin:0; padding:0; box-sizing:border-box; }
        :root {
            --primary: #1e4663;
            --primary-light: #2c5a7a;
            --accent: #2c8b70;
            --bg-light: #f4f9ff;
            --card-bg: #ffffff;
            --border: #dde7f0;
            --text-dark: #1f2e3a;
            --text-muted: #5c6f7e;
            --shadow: 0 10px 25px -5px rgba(0,0,0,0.05), 0 8px 10px -6px rgba(0,0,0,0.02);
        }
        body {
            background: linear-gradient(145deg, #e6f0f9 0%, #d9e6f2 100%);
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            padding: 1rem;
            min-height: 100vh;
        }
        .dashboard-container { max-width: 1400px; margin: 0 auto; }
        .dashboard-header {
            background: var(--primary);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            box-shadow: var(--shadow);
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            font-size: 1rem;
        }
        .logout-btn {
            background: rgba(255,255,255,0.15);
            padding: 0.5rem 1.2rem;
            border-radius: 2rem;
            text-decoration: none;
            color: white;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: 0.2s;
        }
        .logout-btn:hover { background: rgba(255,255,255,0.3); }
        .form-card, .groups-container, .alert {
            background: var(--card-bg);
            border-radius: 1.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }
        .form-card h2, .form-card h3, .groups-container h2 {
            font-size: 1.5rem;
            margin-bottom: 1.2rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.6rem;
            flex-wrap: wrap;
        }
        .form-group { margin-bottom: 1.2rem; }
        label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: block;
            color: var(--text-dark);
        }
        select, input[type="number"], input[type="text"] {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--border);
            border-radius: 2rem;
            font-size: 1rem;
            background: #fff;
            transition: 0.2s;
        }
        select:focus, input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(44,139,112,0.2);
        }
        .btn, .export-btn {
            background: var(--accent);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 2rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
        }
        .btn-secondary {
            background: #eef3fc;
            color: var(--primary);
            border: 1px solid var(--border);
        }
        .btn:hover, .export-btn:hover {
            background: #1e6e58;
            transform: translateY(-2px);
        }
        .alert { border-left: 5px solid; }
        .alert-error { border-left-color: #d9534f; background: #fdf7f7; color: #a94442; }
        .alert-info { border-left-color: #5bc0de; background: #f4f8fc; color: #31708f; }
        .table-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .data-table { width: 100%; border-collapse: collapse; min-width: 500px; }
        .data-table th, .data-table td { padding: 0.8rem; text-align: left; border-bottom: 1px solid var(--border); }
        .data-table th { background: #f8fbfe; font-weight: 600; color: var(--primary); }
        .groups-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; margin: 1.5rem 0; }
        .group-card {
            background: #fafeff;
            border-radius: 1.2rem;
            padding: 1rem;
            border: 1px solid var(--border);
            transition: 0.2s;
        }
        .group-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        .group-header {
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 0.8rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--accent);
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .member-count { font-size: 0.8rem; font-weight: normal; background: #eef3fc; padding: 0.2rem 0.6rem; border-radius: 1rem; }
        .chief-badge {
            background: #fff3e0;
            padding: 0.4rem 0.8rem;
            border-radius: 2rem;
            margin: 0.5rem 0;
            font-size: 0.85rem;
            font-weight: 600;
            color: #b45f1b;
            display: inline-block;
        }
        .member-list { margin-top: 0.8rem; }
        .member { padding: 0.3rem 0; border-bottom: 1px dotted #e2e8f0; font-size: 0.9rem; }
       .export-btn{
    background:#123a5c;
    color:white;
    text-decoration:none;
    padding:12px 22px;
    border-radius:30px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    font-weight:600;
    font-size:15px;
    transition:all 0.3s ease;
    width:auto;
    max-width:100%;
    min-height:48px;
    box-shadow:0 4px 12px rgba(0,0,0,0.12);
    flex-wrap:wrap;
    text-align:center;
}

.export-btn:hover{
    background:#0d2c47;
    transform:translateY(-2px);
}

.export-btn:active{
    transform:scale(0.98);
}

@media(max-width:768px){
    .export-btn{
        width:100%;
        font-size:14px;
        padding:12px 18px;
    }
}

@media(max-width:480px){
    .export-btn{
        width:100%;
        font-size:13px;
        padding:11px 14px;
        border-radius:24px;
    }
}
        @media (max-width: 480px) {
            .groups-grid { grid-template-columns: 1fr; }
            .data-table th, .data-table td { padding: 0.6rem; font-size: 0.85rem; }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <div class="dashboard-header">
        <div class="user-info">
            <i class="fas <?= $is_admin ? 'fa-user-shield' : 'fa-chalkboard-teacher' ?>"></i>
            <span>Logged in as: <strong><?= htmlspecialchars($is_admin ? 'Admin' : ($_SESSION['teacher_username'] ?? 'Teacher')) ?></strong></span>
            <?php if ($is_admin && $selected_teacher_name): ?>
                <span id="viewingBadge" class="badge" style="background: rgba(255,255,255,0.2); padding:0.2rem 0.6rem; border-radius:1rem;">Viewing: <?= htmlspecialchars($selected_teacher_name) ?></span>
            <?php endif; ?>
        </div>
        <a href="<?= $is_admin ? 'admin_panel.php' : 'logout.t.php' ?>" class="logout-btn"><i class="fas fa-sign-out-alt"></i> BACK</a>
    </div>

    <!-- Group Generator Card (main form) -->
    <div class="form-card">
        <h2><i class="fas fa-users"></i> Smart Group Generator</h2>
        <form id="mainForm" method="POST">
            <?php if ($is_admin): ?>
                <div class="form-group">
                    <label><i class="fas fa-chalkboard-user"></i> Select Teacher</label>
                    <select name="teacher_id" id="teacher_id" required>
                        <?php foreach ($teachers_list as $teacher): ?>
                            <option value="<?= $teacher['id'] ?>" <?= $selected_teacher_id == $teacher['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($teacher['fullname'] . ' (' . $teacher['username'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label><i class="fas fa-table"></i> Table</label>
                <div style="background: #f0f4fa; padding: 0.8rem 1rem; border-radius: 2rem; border:1px solid #dce5ef;">
                    <i class="fas fa-database"></i> students
                </div>
                <input type="hidden" name="table_name" value="students">
            </div>

            <!-- View Table Button -->
            <div class="form-group">
                <button type="button" id="viewTableBtn" class="btn btn-secondary"><i class="fas fa-eye"></i> View Table Data</button>
            </div>
        </form>

        <!-- Table Data Display (will be updated via AJAX) -->
        <div id="tableDataContainer">
            <?= $table_data ?>
        </div>
    </div>

    <!-- Separate Section for Group Generation -->
    <div class="form-card">
        <h2><i class="fas fa-layer-group"></i> Generate Balanced Groups</h2>
        <form id="groupForm" method="POST">
            <div class="form-group">
                <label><i class="fas fa-users"></i> Group Size (2–10)</label>
                <input type="number" name="group_size" id="group_size" min="2" max="10" placeholder="Enter group size" required>
            </div>
            <div class="form-group">
                <button type="submit" id="generateBtn" class="btn"><i class="fas fa-magic"></i> Generate Groups</button>
            </div>
        </form>

        <!-- Groups Display (will be updated via AJAX) -->
        <div id="groupsContainer">
            <?= $groups_html ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Elements
        const viewTableBtn = document.getElementById('viewTableBtn');
        const teacherSelect = document.getElementById('teacher_id');
        const tableDataContainer = document.getElementById('tableDataContainer');
        const groupForm = document.getElementById('groupForm');
        const generateBtn = document.getElementById('generateBtn');
        const groupsContainer = document.getElementById('groupsContainer');
        const viewingBadge = document.getElementById('viewingBadge');

        // Helper: send AJAX POST request
        function sendAjax(formData, targetContainer, loadingHtml = '<div class="alert alert-info">Loading...</div>') {
            targetContainer.innerHTML = loadingHtml;
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                targetContainer.innerHTML = html;
            })
            .catch(error => {
                console.error('Error:', error);
                targetContainer.innerHTML = '<div class="alert alert-error">❌ An error occurred. Please try again.</div>';
            });
        }

        // Handle View Table Data click
        if (viewTableBtn) {
            viewTableBtn.addEventListener('click', function() {
                let formData = new FormData();
                formData.append('view_table', '1');
                if (teacherSelect) {
                    formData.append('teacher_id', teacherSelect.value);
                }
                sendAjax(formData, tableDataContainer, '<div class="alert alert-info">Loading table data...</div>');
            });
        }

        // Handle teacher dropdown change (for admin) – update badge and reload table data
        if (teacherSelect) {
            teacherSelect.addEventListener('change', function() {
                // Update the viewing badge with the selected teacher's name
                if (viewingBadge) {
                    const selectedOption = teacherSelect.options[teacherSelect.selectedIndex];
                    let teacherName = selectedOption.text;
                    // Remove the part in parentheses (username) to keep only full name
                    const parenIndex = teacherName.indexOf('(');
                    if (parenIndex > -1) {
                        teacherName = teacherName.substring(0, parenIndex).trim();
                    }
                    viewingBadge.textContent = 'Viewing: ' + teacherName;
                }

                // Then load table data via AJAX
                let formData = new FormData();
                formData.append('view_table', '1');
                formData.append('teacher_id', teacherSelect.value);
                sendAjax(formData, tableDataContainer, '<div class="alert alert-info">Loading table data...</div>');
            });
        }

        // Handle group generation
        if (groupForm) {
            groupForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const groupSize = document.getElementById('group_size').value;
                if (!groupSize || groupSize < 2 || groupSize > 10) {
                    groupsContainer.innerHTML = '<div class="alert alert-error">❌ Group size must be between 2 and 10.</div>';
                    return;
                }

                generateBtn.innerHTML = '<i class="fas fa-spinner fa-pulse"></i> Generating...';
                generateBtn.disabled = true;

                let formData = new FormData();
                formData.append('generate', '1');
                formData.append('group_size', groupSize);
                if (teacherSelect) {
                    formData.append('teacher_id', teacherSelect.value);
                }

                fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                })
                .then(response => response.text())
                .then(html => {
                    groupsContainer.innerHTML = html;
                    generateBtn.innerHTML = '<i class="fas fa-magic"></i> Generate Groups';
                    generateBtn.disabled = false;
                })
                .catch(error => {
                    console.error('Error:', error);
                    groupsContainer.innerHTML = '<div class="alert alert-error">❌ An error occurred. Please try again.</div>';
                    generateBtn.innerHTML = '<i class="fas fa-magic"></i> Generate Groups';
                    generateBtn.disabled = false;
                });
            });
        }
    });
</script>
</body>
</html>