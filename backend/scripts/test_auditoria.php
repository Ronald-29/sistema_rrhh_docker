<?php

$pdo = require __DIR__ . '/../config/database.php';

require_once __DIR__ . '/../src/Models/Auditoria.php';

$auditoriaModel = new Auditoria($pdo);

try {
    $idAuditoria = $auditoriaModel->registrar([
        'id_usuario' => 1,
        'accion' => 'MODIFICAR',
        'tabla_afectada' => 'personal',
        'id_registro' => 1,
        'descripcion' => 'Prueba del módulo de auditoría',
        'datos_anteriores' => [
            'cargo' => 'Auxiliar Administrativo'
        ],
        'datos_nuevos' => [
            'cargo' => 'Tecnico Administrativo'
        ]
    ]);

    echo "Auditoria registrada correctamente." . PHP_EOL;
    echo "ID de auditoria: " . $idAuditoria . PHP_EOL;

    $registro = $auditoriaModel->buscarPorId($idAuditoria);

    echo PHP_EOL;
    echo "Registro guardado:" . PHP_EOL;
    echo json_encode(
        $registro,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    ) . PHP_EOL;
} catch (Throwable $e) {
    echo "Error al registrar auditoria." . PHP_EOL;
    echo $e->getMessage() . PHP_EOL;
}