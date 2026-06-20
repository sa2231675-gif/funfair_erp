<?php
// Direct login test - bypasses the form completely
require_once 'config/db.php';

$username = 'superadmin';
$password = 'admin123';

echo "<h2 style='font-family:sans-serif'>🔐 Direct Login Test</h2>";
echo "<pre style='font-family:monospace;font-size:14px;background:#111;color:#0f0;padding:20px;border-radius:8px'>";

// Step 1: Find user
$stmt = $pdo->prepare("SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.username = ? AND u.status = 'active'");
$stmt->execute([$username]);
$user = $stmt->fetch();

echo "1. Looking for username: '$username'\n";
if (!$user) {
    echo "   ❌ USER NOT FOUND or NOT ACTIVE\n\n";
    
    // Show all usernames
    $all = $pdo->query("SELECT username, status FROM users")->fetchAll();
    echo "   All usernames in DB:\n";
    foreach($all as $u) echo "   - '{$u['username']}' (status: {$u['status']})\n";
} else {
    echo "   ✅ User found: {$user['username']} | Role: {$user['role_name']} | Status: {$user['status']}\n\n";
    
    // Step 2: Verify password
    echo "2. Password verification for '$password':\n";
    $result = password_verify($password, $user['password']);
    echo "   Result: " . ($result ? "✅ SUCCESS" : "❌ FAILED") . "\n\n";
    
    echo "   Stored hash: " . $user['password'] . "\n\n";
    
    if (!$result) {
        echo "3. Trying other common passwords:\n";
        $passwords = ['admin', 'Admin123', 'Admin@123', 'password', '123456', 'superadmin', 'funfair123'];
        foreach ($passwords as $p) {
            echo "   '$p': " . (password_verify($p, $user['password']) ? "✅ MATCH!" : "❌") . "\n";
        }
        
        // Fix it now
        echo "\n4. FIXING - Resetting password to 'admin123'...\n";
        $newHash = password_hash('admin123', PASSWORD_DEFAULT);
        $fix = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
        $fix->execute([$newHash, $username]);
        echo "   ✅ Password reset done! Rows updated: " . $fix->rowCount() . "\n";
        echo "   New hash: $newHash\n";
        
        // Verify fix
        $stmt2 = $pdo->prepare("SELECT password FROM users WHERE username = ?");
        $stmt2->execute([$username]);
        $newStored = $stmt2->fetchColumn();
        echo "   Verify fix: " . (password_verify('admin123', $newStored) ? "✅ NOW WORKS!" : "❌ STILL BROKEN") . "\n";
    }
}

echo "</pre>";

echo "<br><h3 style='font-family:sans-serif'>Test Login Form Below:</h3>";
echo "<div style='font-family:sans-serif;max-width:400px;background:#f5f5f5;padding:20px;border-radius:8px'>";

// Show result of POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = $_POST['u'] ?? '';
    $p = $_POST['p'] ?? '';
    $stmt3 = $pdo->prepare("SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.username = ? AND u.status = 'active'");
    $stmt3->execute([$u]);
    $testUser = $stmt3->fetch();
    echo "<div style='padding:10px;margin-bottom:10px;border-radius:6px;background:" . ($testUser && password_verify($p, $testUser['password']) ? "#d4edda;color:#155724" : "#f8d7da;color:#721c24") . "'>";
    if ($testUser && password_verify($p, $testUser['password'])) {
        echo "✅ <b>LOGIN SUCCESS!</b><br>Welcome: {$testUser['full_name']} ({$testUser['role_name']})";
    } else if (!$testUser) {
        echo "❌ Username '<b>$u</b>' not found or inactive";
    } else {
        echo "❌ Wrong password for user '$u'";
    }
    echo "</div>";
}

echo "
<form method='post'>
<label>Username: <input type='text' name='u' value='superadmin' style='width:100%;padding:8px;margin:5px 0;border-radius:4px;border:1px solid #ccc'></label><br>
<label>Password: <input type='text' name='p' value='admin123' style='width:100%;padding:8px;margin:5px 0;border-radius:4px;border:1px solid #ccc'></label><br>
<button type='submit' style='width:100%;padding:10px;background:#0ea5e9;color:#fff;border:none;border-radius:4px;font-size:16px;cursor:pointer;margin-top:10px'>Test Login</button>
</form>
</div>

<br><p style='color:red;font-family:sans-serif'>⚠️ Delete this file after use!</p>
<a href='login.php' style='font-family:sans-serif;padding:10px 20px;background:#0ea5e9;color:#fff;text-decoration:none;border-radius:6px'>→ Go to Real Login Page</a>
";
?>
