<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/database.php";

require_role('admin');

$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Admin';

$total_users = 0;
$total_residents = 0;
$total_security = 0;
$total_visitors = 0;

$stmt = $conn->prepare("
    SELECT 
        COUNT(*) AS total_users,
        SUM(CASE WHEN role = 'resident' THEN 1 ELSE 0 END) AS residents,
        SUM(CASE WHEN role = 'security' THEN 1 ELSE 0 END) AS security
    FROM users
");

if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $total_users = $row['total_users'] ?? 0;
        $total_residents = $row['residents'] ?? 0;
        $total_security = $row['security'] ?? 0;
    }
    $stmt->close();
}

$stmt_visitors = $conn->prepare("SELECT COUNT(*) AS total FROM visitor_entries");
if ($stmt_visitors) {
    $stmt_visitors->execute();
    $result_visitors = $stmt_visitors->get_result();
    if ($row = $result_visitors->fetch_assoc()) {
        $total_visitors = $row['total'] ?? 0;
    }
    $stmt_visitors->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BEMS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="dashboard">

    <aside class="sidebar">
        <div class="brand">BEMS</div>
        <nav class="nav">
            <a href="/Building/assets/admin/dashboard.php" class="active">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
            <a href="/Building/assets/admin/users.php">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Users
            </a>
            <a href="/Building/assets/admin/visitors.php">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Visitors
            </a>
            <a href="/Building/assets/admin/entries.php">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Entries
            </a>
            <a href="#">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                Reports
            </a>
            <a href="#">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                Settings
            </a>
        </nav>
    </aside>

    <main class="main">
        <header class="header">
            <div class="header-title">Admin Console</div>
            <div class="header-user">
                <span class="user-name"><?php echo htmlspecialchars($admin_name); ?></span>
                <a href="/Building/assets/config/logout.php" class="logout">Logout</a>
            </div>
        </header>

        <section class="content">
            <div class="welcome">
                <h1>Overview & Analytics</h1>
                <p>Welcome back! Here is what's happening across the building today.</p>
            </div>

            <div class="stats">
                <div class="card">
                    <div class="card-title">Total Users</div>
                    <div class="card-value"><?php echo $total_users; ?></div>
                </div>

                <div class="card">
                    <div class="card-title">Residents</div>
                    <div class="card-value"><?php echo $total_residents; ?></div>
                </div>

                <div class="card">
                    <div class="card-title">Security Personnel</div>
                    <div class="card-value"><?php echo $total_security; ?></div>
                </div>

                <div class="card">
                    <div class="card-title">Total Visitors</div>
                    <div class="card-value"><?php echo $total_visitors; ?></div>
                </div>
            </div>

            <div class="page-section">
                <h2>Visitor Operations</h2>
                <p style="color: #64748b; margin-bottom: 20px; font-size: 14px;">Direct access to visitor entries and approval logs.</p>
                <a href="/Building/assets/admin/visitors.php" class="btn">
                    <span>Manage Visitors</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
            </div>
        </section>
    </main>

</div>

</body>
</html>