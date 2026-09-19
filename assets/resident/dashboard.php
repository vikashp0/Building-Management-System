<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/database.php";

require_role('resident');

$resident_id = $_SESSION['user_id'];
$resident_name = $_SESSION['user_name'];

$total_requests = 0;
$pending_requests = 0;
$approved_requests = 0;

$stmt = $conn->prepare("
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved
    FROM visitor_entries
    WHERE resident_id = ?
");

if ($stmt) {
    $stmt->bind_param("i", $resident_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $total_requests = $row['total'] ?? 0;
        $pending_requests = $row['pending'] ?? 0;
        $approved_requests = $row['approved'] ?? 0;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Dashboard - BEMS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #172033;
        }

        .dashboard {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            min-height: 100vh;
            background: #0f172a;
            padding: 30px 20px;
        }

        .logo {
            color: white;
            font-size: 28px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 50px;
        }

        .nav {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .nav a {
            display: block;
            padding: 14px 16px;
            color: #e2e8f0;
            text-decoration: none;
            border-radius: 8px;
        }

        .nav a:hover,
        .nav a.active {
            background: #2563eb;
            color: white;
        }

        .main {
            flex: 1;
        }

        .topbar {
            height: 75px;
            background: white;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 0 35px;
            gap: 25px;
        }

        .resident-name {
            font-weight: bold;
        }

        .logout {
            color: #dc2626;
            text-decoration: none;
            font-weight: bold;
        }

        .content {
            padding: 35px;
        }

        .welcome h1 {
            font-size: 30px;
            margin-bottom: 8px;
        }

        .welcome p {
            color: #64748b;
            margin-bottom: 30px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        .card h3 {
            color: #64748b;
            font-size: 15px;
            margin-bottom: 15px;
        }

        .number {
            font-size: 32px;
            font-weight: bold;
        }

        .request-box {
            margin-top: 30px;
            background: white;
            padding: 25px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        .request-box h2 {
            margin-bottom: 10px;
        }

        .request-box p {
            color: #64748b;
            margin-bottom: 20px;
        }

        .request-button {
            display: inline-block;
            background: #2563eb;
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 7px;
            font-weight: bold;
        }

        .request-button:hover {
            background: #1d4ed8;
        }

        @media (max-width: 800px) {
            .dashboard {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                min-height: auto;
            }

            .cards {
                grid-template-columns: 1fr;
            }

            .content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<div class="dashboard">

    <aside class="sidebar">
        <div class="logo">BEMS</div>
        <nav class="nav">
            <a href="/Building/assets/resident/dashboard.php" class="active">Dashboard</a>
            <a href="#">My Visitors</a>
            <a href="#">Notifications</a>
            <a href="#">Profile</a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <span class="resident-name"><?php echo htmlspecialchars($resident_name); ?></span>
            <a href="/Building/assets/config/logout.php" class="logout">Logout</a>
        </header>

        <section class="content">
            <div class="welcome">
                <h1>Welcome, <?php echo htmlspecialchars($resident_name); ?></h1>
                <p>Manage your visitor requests from here.</p>
            </div>

            <div class="cards">
                <div class="card">
                    <h3>Total Requests</h3>
                    <div class="number"><?php echo $total_requests; ?></div>
                </div>

                <div class="card">
                    <h3>Pending Requests</h3>
                    <div class="number"><?php echo $pending_requests; ?></div>
                </div>

                <div class="card">
                    <h3>Approved Requests</h3>
                    <div class="number"><?php echo $approved_requests; ?></div>
                </div>
            </div>

            <div class="request-box">
                <h2>Visitor Requests</h2>
                <p>Create a new visitor entry request for your guest.</p>
                <a href="#" class="request-button">Create Visitor Request</a>
            </div>
        </section>
    </main>

</div>

</body>
</html>