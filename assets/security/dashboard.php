<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/database.php";

require_role('security');

$security_name = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Security Officer';
$msg = '';
$error = '';

// Handle Visitor Check-In
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_in') {
    $visitor_name = trim($_POST['visitor_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $flat_no = trim($_POST['flat_no'] ?? '');

    if ($visitor_name !== '' && $phone !== '') {
        $stmt = $conn->prepare("INSERT INTO visitor_entries (visitor_name, phone, flat_no, status, in_time) VALUES (?, ?, ?, 'INSIDE', NOW())");
        if ($stmt) {
            $stmt->bind_param("sss", $visitor_name, $phone, $flat_no);
            if ($stmt->execute()) {
                $msg = "Visitor Check-In Recorded Successfully!";
            } else {
                $error = "Failed to process Check-In.";
            }
            $stmt->close();
        }
    } else {
        $error = "Please provide both Visitor Name and Phone Number.";
    }
}

// Handle Visitor Mark Exit
if (isset($_GET['checkout_id'])) {
    $checkout_id = intval($_GET['checkout_id']);
    $stmt = $conn->prepare("UPDATE visitor_entries SET status = 'EXITED', out_time = NOW() WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $checkout_id);
        if ($stmt->execute()) {
            $msg = "Visitor Marked as EXITED!";
        }
        $stmt->close();
    }
}

// Fetch Active & Recent Entries
$entries = [];
$result = $conn->query("SELECT * FROM visitor_entries ORDER BY id DESC LIMIT 25");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $entries[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Terminal - BEMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --glass-card: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.12);
            --blur: blur(20px);
            --sidebar-width: 260px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            background: #090d16;
            background-image: 
                radial-gradient(at 0% 0%, rgba(16, 185, 129, 0.22) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(59, 130, 246, 0.15) 0px, transparent 50%),
                radial-gradient(at 50% 100%, rgba(245, 158, 11, 0.15) 0px, transparent 50%);
            background-attachment: fixed;
            color: #f8fafc;
        }

        .dashboard {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: var(--sidebar-width);
            min-width: var(--sidebar-width);
            background: rgba(10, 15, 29, 0.85);
            backdrop-filter: var(--blur);
            padding: 28px 18px;
            border-right: 1px solid var(--glass-border);
        }

        .brand {
            font-size: 24px;
            font-weight: 800;
            color: #ffffff;
            padding: 10px 14px 30px 14px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand::before {
            content: '';
            width: 12px;
            height: 12px;
            background: #10b981;
            border-radius: 50%;
            box-shadow: 0 0 16px #10b981;
        }

        .nav {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            padding: 12px 16px;
            color: #ffffff;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.9), rgba(5, 150, 105, 0.9));
            text-decoration: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .main {
            flex: 1;
            min-height: 100vh;
        }

        .header {
            height: 72px;
            background: rgba(10, 15, 29, 0.6);
            backdrop-filter: var(--blur);
            border-bottom: 1px solid var(--glass-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 32px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-title {
            font-size: 18px;
            font-weight: 700;
            color: #ffffff;
        }

        .header-user {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .user-name {
            font-size: 14px;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.08);
            padding: 8px 16px;
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            color: #f1f5f9;
        }

        .logout {
            text-decoration: none;
            color: #f87171;
            font-size: 14px;
            font-weight: 600;
            padding: 8px 14px;
            border-radius: 10px;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .content {
            padding: 32px;
            max-width: 1300px;
        }

        .page-section {
            background: var(--glass-card);
            backdrop-filter: var(--blur);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            margin-bottom: 30px;
        }

        .page-section h2 {
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 6px;
        }

        .page-section p {
            color: #94a3b8;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .gate-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .gate-form input {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            color: #ffffff;
            font-size: 14px;
            outline: none;
        }

        .gate-form input:focus {
            border-color: #10b981;
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.3);
        }

        .btn-checkin {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.9), rgba(5, 150, 105, 0.9));
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
            transition: all 0.2s ease;
        }

        .btn-checkin:hover {
            transform: translateY(-2px);
            filter: brightness(1.15);
        }

        .btn-checkout {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-checkout:hover {
            background: rgba(239, 68, 68, 0.4);
            color: #ffffff;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
            margin-top: 15px;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        th, td {
            padding: 16px 20px;
            text-align: left;
            font-size: 14px;
            white-space: nowrap;
        }

        th {
            background: rgba(255, 255, 255, 0.05);
            color: #94a3b8;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.8px;
            border-bottom: 1px solid var(--glass-border);
        }

        td {
            color: #f1f5f9;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .status-inside {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.4);
            color: #34d399;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .status-exited {
            background: rgba(100, 116, 139, 0.2);
            border: 1px solid rgba(100, 116, 139, 0.4);
            color: #94a3b8;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }
    </style>
</head>
<body>

<div class="dashboard">

    <aside class="sidebar">
        <div class="brand">BEMS Gate</div>
        <nav class="nav">
            <a href="/Building/assets/security/dashboard.php">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Gate Console
            </a>
        </nav>
    </aside>

    <main class="main">
        <header class="header">
            <div class="header-title">Security Gate Operations</div>
            <div class="header-user">
                <span class="user-name"><?php echo htmlspecialchars($security_name); ?></span>
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

            <!-- Gate Check-In Form -->
            <div class="page-section">
                <h2>New Visitor Entry</h2>
                <p>Record guest details entering through the gate</p>
                
                <form method="post" class="gate-form">
                    <input type="hidden" name="action" value="check_in">
                    <input type="text" name="visitor_name" placeholder="Visitor Full Name" required>
                    <input type="text" name="phone" placeholder="Phone Number" required>
                    <input type="text" name="flat_no" placeholder="Flat / Unit No. (e.g. B-402)">
                    <button type="submit" class="btn-checkin">Check-In Guest</button>
                </form>
            </div>

            <!-- Live Entry Logs Table -->
            <div class="page-section">
                <h2>Real-Time Gate Activity</h2>
                <p>Active visitors currently inside and recent gate exits</p>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Log ID</th>
                                <th>Status</th>
                                <th>Visitor Name</th>
                                <th>Phone</th>
                                <th>Target Unit</th>
                                <th>In Time</th>
                                <th>Out Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($entries)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; color: #94a3b8;">No gate activity recorded yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($entries as $e): ?>
                                    <tr>
                                        <td>#<?php echo $e['id']; ?></td>
                                        <td>
                                            <?php if (($e['status'] ?? 'INSIDE') === 'INSIDE'): ?>
                                                <span class="status-inside">INSIDE</span>
                                            <?php else: ?>
                                                <span class="status-exited">EXITED</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($e['visitor_name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($e['phone'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($e['flat_no'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($e['in_time'] ?? $e['created_at'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($e['out_time'] ?? '-'); ?></td>
                                        <td>
                                            <?php if (($e['status'] ?? 'INSIDE') === 'INSIDE'): ?>
                                                <a href="?checkout_id=<?php echo $e['id']; ?>" class="btn-checkout">Mark Exit</a>
                                            <?php else: ?>
                                                <span style="color: #64748b; font-size: 13px;">Checked Out</span>
                                            <?php endif; ?>
                                        </td>
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