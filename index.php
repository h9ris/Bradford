<?php
require_once __DIR__ . '/includes/auth.php';

// if already logged in redirect to portal
if (current_user()) {
	header('Location: portal.php');
	exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$email = $_POST['email'] ?? '';
	$password = $_POST['password'] ?? '';
	if (login_user($email, $password)) {
		header('Location: portal.php');
		exit;
	} else {
		$error = 'Invalid email or password';
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Login - Bradford Portal</title>
	<link rel="stylesheet" href="css/style.css">
	<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
	<h1>Bradford Portal Login</h1>
	<?php if ($error): ?>
		<p class="error"><?=htmlspecialchars($error)?></p>
	<?php endif; ?>
	<form method="post">
		<label for="email">Email:</label>
		<input type="email" id="email" name="email" required>
		<label for="password">Password:</label>
		<input type="password" id="password" name="password" required>
		<button type="submit">Login</button>
	</form>
	<p><a href="register.php">Register a new account</a></p>
	<p><a href="forgot.php">Forgot password?</a></p>

	<p>If you already have an admin account, log in normally and an "Admin dashboard" link will appear on your portal page.</p>
	<p><a href="admin_login.php">Admin login</a> (use this if you're an administrator)</p>
</body>
</html>
