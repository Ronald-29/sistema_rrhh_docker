<?php

require_once __DIR__ . '/../Models/Ubicacion.php';
require_once __DIR__ . '/../Models/Auditoria.php';

class UbicacionController
{
    private PDO $pdo;
    private Ubicacion $ubicacionModel;
    private Auditoria $auditoriaModel;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->ubicacionModel = new Ubicacion($pdo);
        $this->auditoriaModel = new Auditoria($pdo);
    }

    public function listar(): void
    {
        $buscar = isset($_GET['buscar'])
            ? trim($_GET['buscar'])
            : null;

        $estadoTexto = isset($_GET['estado'])
            ? strtoupper(trim($_GET['estado']))
            : null;

        $estado = null;

        if ($estadoTexto !== null && $estadoTexto !== '') {
            if (!in_array($estadoTexto, ['ACTIVO', 'INACTIVO'], true)) {
                http_response_code(422);

                echo json_encode([
                    'success' => false,
                    'message' => 'El estado debe ser ACTIVO o INACTIVO.'
                ], JSON_UNESCAPED_UNICODE);

                return;
            }

            $estado = $estadoTexto === 'ACTIVO';
        }

        $ubicaciones = $this->ubicacionModel->listar(
            $buscar,
            $estado
        );

        echo json_encode([
            'success' => true,
            'total' => count($ubicaciones),
            'ubicaciones' => $ubicaciones
        ], JSON_UNESCAPED_UNICODE);
    }

    public function obtenerPorId(int $idUbicacion): void
    {
        if ($idUbicacion <= 0) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'El ID de la ubicación no es válido.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $ubicacion = $this->ubicacionModel->buscarPorId($idUbicacion);

        if (!$ubicacion) {
            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Ubicación no encontrada.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        echo json_encode([
            'success' => true,
            'ubicacion' => $ubicacion
        ], JSON_UNESCAPED_UNICODE);
    }

    public function crear(): void
    {
        $usuario = $_SESSION['usuario'] ?? null;

        if (!$usuario) {
            http_response_code(401);

            echo json_encode([
                'success' => false,
                'message' => 'Debe iniciar sesión para realizar esta acción.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $datos = json_decode(file_get_contents('php://input'), true);

        if (!is_array($datos)) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Solicitud inválida.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $ambiente = trim($datos['ambiente'] ?? '');
        $estante = trim($datos['estante'] ?? '');
        $archivador = trim($datos['archivador'] ?? '');
        $caja = trim($datos['caja'] ?? '');
        $carpeta = trim($datos['carpeta'] ?? '');
        $descripcion = trim($datos['descripcion'] ?? '');

        if ($ambiente === '') {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El ambiente es obligatorio.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        try {
            $this->pdo->beginTransaction();

            $idUbicacion = $this->ubicacionModel->crear([
                'ambiente' => $ambiente,
                'estante' => $estante !== '' ? $estante : null,
                'archivador' => $archivador !== '' ? $archivador : null,
                'caja' => $caja !== '' ? $caja : null,
                'carpeta' => $carpeta !== '' ? $carpeta : null,
                'descripcion' => $descripcion !== '' ? $descripcion : null
            ]);

            $ubicacionCreada = $this->ubicacionModel->buscarPorId($idUbicacion);

            $this->auditoriaModel->registrar([
                'id_usuario' => (int) $usuario['id_usuario'],
                'accion' => 'CREAR',
                'tabla_afectada' => 'ubicaciones',
                'id_registro' => $idUbicacion,
                'descripcion' => 'Registro de ubicación física.',
                'datos_anteriores' => null,
                'datos_nuevos' => $ubicacionCreada
            ]);

            $this->pdo->commit();

            http_response_code(201);

            echo json_encode([
                'success' => true,
                'message' => 'Ubicación registrada correctamente.',
                'id_ubicacion' => $idUbicacion
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'No se pudo registrar la ubicación.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    public function actualizar(int $idUbicacion): void
    {
        $usuario = $_SESSION['usuario'] ?? null;

        if (!$usuario) {
            http_response_code(401);

            echo json_encode([
                'success' => false,
                'message' => 'Debe iniciar sesión para realizar esta acción.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if ($idUbicacion <= 0) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'El ID de la ubicación no es válido.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $ubicacionExistente = $this->ubicacionModel->buscarPorId($idUbicacion);

        if (!$ubicacionExistente) {
            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Ubicación no encontrada.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $datos = json_decode(file_get_contents('php://input'), true);

        if (!is_array($datos)) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Solicitud inválida.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $ambiente = trim($datos['ambiente'] ?? '');
        $estante = trim($datos['estante'] ?? '');
        $archivador = trim($datos['archivador'] ?? '');
        $caja = trim($datos['caja'] ?? '');
        $carpeta = trim($datos['carpeta'] ?? '');
        $descripcion = trim($datos['descripcion'] ?? '');

        if ($ambiente === '') {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El ambiente es obligatorio.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        try {
            $this->pdo->beginTransaction();

            $this->ubicacionModel->actualizar($idUbicacion, [
                'ambiente' => $ambiente,
                'estante' => $estante !== '' ? $estante : null,
                'archivador' => $archivador !== '' ? $archivador : null,
                'caja' => $caja !== '' ? $caja : null,
                'carpeta' => $carpeta !== '' ? $carpeta : null,
                'descripcion' => $descripcion !== '' ? $descripcion : null
            ]);

            $ubicacionActualizada = $this->ubicacionModel->buscarPorId($idUbicacion);

            $this->auditoriaModel->registrar([
                'id_usuario' => (int) $usuario['id_usuario'],
                'accion' => 'MODIFICAR',
                'tabla_afectada' => 'ubicaciones',
                'id_registro' => $idUbicacion,
                'descripcion' => 'Actualización de ubicación física.',
                'datos_anteriores' => $ubicacionExistente,
                'datos_nuevos' => $ubicacionActualizada
            ]);

            $this->pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Ubicación actualizada correctamente.'
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'No se pudo actualizar la ubicación.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    public function cambiarEstado(int $idUbicacion): void
    {
        $usuario = $_SESSION['usuario'] ?? null;

        if (!$usuario) {
            http_response_code(401);

            echo json_encode([
                'success' => false,
                'message' => 'Debe iniciar sesión para realizar esta acción.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if ($idUbicacion <= 0) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'El ID de la ubicación no es válido.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $ubicacionExistente = $this->ubicacionModel->buscarPorId($idUbicacion);

        if (!$ubicacionExistente) {
            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Ubicación no encontrada.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $datos = json_decode(file_get_contents('php://input'), true);

        if (!is_array($datos)) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Solicitud inválida.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $estadoTexto = strtoupper(trim($datos['estado'] ?? ''));

        if (!in_array($estadoTexto, ['ACTIVO', 'INACTIVO'], true)) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El estado debe ser ACTIVO o INACTIVO.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $estado = $estadoTexto === 'ACTIVO';

        try {
            $this->pdo->beginTransaction();

            $this->ubicacionModel->cambiarEstado(
                $idUbicacion,
                $estado
            );

            $ubicacionActualizada = $this->ubicacionModel->buscarPorId($idUbicacion);

            $this->auditoriaModel->registrar([
                'id_usuario' => (int) $usuario['id_usuario'],
                'accion' => 'MODIFICAR',
                'tabla_afectada' => 'ubicaciones',
                'id_registro' => $idUbicacion,
                'descripcion' => $estado
                    ? 'Activación de ubicación física.'
                    : 'Desactivación de ubicación física.',
                'datos_anteriores' => $ubicacionExistente,
                'datos_nuevos' => $ubicacionActualizada
            ]);

            $this->pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => $estado
                    ? 'Ubicación activada correctamente.'
                    : 'Ubicación desactivada correctamente.'
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'No se pudo cambiar el estado de la ubicación.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}