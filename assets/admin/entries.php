<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/database.php";

require_role('admin');

$name = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Admin';

$entries = $conn->query(
    "SELECT
        ve.id,
        ve.visitor_id,
        v.name AS visitor_name,
        v.phone AS visitor_phone,
        ve.resident_id,
        u.name AS resident_name,
        ve.security_id,
        s.name AS security_name,
        ve.purpose,
        ve.scheduled_date,
        ve.entry_time,
        ve.exit_time,
        ve.status,
        ve.created_at
     FROM visitor_entries ve
     LEFT JOIN visitors v ON ve.visitor_id = v.id
     LEFT JOIN users u ON ve.resident_id = u.id
     LEFT JOIN users s ON ve.security_id = s.id
     ORDER BY ve.id DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entries | BEMS Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="dashboard">

    <aside class="sidebar">
        <div class="brand">BEMS</div>
        <nav class="nav">
            <a href="/Building/assets/admin/dashboard.php">Dashboard</a>
            <a href="/Building/assets/admin/users.php">Users</a>
            <a href="/Building/assets/admin/visitors.php">Visitors</a>
            <a href="/Building/assets/admin/entries.php" class="active">Entries</a>
            <a href="#">Reports</a>
            <a href="#">Settings</a>
        </nav>
    </aside>

    <main class="main">
        <header class="header">
            <div class="header-title">Entry Management</div>
            <div class="header-user">
                <span class="user-name"><?php echo htmlspecialchars($name); ?></span>
                <a class="logout" href="/Building/assets/config/logout.php">Logout</a>
            </div>
        </header>

        <section class="content">
            <div class="welcome">
                <h1>Entry Management</h1>
                <p>View visitor entry and exit records in the building.</p>
            </div>

            <div class="page-section">
                <h2>All Entries</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Visitor</th>
                                <th>Phone</th>
                                <th>Resident</th>
                                <th>Security</th>
                                <th>Purpose</th>
                                <th>Scheduled Date</th>
                                <th>Entry Time</th>
                                <th>Exit Time</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($entries && $entries->num_rows > 0): ?>
                            <?php while ($entry = $entries->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($entry['id']); ?></td>
                                    <td><?php echo htmlspecialchars($entry['visitor_name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($entry['visitor_phone'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($entry['resident_name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($entry['security_name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($entry['purpose'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($entry['scheduled_date'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($entry['entry_time'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($entry['exit_time'] ?? '-'); ?></td>
                                    <td>
                                        <?php 
                                            $status = $entry['status'] ?? 'pending';
                                            $status_class = 'status-' . strtolower(str_replace(' ', '_', $status));
                                        ?>
                                        <span class="status <?php echo htmlspecialchars($status_class); ?>">
                                            <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $status))); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($entry['created_at']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" class="empty">No entries found.</td>
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