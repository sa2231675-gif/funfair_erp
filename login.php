<?php
require_once 'config/config.php';

if (isLoggedIn() && !isset($_GET['switch']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$error = '';

// Fetch all active users for dropdown
$users_query = $pdo->query("SELECT u.username, u.full_name, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.status = 'active'");
$all_users = $users_query->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.username = ? AND u.status = 'active'");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role_id']   = $user['role_id'];
        $_SESSION['role_name'] = $user['role_name'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login | <?php echo SITE_NAME; ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --cyan:    #00f5ff;
      --purple:  #9333ea;
      --pink:    #f472b6;
      --dark:    #02020f;
      --card-bg: rgba(6, 8, 30, 0.85);
      --border:  rgba(0, 245, 255, 0.15);
    }

    body, html { height: 100%; font-family: 'Inter', sans-serif; background: var(--dark); overflow: hidden; }

    /* Split Screen Layout */
    .split-container { display: flex; height: 100vh; width: 100%; }

    /* Left Side - Visuals */
    .left-panel {
      flex: 1.2;
      position: relative;
      background: radial-gradient(circle at center, #0a0a20 0%, #02020f 100%);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }

    #particles-js { position: absolute; width: 100%; height: 100%; z-index: 1; }

    /* Spinning Ring Logo */
    .logo-container { position: relative; z-index: 10; display: flex; flex-direction: column; align-items: center; }
    
    .rings {
      position: relative;
      width: 250px; height: 250px;
      display: flex; align-items: center; justify-content: center;
      margin-bottom: 2rem;
    }
    
    .ring {
      position: absolute;
      border-radius: 50%;
      border: 2px solid transparent;
      animation: spin linear infinite;
    }
    
    .ring-1 { width: 100%; height: 100%; border-top-color: var(--cyan); border-left-color: var(--cyan); animation-duration: 4s; }
    .ring-2 { width: 85%; height: 85%; border-right-color: var(--pink); border-bottom-color: var(--pink); animation-duration: 5s; animation-direction: reverse; }
    .ring-3 { width: 70%; height: 70%; border-top-color: var(--purple); border-right-color: var(--purple); animation-duration: 3s; }
    
    .logo-img { width: 100px; z-index: 5; filter: drop-shadow(0 0 15px var(--cyan)); }
    
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

    .brand-title {
      font-family: 'Orbitron', sans-serif;
      font-size: 3.5rem;
      font-weight: 900;
      color: #fff;
      text-transform: uppercase;
      letter-spacing: 4px;
      text-shadow: 0 0 20px rgba(0, 245, 255, 0.8), 0 0 40px rgba(0, 245, 255, 0.4);
      z-index: 10;
    }

    .brand-subtitle { color: #94a3b8; font-size: 1.2rem; margin-top: 10px; z-index: 10; font-weight: 300; letter-spacing: 1px; }

    /* Right Side - Form */
    .right-panel {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(4, 5, 15, 0.95);
      position: relative;
      border-left: 1px solid var(--border);
      box-shadow: -20px 0 50px rgba(0,0,0,0.5);
    }

    .login-card {
      width: 100%; max-width: 450px; padding: 3rem;
      background: var(--card-bg);
      backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
      border: 1px solid var(--border);
      border-radius: 24px;
      box-shadow: 0 30px 60px rgba(0,0,0,0.6), inset 0 0 20px rgba(0,245,255,0.05);
    }

    .login-header { margin-bottom: 2.5rem; }
    .login-header h2 { color: #fff; font-size: 2rem; font-weight: 700; margin-bottom: 8px; font-family: 'Orbitron', sans-serif; }
    .login-header p { color: #94a3b8; font-size: 0.95rem; }

    .input-group { position: relative; margin-bottom: 1.5rem; }
    
    .input-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--cyan); font-size: 1.2rem; pointer-events: none; }
    
    .form-control {
      width: 100%;
      background: rgba(0, 0, 0, 0.5);
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 12px;
      padding: 1rem 1rem 1rem 3rem;
      color: #fff;
      font-size: 1rem;
      transition: all 0.3s;
    }
    
    .form-control:focus { outline: none; border-color: var(--cyan); box-shadow: 0 0 15px rgba(0, 245, 255, 0.2); background: rgba(0, 0, 0, 0.8); }

    .btn-login {
      width: 100%; padding: 1.2rem;
      background: linear-gradient(135deg, var(--cyan), #0284c7);
      border: none; border-radius: 12px;
      color: #000; font-weight: 800; font-size: 1.1rem;
      text-transform: uppercase; letter-spacing: 2px;
      cursor: pointer; transition: all 0.3s;
      box-shadow: 0 10px 20px rgba(0, 245, 255, 0.3);
      margin-top: 1rem;
    }
    
    .btn-login:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(0, 245, 255, 0.5); }

    /* Custom Dropdown */
    .user-dropdown {
      position: absolute; top: calc(100% + 5px); left: 0; right: 0;
      background: rgba(10, 12, 35, 0.95); backdrop-filter: blur(10px);
      border: 1px solid var(--border); border-radius: 12px;
      max-height: 250px; overflow-y: auto; z-index: 1000; display: none; padding: 5px;
    }
    
    .dropdown-item {
      padding: 12px 15px; color: #cbd5e1; cursor: pointer; border-radius: 8px;
      display: flex; justify-content: space-between; align-items: center; transition: all 0.2s;
    }
    .dropdown-item:hover { background: rgba(0, 245, 255, 0.1); color: #fff; transform: translateX(5px); }
    
    .role-badge {
      font-size: 0.7rem; padding: 4px 8px; border-radius: 6px;
      background: rgba(0, 245, 255, 0.15); color: var(--cyan); font-weight: 700;
    }
    
    .alert { padding: 12px 15px; border-radius: 12px; margin-bottom: 1.5rem; font-size: 0.9rem; font-weight: 500; }
    .alert-danger { background: rgba(244, 63, 94, 0.1); border: 1px solid rgba(244, 63, 94, 0.3); color: #f43f5e; }
    
    .register-link { text-align: center; margin-top: 2rem; color: #94a3b8; }
    .register-link a { color: var(--cyan); text-decoration: none; font-weight: 600; transition: all 0.2s; }
    .register-link a:hover { text-shadow: 0 0 10px var(--cyan); }

    /* Responsive */
    @media (max-width: 992px) {
      .split-container { flex-direction: column; }
      .left-panel { flex: 0.8; }
      .right-panel { border-left: none; border-top: 1px solid var(--border); padding: 2rem; }
      .brand-title { font-size: 2.5rem; }
      .rings { width: 150px; height: 150px; }
      .logo-img { width: 60px; }
    }
  </style>
</head>
<body>

<div class="split-container">
  <div class="left-panel">
    <div id="particles-js"></div>
    <div class="logo-container">
      <div class="rings">
        <div class="ring ring-1"></div>
        <div class="ring ring-2"></div>
        <div class="ring ring-3"></div>
        <img src="assets/images/logo.png" alt="Logo" class="logo-img" onerror="this.style.display='none'">
      </div>
      <h1 class="brand-title">FunFair</h1>
      <p class="brand-subtitle">Enterprise Resource Planning</p>
    </div>
  </div>

  <div class="right-panel">
    <div class="login-card">
      <div class="login-header">
        <h2>Welcome Back</h2>
        <p>Access your secure dashboard to manage operations.</p>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error; ?></div>
      <?php endif; ?>

      <form action="" method="post" autocomplete="off">
        <div class="input-group">
          <i class="fas fa-user input-icon"></i>
          <input type="text" name="username" id="usernameInput" class="form-control" placeholder="Username" required>
          
          <div id="userDropdown" class="user-dropdown">
            <?php foreach($all_users as $u): ?>
              <div class="dropdown-item" data-username="<?php echo htmlspecialchars($u['username']); ?>" data-password="<?php echo in_array($u['role_name'], ['Super Admin', 'Admin']) ? 'admin123' : 'operator123'; ?>">
                <div>
                  <strong style="display:block;"><?php echo htmlspecialchars($u['username']); ?></strong>
                  <small style="opacity:0.7;"><?php echo htmlspecialchars($u['full_name']); ?></small>
                </div>
                <span class="role-badge"><?php echo htmlspecialchars($u['role_name']); ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="input-group">
          <i class="fas fa-lock input-icon"></i>
          <input type="password" name="password" id="passwordInput" class="form-control" placeholder="Password" autocomplete="new-password" required>
        </div>

        <button type="submit" class="btn-login">Authenticate</button>
      </form>

      <div class="register-link">
        New staff member? <br>
        <a href="register.php">Register your account</a>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
<script>
  // Canvas Particles
  particlesJS("particles-js", {
    "particles": {
      "number": { "value": 100, "density": { "enable": true, "value_area": 800 } },
      "color": { "value": ["#00f5ff", "#9333ea", "#f472b6"] },
      "shape": { "type": "circle" },
      "opacity": { "value": 0.6, "random": true },
      "size": { "value": 3, "random": true },
      "line_linked": { "enable": true, "distance": 150, "color": "#00f5ff", "opacity": 0.2, "width": 1 },
      "move": { "enable": true, "speed": 1.5, "direction": "none", "random": true, "straight": false, "out_mode": "out", "bounce": false }
    },
    "interactivity": {
      "detect_on": "canvas",
      "events": { "onhover": { "enable": true, "mode": "grab" }, "resize": true },
      "modes": { "grab": { "distance": 200, "line_linked": { "opacity": 0.6 } } }
    },
    "retina_detect": true
  });

  // Dropdown Logic
  $(document).ready(function() {
      const $input = $('#usernameInput');
      const $dropdown = $('#userDropdown');
      const $items = $('.dropdown-item');
      const $password = $('#passwordInput');

      $input.on('focus click input', function(e) {
          e.stopPropagation();
          $dropdown.slideDown(200);
          const val = $(this).val().toLowerCase();
          $items.each(function() {
              const text = $(this).text().toLowerCase();
              $(this).toggle(text.includes(val));
          });
      });

      $items.on('click', function(e) {
          e.stopPropagation();
          $input.val($(this).data('username'));
          
          const pass = $(this).data('password');
          $password.val(pass ? pass : 'operator123');
          
          $dropdown.slideUp(200);
      });

      $(document).on('click', function(e) {
          if (!$(e.target).closest('.input-group').length) {
              $dropdown.slideUp(200);
          }
      });
  });
</script>
</body>
</html>
