<?php

$pdo = require __DIR__ . '/../config/database.php';

echo "=== CREAR USUARIO DE PRUEBA ===" . PHP_EOL;

echo "Contraseña para usuario consulta_test: ";
$password = trim(fgets(STDIN));

if ($password === '') {
    exit("La contraseña es obligatoria." . PHP_EOL);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

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
        'Usuario',
        'Prueba',
        'consulta_test',
        :password_hash
    FROM roles
    WHERE nombre = 'Consulta'
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':password_hash' => $passwordHash
]);

if ($stmt->rowCount() === 0) {
    exit("No se encontró el rol Consulta." . PHP_EOL);
}

echo "Usuario consulta_test creado correctamente." . PHP_EOL;