<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$user = current_user();
$db = get_db();

$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name  = trim($_POST['last_name']  ?? '');
        $email      = trim($_POST['email']       ?? '');
        $current_password = $_POST['current_password'] ?? '';
        $new_password     = $_POST['new_password']     ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($first_name)) $errors[] = 'First name is required';
        if (empty($last_name))  $errors[] = 'Last name is required';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address';

        $stmt = $db->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $stmt->execute([$email, $user['id']]);
        if ($stmt->fetch()) $errors[] = 'Email already in use';

        if (!empty($new_password)) {
            if (!password_verify($current_password, $user['password_hash'])) $errors[] = 'Current password is incorrect';
            if (strlen($new_password) < 8) $errors[] = 'New password must be at least 8 characters';
            if (!preg_match('/[A-Z]/', $new_password)) $errors[] = 'New password needs an uppercase letter';
            if (!preg_match('/[a-z]/', $new_password)) $errors[] = 'New password needs a lowercase letter';
            if (!preg_match('/[0-9]/', $new_password)) $errors[] = 'New password needs a number';
            if ($new_password !== $confirm_password) $errors[] = 'New passwords do not match';
        }

        if (empty($errors)) {
            $full_name = trim("$first_name $last_name");
            $update_fields = ['first_name = ?', 'last_name = ?', 'name = ?', 'email = ?'];
            $update_values = [encrypt_data($first_name), encrypt_data($last_name), encrypt_data($full_name), $email];
            if (!empty($new_password)) {
                $update_fields[] = 'password_hash = ?';
                $update_values[] = password_hash($new_password, PASSWORD_DEFAULT);
            }
            $update_values[] = $user['id'];
            $stmt = $db->prepare('UPDATE users SET ' . implode(', ', $update_fields) . ' WHERE id = ?');
            $stmt->execute($update_values);
            log_activity($user['id'], 'profile_update');
            $message = 'Profile updated successfully!';
            $user = current_user();
        }
    }
}

$stmt = $db->prepare('SELECT COUNT(*) as upload_count, MAX(created_at) as last_upload FROM uploads WHERE user_id = ?');
$stmt->execute([$user['id']]);
$stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile - Bradford Council Portal</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <a class="header-brand" href="portal.php">
            <div class="header-crest">&#9632;</div>
            <div class="header-brand-text"><h1>Bradford Council Portal</h1><p>Metropolitan District Council</p></div>
        </a>
        <div class="header-user">
            <span>&#128100; <?=htmlspecialchars($user['display_name'])?></span>
            <a href="portal.php" style="color:rgba(255,255,255,0.8); font-size:12px;">&#8592; Dashboard</a>
            <a href="logout.php" style="color:rgba(255,255,255,0.8); font-size:12px;">Sign Out</a>
        </div>
    </div>
</header>

<main class="portal-body">
    <h2>Profile Settings</h2>

    <?php if ($message): ?><div class="success-banner">&#10003; <?=htmlspecialchars($message)?></div><?php endif; ?>
    <?php if ($errors): ?><ul class="error-list"><?php foreach ($errors as $e): ?><li><?=htmlspecialchars($e)?></li><?php endforeach; ?></ul><?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card"><div class="stat-number"><?=$stats['upload_count'] ?? 0?></div><div class="stat-label">Total Uploads</div></div>
        <div class="stat-card"><div class="stat-number"><?=$stats['last_upload'] ? date('M j, Y', strtotime($stats['last_upload'])) : 'Never'?></div><div class="stat-label">Last Upload</div></div>
        <div class="stat-card"><div class="stat-number"><?=date('M j, Y', strtotime($user['created_at']))?></div><div class="stat-label">Member Since</div></div>
    </div>

    <div class="card">
        <div class="card-title">&#128100; Personal Information</div>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?=generate_csrf_token()?>">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div>
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" required value="<?=htmlspecialchars($user['first_name'] ?? '')?>">
                </div>
                <div>
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" required value="<?=htmlspecialchars($user['last_name'] ?? '')?>">
                </div>
            </div>
            <label for="email">Email Address *</label>
            <input type="email" id="email" name="email" required value="<?=htmlspecialchars($user['email'])?>">
            <button type="submit">Save Changes</button>
        </form>
    </div>

    <div class="card" style="border-left: 4px solid #f5a623;">
        <div class="card-title">&#128274; Change Password <span style="font-size:12px; font-weight:400; color:#6b7280;">(leave blank to keep current)</span></div>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?=generate_csrf_token()?>">
            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password">
            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" placeholder="Min 8 chars, upper, lower, number">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password">
            <button type="submit">Change Password</button>
        </form>
    </div>

    <div class="card">
        <div class="card-title">&#128272; Two-Factor Authentication</div>
        <p>Status: <?=!empty($user['two_factor_secret']) ? '<span style="color:#16a34a;font-weight:600;">&#10003; Enabled</span>' : '<span style="color:#dc2626;">&#9888; Not enabled</span>'?></p>
        <a href="twofactor_setup.php" class="btn" style="display:inline-block; text-decoration:none; font-size:13px; padding:8px 16px;">Manage 2FA Settings</a>
    </div>
</main>
</body>
</html>
