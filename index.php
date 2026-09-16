<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Building Entry Management System</title>

    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

    <nav class="navbar">

        <div class="logo">
            BEMS
        </div>

        <div class="nav-title">
            Building Entry Management System
        </div>

    </nav>


    <main class="main">

        <div class="welcome">

            <h1>Welcome</h1>

            <p>
                Secure and simple building entry management system
            </p>

        </div>


        <div class="cards">

            <!-- Admin -->

            <div class="card">

                <div class="icon admin-icon">
                    A
                </div>

                <h2>Admin</h2>

                <p>
                    Manage residents, security staff,
                    visitors and building information.
                </p>

                <a href="assets/login/login.php" class="btn">
                    Admin Login
                </a>

            </div>


            <!-- Security -->

            <div class="card">

                <div class="icon security-icon">
                    S
                </div>

                <h2>Security</h2>

                <p>
                    Manage visitor entry, exit and
                    building security.
                </p>

                <a href="assets/login/login.php?role=security" class="btn">
                   Security Login
                </a>

            </div>


            <!-- Resident -->

            <div class="card">

                <div class="icon resident-icon">
                    R
                </div>

                <h2>Resident</h2>

                <p>
                    Manage your profile, visitors
                    and entry requests.
                </p>

                <a href="assets/login/login.php?role=resident" class="btn">
                    Resident Login
                </a>

            </div>

        </div>

    </main>


    <footer>

        <p>
            © 2026 Building Entry Management System
        </p>

    </footer>

</body>
</html>