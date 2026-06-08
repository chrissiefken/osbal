<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/check.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/user.php';

// If already authenticated, redirect to reporting dashboard
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header('Location: /reporting/index.php');
    exit;
}

$config = checkConfig();
$alert = '';

// Check if there are any users. If config is missing or no users exist, redirect to installer
$users = getUsers();
if ($config['error'] == true || empty($users)) {
    $alert = '
        <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); padding: 16px; border-radius: 12px; margin-bottom: 24px; font-size: 0.95rem;">
            <strong style="color: var(--success);">First Time Here?</strong> You need to initialize the appliance settings.<br /> 
            <a href="install.php" style="color: var(--accent); text-decoration: underline; font-weight: 500;">Run the Install Wizard now &rarr;</a>
        </div>
    ';
}

$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($username) || empty($password)) {
        $loginError = 'Please enter both username and password.';
    } else {
        if (verifyUser($username, $password)) {
            $_SESSION['authenticated'] = true;
            $_SESSION['username'] = $username;
            header('Location: /reporting/index.php');
            exit;
        } else {
            $loginError = 'Invalid username or password.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>OSBal - Sign In</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/css/modern.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 0;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            margin: auto;
        }
        .logo-area {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo-title {
            font-size: 2.2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #fff 30%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 6px;
        }
        .logo-sub {
            color: var(--text-muted);
            font-size: 0.9rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo-area">
            <h1 class="logo-title">OSBal</h1>
            <div class="logo-sub">Loadbalancer stack</div>
        </div>

        <div class="card-glass">
            <h3 style="text-align: center; margin-bottom: 24px; font-weight: 500;">Please Sign In</h3>
            
            <?php echo $alert; ?>

            <?php if (!empty($loginError)): ?>
                <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: var(--danger); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem; text-align: center;">
                    <?php echo htmlspecialchars($loginError); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php">
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input class="form-control" placeholder="admin" name="username" id="username" type="text" required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" placeholder="••••••••" name="password" id="password" type="password" required>
                </div>
                
                <button class="btn btn-primary" type="submit" style="width: 100%; margin-top: 10px; justify-content: center; height: 50px;">
                    Sign In
                </button>
            </form>
        </div>
        
        <p style="text-align: center; font-size: 0.85rem;">
            <a href="https://github.com/siefkencp/osbal" target="_blank" style="color: var(--text-muted); text-decoration: none;">Learn more on GitHub &rarr;</a>
        </p>
    </div>
</body>
</html>
