<?php

declare(strict_types=1);

date_default_timezone_set('America/La_Paz');

session_start();

header('Content-Type: application/json; charset=utf-8');

$pdo = require __DIR__ . '/../config/database.php';

require_once __DIR__ . '/../src/Controllers/AuthController.php';
require_once __DIR__ . '/../src/Controllers/PersonalController.php';
require_once __DIR__ . '/../src/Controllers/UbicacionController.php';
require_once __DIR__ . '/../src/Controllers/ExpedienteController.php';
require_once __DIR__ . '/../src/Controllers/DocumentoController.php';
require_once __DIR__ . '/../src/Controllers/PrestamoController.php';
require_once __DIR__ . '/../src/Controllers/AuditoriaController.php';
require_once __DIR__ . '/../src/Controllers/ReporteController.php';
require_once __DIR__ . '/../src/Controllers/UsuarioController.php';

require_once __DIR__ . '/../src/Middleware/AuthMiddleware.php';

// El middleware revalida en cada petición que el usuario siga activo y con qué rol.
AuthMiddleware::usarConexion($pdo);

$metodo = $_SERVER['REQUEST_METHOD'];

$ruta = parse_url(
    $_SERVER['REQUEST_URI'],
    PHP_URL_PATH
);

if (!is_string($ruta)) {
    $ruta = '';
}

if ($ruta === '/api/login' && $metodo === 'POST') {
    $authController = new AuthController($pdo);
    $authController->login();
    exit;
}

if ($ruta === '/api/me' && $metodo === 'GET') {
    AuthMiddleware::verificarAutenticacion();

    $authController = new AuthController($pdo);
    $authController->me();
    exit;
}

if ($ruta === '/api/logout' && $metodo === 'POST') {
    AuthMiddleware::verificarAutenticacion();

    $authController = new AuthController($pdo);
    $authController->logout();
    exit;
}

if ($ruta === '/api/mi-password' && $metodo === 'PUT') {
    AuthMiddleware::verificarAutenticacion();

    $usuarioController = new UsuarioController($pdo);
    $usuarioController->cambiarMiPassword();
    exit;
}

if ($ruta === '/api/roles' && $metodo === 'GET') {
    AuthMiddleware::verificarRol([
        'Administrador'
    ]);

    $usuarioController = new UsuarioController($pdo);
    $usuarioController->listarRoles();
    exit;
}

if ($ruta === '/api/usuarios' && $metodo === 'GET') {
    AuthMiddleware::verificarRol([
        'Administrador'
    ]);

    $usuarioController = new UsuarioController($pdo);
    $usuarioController->listar();
    exit;
}

if ($ruta === '/api/usuarios' && $metodo === 'POST') {
    AuthMiddleware::verificarRol([
        'Administrador'
    ]);

    $usuarioController = new UsuarioController($pdo);
    $usuarioController->crear();
    exit;
}

if (
    preg_match(
        '#^/api/usuarios/(\d+)/estado$#',
        $ruta,
        $coincidencias
    )
    && $metodo === 'PUT'
) {
    AuthMiddleware::verificarRol([
        'Administrador'
    ]);

    $id = (int) $coincidencias[1];

    $usuarioController = new UsuarioController($pdo);
    $usuarioController->cambiarEstado($id);
    exit;
}

if (
    preg_match(
        '#^/api/usuarios/(\d+)/password$#',
        $ruta,
        $coincidencias
    )
    && $metodo === 'PUT'
) {
    AuthMiddleware::verificarRol([
        'Administrador'
    ]);

    $id = (int) $coincidencias[1];

    $usuarioController = new UsuarioController($pdo);
    $usuarioController->cambiarPassword($id);
    exit;
}

if (
    preg_match(
        '#^/api/usuarios/(\d+)$#',
        $ruta,
        $coincidencias
    )
) {
    AuthMiddleware::verificarRol([
        'Administrador'
    ]);

    $id = (int) $coincidencias[1];

    $usuarioController = new UsuarioController($pdo);

    if ($metodo === 'GET') {
        $usuarioController->obtenerPorId($id);
        exit;
    }

    if ($metodo === 'PUT') {
        $usuarioController->actualizar($id);
        exit;
    }
}

if ($ruta === '/api/personal' && $metodo === 'GET') {
    AuthMiddleware::verificarRol([
        'Administrador',
        'Recursos Humanos',
        'Consulta'
    ]);

    $personalController = new PersonalController($pdo);
    $personalController->listar();
    exit;
}

if ($ruta === '/api/personal' && $metodo === 'POST') {
    AuthMiddleware::verificarRol([
        'Administrador',
        'Recursos Humanos'
    ]);

    $personalController = new PersonalController($pdo);
    $personalController->crear();
    exit;
}

if (
    preg_match(
        '#^/api/personal/(\d+)$#',
        $ruta,
        $coincidencias
    )
) {
    $id = (int) $coincidencias[1];

    if ($metodo === 'GET') {
        AuthMiddleware::verificarRol([
            'Administrador',
            'Recursos Humanos',
            'Consulta'
        ]);

        $personalController = new PersonalController($pdo);
        $personalController->obtenerPorId($id);
        exit;
    }

    if ($metodo === 'PUT') {
        AuthMiddleware::verificarRol([
            'Administrador',
            'Recursos Humanos'
        ]);

        $personalController = new PersonalController($pdo);
        $personalController->actualizar($id);
        exit;
    }
}

if ($ruta === '/api/ubicaciones' && $metodo === 'GET') {
    AuthMiddleware::verificarRol([
        'Administrador',
        'Recursos Humanos',
        'Consulta'
    ]);

    $ubicacionController = new UbicacionController($pdo);
    $ubicacionController->listar();
    exit;
}

if ($ruta === '/api/ubicaciones' && $metodo === 'POST') {
    AuthMiddleware::verificarRol([
        'Administrador',
        'Recursos Humanos'
    ]);

    $ubicacionController = new UbicacionController($pdo);
    $ubicacionController->crear();
    exit;
}

if (
    preg_match(
        '#^/api/ubicaciones/(\d+)/estado$#',
        $ruta,
        $coincidencias
    )
    && $metodo === 'PUT'
) {
    AuthMiddleware::verificarRol([
        'Administrador',
        'Recursos Humanos'
    ]);

    $id = (int) $coincidencias[1];

    $ubicacionController = new UbicacionController($pdo);
    $ubicacionController->cambiarEstado($id);
    exit;
}

if (
    preg_match(
        '#^/api/ubicaciones/(\d+)$#',
        $ruta,
        $coincidencias
    )
) {
    $id = (int) $coincidencias[1];

    if ($metodo === 'GET') {
        AuthMiddleware::verificarRol([
            'Administrador',
            'Recursos Humanos',
            'Consulta'
        ]);

        $ubicacionController = new UbicacionController($pdo);
        $ubicacionController->obtenerPorId($id);
        exit;
    }

    if ($metodo === 'PUT') {
        AuthMiddleware::verificarRol([
            'Administrador',
            'Recursos Humanos'
        ]);

        $ubicacionController = new UbicacionController($pdo);
        $ubicacionController->actualizar($id);
        exit;
    }
}

if ($ruta === '/api/expedientes' && $metodo === 'GET') {
    AuthMiddleware::verificarRol([
        'Administrador',
        'Recursos Humanos',
        'Consulta'
    ]);

    $expedienteController = new ExpedienteController($pdo);
    $expedienteController->listar();
    exit;
}

if ($ruta === '/api/expedientes' && $metodo === 'POST') {
    AuthMiddleware::verificarRol([
        'Administrador',
        'Recursos Humanos'
    ]);

    $expedienteController = new ExpedienteController($pdo);
    $expedienteController->crear();
    exit;
}

if (
    preg_match(
        '#^/api/expedientes/(\d+)/documentos$#',
        $ruta,
        $coincidencias
    )
    && $metodo === 'GET'
) {
    AuthMiddleware::verificarRol([
        'Administrador',
        'Recursos Humanos',
        'Consulta'
    ]);

    $id = (int) $coincidencias[1];

    $documentoController = new DocumentoController($pdo);
    $documentoController->listarPorExpediente($id);
    exit;
}

if (
    preg_match(
        '#^/api/expedientes/(\d+)/revision$#',
        $ruta,
        $coincidencias
    )
) {
    $id = (int) $coincidencias[1];

    if ($metodo === 'PUT') {
        AuthMiddleware::verificarRol([
            'Administrador',
            'Recursos Humanos'
        ]);

        $expedienteController = new ExpedienteController($pdo);
        $expedienteController->registrarRevision($id);
        exit;
    }

    if ($metodo === 'DELETE') {
        AuthMiddleware::verificarRol([
            'Administrador'
        ]);

        $expedienteController = new ExpedienteController($pdo);
        $expedienteController->anularRevision($id);
        exit;
    }
}

if (
    preg_match(
        '#^/api/expedientes/(\d+)$#',
        $ruta,
        $coincidencias
    )
) {
    $id = (int) $coincidencias[1];

    if ($metodo === 'GET') {
        AuthMiddleware::verificarRol([
            'Administrador',
            'Recursos Humanos',
            'Consulta'
        ]);

        $expedienteController = new ExpedienteController($pdo);
        $expedienteController->obtenerPorId($id);
        exit;
    }

    if ($metodo === 'PUT') {
        AuthMiddleware::verificarRol([
            'Administrador',
            'Recursos Humanos'
        ]);

        $expedienteController = new ExpedienteController($pdo);
        $expedienteController->actualizar($id);
        exit;
    }
}

if ($ruta === '/api/documentos' && $metodo === 'POST') {
    AuthMiddleware::verificarRol([
        'Administrador',
        'Recursos Humanos'
    ]);

    $documentoController = new DocumentoController($pdo);
    $documentoController->crear();
    exit;
}

if (
    preg_match(
        '#^/api/documentos/(\d+)$#',
        $ruta,
        $coincidencias
    )
) {
    $id = (int) $coincidencias[1];

    if ($metodo === 'GET') {
        AuthMiddleware::verificarRol([
            'Administrador',
            'Recursos Humanos',
            'Consulta'
        ]);

        $documentoController = new DocumentoController($pdo);
        $documentoController->obtenerPorId($id);
        exit;
    }

    if ($metodo === 'PUT') {
        AuthMiddleware::verificarRol([
            'Administrador',
            'Recursos Humanos'
        ]);

        $documentoController = new DocumentoController($pdo);
        $documentoController->actualizar($id);
        exit;
    }
}

if ($ruta === '/api/prestamos' && $metodo === 'GET') {
    AuthMiddleware::verificarRol([
        'Administrador',
        'Recursos Humanos',
        'Consulta'
    ]);

    $prestamoController = new PrestamoController($pdo);
    $prestamoController->listar();
    exit;
}

if ($ruta === '/api/prestamos' && $metodo === 'POST') {
    AuthMiddleware::verificarRol([
        'Administrador',
        'Recursos Humanos'
    ]);

    $prestamoController = new PrestamoController($pdo);
    $prestamoController->crear();
    exit;
}

if (
    preg_match(
        '#^/api/prestamos/(\d+)/devolucion$#',
        $ruta,
        $coincidencias
    )
    && $metodo === 'PUT'
) {
    AuthMiddleware::verificarRol([
        'Administrador',
        'Recursos Humanos'
    ]);

    $id = (int) $coincidencias[1];

    $prestamoController = new PrestamoController($pdo);
    $prestamoController->devolver($id);
    exit;
}

if (
    preg_match(
        '#^/api/prestamos/(\d+)$#',
        $ruta,
        $coincidencias
    )
    && $metodo === 'GET'
) {
    AuthMiddleware::verificarRol([
        'Administrador',
        'Recursos Humanos',
        'Consulta'
    ]);

    $id = (int) $coincidencias[1];

    $prestamoController = new PrestamoController($pdo);
    $prestamoController->obtenerPorId($id);
    exit;
}

if ($ruta === '/api/auditoria' && $metodo === 'GET') {
    AuthMiddleware::verificarRol([
        'Administrador'
    ]);

    $auditoriaController = new AuditoriaController($pdo);
    $auditoriaController->listar();
    exit;
}

if (
    preg_match(
        '#^/api/auditoria/(\d+)$#',
        $ruta,
        $coincidencias
    )
    && $metodo === 'GET'
) {
    AuthMiddleware::verificarRol([
        'Administrador'
    ]);

    $id = (int) $coincidencias[1];

    $auditoriaController = new AuditoriaController($pdo);
    $auditoriaController->obtenerPorId($id);
    exit;
}

if (
    $ruta === '/api/reportes/inventario/resumen'
    && $metodo === 'GET'
) {
    AuthMiddleware::verificarRol([
        'Administrador',
        'Recursos Humanos',
        'Consulta'
    ]);

    $reporteController = new ReporteController($pdo);
    $reporteController->resumenInventario();
    exit;
}

if (
    $ruta === '/api/reportes/inventario'
    && $metodo === 'GET'
) {
    AuthMiddleware::verificarRol([
        'Administrador',
        'Recursos Humanos',
        'Consulta'
    ]);

    $reporteController = new ReporteController($pdo);
    $reporteController->inventario();
    exit;
}

if (
    $ruta === '/api/reportes/inventario/excel'
    && $metodo === 'GET'
) {
    AuthMiddleware::verificarRol([
        'Administrador',
        'Recursos Humanos',
        'Consulta'
    ]);

    $reporteController = new ReporteController($pdo);
    $reporteController->exportarInventarioExcel();
    exit;
}

if (
    $ruta === '/api/reportes/informe-final'
    && $metodo === 'GET'
) {
    AuthMiddleware::verificarRol([
        'Administrador',
        'Recursos Humanos',
        'Consulta'
    ]);

    $reporteController = new ReporteController($pdo);
    $reporteController->informeFinal();
    exit;
}

if (
    $ruta === '/api/reportes/rotulos'
    && $metodo === 'GET'
) {
    AuthMiddleware::verificarRol([
        'Administrador',
        'Recursos Humanos',
        'Consulta'
    ]);

    $reporteController = new ReporteController($pdo);
    $reporteController->rotulos();
    exit;
}

if (
    $ruta === '/api/reportes/prestamos/pendientes'
    && $metodo === 'GET'
) {
    AuthMiddleware::verificarRol([
        'Administrador',
        'Recursos Humanos',
        'Consulta'
    ]);

    $reporteController = new ReporteController($pdo);
    $reporteController->prestamosPendientes();
    exit;
}

if (
    $ruta === '/api/reportes/prestamos/resumen'
    && $metodo === 'GET'
) {
    AuthMiddleware::verificarRol([
        'Administrador',
        'Recursos Humanos',
        'Consulta'
    ]);

    $reporteController = new ReporteController($pdo);
    $reporteController->resumenPrestamos();
    exit;
}

http_response_code(404);

echo json_encode([
    'success' => false,
    'message' => 'Ruta no encontrada.'
], JSON_UNESCAPED_UNICODE);
