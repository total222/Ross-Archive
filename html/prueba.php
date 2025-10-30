<?php
// 1️⃣ Verificar la variable de entorno
$clave = getenv('GOOGLE_APLICATION_CREDENTIAL');
if (!$clave) {
    die("❌ Variable de entorno GOOGLE_APPLICATION_CREDENTIALS NO encontrada.\n");
}
echo "✅ Variable de entorno encontrada: $clave\n";

// 2️⃣ Verificar que el archivo JSON existe y se puede leer
if (!file_exists($clave)) {
    die("❌ Archivo de clave JSON NO encontrado en la ruta especificada.\n");
}

$json = file_get_contents($clave);
if ($json === false) {
    die("❌ No se pudo leer el archivo JSON.\n");
}

// 3️⃣ Validar que sea un JSON válido
$data = json_decode($json, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    die("❌ El archivo JSON NO es válido: " . json_last_error_msg() . "\n");
}

echo "🎉 El archivo JSON se puede leer y es válido.\n";
echo "Información de la cuenta de servicio:\n";
echo "Client Email: " . ($data['client_email'] ?? 'No disponible') . "\n";
echo "Project ID: " . ($data['project_id'] ?? 'No disponible') . "\n";
?>