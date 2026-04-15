<?php
/**
 * Bradford Portal - Authentication Functions
 * Updated: April 2026 - first_name/last_name, email working, 2FA fixes
 */

require_once __DIR__ . '/db.php';

session_start();

define('ADMIN_REGISTRATION_CODE', 'BradfordAdmin2026');

function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Encrypt sensitive data (name fields)
 */
function encrypt_data($data) {
    if (empty($data)) return $data;
    $key = defined('ENCRYPTION_KEY') ? ENCRYPTION_KEY : 'bradford_default_key_2026';
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

/**
 * Decrypt sensitive data
 */
function decrypt_data($data) {
    if (empty($data)) return $data;
    try {
        $key = defined('ENCRYPTION_KEY') ? ENCRYPTION_KEY : 'bradford_default_key_2026';
        $raw = base64_decode($data);
        if (strlen($raw) < 16) return $data; // not encrypted
        $iv = substr($raw, 0, 16);
        $encrypted = substr($raw, 16);
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
        return $decrypted !== false ? $decrypted : $data;
    } catch (Exception $e) {
        return $data;
    }
}

/**
 * Create a new user record with first_name and last_name
 */
function register_user($email, $password, $first_name = null, $last_name = null, $isAdmin = false) {
    $db = get_db();
    $hash = hash_password($password);

    $enc_first = ($first_name !== null && $first_name !== '') ? encrypt_data($first_name) : null;
    $enc_last  = ($last_name  !== null && $last_name  !== '') ? encrypt_data($last_name)  : null;
    // keep name field as full name for backward compat
    $full_name = trim("$first_name $last_name");
    $enc_name  = $full_name ? encrypt_data($full_name) : null;

    if (!$isAdmin) {
        $count = $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
        if ($count == 0) $isAdmin = true;
    }

    // Add columns if they don't exist (migration safety)
    try {
        $db->exec("ALTER TABLE users ADD COLUMN first_name VARCHAR(255) DEFAULT NULL AFTER name");
    } catch (PDOException $e) {}
    try {
        $db->exec("ALTER TABLE users ADD COLUMN last_name VARCHAR(255) DEFAULT NULL AFTER first_name");
    } catch (PDOException $e) {}

    $stmt = $db->prepare('INSERT INTO users (email, password_hash, name, first_name, last_name, is_admin) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$email, $hash, $enc_name, $enc_first, $enc_last, $isAdmin ? 1 : 0]);
    $userId = $db->lastInsertId();
    log_activity($userId, 'register');
    return $userId;
}

function enforce_https() {
    if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) return;
    if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
        $url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header('Location: ' . $url);
        exit;
    }
}

function check_rate_limit($email, $ip) {
    $db = get_db();
    $since = date('Y-m-d H:i:s', strtotime('-15 minutes'));
    try {
        $stmt = $db->prepare('SELECT COUNT(*) as attempts FROM login_attempts WHERE ip_address = ? AND attempt_time > ? AND successful = 0');
        $stmt->execute([$ip, $since]);
        $ip_attempts = $stmt->fetch()['attempts'];
        $stmt = $db->prepare('SELECT COUNT(*) as attempts FROM login_attempts WHERE email = ? AND attempt_time > ? AND successful = 0');
        $stmt->execute([$email, $since]);
        $email_attempts = $stmt->fetch()['attempts'];
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'login_attempts') !== false) {
            $db->exec("CREATE TABLE IF NOT EXISTS login_attempts (id INT AUTO_INCREMENT PRIMARY KEY, ip_address VARCHAR(45) NOT NULL, email VARCHAR(255), attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP, successful TINYINT(1) DEFAULT 0, INDEX idx_ip_time (ip_address, attempt_time), INDEX idx_email_time (email, attempt_time)) ENGINE=InnoDB;");
            return true;
        }
        throw $e;
    }
    return $ip_attempts < 5 && $email_attempts < 5;
}

function record_login_attempt($email, $ip, $successful = false) {
    $db = get_db();
    $stmt = $db->prepare('INSERT INTO login_attempts (ip_address, email, successful) VALUES (?, ?, ?)');
    $stmt->execute([$ip, $email, $successful ? 1 : 0]);
}

function get_remaining_attempts($email, $ip) {
    $db = get_db();
    $since = date('Y-m-d H:i:s', strtotime('-15 minutes'));
    $stmt = $db->prepare('SELECT COUNT(*) as attempts FROM login_attempts WHERE ip_address = ? AND attempt_time > ? AND successful = 0');
    $stmt->execute([$ip, $since]);
    $ip_attempts = $stmt->fetch()['attempts'];
    $stmt = $db->prepare('SELECT COUNT(*) as attempts FROM login_attempts WHERE email = ? AND attempt_time > ? AND successful = 0');
    $stmt->execute([$email, $since]);
    $email_attempts = $stmt->fetch()['attempts'];
    $max = 5;
    return ['ip_remaining' => max(0, $max - $ip_attempts), 'email_remaining' => max(0, $max - $email_attempts)];
}

function login_user($email, $password, $code = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!check_rate_limit($email, $ip)) {
        record_login_attempt($email, $ip, false);
        return false;
    }
    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && verify_password($password, $user['password_hash'])) {
        // 2FA check
        if (!empty($user['two_factor_secret'])) {
            if (!$code || !verify_totp_code($user['two_factor_secret'], trim($code))) {
                record_login_attempt($email, $ip, false);
                return '2fa_required'; // special return so login page can show correct message
            }
        }
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['is_admin'] = (bool)$user['is_admin'];
        log_activity($user['id'], 'login');
        record_login_attempt($email, $ip, true);
        return true;
    }
    record_login_attempt($email, $ip, false);
    return false;
}

function current_user() {
    if (isset($_SESSION['user_id'])) {
        $db = get_db();
        $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if ($user) {
            if (!empty($user['first_name'])) $user['first_name'] = decrypt_data($user['first_name']);
            if (!empty($user['last_name']))  $user['last_name']  = decrypt_data($user['last_name']);
            if (!empty($user['name']))       $user['name']       = decrypt_data($user['name']);
            // build display_name helper
            $user['display_name'] = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            if (empty($user['display_name'])) $user['display_name'] = $user['email'];
        }
        return $user;
    }
    return null;
}

function require_login() {
    if (!current_user()) {
        header('Location: index.php');
        exit;
    }
}

function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

function send_password_reset($email) {
    $db = get_db();
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user) return false;
    $token = generate_token(16);
    $expires = date('Y-m-d H:i:s', time() + 15*60);
    $stmt = $db->prepare('UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?');
    $stmt->execute([$token, $expires, $user['id']]);
    log_activity($user['id'], 'password_reset_requested');
    return $token;
}

function reset_password($token, $newPassword) {
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

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
