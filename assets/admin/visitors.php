<?php
require_once "../config/auth.php";
require_once "../config/database.php";

require_role('admin');

$name = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Admin';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visitor_name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($visitor_name === '' || $phone === '') {
        $error = 'Visitor name and phone number are required.';
    } elseif (strlen($visitor_name) < 2) {
        $error = 'Please enter a valid visitor name.';
    } elseif (!preg_match('/^[0-9+\-\s]{10,20}$/', $phone)) {
        $error = 'Please enter a valid phone number.';
    } else {
        $stmt = $conn->prepare("INSERT INTO visitors (name, phone) VALUES (?, ?)");

        if ($stmt) {
            $stmt->bind_param("ss", $visitor_name, $phone);

            if ($stmt->execute()) {
                $message = 'Visitor added successfully.';
            } else {
                $error = 'Unable to add visitor.';
            }

            $stmt->close();
        } else {
            $error = 'Unable to process request.';
        }
    }
}

$visitors = $conn->query("SELECT id, name, phone, created_at FROM visitors ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitors | BEMS Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="dashboard">

    <aside class="sidebar">
        <div class="brand">BEMS</div>
        <nav class="nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="users.php">Users</a>
            <a href="visitors.php" class="active">Visitors</a>
            <a href="entries.php">Entries</a>
            <a href="#">Reports</a>
            <a href="#">Settings</a>
        </nav>
    </aside>

    <main class="main">
        <header class="header">
            <div class="header-title">Visitor Management</div>
            <div class="header-user">
                <span class="user-name"><?php echo htmlspecialchars($name); ?></span>
                <a class="logout" href="../logout.php">Logout</a>
            </div>
        </header>

        <section class="content">
            <div class="welcome">
                <h1>Visitor Management</h1>
                <p>Manage visitor records in the building.</p>
            </div>

            <div class="page-section">
                <h2>Add New Visitor</h2>

                <?php if ($message !== ''): ?>
                    <div class="message"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <?php if ($error !== ''): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="visitor-form">
                        <input type="text" name="name" placeholder="Visitor Full Name" required>
                        <input type="tel" name="phone" placeholder="Phone Number" required>

                        <div class="form-full">
                            <button type="submit" class="add-visitor-btn">Add Visitor</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="page-section">
                <h2>All Visitors</h2>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($visitors && $visitors->num_rows > 0): ?>
                            <?php while ($visitor = $visitors->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($visitor['id']); ?></td>
                                    <td><?php echo htmlspecialchars($visitor['name']); ?></td>
                                    <td><?php echo htmlspecialchars($visitor['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($visitor['created_at']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="empty">No visitors found.</td>
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