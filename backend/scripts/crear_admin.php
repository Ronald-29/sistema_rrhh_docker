<?php

$pdo = require __DIR__ . '/../config/database.php';

echo "=== CREAR USUARIO ADMINISTRADOR ===" . PHP_EOL;

echo "Nombres: ";
$nombres = trim(fgets(STDIN));

echo "Apellidos: ";
$apellidos = trim(fgets(STDIN));

echo "Nombre de usuario: ";
$usuario = trim(fgets(STDIN));

echo "Contraseña: ";
$password = trim(fgets(STDIN));

if ($nombres === '' || $apellidos === '' || $usuario === '' || $password === '') {
    exit("Error: todos los campos son obligatorios." . PHP_EOL);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$sql = "
    INSERT INTO usuarios (
        id_rol,
        nombres,
        apellidos,
        nombre_usuario,
        password_hash
    )
    SELECT
        id_rol,
        :nombres,
        :apellidos,
        :usuario,
        :password_hash
    FROM roles
    WHERE nombre = 'Administrador'
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':nombres' => $nombres,
    ':apellidos' => $apellidos,
    ':usuario' => $usuario,
    ':password_hash' => $hash
]);

if ($stmt->rowCount() === 0) {
    exit("Error: no se encontró el rol Administrador." . PHP_EOL);
}

echo PHP_EOL;
echo "Administrador creado correctamente." . PHP_EOL;