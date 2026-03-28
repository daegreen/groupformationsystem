<?php
session_start();
include 'conn.php'; // Must return a PDO object named $conn

/* =======================
   SAFE TABLE NAME FUNCTION
======================= */
function safeTable($name){
    return preg_replace('/[^a-zA-Z0-9_]/', '', strtolower(trim($name)));
}

/* =======================
   Helper: check if column exists
======================= */
function columnExists($conn, $table, $column) {
    $stmt = $conn->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$column]);
    return $stmt->rowCount() > 0;
}

/* =======================
   Helper: get teacher's registration window
======================= */
function getTeacherWindow($conn, $teacherId) {
    $stmt = $conn->prepare("SELECT start_time, end_time FROM teacher_registration_windows WHERE teacher_id = ?");
    $stmt->execute([$teacherId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/* =======================
   Load tables (for existence check only)
======================= */
$tables = [];
$stmt = $conn->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

/* =======================
   Handle Registration Window Setting
======================= */
$window_message = '';
$current_window = getTeacherWindow($conn, $_SESSION['teacher_id']);

if (isset($_POST['set_window'])) {
    $start = $_POST['start_time'] ?? '';
    $end   = $_POST['end_time'] ?? '';

    if (empty($start) || empty($end)) {
        $window_message = "<div class='alert alert-error'>❌ Please fill both start and end time.</div>";
    } else {
        try {
            // Delete any existing window for this teacher
            $del = $conn->prepare("DELETE FROM teacher_registration_windows WHERE teacher_id = ?");
            $del->execute([$_SESSION['teacher_id']]);

            // Insert new window
            $ins = $conn->prepare("INSERT INTO teacher_registration_windows (teacher_id, start_time, end_time) VALUES (?, ?, ?)");
            $ins->execute([$_SESSION['teacher_id'], $start, $end]);

            $window_message = "<div class='alert alert-info'>✅ Registration window set successfully!</div>";
            $current_window = ['start_time' => $start, 'end_time' => $end];
        } catch (PDOException $e) {
            $window_message = "<div class='alert alert-error'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

/* =======================
   View selected table data (only for current teacher)
======================= */
$table_data = '';
// Force selected table to 'students'
$selected_table = 'students';

if(isset($_POST['view_table'])){
    if(in_array($selected_table, $tables)){
        // Check if teacher_id column exists
        $hasTeacherId = columnExists($conn, $selected_table, 'teacher_id');
        if (!$hasTeacherId) {
            $table_data = "<div class='alert alert-error'>❌ Table '$selected_table' does not have a teacher_id column. Cannot filter by teacher.</div>";
        } else {
            $hasStatus = columnExists($conn, $selected_table, 'status');
            // Build query: filter by teacher_id and optionally status
            if ($hasStatus) {
                $sql = "SELECT * FROM `$selected_table` WHERE teacher_id = ? AND status = 'Active'";
            } else {
                $sql = "SELECT * FROM `$selected_table` WHERE teacher_id = ?";
            }
            $stmt = $conn->prepare($sql);
            $stmt->execute([$_SESSION['teacher_id']]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if(count($rows) > 0){
                $table_data .= "<div class='data-section'>";
                $table_data .= "<h3>📋 Your Students in Table: <b>$selected_table</b></h3>";
                $table_data .= "<div class='table-wrapper'>";
                $table_data .= "<table class='data-table'>";
                $table_data .= "<thead><tr>
                            <th>ID</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Reg Number</th>
                            <th>Gender</th>
                            <th>Interested</th>
                          </tr></thead><tbody>";

                foreach($rows as $row){
                    $table_data .= "<tr>
                                        <td>".htmlspecialchars($row['id'])."</td>
                                        <td>".htmlspecialchars($row['first_name'])."</td>
                                        <td>".htmlspecialchars($row['last_name'])."</td>
                                        <td>".htmlspecialchars($row['reg_number'])."</td>
                                        <td>".htmlspecialchars($row['gender'])."</td>
                                        <td>".htmlspecialchars($row['interested'])."</td>
                                    </tr>";
                }

                $table_data .= "</tbody></table></div></div>";
            } else {
                $table_data = "<div class='alert alert-info'>ℹ️ No students found for you in this table.</div>";
            }
        }
    } else {
        $table_data = "<div class='alert alert-error'>❌ Table '$selected_table' does not exist.</div>";
    }
}

/* =======================
   Smart Group Generation (only for current teacher)
======================= */
$groups_html = '';

if(isset($_POST['generate'])){
    // Check if group_size is provided
    if (!isset($_POST['group_size']) || trim($_POST['group_size']) === '') {
        $groups_html = "<div class='alert alert-error'>❌ Please enter a group size (2-10).</div>";
    } else {
        $size = (int)$_POST['group_size'];
        $selected_table = 'students'; // Fixed table name

        if($size < 2 || $size > 10){
            $groups_html = "<div class='alert alert-error'>❌ Group size must be 2-10</div>";
        }
        else if(in_array($selected_table, $tables)){
            // Check if teacher_id column exists
            $hasTeacherId = columnExists($conn, $selected_table, 'teacher_id');
            if (!$hasTeacherId) {
                $groups_html = "<div class='alert alert-error'>❌ Table '$selected_table' does not have a teacher_id column. Cannot filter by teacher.</div>";
            } else {
                $hasStatus = columnExists($conn, $selected_table, 'status');
                // Fetch only rows belonging to this teacher (and active if status column exists)
                if ($hasStatus) {
                    $stmt = $conn->prepare("SELECT * FROM `$selected_table` WHERE teacher_id = ? AND status = 'Active'");
                } else {
                    $stmt = $conn->prepare("SELECT * FROM `$selected_table` WHERE teacher_id = ?");
                }
                $stmt->execute([$_SESSION['teacher_id']]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if(count($rows) == 0){
                    $groups_html = "<div class='alert alert-info'>ℹ️ No students found for you to generate groups.</div>";
                } else {
                    $interested = [];
                    $others = [];

                    foreach($rows as $row){
                        if(strtolower(trim($row['interested'])) == 'yes'){
                            $interested[] = $row;
                        } else {
                            $others[] = $row;
                        }
                    }

                    shuffle($interested);
                    shuffle($others);

                    $total_students = count($interested)+count($others);
                    $group_count = ceil($total_students/$size);

                    $groups = array_fill(0,$group_count,[]);

                    $i=0;
                    foreach($interested as $student){
                        $groups[$i % $group_count][] = $student;
                        $i++;
                    }

                    $i=0;
                    foreach($others as $student){
                        $groups[$i % $group_count][] = $student;
                        $i++;
                    }

                    $_SESSION['groups'] = $groups;

                    $groups_html .= "<div class='groups-container'>";
                    $groups_html .= "<h2>🎯 Generated Groups</h2>";
                    $groups_html .= "<div class='groups-grid'>";

                    foreach($groups as $index => $group){

                        $groups_html .= "<div class='group-card'>";
                        $groups_html .= "<div class='group-header'>Group ".($index+1)." <span class='member-count'>(" . count($group) . " members)</span></div>";

                        $chief = null;

                        foreach($group as $member){
                            if(strtolower(trim($member['interested'])) == 'yes'){
                                $chief = $member;
                                break;
                            }
                        }

                        if(!$chief && !empty($group)) $chief = $group[0];

                        if($chief){
                            $groups_html .= "<div class='chief-badge'>👑 Chief: ".htmlspecialchars($chief['first_name'])." ".htmlspecialchars($chief['last_name'])."</div>";
                        }

                        $groups_html .= "<div class='member-list'>";
                        foreach($group as $member){
                            $groups_html .= "<div class='member'>👤 ".htmlspecialchars($member['first_name'])." ".htmlspecialchars($member['last_name'])."</div>";
                        }
                        $groups_html .= "</div>";

                        $groups_html .= "</div>";
                    }

                    $groups_html .= "</div>";
                    // Print button
                    $groups_html .= "<div class='export-pdf'><button class='export-btn' onclick='window.print()'>📄 Export Groups to PDF</button></div>";
                    $groups_html .= "</div>";
                }
            }
        } else {
            $groups_html = "<div class='alert alert-error'>❌ Table '$selected_table' does not exist.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    <title>Teacher Dashboard · Group Formation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* ---------- GLOBAL RESET & VARIABLES ---------- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        :root {
            --primary: #1e4663;
            --primary-light: #2c5a7a;
            --accent: #2c8b70;
            --accent-light: #54c0a1;
            --bg-light: #f4f9ff;
            --card-bg: #ffffff;
            --border: #dde7f0;
            --text-dark: #1f2e3a;
            --text-muted: #5c6f7e;
            --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
        }

        body {
            background: linear-gradient(145deg, #e6f0f9 0%, #d9e6f2 100%);
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            padding: 1rem;
            min-height: 100vh;
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* ---------- HEADER ---------- */
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
            flex-wrap: wrap;
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

        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-1px);
        }

        /* ---------- CARDS & FORMS ---------- */
        .form-card, .groups-container, .alert {
            background: var(--card-bg);
            border-radius: 1.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        .form-card h2, .form-card h3, .groups-container h2 {
            font-size: clamp(1.2rem, 5vw, 1.8rem);
            margin-bottom: 1.2rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.6rem;
            flex-wrap: wrap;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: block;
            color: var(--text-dark);
        }

        select, input[type="number"], input[type="text"], input[type="datetime-local"] {
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

        .btn-secondary:hover {
            background: #e2ecf5;
            transform: translateY(-2px);
        }

        /* ---------- ALERTS ---------- */
        .alert {
            border-left: 5px solid;
            padding: 1rem;
        }
        .alert-error {
            border-left-color: #d9534f;
            background: #fdf7f7;
            color: #a94442;
        }
        .alert-info {
            border-left-color: #5bc0de;
            background: #f4f8fc;
            color: #31708f;
        }

        /* ---------- TABLE (RESPONSIVE) ---------- */
        .table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-top: 1rem;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 500px;
        }

        .data-table th, .data-table td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .data-table th {
            background: #f8fbfe;
            font-weight: 600;
            color: var(--primary);
        }

        .data-section {
            margin-top: 1rem;
            margin-bottom: 1.5rem;
        }

        /* ---------- GROUPS GRID (FULLY RESPONSIVE) ---------- */
        .groups-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin: 1.5rem 0;
        }

        .group-card {
            background: #fafeff;
            border-radius: 1.2rem;
            padding: 1rem;
            border: 1px solid var(--border);
            transition: 0.2s;
        }

        .group-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }

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

        .member-count {
            font-size: 0.8rem;
            font-weight: normal;
            background: #eef3fc;
            padding: 0.2rem 0.6rem;
            border-radius: 1rem;
        }

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

        .member-list {
            margin-top: 0.8rem;
        }

        .member {
            padding: 0.3rem 0;
            border-bottom: 1px dotted #e2e8f0;
            font-size: 0.9rem;
        }

        .export-pdf {
            text-align: center;
            margin-top: 2rem;
        }

        /* ---------- PRINT STYLES (for PDF export) ---------- */
        @media print {
            /* Hide everything except the groups container */
            .dashboard-header,
            .form-card:not(:last-child), /* hide all form cards except groups container? better hide all .form-card, .alert, etc */
            .form-card,
            .alert,
            .data-section,
            .export-pdf button,
            .logout-btn {
                display: none !important;
            }

            /* Show only the groups container */
            .groups-container {
                display: block !important;
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
                box-shadow: none !important;
            }

            /* Ensure group cards break nicely */
            .groups-grid {
                display: block !important;
            }

            .group-card {
                page-break-inside: avoid;
                break-inside: avoid;
                border: 1px solid #ccc !important;
                margin-bottom: 1rem;
            }

            body {
                background: white !important;
                padding: 0.2in !important;
                font-size: 12pt;
            }
        }

        /* ---------- RESPONSIVE MEDIA QUERIES ---------- */
        @media (max-width: 768px) {
            body {
                padding: 0.8rem;
            }
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                text-align: left;
            }
            .form-card, .groups-container {
                padding: 1.2rem;
            }
            .btn, .export-btn {
                width: 100%;
                justify-content: center;
            }
            .data-table th, .data-table td {
                padding: 0.6rem;
                font-size: 0.85rem;
            }
        }

        @media (max-width: 480px) {
            .groups-grid {
                grid-template-columns: 1fr;
            }
            .group-header {
                font-size: 1rem;
            }
            .member-count {
                font-size: 0.7rem;
            }
            .chief-badge {
                font-size: 0.75rem;
            }
            .member {
                font-size: 0.8rem;
            }
            input, select, .btn {
                font-size: 0.9rem;
                padding: 0.7rem 1rem;
            }
        }

        /* Touch-friendly active states */
        .btn:active, .export-btn:active, .logout-btn:active {
            transform: scale(0.96);
        }

        /* Smooth transitions */
        * {
            transition: all 0.2s ease;
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <div class="dashboard-header">
        <div class="user-info">
            <i class="fas fa-chalkboard-teacher"></i>
            <span>Logged in as: <strong><?php echo htmlspecialchars($_SESSION['teacher_username'] ?? 'Teacher'); ?></strong></span>
        </div>
        <a href="logout.t.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Registration Window Card -->
    <div class="form-card">
        <h2><i class="fas fa-clock"></i> Set Registration Time Window</h2>
        <?php echo $window_message; ?>
        <form method="POST">
            <div class="form-group">
                <label>Start Time</label>
                <input type="datetime-local" name="start_time" required
                       value="<?php echo $current_window ? htmlspecialchars($current_window['start_time']) : ''; ?>">
            </div>
            <div class="form-group">
                <label>End Time</label>
                <input type="datetime-local" name="end_time" required
                       value="<?php echo $current_window ? htmlspecialchars($current_window['end_time']) : ''; ?>">
            </div>
            <div class="form-group">
                <button type="submit" name="set_window" class="btn"><i class="fas fa-save"></i> Save Window</button>
            </div>
        </form>
    </div>

    <!-- Smart Group Generator Card -->
    <div class="form-card">
        <h2><i class="fas fa-users"></i> Smart Group Generator</h2>
        <form method="POST">
            <!-- Fixed table selection: only "students" is shown -->
            <div class="form-group">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Selected Table:</label>
                <div style="background: #f0f4fa; padding: 0.85rem 1rem; border-radius: 2rem; border: 1px solid #dce5ef;">
                    <i class="fas fa-table"></i> students
                </div>
                <input type="hidden" name="generate_table" value="students">
            </div>
            <div class="form-group">
                <button type="submit" name="view_table" class="btn btn-secondary"><i class="fas fa-eye"></i> View Table Data</button>
            </div>

            <!-- Table data appears right here after clicking View Table Data -->
            <?php if(!empty($table_data)) echo $table_data; ?>

            <div class="form-group">
                <input type="number" name="group_size" min="2" max="10" placeholder="Group size (2–10)">
            </div>
            <div class="form-group">
                <button type="submit" name="generate" class="btn"><i class="fas fa-magic"></i> Generate Groups</button>
            </div>
        </form>
    </div>

    <!-- Groups Display (separate card) -->
    <?php echo $groups_html; ?>
</div>
</body>
</html>
