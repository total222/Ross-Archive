<?php
include __DIR__ . '/../global_scripts.php';
session_start();
// This file provides profile configuration and can return JSON data for AJAX requests
header('Content-Type: application/json; charset=utf-8');

try{
	$db = db_connect();
}catch(Exception $e){
	http_response_code(500);
	echo json_encode(['error' => 'DB connection failed']);
	exit;
}

$response = ['name' => null, 'email' => null, 'phone' => null, 'bio' => null, 'img' => null, 'is_google_oauth' => false];

// Determine session source
if(!empty($_SESSION['ross_user'])){
	$sess = $_SESSION['ross_user'];
	// ross_user may contain name_ross, email_ross and img
	$response['name'] = $sess['name_ross'] ?? null;
	$response['email'] = $sess['email_ross'] ?? null;
	// prefer session-stored image if set
	$response['img'] = $sess['img'] ?? null;
	$response['is_google_oauth'] = false; // Regular user with password
	$lookupField = 'correo';
	$lookupValue = $response['email'];
} else if(!empty($_SESSION['user'])){
	$sess = $_SESSION['user'];
	$response['name'] = $sess['name'] ?? null;
	$response['email'] = $sess['email'] ?? null;
	// prefer session-stored image if set
	$response['img'] = $sess['img'] ?? null;
	$response['is_google_oauth'] = true; // Google OAuth user (no password)
	$lookupField = 'correo';
	$lookupValue = $response['email'];
} else {
	// no session - return empty
	echo json_encode($response);
	exit;
}

// If we have an email to lookup, query the DB for telefono, biografia and perfil
if(!empty($lookupValue)){
	try{
		$stmt = $db->prepare('SELECT telefono, biografia, perfil FROM usuarios WHERE correo = :val LIMIT 1');
		$stmt->bindParam(':val', $lookupValue);
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if($row){
			$response['phone'] = $row['telefono'] ?? null;
			$response['bio'] = $row['biografia'] ?? null;
			// if session didn't already provide an image URL, use DB value
			if(empty($response['img']) && !empty($row['perfil'])){
				$response['img'] = $row['perfil'];
			}
		}
	}catch(Exception $e){
		// ignore DB errors but return partial data
	}
}

// If request is POST -> process update and then redirect back to edit page
if($_SERVER['REQUEST_METHOD'] === 'POST'){
	// Basic CSRF: if a CSRF token handling exists in session scripts use it; otherwise simple check
	// NOTE: other scripts use $_SESSION['csrf_token'] — if present validate it
	if(isset($_POST['csrf_token']) && isset($_SESSION['csrf_token'])){
		if(!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])){
			$_SESSION['http_response'] = 401;
			header('Location: /../excepciones/error.php');
			exit;
		}
	}

	// determine which field to update
	$field = $_POST['field'] ?? null;
	if(!$field){
		header('Location: /../profile/edit_profile.php'); exit;
	}

	// reuse lookupValue (correo) to find user row
	if(empty($lookupValue)){
		header('Location: /../profile/edit_profile.php'); exit;
	}

	try{
		if($field === 'name'){
			$v = trim($_POST['value'] ?? '');
			$v = htmlspecialchars($v);
			// update session and DB username (usuarios.usuario)
			$stmt = $db->prepare('UPDATE usuarios SET usuario = :v WHERE correo = :correo');
			$stmt->execute([':v'=>$v, ':correo'=>$lookupValue]);
			// update session
			if(!empty($_SESSION['ross_user'])) $_SESSION['ross_user']['name_ross'] = $v;
			if(!empty($_SESSION['user'])) $_SESSION['user']['name'] = $v;
		} else if($field === 'email'){
			// Prevent Google OAuth users from changing email
			if(!empty($_SESSION['user'])){
				header('Location: /../profile/edit_profile.php?error=' . urlencode('No puedes cambiar el correo de una cuenta de Google'));
				exit;
			}
			$v = trim($_POST['value'] ?? '');
			if(!filter_var($v, FILTER_VALIDATE_EMAIL)){
				header('Location: /../profile/edit_profile.php?error=email_invalid'); exit;
			}
			$v = htmlspecialchars($v);
			$stmt = $db->prepare('UPDATE usuarios SET correo = :v WHERE correo = :correo');
			$stmt->execute([':v'=>$v, ':correo'=>$lookupValue]);
			// update session and lookupValue for further operations
			if(!empty($_SESSION['ross_user'])) $_SESSION['ross_user']['email_ross'] = $v;
		} else if($field === 'phone'){
			$v = trim($_POST['value'] ?? '');
			$v = htmlspecialchars($v);
			$stmt = $db->prepare('UPDATE usuarios SET telefono = :v WHERE correo = :correo');
			$stmt->execute([':v'=>$v, ':correo'=>$lookupValue]);
		} else if($field === 'bio'){
			$v = trim($_POST['value'] ?? '');
			$v = htmlspecialchars($v);
			$stmt = $db->prepare('UPDATE usuarios SET biografia = :v WHERE correo = :correo');
			$stmt->execute([':v'=>$v, ':correo'=>$lookupValue]);
		} else if($field === 'image'){
			// handle file upload and push to Google Cloud Storage
			if(!isset($_FILES['value']) || !is_uploaded_file($_FILES['value']['tmp_name'])){
				header('Location: /../profile/edit_profile.php?error=no_file'); exit;
			}
			$file = $_FILES['value'];
			// basic checks
			$allowed = ['image/jpeg','image/png','image/webp'];
			if(!in_array($file['type'], $allowed)){
				header('Location: /../profile/edit_profile.php?error=bad_type'); exit;
			}
			$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
			$targetDir = __DIR__ . '/../../html/recursos/imagenes/';
			if(!is_dir($targetDir)) mkdir($targetDir, 0755, true);
			$safe = 'profile_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
			$dest = $targetDir . $safe;
			if(!move_uploaded_file($file['tmp_name'], $dest)){
				header('Location: /../profile/edit_profile.php?error=upload_failed'); exit;
			}

			// Default public path (fallback to local public folder)
			$public = '/recursos/imagenes/' . $safe;

			// helper log for upload diagnostics
			$logFile = __DIR__ . '/../../storage_upload.log';
			$log = function($m) use ($logFile){
				$line = '[' . date('Y-m-d H:i:s') . '] ' . $m . "\n";
				@file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
			};

			// Try uploading to Google Cloud Storage if credentials are provided
			$gcsBucket = 'ross-archive_public-bucket';
			$gcsPrefix = 'fotos_de_perfil/';
			$envCred = getenv('GOOGLE_APLICATION_CREDENTIAL');
			if($envCred && trim($envCred) !== ''){
				try{
					require_once __DIR__ . '/../../vendor/autoload.php';
					$opts = [];

					// If env contains a path to a JSON file, use keyFilePath
					if(is_string($envCred) && file_exists($envCred)){
						$opts['keyFilePath'] = $envCred;
						$log("Using credential file path from env: $envCred");
					} else {
						// try decode JSON string
						$decoded = json_decode($envCred, true);
						if(json_last_error() === JSON_ERROR_NONE && is_array($decoded)){
							$opts['keyFile'] = $decoded;
							$log('Using credential JSON from env variable');
						} else {
							$log('GOOGLE_APLICATION_CREDENTIAL does not point to a file and is not valid JSON');
						}
					}

					if(!empty($opts)){
						if(!empty($opts['keyFile']['project_id'])) $opts['projectId'] = $opts['keyFile']['project_id'];
						// instantiate storage client
						$storage = new Google\Cloud\Storage\StorageClient($opts);
						$bucket = $storage->bucket($gcsBucket);
						if(!$bucket){
							$log('Bucket not found or not accessible: ' . $gcsBucket);
						} else {
							$objectName = $gcsPrefix . $safe;
							$log('Uploading to GCS: ' . $objectName);
							$object = $bucket->upload(fopen($dest, 'r'), [
								'name' => $objectName
							]);
							// build public URL (standard public object URL)
							$public = 'https://storage.googleapis.com/' . $gcsBucket . '/' . $objectName;
							// remove local copy to save space
							@unlink($dest);
							$log('Upload successful, public URL: ' . $public);
						}
					}
				}catch(Exception $e){
					// if GCS upload fails, keep local public path and write detailed log
					$log('GCS upload failed: ' . $e->getMessage());
					@file_put_contents($logFile, "Trace:\n" . $e->getTraceAsString() . "\n", FILE_APPEND | LOCK_EX);
				}
			} else {
				$log('GOOGLE_APLICATION_CREDENTIAL env var empty or not set; skipping GCS upload');
			}

			// attempt to update perfil column with the public URL
			try{
				$stmt = $db->prepare('UPDATE usuarios SET perfil = :p WHERE correo = :correo');
				$stmt->execute([':p'=>$public, ':correo'=>$lookupValue]);
			}catch(Exception $e){
				// ignore if column doesn't exist
			}
			// update session
			if(!empty($_SESSION['ross_user'])) $_SESSION['ross_user']['img'] = $public;
			if(!empty($_SESSION['user'])) $_SESSION['user']['img'] = $public;
		}
	}catch(Exception $e){
		error_log('Profile update error: ' . $e->getMessage());
	}

	// after POST update, redirect back to edit page
	header('Location: /../profile/edit_profile.php');
	exit;
}

// default GET behavior: return JSON for AJAX clients
echo json_encode($response);
exit;

?>