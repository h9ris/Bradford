<?php
// auth.php - authentication helpers for Bradford Portal

require_once __DIR__ . '/db.php';

session_start();

// simple admin code for registration (replace with secure mechanism in real use)
define('ADMIN_REGISTRATION_CODE', 'BradfordAdmin2026');

/**
 * Hash a password using bcrypt.
 */
function hash_password($password)
{
	return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify a candidate password against a hash.
 */
function verify_password($password, $hash)
{
	return password_verify($password, $hash);
}

/**
 * Create a new user record.
 */
/**
 * Create a new user record.
 *
 * @param string $email
 * @param string $password
 * @param string|null $name
 * @param bool $isAdmin
 * @return int user id
 */
function register_user($email, $password, $name = null, $isAdmin = false)
{
    $db = get_db();
    $hash = hash_password($password);
    // encrypt the name field if provided
    if ($name !== null && $name !== '') {
        $name = encrypt_data($name);
    }
	// if this is the first user ever, make them admin regardless of code
	if (!$isAdmin) {
		$count = $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
		if ($count == 0) {
			$isAdmin = true;
		}
	}
	$stmt = $db->prepare('INSERT INTO users (email, password_hash, name, is_admin) VALUES (?, ?, ?, ?)');
	$stmt->execute([$email, $hash, $name, $isAdmin ? 1 : 0]);
    $userId = $db->lastInsertId();
    log_activity($userId, 'register');
    return $userId;
}

/**
 * Authenticate credentials and start a session.
 */
function login_user($email, $password)
{
	$db = get_db();
	$stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
	$stmt->execute([$email]);
	$user = $stmt->fetch();
	if ($user && verify_password($password, $user['password_hash'])) {
		// simple session, could add 2FA check here later
		$_SESSION['user_id'] = $user['id'];
		$_SESSION['is_admin'] = (bool)$user['is_admin'];
		log_activity($user['id'], 'login');
		return true;
	}
	return false;
}

/**
 * Returns the currently logged in user or null.
 */
function current_user()
{
    if (isset($_SESSION['user_id'])) {
        $db = get_db();
        $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if ($user && !empty($user['name'])) {
            $user['name'] = decrypt_data($user['name']);
        }
        return $user;
    }
    return null;
}

/**
 * Ensure the user is logged in; redirect to login page if not.
 */
function require_login()
{
	if (!current_user()) {
		header('Location: index.php');
		exit;
	}
}

/**
 * Generate a secure random token.
 */
function generate_token($length = 32)
{
	return bin2hex(random_bytes($length));
}

/**
 * Initiate a password reset process for an email.
 */
function send_password_reset($email)
{
	$db = get_db();
	$stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
	$stmt->execute([$email]);
	$user = $stmt->fetch();
	if (!$user) {
		return false;
	}
	$token = generate_token(16);
	$expires = date('Y-m-d H:i:s', time() + 15*60);
	$stmt = $db->prepare('UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?');
	$stmt->execute([$token, $expires, $user['id']]);
	// TODO: send an email with link including token
	log_activity($user['id'], 'password_reset_requested');
	return $token;
}

/**
 * Verify reset token and set new password.
 */
function reset_password($token, $newPassword)
{
	$db = get_db();
	$stmt = $db->prepare('SELECT id, reset_expires FROM users WHERE reset_token = ?');
	$stmt->execute([$token]);
	$user = $stmt->fetch();
	if ($user && strtotime($user['reset_expires']) > time()) {
		$hash = hash_password($newPassword);
		$stmt = $db->prepare('UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?');
		$stmt->execute([$hash, $user['id']]);
		log_activity($user['id'], 'password_reset');
		return true;
	}
	return false;
}

?>
