<?php
require_once 'config/db.php';

echo "<h2 style='font-family:sans-serif'>🔧 Login Debug & Fix</h2><pre style='font-family:monospace;font-size:14px'>";

try {
    // Show all users
    echo "=== ALL USERS IN DB ===\n";
    $stmt = $pdo->query("SELECT u.id, u.username, u.full_name, u.password, u.status, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($users)) {
        echo "❌ NO USERS FOUND!\n";
    }

    foreach ($users as $u) {
        echo "\nID: {$u['id']} | Username: {$u['username']} | Role: {$u['role_name']} | Status: {$u['status']}\n";
        echo "  admin123    : " . (password_verify('admin123',    $u['password']) ? '✅ MATCH' : '❌ NO') . "\n";
        echo "  operator123 : " . (password_verify('operator123', $u['password']) ? '✅ MATCH' : '❌ NO') . "\n";
        echo "  admin       : " . (password_verify('admin',       $u['password']) ? '✅ MATCH' : '❌ NO') . "\n";
    }

    // Fix: Reset all passwords correctly
    echo "\n\n=== FIXING PASSWORDS ===\n";

    $adminHash    = password_hash('admin123',    PASSWORD_DEFAULT);
    $operatorHash = password_hash('operator123', PASSWORD_DEFAULT);

    // Super Admin & Admin roles => admin123
    $stmt1 = $pdo->prepare("UPDATE users u JOIN roles r ON u.role_id = r.id SET u.password = ? WHERE r.name IN ('Super Admin','Admin')");
    $stmt1->execute([$adminHash]);
    echo "✅ Set 'admin123' for Super Admin & Admin users. Rows: " . $stmt1->rowCount() . "\n";

    // All other roles => operator123
    $stmt2 = $pdo->prepare("UPDATE users u JOIN roles r ON u.role_id = r.id SET u.password = ? WHERE r.name NOT IN ('Super Admin','Admin')");
    $stmt2->execute([$operatorHash]);
    echo "✅ Set 'operator123' for other users. Rows: " . $stmt2->rowCount() . "\n";

    // Make sure all users are active
    $stmt3 = $pdo->prepare("UPDATE users SET status = 'active'");
    $stmt3->execute();
    echo "✅ All users set to active. Rows: " . $stmt3->rowCount() . "\n";

    // Verify
    echo "\n=== VERIFY AFTER FIX ===\n";
    $stmt4 = $pdo->query("SELECT u.id, u.username, u.full_name, u.status, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.id");
    $users2 = $stmt4->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users2 as $u) {
        echo "Username: {$u['username']} | Role: {$u['role_name']} | Status: {$u['status']}\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<h3 style='font-family:sans-serif;color:green'>✅ Done! Now try logging in:</h3>";
echo "<ul style='font-family:sans-serif'>";
echo "<li><b>Super Admin/Admin users:</b> password = <code>admin123</code></li>";
echo "<li><b>Other users:</b> password = <code>operator123</code></li>";
echo "</ul>";
echo "<a href='login.php' style='font-family:sans-serif;padding:10px 20px;background:#0ea5e9;color:#fff;text-decoration:none;border-radius:6px'>Go to Login →</a>";
echo "<br><br><p style='color:red;font-family:sans-serif'>⚠️ DELETE this file after use: debug_fix.php</p>";
?>
