<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../config/database.php";

$role = $_GET['role'] ?? 'admin';
if (!in_array($role, ['admin', 'security', 'resident'], true)) {
    $role = 'admin';
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? AND role = ? LIMIT 1");
        
        if ($stmt) {
            $stmt->bind_param("ss", $email, $role);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($user = $result->fetch_assoc()) {
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['username'] = $user['name'];
                    $_SESSION['user_role'] = $user['role'];

                    if ($user['role'] === 'admin') {
                        header("Location: /Building/assets/admin/dashboard.php");
                    } elseif ($user['role'] === 'security') {
                        header("Location: /Building/assets/security/dashboard.php");
                    } elseif ($user['role'] === 'resident') {
                        header("Location: /Building/assets/resident/dashboard.php");
                    }
                    exit();
                } else {
                    $error = 'Invalid password.';
                }
            } else {
                $error = 'No account found with this email for the selected role.';
            }

            $stmt->close();
        } else {
            $error = 'Unable to process login. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($role); ?> Portal | BEMS</title>
    <link rel="stylesheet" href="/Building/assets/style.css?v=4.0">
    <style>
        .login-wrapper {
            min-height: calc(100vh - 120px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .login-card {
            background: rgba(15, 23, 42, 0.75) !important;
            backdrop-filter: blur(25px) !important;
            -webkit-backdrop-filter: blur(25px) !important;
            border: 1px solid rgba(255, 255, 255, 0.18) !important;
            width: 100%;
            max-width: 420px;
            padding: 40px 35px;
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5) !important;
        }

        .login-header {
            text-align: center;
            margin-bottom: 28px;
        }

        .login-header h2 {
            font-size: 26px;
            font-weight: 800;
            color: #ffffff !important;
            text-transform: capitalize;
            margin-bottom: 8px;
        }

        .login-header p {
            color: #94a3b8 !important;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 700;
            color: #cbd5e1 !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            background: rgba(255, 255, 255, 0.07) !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            border-radius: 12px;
            font-size: 14px;
            color: #ffffff !important;
            outline: none;
            transition: all 0.2s ease;
        }

        .form-group input::placeholder {
            color: #64748b !important;
        }

        .form-group input:focus {
            border-color: #3b82f6 !important;
            background: rgba(255, 255, 255, 0.12) !important;
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.3) !important;
        }

        .login-submit-btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 15px;
            color: #ffffff;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.9), rgba(29, 78, 216, 0.9));
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.4);
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 10px;
        }

        .login-submit-btn:hover {
            transform: translateY(-2px);
            filter: brightness(1.15);
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 22px;
            color: #94a3b8 !important;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .back-link:hover {
            color: #ffffff !important;
        }
    </style>
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

    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <h2><?php echo htmlspecialchars($role); ?> Portal</h2>
                <p>Sign in to access your dashboard</p>
            </div>

            <?php if ($error !== ''): ?>
                <div style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.4); color: #fca5a5; padding: 12px 16px; border-radius: 12px; font-size: 14px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="name@example.com" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter password" required>
                </div>

                <button type="submit" class="login-submit-btn">Sign In to Dashboard</button>
            </form>

            <a href="/Building/index.php" class="back-link">&larr; Back to Role Selection</a>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Building Entry Management System. All rights reserved.</p>
    </footer>

</body>
</html>