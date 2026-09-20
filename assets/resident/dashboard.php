<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/database.php";

require_role('resident');

$resident_name = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Resident';
$resident_id = $_SESSION['user_id'] ?? 0;

$msg = '';
$error = '';

// Determine target visitors table
$table_check = $conn->query("SHOW TABLES LIKE 'visitors'");
$v_table = ($table_check && $table_check->num_rows > 0) ? 'visitors' : 'visitor_entries';

// Determine column names dynamically
$col_name_check = $conn->query("SHOW COLUMNS FROM {$v_table} LIKE 'visitor_name'");
$name_col = ($col_name_check && $col_name_check->num_rows > 0) ? 'visitor_name' : 'name';

$col_date_check = $conn->query("SHOW COLUMNS FROM {$v_table} LIKE 'visit_date'");
$date_col = ($col_date_check && $col_date_check->num_rows > 0) ? 'visit_date' : 'created_at';

// Check if status column exists
$col_status_check = $conn->query("SHOW COLUMNS FROM {$v_table} LIKE 'status'");
$has_status = ($col_status_check && $col_status_check->num_rows > 0);
$status_col_sql = $has_status ? "status" : "'PRE_APPROVED' AS status";

// Handle Pre-Approval Request Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pre_approve') {
    $guest_name = trim($_POST['guest_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $expected_date = trim($_POST['expected_date'] ?? date('Y-m-d'));

    if ($guest_name !== '' && $phone !== '') {
        $check_res_col = $conn->query("SHOW COLUMNS FROM {$v_table} LIKE 'resident_id'");
        $has_resident_id = ($check_res_col && $check_res_col->num_rows > 0);
        
        if ($has_resident_id) {
            if ($has_status) {
                $stmt = $conn->prepare("INSERT INTO {$v_table} (resident_id, {$name_col}, phone, {$date_col}, status) VALUES (?, ?, ?, ?, 'PRE_APPROVED')");
                if ($stmt) {
                    $stmt->bind_param("isss", $resident_id, $guest_name, $phone, $expected_date);
                    if ($stmt->execute()) { $msg = "Guest Pre-Approved Successfully!"; }
                    else { $error = "Failed to pre-approve guest."; }
                    $stmt->close();
                }
            } else {
                $stmt = $conn->prepare("INSERT INTO {$v_table} (resident_id, {$name_col}, phone, {$date_col}) VALUES (?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("isss", $resident_id, $guest_name, $phone, $expected_date);
                    if ($stmt->execute()) { $msg = "Guest Pre-Approved Successfully!"; }
                    else { $error = "Failed to pre-approve guest."; }
                    $stmt->close();
                }
            }
        } else {
            if ($has_status) {
                $stmt = $conn->prepare("INSERT INTO {$v_table} ({$name_col}, phone, {$date_col}, status) VALUES (?, ?, ?, 'PRE_APPROVED')");
                if ($stmt) {
                    $stmt->bind_param("sss", $guest_name, $phone, $expected_date);
                    if ($stmt->execute()) { $msg = "Guest Pre-Approved Successfully!"; }
                    else { $error = "Failed to pre-approve guest."; }
                    $stmt->close();
                }
            } else {
                $stmt = $conn->prepare("INSERT INTO {$v_table} ({$name_col}, phone, {$date_col}) VALUES (?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("sss", $guest_name, $phone, $expected_date);
                    if ($stmt->execute()) { $msg = "Guest Pre-Approved Successfully!"; }
                    else { $error = "Failed to pre-approve guest."; }
                    $stmt->close();
                }
            }
        }
    } else {
        $error = "Guest name and phone number are required.";
    }
}

// Fetch Resident's Guests safely
$visitors = [];
$check_res_col = $conn->query("SHOW COLUMNS FROM {$v_table} LIKE 'resident_id'");

if ($check_res_col && $check_res_col->num_rows > 0) {
    $stmt = $conn->prepare("SELECT id, {$name_col} AS visitor_name, phone, {$date_col} AS visit_date, {$status_col_sql} FROM {$v_table} WHERE resident_id = ? ORDER BY id DESC LIMIT 20");
    if ($stmt) {
        $stmt->bind_param("i", $resident_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $visitors[] = $row;
        }
        $stmt->close();
    }
} else {
    $res = $conn->query("SELECT id, {$name_col} AS visitor_name, phone, {$date_col} AS visit_date, {$status_col_sql} FROM {$v_table} ORDER BY id DESC LIMIT 20");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $visitors[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Portal - BEMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --glass-card: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.12);
            --blur: blur(20px);
            --sidebar-width: 260px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            background: #090d16;
            background-image: 
                radial-gradient(at 0% 0%, rgba(245, 158, 11, 0.2) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(59, 130, 246, 0.18) 0px, transparent 50%),
                radial-gradient(at 50% 100%, rgba(139, 92, 246, 0.15) 0px, transparent 50%);
            background-attachment: fixed;
            color: #f8fafc;
        }

        .dashboard { display: flex; min-height: 100vh; }

        .sidebar {
            width: var(--sidebar-width);
            min-width: var(--sidebar-width);
            background: rgba(10, 15, 29, 0.85);
            backdrop-filter: var(--blur);
            padding: 28px 18px;
            border-right: 1px solid var(--glass-border);
        }

        .brand {
            font-size: 24px; font-weight: 800; color: #ffffff;
            padding: 10px 14px 30px 14px; display: flex; align-items: center; gap: 12px;
        }

        .brand::before {
            content: ''; width: 12px; height: 12px;
            background: #f59e0b; border-radius: 50%; box-shadow: 0 0 16px #f59e0b;
        }

        .nav { display: flex; flex-direction: column; gap: 8px; }

        .nav a {
            display: flex; align-items: center; gap: 12px; width: 100%;
            padding: 12px 16px; color: #ffffff;
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.9), rgba(217, 119, 6, 0.9));
            text-decoration: none; border-radius: 12px; font-size: 14px; font-weight: 700;
            box-shadow: 0 4px 20px rgba(245, 158, 11, 0.4); border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .main { flex: 1; min-height: 100vh; }

        .header {
            height: 72px; background: rgba(10, 15, 29, 0.6);
            backdrop-filter: var(--blur); border-bottom: 1px solid var(--glass-border);
            display: flex; justify-content: space-between; align-items: center; padding: 0 32px;
            position: sticky; top: 0; z-index: 100;
        }

        .header-title { font-size: 18px; font-weight: 700; color: #ffffff; }

        .header-user { display: flex; align-items: center; gap: 16px; }

        .user-name {
            font-size: 14px; font-weight: 600; background: rgba(255, 255, 255, 0.08);
            padding: 8px 16px; border-radius: 20px; border: 1px solid var(--glass-border); color: #f1f5f9;
        }

        .logout {
            text-decoration: none; color: #f87171; font-size: 14px; font-weight: 600;
            padding: 8px 14px; border-radius: 10px; background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .content { padding: 32px; max-width: 1300px; }

        .page-section {
            background: var(--glass-card); backdrop-filter: var(--blur);
            border: 1px solid var(--glass-border); border-radius: 20px; padding: 28px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3); margin-bottom: 30px;
        }

        .page-section h2 { font-size: 20px; font-weight: 700; color: #ffffff; margin-bottom: 6px; }
        .page-section p { color: #94a3b8; font-size: 14px; margin-bottom: 20px; }

        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }

        .form-grid input {
            width: 100%; padding: 12px 16px; background: rgba(255, 255, 255, 0.06);
            border: 1px solid var(--glass-border); border-radius: 12px; color: #ffffff; font-size: 14px; outline: none;
        }

        .form-grid input:focus { border-color: #f59e0b; box-shadow: 0 0 15px rgba(245, 158, 11, 0.3); }

        .btn-submit {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.9), rgba(217, 119, 6, 0.9));
            color: #ffffff; border: 1px solid rgba(255, 255, 255, 0.2); padding: 12px 24px;
            border-radius: 12px; font-weight: 700; font-size: 14px; cursor: pointer;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4); transition: all 0.2s ease;
        }

        .btn-submit:hover { transform: translateY(-2px); filter: brightness(1.15); }

        .table-wrapper { width: 100%; overflow-x: auto; margin-top: 15px; }
        table { width: 100%; border-collapse: separate; border-spacing: 0; }
        th, td { padding: 16px 20px; text-align: left; font-size: 14px; white-space: nowrap; }
        th {
            background: rgba(255, 255, 255, 0.05); color: #94a3b8; font-weight: 700;
            text-transform: uppercase; font-size: 12px; letter-spacing: 0.8px;
            border-bottom: 1px solid var(--glass-border);
        }
        td { color: #f1f5f9; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }

        .status-badge {
            background: rgba(245, 158, 11, 0.2); border: 1px solid rgba(245, 158, 11, 0.4);
            color: #fbbf24; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700;
        }
    </style>
</head>
<body>

<div class="dashboard">

    <aside class="sidebar">
        <div class="brand">Resident Portal</div>
        <nav class="nav">
            <a href="/Building/assets/resident/dashboard.php">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                Guest Passes
            </a>
        </nav>
    </aside>

    <main class="main">
        <header class="header">
            <div class="header-title">Resident Guest Pre-Approval</div>
            <div class="header-user">
                <span class="user-name"><?php echo htmlspecialchars($resident_name); ?></span>
                <a href="/Building/assets/config/logout.php" class="logout">Logout</a>
            </div>
        </header>

        <section class="content">

            <?php if ($msg !== ''): ?>
                <div style="background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.4); color: #34d399; padding: 12px 18px; border-radius: 12px; font-size: 14px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.4); color: #fca5a5; padding: 12px 18px; border-radius: 12px; font-size: 14px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Pre-Approve Form -->
            <div class="page-section">
                <h2>Pre-Approve a Guest</h2>
                <p>Allow quick gate entry for expected guests and visitors</p>

                <form method="post" class="form-grid">
                    <input type="hidden" name="action" value="pre_approve">
                    <input type="text" name="guest_name" placeholder="Guest Full Name" required>
                    <input type="text" name="phone" placeholder="Phone Number" required>
                    <input type="date" name="expected_date" value="<?php echo date('Y-m-d'); ?>" required>
                    <button type="submit" class="btn-submit">Issue Gate Pass</button>
                </form>
            </div>

            <!-- Approved Guests Table -->
            <div class="page-section">
                <h2>Your Pre-Approved Guests</h2>
                <p>List of guest passes created for gate verification</p>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Pass ID</th>
                                <th>Status</th>
                                <th>Guest Name</th>
                                <th>Phone</th>
                                <th>Visit Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($visitors)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: #94a3b8;">No pre-approved guests added yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($visitors as $v): ?>
                                    <tr>
                                        <td>#<?php echo $v['id']; ?></td>
                                        <td><span class="status-badge"><?php echo htmlspecialchars($v['status'] ?? 'PRE_APPROVED'); ?></span></td>
                                        <td><?php echo htmlspecialchars($v['visitor_name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($v['phone'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($v['visit_date'] ?? 'N/A'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </section>
    </main>

</div>

</body>
</html>