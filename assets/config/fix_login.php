<?php
require_once __DIR__ . "/database.php";

// Standard Test Accounts
$accounts = [
    ['name' => 'System Admin', 'email' => 'admin@bems.com', 'pass' => 'admin123', 'role' => 'admin'],
    ['name' => 'Security Guard', 'email' => 'security@bems.com', 'pass' => 'security123', 'role' => 'security'],
    ['name' => 'Resident User', 'email' => 'resident@bems.com', 'pass' => 'resident123', 'role' => 'resident']
];

echo "<h2>BEMS Database Auto-Fix Tool</h2>";

foreach ($accounts as $acc) {
    $hash = password_hash($acc['pass'], PASSWORD_DEFAULT);
    
    // Clean old records for exact match
    $stmt = $conn->prepare("DELETE FROM users WHERE email = ?");
    $stmt->bind_param("s", $acc['email']);
    $stmt->execute();
    $stmt->close();

    // Insert fresh clean record
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $acc['name'], $acc['email'], $hash, $acc['role']);
    
    if ($stmt->execute()) {
        echo "<p style='color:green;'>SUCCESS: Created account <b>{$acc['email']}</b> | Role: <b>{$acc['role']}</b> | Password: <b>{$acc['pass']}</b></p>";
    } else {
        echo "<p style='color:red;'>ERROR: " . $conn->error . "</p>";
    }
    $stmt->close();
}

echo "<hr><p><b>Ab Login Page Par Wapas Jaakar Login Karein!</b></p>";
?>