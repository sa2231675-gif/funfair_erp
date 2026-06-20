<?php
require_once 'config/db.php';

echo "<h2>Users in Database</h2><pre>";

try {
    $stmt = $pdo->query("SELECT u.id, u.username, u.full_name, u.password, u.status, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id ORDER BY r.id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "NO USERS FOUND IN DATABASE!\n";
    }
    
    foreach ($users as $u) {
        echo "ID: " . $u['id'] . " | Username: " . $u['username'] . " | Role: " . $u['role_name'] . " | Status: " . $u['status'] . "\n";
        echo "  Hash starts: " . substr($u['password'], 0, 25) . "...\n";
        echo "  admin123 match:    " . (password_verify('admin123',    $u['password']) ? 'YES ✓' : 'NO ✗') . "\n";
        echo "  operator123 match: " . (password_verify('operator123', $u['password']) ? 'YES ✓' : 'NO ✗') . "\n";
        echo "  admin match:       " . (password_verify('admin',       $u['password']) ? 'YES ✓' : 'NO ✗') . "\n";
        echo "  password match:    " . (password_verify('password',    $u['password']) ? 'YES ✓' : 'NO ✗') . "\n";
        echo "  123456 match:      " . (password_verify('123456',      $u['password']) ? 'YES ✓' : 'NO ✗') . "\n";
        echo "---\n";
    }
    
    // Also test manual login
    echo "\n<b>Testing login for 'superadmin' with 'admin123':</b>\n";
    $stmt2 = $pdo->prepare("SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.username = ? AND u.status = 'active'");
    $stmt2->execute(['superadmin']);
    $user = $stmt2->fetch();
    if ($user) {
        echo "User found: " . $user['username'] . " (role: " . $user['role_name'] . ")\n";
        echo "Password verify result: " . (password_verify('admin123', $user['password']) ? 'SUCCESS ✓' : 'FAILED ✗') . "\n";
    } else {
        echo "User 'superadmin' NOT FOUND or not active!\n";
        
        // Try to find any admin-type user
        echo "\nSearching for any Super Admin or Admin users:\n";
        $stmt3 = $pdo->query("SELECT u.username, u.status, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name IN ('Super Admin', 'Admin', 'superadmin', 'admin')");
        $admins = $stmt3->fetchAll(PDO::FETCH_ASSOC);
        if (empty($admins)) {
            echo "NO admin users found!\n";
        } else {
            foreach ($admins as $a) {
                echo "  Username: " . $a['username'] . " | Status: " . $a['status'] . " | Role: " . $a['role_name'] . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";

// Generate a fresh hash for admin123
echo "<h2>Fresh password hash for 'admin123':</h2>";
echo "<pre>" . password_hash('admin123', PASSWORD_DEFAULT) . "</pre>";

echo "<p><a href='login.php'>Back to Login</a></p>";
echo "<p style='color:red'><strong>DELETE this file after use!</strong></p>";
?>
