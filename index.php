<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/totp.php';

session_start();

// if already logged in redirect to portal
if (current_user()) {
	header('Location: portal.php');
	exit;
}

$error = '';
$show_2fa_form = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['action'] ?? 'login';
	
	if ($action === 'login') {
		// Step 1: Verify email and password
		$email = $_POST['email'] ?? '';
		$password = $_POST['password'] ?? '';
		
		// Check if credentials are valid without creating a session yet
		require_once __DIR__ . '/includes/db.php';
		$pdo = get_db_connection();
		$stmt = $pdo->prepare('SELECT id, password_hash, two_factor_secret FROM users WHERE email = ?');
		$stmt->execute([$email]);
		$user = $stmt->fetch();
		
		if ($user && password_verify($password, $user['password_hash'])) {
			// Credentials are correct
			if (!empty($user['two_factor_secret'])) {
				// 2FA is enabled; show 2FA verification form
				$_SESSION['pending_user_id'] = $user['id'];
				$_SESSION['pending_email'] = $email;
				$show_2fa_form = true;
			} else {
				// No 2FA; log in directly
				if (login_user($email, $password)) {
					header('Location: portal.php');
					exit;
				}
			}
		} else {
			$error = 'Invalid email or password';
		}
		
	} elseif ($action === 'verify_2fa') {
		// Step 2: Verify 2FA code
		$totp_code = trim($_POST['totp_code'] ?? '');
		$pending_user_id = $_SESSION['pending_user_id'] ?? null;
		$pending_email = $_SESSION['pending_email'] ?? null;
		
		if (empty($pending_user_id) || empty($pending_email)) {
			$error = 'Session expired. Please log in again.';
			$show_2fa_form = false;
		} elseif (empty($totp_code) || !preg_match('/^\d{6}$/', $totp_code)) {
			$error = 'Please enter a valid 6-digit code.';
			$show_2fa_form = true;
		} else {
			// Get the user's 2FA secret
			$pdo = get_db_connection();
			$stmt = $pdo->prepare('SELECT two_factor_secret FROM users WHERE id = ?');
			$stmt->execute([$pending_user_id]);
			$user = $stmt->fetch();
			
			if ($user && verify_totp_code($user['two_factor_secret'], $totp_code)) {
				// 2FA code is valid; create session
				$_SESSION['user_id'] = $pending_user_id;
				$_SESSION['email'] = $pending_email;
				unset($_SESSION['pending_user_id']);
				unset($_SESSION['pending_email']);
				
				// Log the activity
				log_activity($pending_user_id, 'LOGIN', 'User logged in with 2FA');
				
				header('Location: portal.php');
				exit;
			} else {
				$error = 'Invalid 2FA code. Please try again.';
				$show_2fa_form = true;
			}
		}
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
	<style>
		body {
			background: linear-gradient(135deg, #8B3A62 0%, #D4AF37 100%);
			min-height: 100vh;
			display: flex;
			align-items: center;
			justify-content: center;
			font-family: Arial, sans-serif;
		}
		.login-container {
			background: white;
			padding: 40px;
			border-radius: 10px;
			box-shadow: 0 10px 25px rgba(0,0,0,0.2);
			max-width: 400px;
			width: 100%;
		}
		.login-container h1 {
			color: #8B3A62;
			text-align: center;
			margin-bottom: 30px;
		}
		.form-group {
			margin-bottom: 20px;
		}
		.form-group label {
			display: block;
			margin-bottom: 8px;
			font-weight: bold;
			color: #333;
		}
		.form-group input {
			width: 100%;
			padding: 12px;
			border: 1px solid #ddd;
			border-radius: 5px;
			font-size: 16px;
			box-sizing: border-box;
		}
		.form-group input:focus {
			outline: none;
			border-color: #8B3A62;
		}
		.error {
			background: #f8d7da;
			color: #721c24;
			padding: 12px;
			border-radius: 5px;
			margin-bottom: 20px;
			border: 1px solid #f5c6cb;
		}
		button {
			width: 100%;
			padding: 12px;
			background: #8B3A62;
			color: white;
			border: none;
			border-radius: 5px;
			font-size: 16px;
			cursor: pointer;
			font-weight: bold;
		}
		button:hover {
			background: #6d2d4a;
		}
		.links {
			text-align: center;
			margin-top: 20px;
			font-size: 14px;
		}
		.links a {
			color: #8B3A62;
			text-decoration: none;
		}
		.links a:hover {
			text-decoration: underline;
		}
		.links p {
			margin: 10px 0;
		}
		.totp-info {
			background: #e7f3ff;
			border: 1px solid #b3d9ff;
			padding: 12px;
			border-radius: 5px;
			margin-bottom: 20px;
			font-size: 14px;
			color: #004085;
		}
	</style>
</head>
<body>
	<div class="login-container">
		<h1>Bradford Portal</h1>
		
		<?php if ($error): ?>
			<div class="error"><?=htmlspecialchars($error)?></div>
		<?php endif; ?>
		
		<?php if ($show_2fa_form): ?>
			<!-- Step 2: 2FA Verification -->
			<div class="totp-info">
				📱 Two-Factor Authentication Enabled<br>
				Enter the 6-digit code from your authenticator app.
			</div>
			<form method="post">
				<div class="form-group">
					<label for="totp_code">Authentication Code:</label>
					<input type="text" id="totp_code" name="totp_code" 
						   pattern="\d{6}" 
						   placeholder="000000" 
						   maxlength="6" 
						   autocomplete="off"
						   inputmode="numeric"
						   required
						   autofocus>
				</div>
				<input type="hidden" name="action" value="verify_2fa">
				<button type="submit">Verify</button>
			</form>
			<div class="links">
				<p><a href="index.php">Back to login</a></p>
			</div>
		<?php else: ?>
			<!-- Step 1: Email and Password -->
			<form method="post">
				<div class="form-group">
					<label for="email">Email:</label>
					<input type="email" id="email" name="email" required autofocus>
				</div>
				<div class="form-group">
					<label for="password">Password:</label>
					<input type="password" id="password" name="password" required>
				</div>
				<input type="hidden" name="action" value="login">
				<button type="submit">Login</button>
			</form>
			<div class="links">
				<p><a href="register.php">Register a new account</a></p>
				<p><a href="forgot.php">Forgot password?</a></p>
				<p><a href="admin_login.php">Admin login</a></p>
			</div>
		<?php endif; ?>
	</div>
</body>
</html>
