<?php
require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
	header('Location: portal.php');
	exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$email = $_POST['email'] ?? '';
	$name = $_POST['name'] ?? null;
	$password = $_POST['password'] ?? '';
	$password2 = $_POST['password2'] ?? '';
	$adminCode = $_POST['admin_code'] ?? '';
	if ($password !== $password2) {
		$errors[] = 'Passwords do not match';
	}
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$errors[] = 'Invalid email address';
	}
	if (empty($errors)) {
		try {
			$makeAdmin = ($adminCode === ADMIN_REGISTRATION_CODE);
			register_user($email, $password, $name, $makeAdmin);
			// TODO: send confirmation email
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
	<title>Register - Bradford Portal</title>
	<link rel="stylesheet" href="css/style.css">
	<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
	<h1>Register for Bradford Portal</h1>
	<p>If you have been given an <strong>admin code</strong>, enter it below to create an admin account.</p>
	<?php if ($errors): ?>
		<ul class="error">
		<?php foreach ($errors as $e): ?>
			<li><?=htmlspecialchars($e)?></li>
		<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<form method="post">
		<label for="name">Name (optional):</label>
		<input type="text" id="name" name="name">
		<label for="admin_code">Admin code (if you have one):</label>
		<input type="text" id="admin_code" name="admin_code">
		<label for="admin_code">Admin code (if you have one):</label>
		<input type="text" id="admin_code" name="admin_code">
		<label for="email">Email:</label>
		<input type="email" id="email" name="email" required>
		<label for="password">Password:</label>
		<input type="password" id="password" name="password" required>
		<label for="password2">Confirm password:</label>
		<input type="password" id="password2" name="password2" required>
		<button type="submit">Register</button>
	</form>
	<p><a href="index.php">Back to login</a></p>
</body>
</html>
