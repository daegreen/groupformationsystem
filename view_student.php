<?php
session_start();
require_once 'conn.php'; // Must return a PDO object named $conn

// ==================== Get all tables (exclude admins and teachers) ====================
$tables = [];
$stmt = $conn->query("SHOW TABLES");
if ($stmt) {
    $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    // Exclude 'admins' and 'teachers'
    $tables = array_filter($allTables, function($table) {
        return !in_array($table, ['admins', 'teachers']);
    });
    $tables = array_values($tables); // re-index
}

// ==================== Determine which tables have a teacher_id column ====================
$tablesWithTeacherId = [];
foreach ($tables as $table) {
    $colCheckSql = "DESCRIBE `$table`";
    $colStmt = $conn->query($colCheckSql);
    if ($colStmt) {
        $columns = $colStmt->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('teacher_id', $columns)) {
            $tablesWithTeacherId[] = $table;
        }
        $colStmt = null;
    }
}

// ==================== Fetch teachers (robust: detect name columns) ====================
$teachers = [];
$teachersTableExists = in_array('teachers', $allTables ?? []);
if ($teachersTableExists) {
    // Get the structure of the teachers table
    $teacherColumns = [];
    $descStmt = $conn->query("DESCRIBE teachers");
    if ($descStmt) {
        $teacherColumns = $descStmt->fetchAll(PDO::FETCH_COLUMN);
        $descStmt = null;
    }

    // Normalize column names to lowercase for comparison, but keep original for SQL
    $lowerColumns = array_map('strtolower', $teacherColumns);
    $originalColumns = $teacherColumns;

    $nameExpression = null;

    // 1. Check for separate first_name and last_name (case-insensitive)
    $firstIndex = array_search('first_name', $lowerColumns);
    $lastIndex  = array_search('last_name', $lowerColumns);
    if ($firstIndex !== false && $lastIndex !== false) {
        $firstCol = $originalColumns[$firstIndex];
        $lastCol  = $originalColumns[$lastIndex];
        $nameExpression = "CONCAT($firstCol, ' ', $lastCol)";
    }
    // 2. Check for single name columns (priority order)
    else {
        $singleNameCandidates = ['name', 'full_name', 'teacher_name', 'display_name'];
        foreach ($singleNameCandidates as $candidate) {
            $idx = array_search($candidate, $lowerColumns);
            if ($idx !== false) {
                $nameExpression = $originalColumns[$idx];
                break;
            }
        }
    }

    // 3. If still no name column, try to find any column that contains 'name'
    if ($nameExpression === null) {
        foreach ($lowerColumns as $idx => $colLower) {
            if (strpos($colLower, 'name') !== false) {
                $nameExpression = $originalColumns[$idx];
                break;
            }
        }
    }

    // 4. Final fallback: use the first column that is not 'id', else use 'id' with a prefix
    if ($nameExpression === null) {
        $idIndex = array_search('id', $lowerColumns);
        foreach ($originalColumns as $idx => $col) {
            if ($col !== 'id') {
                $nameExpression = $col;
                break;
            }
        }
        // If only 'id' exists, we'll still use it but will format later
        if ($nameExpression === null && $idIndex !== false) {
            $nameExpression = 'id';
        }
    }

    // Build the query – if we are forced to use 'id', display "Teacher #id"
    if ($nameExpression === 'id') {
        $teacherSql = "SELECT id, CONCAT('Teacher #', id) AS display_name FROM teachers ORDER BY id";
    } else {
        $teacherSql = "SELECT id, $nameExpression AS display_name FROM teachers ORDER BY display_name";
    }

    $teacherStmt = $conn->query($teacherSql);
    if ($teacherStmt && $teacherStmt->rowCount() > 0) {
        $teachers = $teacherStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    $teacherStmt = null;
}

// ==================== Helper: Check if teacher has an active registration window ====================
function teacherHasValidWindow($conn, $teacherId) {
    // Check if teachers table exists (it should, but just in case)
    $checkTable = $conn->query("SHOW TABLES LIKE 'teachers'");
    if ($checkTable->rowCount() == 0) {
        return false;
    }

    // Check if registration_start and registration_end columns exist
    $colStmt = $conn->query("DESCRIBE teachers");
    if (!$colStmt) return false;
    $columns = $colStmt->fetchAll(PDO::FETCH_COLUMN);
    $hasStart = in_array('registration_start', $columns);
    $hasEnd = in_array('registration_end', $columns);
    if (!$hasStart || !$hasEnd) {
        // Columns missing → cannot set windows → block registration
        return false;
    }

    // Retrieve the registration window for this teacher
    $stmt = $conn->prepare("SELECT registration_start, registration_end FROM teachers WHERE id = ?");
    $stmt->execute([$teacherId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return false; // teacher not found

    $start = $row['registration_start'];
    $end = $row['registration_end'];

    // If either value is NULL, no window is set → block
    if (is_null($start) || is_null($end)) {
        return false;
    }

    $now = date('Y-m-d H:i:s');
    return ($now >= $start && $now <= $end);
}

// ==================== Handle Form Submission ====================
$message = '';
$messageType = '';
// Default to 'students' if exists, otherwise first available table
if (isset($_POST['table_name'])) {
    $selectedTable = $_POST['table_name'];
} else {
    $selectedTable = in_array('students', $tables) ? 'students' : ($tables[0] ?? '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $reg_number = trim($_POST['reg_number'] ?? '');
    $gender     = $_POST['gender'] ?? '';
    $interested = $_POST['interested'] ?? '';
    $teacher_id = isset($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : 0;

    $errors = [];
    if (empty($first_name)) $errors[] = 'First name is required.';
    if (empty($last_name)) $errors[] = 'Last name is required.';
    if (empty($reg_number)) $errors[] = 'Registration number is required.';
    if (empty($gender)) $errors[] = 'Please select gender.';
    if (empty($interested)) $errors[] = 'Please select leadership interest.';
    if (empty($selectedTable)) $errors[] = 'Please select a table.';

    if (empty($errors)) {
        if (!in_array($selectedTable, $tables)) {
            $errors[] = "Selected table '$selectedTable' does not exist.";
        } else {
            // Check for teacher_id column in the selected table
            $hasTeacherId = in_array($selectedTable, $tablesWithTeacherId);

            // If the table expects a teacher, validate that a teacher was selected
            if ($hasTeacherId && empty($teacher_id)) {
                $errors[] = "Please select a teacher for this registration.";
            } 
            // Check if the selected teacher has an active registration window
            elseif ($hasTeacherId && !teacherHasValidWindow($conn, $teacher_id)) {
                $errors[] = "Registration is only allowed during the time set by your teacher. Please contact your teacher for the registration period.";
            }

            if (empty($errors)) {
                // Check duplicate reg_number
                $check_sql = "SELECT id FROM `$selectedTable` WHERE reg_number = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->execute([$reg_number]);
                $exists = $check_stmt->fetchColumn();

                if ($exists) {
                    $errors[] = "Registration number '$reg_number' already exists in $selectedTable.";
                } else {
                    if ($hasTeacherId) {
                        $sql = "INSERT INTO `$selectedTable` (first_name, last_name, reg_number, gender, interested, teacher_id)
                                VALUES (?, ?, ?, ?, ?, ?)";
                        $params = [$first_name, $last_name, $reg_number, $gender, $interested, $teacher_id];
                    } else {
                        $sql = "INSERT INTO `$selectedTable` (first_name, last_name, reg_number, gender, interested)
                                VALUES (?, ?, ?, ?, ?)";
                        $params = [$first_name, $last_name, $reg_number, $gender, $interested];
                    }

                    $stmt = $conn->prepare($sql);
                    if ($stmt->execute($params)) {
                        $message = "✅ Student $first_name $last_name registered successfully in '$selectedTable'!";
                        $messageType = 'success';
                        $_POST = [];
                    } else {
                        $errors[] = "Database error: " . implode(' ', $stmt->errorInfo());
                    }
                    $stmt = null;
                }
                $check_stmt = null;
            }
        }
    }

    if (!empty($errors)) {
        $message = "❌ " . implode('<br>', $errors);
        $messageType = 'error';
    }
}

// ==================== Handle View All ====================
if (isset($_GET['view']) && $_GET['view'] === 'all') {
    $table = $_GET['table'] ?? $tables[0];
    if (!in_array($table, $tables)) $table = $tables[0];

    $students = [];
    $sql = "SELECT * FROM `$table` ORDER BY created_at DESC LIMIT 10";
    $stmt = $conn->query($sql);
    if ($stmt && $stmt->rowCount() > 0) {
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if (!empty($students)) {
        $messageLines = ["<strong>📋 Recent Registrations in '$table':</strong>"];
        foreach ($students as $s) {
            $safeFirst = htmlspecialchars($s['first_name']);
            $safeLast = htmlspecialchars($s['last_name']);
            $safeReg = htmlspecialchars($s['reg_number']);
            $safeGender = htmlspecialchars($s['gender']);
            $safeInterest = htmlspecialchars($s['interested']);
            $messageLines[] = "• {$safeFirst} {$safeLast} ({$safeReg}) - {$safeGender}, Interested: {$safeInterest}";
        }
        $message = implode('<br>', $messageLines);
        $messageType = 'success';
    } else {
        $message = "No students found in '$table'.";
        $messageType = 'info';
    }
}

// ==================== Handle CSV Export ====================
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $table = $_GET['table'] ?? $tables[0];
    if (!in_array($table, $tables)) $table = $tables[0];

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $table . '_export.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'First Name', 'Last Name', 'Reg Number', 'Gender', 'Interested', 'Created At']);

    $sql = "SELECT * FROM `$table` ORDER BY created_at DESC";
    $stmt = $conn->query($sql);
    if ($stmt && $stmt->rowCount() > 0) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['id'],
                $row['first_name'],
                $row['last_name'],
                $row['reg_number'],
                $row['gender'],
                $row['interested'],
                $row['created_at']
            ]);
        }
    }
    fclose($output);
    exit();
}

// Get current database name for footer
$dbName = $conn->query("SELECT DATABASE()")->fetchColumn() ?? 'unknown';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
<title>Student Registration · Adaptive Form</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<style>
/* ----- RESET & GLOBAL ----- */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    -webkit-tap-highlight-color: transparent;
}

html {
    font-size: 16px;
    scroll-behavior: smooth;
}

body {
    background: linear-gradient(135deg, #e3f0fc 0%, #cbe4f5 100%);
    font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, sans-serif;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: clamp(0.5rem, 3vw, 1.5rem);
}

/* ----- MAIN CONTAINER (ULTRA RESPONSIVE) ----- */
.app-container {
    width: 100%;
    max-width: 780px;
    margin: 0 auto;
}

/* ----- FORM CARD (RESILIENT) ----- */
.form-card {
    background: #ffffff;
    border-radius: clamp(1rem, 5vw, 2rem);
    box-shadow: 0 20px 35px -12px rgba(10, 42, 68, 0.25);
    overflow: hidden;
    transition: all 0.2s ease;
    border: 1px solid rgba(255, 255, 255, 0.5);
}

/* ----- HEADER + TOP BAR (integrated back button) ----- */
.header {
    background: #0f2b3f;
    color: white;
    padding: clamp(1rem, 4vw, 1.8rem);
}

.top-bar {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 1rem;
    align-items: start;
    margin-bottom: 1rem;
}

.back-home-btn {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(4px);
    padding: 0.55rem 1.3rem;
    border-radius: 3rem;
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
    color: white;
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    transition: 0.2s;
    border: 1px solid rgba(255, 255, 255, 0.25);
    white-space: nowrap;
}

.back-home-btn:hover {
    background: rgba(255, 255, 255, 0.28);
    transform: translateY(-1px);
}

.title-section {
    text-align: right;
}

.title-section h1 {
    font-size: clamp(1.3rem, 6vw, 2.2rem);
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 0.5rem;
    line-height: 1.2;
}

.title-section p {
    font-size: clamp(0.75rem, 3.5vw, 1rem);
    opacity: 0.85;
    margin-top: 0.2rem;
}

/* ----- FORM CONTAINER ----- */
.form-container {
    padding: clamp(1rem, 5vw, 2.5rem);
    background: white;
}

.inner-card {
    background: #fafeff;
    border-radius: 1.8rem;
    padding: clamp(1rem, 4vw, 2rem);
    border: 1px solid #cde2f2;
    transition: all 0.2s ease;
}

.inner-card h2 {
    color: #0f2b3f;
    font-size: clamp(1.2rem, 5vw, 1.8rem);
    margin-bottom: 1.2rem;
    display: flex;
    align-items: center;
    gap: 0.7rem;
    border-left: 5px solid #2a7f6b;
    padding-left: 1rem;
}

/* ----- INPUT GROUPS (touch friendly) ----- */
.input-group {
    display: flex;
    align-items: center;
    background: white;
    border-radius: 3rem;
    padding: 0.2rem 1rem;
    margin-bottom: 1rem;
    border: 1.5px solid #deedf9;
    transition: 0.15s;
    width: 100%;
}

.input-group:focus-within {
    border-color: #2a7f6b;
    box-shadow: 0 0 0 3px rgba(42, 127, 107, 0.2);
}

.input-group i {
    color: #1e4b6e;
    width: 28px;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.input-group input,
.input-group select {
    width: 100%;
    padding: 0.85rem 0.4rem;
    border: none;
    background: transparent;
    outline: none;
    font-size: 1rem;
    font-family: inherit;
    font-weight: 500;
}

.input-group select {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%231e4b6e' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 9 12 15 18 9'></polyline></svg>");
    background-repeat: no-repeat;
    background-position: right 0.8rem center;
    background-size: 1rem;
}

/* submit button */
.submit-btn {
    background: #2a7f6b;
    color: white;
    border: none;
    padding: 1rem 1.5rem;
    border-radius: 4rem;
    font-weight: 700;
    font-size: 1rem;
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

.submit-btn:hover {
    background: #1e9a80;
    transform: scale(1.01);
}

/* message area */
.status-message {
    background: #e3f5ef;
    border-radius: 2rem;
    padding: 1rem 1.5rem;
    margin-bottom: 1.6rem;
    text-align: left;
    color: #146b54;
    font-weight: 500;
    border-left: 5px solid #2a7f6b;
    word-break: break-word;
    font-size: 0.95rem;
    line-height: 1.4;
}

.status-message.error {
    background: #ffe8e8;
    color: #bc4747;
    border-left-color: #d9534f;
}

.status-message.info {
    background: #eef3fc;
    color: #1e5f7a;
    border-left-color: #398eac;
}

/* footer */
.footer-note {
    background: #ecf5fd;
    padding: 0.9rem 1.5rem;
    text-align: center;
    border-top: 1px solid #cde2f2;
    font-size: 0.8rem;
    color: #1e4b6e;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

/* RESPONSIVE ADJUSTMENTS */
@media (max-width: 600px) {
    .top-bar {
        grid-template-columns: 1fr;
        text-align: center;
    }
    .title-section {
        text-align: center;
    }
    .title-section h1 {
        justify-content: center;
    }
    .back-home-btn {
        justify-content: center;
        width: fit-content;
        margin: 0 auto;
    }
    .input-group {
        padding: 0.1rem 0.8rem;
    }
    .input-group input, 
    .input-group select {
        padding: 0.75rem 0.2rem;
        font-size: 0.95rem;
    }
}

@media (max-width: 480px) {
    body {
        padding: 0.5rem;
    }
    .form-card {
        border-radius: 1.2rem;
    }
    .header {
        padding: 1rem;
    }
    .form-container {
        padding: 0.8rem;
    }
    .inner-card {
        padding: 1rem;
    }
    .inner-card h2 {
        font-size: 1.2rem;
    }
    .submit-btn {
        font-size: 0.95rem;
        padding: 0.8rem;
    }
    .status-message {
        font-size: 0.85rem;
        padding: 0.8rem 1rem;
    }
    .footer-note {
        font-size: 0.7rem;
        padding: 0.7rem;
    }
}

@media (orientation: landscape) and (max-height: 500px) {
    body {
        align-items: flex-start;
        padding: 1rem;
    }
    .form-card {
        margin: 0 auto;
    }
    .header {
        padding: 0.8rem;
    }
    .form-container {
        padding: 0.8rem;
    }
    .inner-card {
        padding: 0.8rem;
    }
    .input-group {
        margin-bottom: 0.7rem;
    }
}

/* touch improvements */
select, input, .action-btn, .submit-btn {
    touch-action: manipulation;
}

/* overflow safety */
pre, code, .status-message {
    white-space: normal;
    word-wrap: break-word;
}
</style>
</head>
<body>
<div class="app-container">
    <div class="form-card">
        <div class="header">
            <div class="top-bar">
                <a href="index.php" class="back-home-btn">
                    <i class="fas fa-arrow-left"></i> Back Home
                </a>
                <div class="title-section">
                    <h1><i class="fas fa-users"></i> GROUP FORMATION</h1>
                    <p>Smart student registration</p>
                </div>
            </div>
        </div>

        <div class="form-container">
            <div class="inner-card">
                <h2><i class="fas fa-user-graduate"></i> Register Student</h2>

                <?php if (!empty($message)): ?>
                    <div class="status-message <?php echo $messageType === 'error' ? 'error' : ($messageType === 'info' ? 'info' : ''); ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="registrationForm">
                    <div class="input-group">
                        <i class="fas fa-database"></i>
                        <select name="table_name" id="tableSelect" required>
                            <option value="">Select Table</option>
                            <?php foreach($tables as $table): ?>
                                <option value="<?php echo htmlspecialchars($table); ?>" <?php echo ($selectedTable === $table) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($table)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Teacher dropdown – shown only if selected table has teacher_id column -->
                    <div id="teacherFieldWrapper" style="display: none;">
                        <div class="input-group">
                            <i class="fas fa-chalkboard-user"></i>
                            <select name="teacher_id" id="teacherSelect">
                                <option value="">Select Teacher</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo (int)$teacher['id']; ?>"
                                        <?php echo (isset($_POST['teacher_id']) && (int)$_POST['teacher_id'] === (int)$teacher['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($teacher['display_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="input-group"><i class="fas fa-user"></i><input type="text" name="first_name" placeholder="First Name *" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required></div>
                    <div class="input-group"><i class="fas fa-user-friends"></i><input type="text" name="last_name" placeholder="Last Name *" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required></div>
                    <div class="input-group"><i class="fas fa-id-badge"></i><input type="text" name="reg_number" placeholder="Registration Number *" value="<?php echo htmlspecialchars($_POST['reg_number'] ?? ''); ?>" required></div>
                    
                    <div class="input-group">
                        <i class="fas fa-venus-mars"></i>
                        <select name="gender" required>
                            <option value="" <?php echo empty($_POST['gender']) ? 'selected' : ''; ?>>Select Gender</option>
                            <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <i class="fas fa-chalkboard-user"></i>
                        <select name="interested" required>
                            <option value="" <?php echo empty($_POST['interested']) ? 'selected' : ''; ?>>Leadership Interest?</option>
                            <option value="Yes" <?php echo (isset($_POST['interested']) && $_POST['interested'] === 'Yes') ? 'selected' : ''; ?>>Yes, interested</option>
                            <option value="No" <?php echo (isset($_POST['interested']) && $_POST['interested'] === 'No') ? 'selected' : ''; ?>>Not interested</option>
                        </select>
                    </div>

                    <button type="submit" name="send" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> Register Student
                    </button>
                </form>
            </div>
        </div>

        <div class="footer-note">
            <span>•</span> <i class="fas fa-table"></i> Flexible tables
        </div>
    </div>
</div>

<script>
    (function() {
        const form = document.getElementById('registrationForm');
        const tableSelect = document.getElementById('tableSelect');
        const teacherWrapper = document.getElementById('teacherFieldWrapper');
        const teacherSelect = document.getElementById('teacherSelect');

        // List of tables that have a teacher_id column (passed from PHP)
        const tablesWithTeacherId = <?php echo json_encode($tablesWithTeacherId); ?>;

        function toggleTeacherField() {
            const selectedTable = tableSelect.value;
            if (tablesWithTeacherId.includes(selectedTable)) {
                teacherWrapper.style.display = 'block';
                teacherSelect.required = true;
            } else {
                teacherWrapper.style.display = 'none';
                teacherSelect.required = false;
                teacherSelect.value = ''; // clear any previously selected teacher
            }
        }

        // Initial toggle on page load
        toggleTeacherField();

        // Toggle when table selection changes
        tableSelect.addEventListener('change', toggleTeacherField);

        // Optional: confirm short registration number
        if (form) {
            form.addEventListener('submit', function(e) {
                const regNumber = form.querySelector('[name="reg_number"]').value.trim();
                if (regNumber.length < 2 && regNumber.length > 0) {
                    if (!confirm('Registration number seems very short. Continue anyway?')) {
                        e.preventDefault();
                    }
                }
            });
        }
    })();
</script>
</body>
</html>
