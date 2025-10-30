<?php
// Simple test to verify API is working
session_start();
$_SESSION['admin_logged'] = true;

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Admin API</title>
</head>
<body>
    <h1>Test Admin API</h1>
    <button onclick="testCounts()">Test Get Counts</button>
    <button onclick="testData('usuarios')">Test Get Usuarios</button>
    <button onclick="testData('items')">Test Get Items</button>
    <button onclick="testData('hilos')">Test Get Hilos</button>
    <pre id="result"></pre>

    <script>
    function testCounts() {
        fetch('api_admin.php?action=get_counts')
            .then(r => r.json())
            .then(data => {
                document.getElementById('result').textContent = JSON.stringify(data, null, 2);
            })
            .catch(err => {
                document.getElementById('result').textContent = 'Error: ' + err;
            });
    }

    function testData(type) {
        fetch('api_admin.php?action=get_data&type=' + type)
            .then(r => r.json())
            .then(data => {
                document.getElementById('result').textContent = JSON.stringify(data, null, 2);
            })
            .catch(err => {
                document.getElementById('result').textContent = 'Error: ' + err;
            });
    }
    </script>
</body>
</html>
