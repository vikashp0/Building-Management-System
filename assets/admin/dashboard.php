<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/database.php";

require_role('admin');

$admin_id = $_SESSION['user_id'] ?? 0;
$admin_name = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Admin';

$total_users = 0;
$total_residents = 0;
$total_security = 0;
$total_visitors = 0;

// Fetch User Role Breakdown Safely
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

// Fetch Visitors Count (Fallback for 'visitors' vs 'visitor_entries')
$table_check = $conn->query("SHOW TABLES LIKE 'visitors'");
$visitor_table = ($table_check && $table_check->num_rows > 0) ? 'visitors' : 'visitor_entries';

$stmt_visitors = $conn->prepare("SELECT COUNT(*) AS total FROM {$visitor_table}");
if ($stmt_visitors) {
    $stmt_visitors->execute();
    $result_visitors = $stmt_visitors->get_result();
    if ($row = $result_visitors->fetch_assoc()) {
        $total_visitors = $row['total'] ?? 0;
    }
    $stmt_visitors->close();
}

// Fetch Recent Registered Accounts with Dynamic Column Detection
$recent_users = [];
$col_check = $conn->query("SHOW COLUMNS FROM users LIKE 'username'");
$name_col = ($col_check && $col_check->num_rows > 0) ? 'username' : 'name';

$u_res = $conn->query("SELECT id, {$name_col} AS username, email, role FROM users ORDER BY id DESC LIMIT 10");
if ($u_res) {
    while ($r = $u_res->fetch_assoc()) {
        $recent_users[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BEMS</title>
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
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.22) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(168, 85, 247, 0.18) 0px, transparent 50%),
                radial-gradient(at 50% 100%, rgba(59, 130, 246, 0.15) 0px, transparent 50%);
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
            background: #6366f1; border-radius: 50%; box-shadow: 0 0 16px #6366f1;
        }

        .nav { display: flex; flex-direction: column; gap: 8px; }

        .nav a {
            display: flex; align-items: center; gap: 12px; width: 100%;
            padding: 12px 16px; color: #ffffff; text-decoration: none; border-radius: 12px;
            font-size: 14px; font-weight: 600; transition: all 0.2s ease;
        }

        .nav a.active, .nav a:hover {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.9), rgba(79, 70, 229, 0.9));
            font-weight: 700; box-shadow: 0 4px 20px rgba(99, 102, 241, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.2);
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

        .welcome { margin-bottom: 24px; }
        .welcome h1 { font-size: 26px; font-weight: 800; color: #ffffff; margin-bottom: 6px; }
        .welcome p { color: #94a3b8; font-size: 14px; }

        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px;
        }

        .stat-card {
            background: var(--glass-card); backdrop-filter: var(--blur);
            border: 1px solid var(--glass-border); border-radius: 20px; padding: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .stat-card h3 { font-size: 13px; color: #94a3b8; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; margin-bottom: 10px; }
        .stat-card .val { font-size: 32px; font-weight: 800; color: #ffffff; }

        .page-section {
            background: var(--glass-card); backdrop-filter: var(--blur);
            border: 1px solid var(--glass-border); border-radius: 20px; padding: 28px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3); margin-bottom: 30px;
        }

        .page-section h2 { font-size: 20px; font-weight: 700; color: #ffffff; margin-bottom: 6px; }
        .page-section p { color: #94a3b8; font-size: 14px; margin-bottom: 20px; }

        .table-wrapper { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: separate; border-spacing: 0; }
        th, td { padding: 16px 20px; text-align: left; font-size: 14px; white-space: nowrap; }
        th {
            background: rgba(255, 255, 255, 0.05); color: #94a3b8; font-weight: 700;
            text-transform: uppercase; font-size: 12px; letter-spacing: 0.8px;
            border-bottom: 1px solid var(--glass-border);
        }
        td { color: #f1f5f9; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }

        .role-badge {
            padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase;
        }
        .role-admin { background: rgba(99, 102, 241, 0.2); border: 1px solid rgba(99, 102, 241, 0.4); color: #818cf8; }
        .role-security { background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.4); color: #34d399; }
        .role-resident { background: rgba(245, 158, 11, 0.2); border: 1px solid rgba(245, 158, 11, 0.4); color: #fbbf24; }
    </style>
</head>
<body>

<div class="dashboard">

    <aside class="sidebar">
        <div class="brand">BEMS Console</div>
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

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <div class="val"><?php echo $total_users; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Residents</h3>
                    <div class="val" style="color: #fbbf24;"><?php echo $total_residents; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Security Personnel</h3>
                    <div class="val" style="color: #34d399;"><?php echo $total_security; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Visitors</h3>
                    <div class="val" style="color: #818cf8;"><?php echo $total_visitors; ?></div>
                </div>
            </div>

            <!-- Users Accounts Section -->
            <div class="page-section">
                <h2>Registered Accounts</h2>
                <p>Overview of system users and assigned roles</p>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Role</th>
                                <th>Username</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_users)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #94a3b8;">No registered users found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_users as $u): ?>
                                    <tr>
                                        <td>#<?php echo $u['id']; ?></td>
                                        <td>
                                            <span class="role-badge role-<?php echo strtolower($u['role']); ?>">
                                                <?php echo htmlspecialchars($u['role']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($u['username']); ?></td>
                                        <td><?php echo htmlspecialchars($u['email']); ?></td>
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