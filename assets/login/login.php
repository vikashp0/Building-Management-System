<?php

$role = $_GET['role'] ?? 'admin';

$role = strtolower($role);

if ($role == 'security') {

    $title = 'Security Login';
    $description = 'Building Entry Management System';

} elseif ($role == 'resident') {

    $title = 'Resident Login';
    $description = 'Building Entry Management System';

} else {

    $title = 'Admin Login';
    $description = 'Building Entry Management System';

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo $title; ?> | BEMS</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

    <div class="page">

        <div class="login-card">

            <div class="logo">
                BEMS
            </div>

            <h1>
                <?php echo $title; ?>
            </h1>

            <p class="subtitle">
                <?php echo $description; ?>
            </p>


            <form method="post">

                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                    >

                </div>


                <div class="login-options">

                    <label class="remember">

                        <input
                            type="checkbox"
                            name="remember"
                        >

                        <span>Remember me</span>

                    </label>


                    <a href="#">
                        Forgot Password?
                    </a>

                </div>


                <button type="submit">
                    Login
                </button>

            </form>


            <a class="back-home" href="../../index.php">
                ← Back to Home
            </a>

        </div>

    </div>

</body>

</html>