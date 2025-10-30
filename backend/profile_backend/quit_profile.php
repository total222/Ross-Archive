<?php
include __DIR__ . '/../global_scripts.php';
session_start();

// Ensure user is logged in
if(empty($_SESSION['ross_user']) && empty($_SESSION['user'])){
	$_SESSION['http_response'] = 401;
	header('Location: /../excepciones/error');
	exit;
}

$db = db_connect();

// Determine email
$email = null;
if(!empty($_SESSION['ross_user'])){
	$email = $_SESSION['ross_user']['email_ross'] ?? null;
} else if(!empty($_SESSION['user'])){
	$email = $_SESSION['user']['email'] ?? null;
}

if(empty($email)){
	header('Location: /../profile/edit_profile'); exit;
}

// Validate CSRF token if present in POST
if($_SERVER['REQUEST_METHOD'] === 'POST'){
	if(isset($_POST['csrf_token']) && isset($_SESSION['csrf_token'])){
		if(!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])){
			$_SESSION['http_response'] = 401;
			header('Location: /../excepciones/error');
			exit;
		}
	}
}

try{
	// Log for debugging
	$logFile = __DIR__ . '/../../storage_upload.log';
	$log = function($m) use ($logFile){
		$line = '[' . date('Y-m-d H:i:s') . '] ' . $m . "\n";
		@file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
	};

	$log("User deletion attempt for email: $email");

	// Delete user row
	$stmt = $db->prepare('DELETE FROM usuarios WHERE correo = :correo');
	$stmt->bindParam(':correo', $email, PDO::PARAM_STR);
	$result = $stmt->execute();

	$rowCount = $stmt->rowCount();
	$log("Delete query executed. Result: " . ($result ? 'true' : 'false') . ", Rows affected: $rowCount");

	if($rowCount === 0){
		$log("WARNING: No rows deleted for email: $email");
		// User might not exist, but don't fail - just proceed
	}

	db_disconnect($db);

	$log("User deletion successful, destroying session");

	// Destroy session and redirect to index
	$_SESSION = [];
	if (ini_get('session.use_cookies')) {
		$params = session_get_cookie_params();
		setcookie(session_name(), '', time() - 42000,
			$params['path'], $params['domain'], $params['secure'], $params['httponly']
		);
	}
	session_destroy();

	$log("Session destroyed, redirecting to index");
	header('Location: /index.html');
	exit;

}catch(PDOException $e){
	error_log('PDO Error deleting user: ' . $e->getMessage());
	error_log('SQL State: ' . $e->getCode());
	error_log('Error Info: ' . json_encode($e->errorInfo ?? []));

	// Log to file as well
	$logFile = __DIR__ . '/../../storage_upload.log';
	$msg = '[' . date('Y-m-d H:i:s') . '] PDO Error deleting user: ' . $e->getMessage() . ' | SQL State: ' . $e->getCode() . ' | Info: ' . json_encode($e->errorInfo ?? []) . "\n";
	@file_put_contents($logFile, $msg, FILE_APPEND | LOCK_EX);

	$_SESSION['http_response'] = 500;
	header('Location: /excepciones/error.php');
	exit;
}catch(Exception $e){
	error_log('General Error deleting user: ' . $e->getMessage());

	$logFile = __DIR__ . '/../../storage_upload.log';
	$msg = '[' . date('Y-m-d H:i:s') . '] General Error deleting user: ' . $e->getMessage() . "\n";
	@file_put_contents($logFile, $msg, FILE_APPEND | LOCK_EX);

	$_SESSION['http_response'] = 500;
	header('Location: /excepciones/error');
	exit;
}

?>