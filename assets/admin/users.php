<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/database.php";

require_role('admin');

$name = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Admin';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if ($user_name === '' || $email === '' || $password === '' || $role === '') {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!in_array($role, ['admin', 'security', 'resident'], true)) {
        $error = 'Invalid user role.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $check->bind_param("s", $email);
        $check->execute();
        $check_result = $check->get_result();

        if ($check_result->num_rows > 0) {
            $error = 'This email is already registered.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $user_name, $email, $hashed_password, $role);

            if ($stmt->execute()) {
                $message = 'User added successfully.';
            } else {
                $error = 'Unable to add user.';
            }

            $stmt->close();
        }

        $check->close();
    }
}

$users = $conn->query("SELECT id, name, email, role, created_at FROM users ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users | BEMS Admin</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .user-form {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        .user-form input, .user-form select {
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
        }
        .form-full {
            grid-column: span 2;
        }
        .add-user-btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="dashboard">

    <aside class="sidebar">
        <div class="brand">BEMS</div>
        <nav class="nav">
            <a href="/Building/assets/admin/dashboard.php">Dashboard</a>
            <a href="/Building/assets/admin/users.php" class="active">Users</a>
            <a href="/Building/assets/admin/visitors.php">Visitors</a>
            <a href="/Building/assets/admin/entries.php">Entries</a>
            <a href="#">Reports</a>
            <a href="#">Settings</a>
        </nav>
    </aside>

    <main class="main">
        <header class="header">
            <div class="header-title">User Management</div>
            <div class="header-user">
                <span class="user-name"><?php echo htmlspecialchars($name); ?></span>
                <a class="logout" href="/Building/assets/config/logout.php">Logout</a>
            </div>
        </header>

        <section class="content">
            <div class="welcome">
                <h1>User Management</h1>
                <p>Manage Admin, Security and Resident accounts.</p>
            </div>

            <div class="page-section">
                <h2>Add New User</h2>

                <?php if ($message !== ''): ?>
                    <div class="message"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <?php if ($error !== ''): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="user-form">
                        <input type="text" name="name" placeholder="Full Name" required>
                        <input type="email" name="email" placeholder="Email Address" required>
                        <input type="password" name="password" placeholder="Password" required>

                        <select name="role" required>
                            <option value="">Select Role</option>
                            <option value="resident">Resident</option>
                            <option value="security">Security</option>
                            <option value="admin">Admin</option>
                        </select>

                        <div class="form-full">
                            <button type="submit" class="add-user-btn">Add User</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="page-section">
                <h2>All Users</h2>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($users && $users->num_rows > 0): ?>
                            <?php while ($user = $users->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td class="role"><?php echo htmlspecialchars($user['role']); ?></td>
                                    <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="empty">No users found.</td>
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