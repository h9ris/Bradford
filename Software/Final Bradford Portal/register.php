<?php
require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    header('Location: portal.php');
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email      = $_POST['email']      ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name']  ?? '');
    $password   = $_POST['password']   ?? '';
    $password2  = $_POST['password2']  ?? '';
    $adminCode  = $_POST['admin_code'] ?? '';

    if (empty($first_name)) $errors[] = 'First name is required';
    if (empty($last_name))  $errors[] = 'Last name is required';
    if ($password !== $password2) $errors[] = 'Passwords do not match';
    if (strlen($password) < 8)    $errors[] = 'Password must be at least 8 characters long';
    if (!preg_match('/[A-Z]/', $password)) $errors[] = 'Password must contain at least one uppercase letter';
    if (!preg_match('/[a-z]/', $password)) $errors[] = 'Password must contain at least one lowercase letter';
    if (!preg_match('/[0-9]/', $password)) $errors[] = 'Password must contain at least one number';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address';

    if (empty($errors)) {
        try {
            $makeAdmin = ($adminCode === ADMIN_REGISTRATION_CODE);
            register_user($email, $password, $first_name, $last_name, $makeAdmin);
            require_once __DIR__ . '/includes/mailer.php';
            send_registration_email($email, $first_name, $last_name);
            header('Location: index.php?registered=1');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Email already registered';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Bradford Council Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-logo">
                <div class="bradford-crest">&#9632;</div>
                <h1>Bradford Council</h1>
                <p class="auth-subtitle">Create your portal account</p>
            </div>
            <?php if ($errors): ?>
                <ul class="error-list">
                <?php foreach ($errors as $e): ?>
                    <li><?=htmlspecialchars($e)?></li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <form method="post" class="auth-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name <span class="required">*</span></label>
                        <input type="text" id="first_name" name="first_name" required
                               value="<?=htmlspecialchars($_POST['first_name'] ?? '')?>"
                               placeholder="e.g. John">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name <span class="required">*</span></label>
                        <input type="text" id="last_name" name="last_name" required
                               value="<?=htmlspecialchars($_POST['last_name'] ?? '')?>"
                               placeholder="e.g. Smith">
                    </div>
                </div>
                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email" required
                           value="<?=htmlspecialchars($_POST['email'] ?? '')?>"
                           placeholder="you@example.com">
                </div>
                <div class="form-group">
                    <label for="admin_code">Admin Code <span class="optional">(if provided)</span></label>
                    <input type="text" id="admin_code" name="admin_code" placeholder="Leave blank if not an admin">
                </div>
                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password" required placeholder="Min 8 chars, upper, lower, number">
                </div>
                <div class="form-group">
                    <label for="password2">Confirm Password <span class="required">*</span></label>
                    <input type="password" id="password2" name="password2" required placeholder="Repeat your password">
                </div>
                <button type="submit" class="btn-primary btn-full">Create Account</button>
            </form>
            <p class="auth-footer">Already have an account? <a href="index.php">Sign in here</a></p>
        </div>
    </div>
</body>
</html>
