<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/user.php';

$feedback = '';

// Handle creating user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_user') {
    $username = trim($_POST['uname']);
    $password = $_POST['passwd'];
    $confirm = $_POST['conpasswd'];

    if (empty($username) || empty($password)) {
        $feedback = '<div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: var(--danger); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">Username and password are required.</div>';
    } elseif ($password !== $confirm) {
        $feedback = '<div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: var(--danger); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">Passwords do not match.</div>';
    } else {
        $users = getUsers();
        if (isset($users[$username])) {
            $feedback = '<div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: var(--danger); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">User already exists.</div>';
        } else {
            createUser($username, $password);
            $feedback = '<div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: var(--success); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">User account created successfully.</div>';
        }
    }
}

// Handle deleting user
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['user'])) {
    $usernameToDelete = $_GET['user'];
    
    // Check if user is deleting themselves
    if (isset($_SESSION['username']) && $_SESSION['username'] === $usernameToDelete) {
        $feedback = '<div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: var(--danger); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">You cannot delete your own currently logged-in account.</div>';
    } else {
        deleteUser($usernameToDelete);
        $feedback = '<div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: var(--success); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">User deleted successfully.</div>';
    }
}

$activeUsers = array_keys(getUsers());
?>

<div style="margin-bottom: 30px;">
    <h1>User Accounts</h1>
    <p>Manage administrative user credentials authorized to configure this appliance.</p>
</div>

<?php echo $feedback; ?>

<div class="grid-2">
    <!-- Active User List -->
    <div class="card-glass">
        <h3 style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 20px;">Administrative Users</h3>
        <div class="list-group">
            <?php foreach ($activeUsers as $username): ?>
                <div class="list-item" style="display:flex; justify-content:space-between; align-items:center;">
                    <div style="display:flex; align-items:center; gap: 10px;">
                        <div style="background: rgba(92, 98, 236, 0.1); width: 36px; height: 36px; border-radius: 50%; display:grid; place-items:center; color: var(--accent);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        </div>
                        <div>
                            <div style="font-weight: 500;"><?php echo htmlspecialchars($username); ?></div>
                            <div style="font-size:0.75rem; color:var(--text-muted);">
                                <?php echo (isset($_SESSION['username']) && $_SESSION['username'] === $username) ? 'Active Session' : 'Administrator'; ?>
                            </div>
                        </div>
                    </div>
                    <?php if (count($activeUsers) > 1 && (!isset($_SESSION['username']) || $_SESSION['username'] !== $username)): ?>
                        <a href="?action=delete&user=<?php echo urlencode($username); ?>" class="btn btn-danger" style="padding: 6px 12px; font-size:0.8rem;" onclick="return confirm('Are you sure you want to delete this user?');">
                            Remove
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Create User Form -->
    <div class="card-glass">
        <h3 style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 20px;">Create Account</h3>
        <form method="POST" action="index.php">
            <input type="hidden" name="action" value="create_user">
            
            <div class="form-group">
                <label class="form-label" for="uname">Username</label>
                <input type="text" class="form-control" id="uname" name="uname" placeholder="Enter username" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="passwd">Password</label>
                <input type="password" class="form-control" id="passwd" name="passwd" placeholder="Enter password" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="conpasswd">Confirm Password</label>
                <input type="password" class="form-control" id="conpasswd" name="conpasswd" placeholder="Re-enter password" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px; justify-content: center; height:45px;">
                Create Account
            </button>
        </form>
    </div>
</div>

<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/footer.php';
?>