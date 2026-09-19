<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/database.php";

require_role('security');

$security_id = $_SESSION['user_id'];
$security_name = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Security Staff';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $entry_id = intval($_POST['entry_id'] ?? 0);
    $action = $_POST['action'];

    if ($entry_id > 0) {
        if ($action === 'check_in') {
            $stmt = $conn->prepare("UPDATE visitor_entries SET status = 'checked_in', entry_time = NOW(), security_id = ? WHERE id = ?");
            $stmt->bind_param("ii", $security_id, $entry_id);
            if ($stmt->execute()) {
                $message = 'Visitor checked in successfully.';
            } else {
                $error = 'Failed to check in visitor.';
            }
            $stmt->close();
        } elseif ($action === 'check_out') {
            $stmt = $conn->prepare("UPDATE visitor_entries SET status = 'checked_out', exit_time = NOW(), security_id = ? WHERE id = ?");
            $stmt->bind_param("ii", $security_id, $entry_id);
            if ($stmt->execute()) {
                $message = 'Visitor checked out successfully.';
            } else {
                $error = 'Failed to check out visitor.';
            }
            $stmt->close();
        }
    }
}

$entries = $conn->query("
    SELECT ve.id, v.name AS visitor_name, v.phone AS visitor_phone, 
           u.name AS resident_name, ve.purpose, ve.entry_time, ve.exit_time, ve.status 
    FROM visitor_entries ve 
    LEFT JOIN visitors v ON ve.visitor_id = v.id 
    LEFT JOIN users u ON ve.resident_id = u.id 
    ORDER BY ve.id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Dashboard - BEMS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="dashboard">

    <aside class="sidebar">
        <div class="brand">BEMS</div>
        <nav class="nav">
            <a href="/Building/assets/security/dashboard.php" class="active">Dashboard</a>
            <a href="#">Visitor Logs</a>
            <a href="#">Check-In</a>
            <a href="#">Check-Out</a>
        </nav>
    </aside>

    <main class="main">
        <header class="header">
            <div class="header-title">Security Desk</div>
            <div class="header-user">
                <span class="user-name"><?php echo htmlspecialchars($security_name); ?></span>
                <a href="/Building/assets/config/logout.php" class="logout">Logout</a>
            </div>
        </header>

        <section class="content">
            <div class="welcome">
                <h1>Security Operations</h1>
                <p>Manage real-time gate entry and visitor check-ins.</p>
            </div>

            <?php if ($message !== ''): ?>
                <div class="message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="page-section">
                <h2>Active Gate Entries</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Visitor</th>
                                <th>Phone</th>
                                <th>Resident</th>
                                <th>Purpose</th>
                                <th>Entry Time</th>
                                <th>Exit Time</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($entries && $entries->num_rows > 0): ?>
                            <?php while ($row = $entries->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['visitor_name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($row['visitor_phone'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($row['resident_name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($row['purpose'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($row['entry_time'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($row['exit_time'] ?? '-'); ?></td>
                                    <td>
                                        <span class="status status-<?php echo htmlspecialchars(strtolower($row['status'])); ?>">
                                            <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $row['status']))); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row['status'] === 'approved'): ?>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="entry_id" value="<?php echo $row['id']; ?>">
                                                <input type="hidden" name="action" value="check_in">
                                                <button type="submit" class="action-btn check-in-btn">Check In</button>
                                            </form>
                                        <?php elseif ($row['status'] === 'checked_in'): ?>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="entry_id" value="<?php echo $row['id']; ?>">
                                                <input type="hidden" name="action" value="check_out">
                                                <button type="submit" class="action-btn check-out-btn">Check Out</button>
                                            </form>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="empty">No active entries found.</td>
                            </tr>
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