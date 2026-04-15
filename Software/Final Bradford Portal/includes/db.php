<?php
// db.php - simple PDO wrapper for Bradford Portal
// this file is included where a database connection is needed

// adjust these constants to match your environment
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'bradford_portal');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Returns a PDO instance connected to the portal database.
 *
 * @return PDO
 */
function get_db()
{
	static $pdo;
	if ($pdo instanceof PDO) {
		return $pdo;
	}

	$dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);
	$options = [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::ATTR_EMULATE_PREPARES => false,
	];

	try {
		$pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
	} catch (PDOException $e) {
		// in production you might log instead
		die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
	}

	return $pdo;
}

// helper for simple logging to activity_log
function log_activity($userId, $action, $details = null)
{
	$db = get_db();
	$stmt = $db->prepare('INSERT INTO activity_log (user_id, action, details) VALUES (?, ?, ?)');
	$stmt->execute([$userId, $action, $details]);
}

?>
