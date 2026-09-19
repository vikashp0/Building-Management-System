<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Building Entry Management System</title>
    <!-- Absolute Path Link to CSS -->
    <link rel="stylesheet" href="/Building/assets/style.css?v=2.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <header class="navbar">
        <div class="nav-container">
            <div class="logo">
                <span class="logo-badge">BEMS</span>
            </div>
            <div class="nav-title">Building Entry Management System</div>
        </div>
    </header>

    <main class="main">
        <div class="welcome">
            <span class="welcome-badge">Secure Access Portal</span>
            <h1>Welcome to BEMS</h1>
            <p>Streamlined visitor logging, security operations, and resident portal management.</p>
        </div>

        <div class="cards">
            <div class="card">
                <div class="card-header">
                    <div class="icon admin-icon">A</div>
                    <h2>Admin</h2>
                </div>
                <p>Manage residents, security staff, visitor logs, and core building configurations.</p>
                <a href="/Building/assets/login/login.php?role=admin" class="btn btn-admin">Admin Login</a>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="icon security-icon">S</div>
                    <h2>Security</h2>
                </div>
                <p>Real-time gate check-ins, check-outs, and active visitor verification.</p>
                <a href="/Building/assets/login/login.php?role=security" class="btn btn-security">Security Login</a>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="icon resident-icon">R</div>
                    <h2>Resident</h2>
                </div>
                <p>Request pre-approvals for guests, track visitor activity, and edit profile details.</p>
                <a href="/Building/assets/login/login.php?role=resident" class="btn btn-resident">Resident Login</a>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Building Entry Management System. All rights reserved.</p>
    </footer>

</body>
</html>