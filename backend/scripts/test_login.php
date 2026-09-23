<?php

$pdo = require __DIR__ . '/../config/database.php';

require_once __DIR__ . '/../src/Services/AuthService.php';

$authService = new AuthService($pdo);

echo "=== PRUEBA DE INICIO DE SESION ===" . PHP_EOL;

echo "Usuario: ";
$nombreUsuario = trim(fgets(STDIN));

echo "Contraseña: ";
$password = trim(fgets(STDIN));

$usuario = $authService->autenticar($nombreUsuario, $password);

if (!$usuario) {
    echo PHP_EOL;
    echo "Acceso denegado: usuario o contraseña incorrectos." . PHP_EOL;
    exit;
}

echo PHP_EOL;
echo "Inicio de sesion correcto." . PHP_EOL;
echo "Usuario: " . $usuario['nombre_usuario'] . PHP_EOL;
echo "Nombre: " . $usuario['nombres'] . " " . $usuario['apellidos'] . PHP_EOL;
echo "Rol: " . $usuario['rol'] . PHP_EOL;