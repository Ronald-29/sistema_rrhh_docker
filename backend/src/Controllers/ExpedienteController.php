<?php

require_once __DIR__ . '/../Models/Expediente.php';
require_once __DIR__ . '/../Models/Personal.php';
require_once __DIR__ . '/../Models/Ubicacion.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Models/Prestamo.php';

class ExpedienteController
{
    private PDO $pdo;
    private Expediente $expedienteModel;
    private Personal $personalModel;
    private Ubicacion $ubicacionModel;
    private Auditoria $auditoriaModel;
    private Prestamo $prestamoModel;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->expedienteModel = new Expediente($pdo);
        $this->personalModel = new Personal($pdo);
        $this->ubicacionModel = new Ubicacion($pdo);
        $this->auditoriaModel = new Auditoria($pdo);
        $this->prestamoModel = new Prestamo($pdo);
    }

    public function listar(): void
    {
        $buscar = isset($_GET['buscar'])
            ? trim($_GET['buscar'])
            : null;

        $estadoExpediente = isset($_GET['estado_expediente'])
            ? strtoupper(trim($_GET['estado_expediente']))
            : null;

        $estadoLaboral = isset($_GET['estado_laboral'])
            ? strtoupper(trim($_GET['estado_laboral']))
            : null;

        $estadosExpedientePermitidos = [
            'COMPLETO',
            'INCOMPLETO',
            'DETERIORADO',
            'OBSERVADO'
        ];

        if (
            $estadoExpediente !== null
            && $estadoExpediente !== ''
            && !in_array(
                $estadoExpediente,
                $estadosExpedientePermitidos,
                true
            )
        ) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El estado del expediente no es válido.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if (
            $estadoLaboral !== null
            && $estadoLaboral !== ''
            && !in_array(
                $estadoLaboral,
                ['ACTIVO', 'PASIVO'],
                true
            )
        ) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El estado laboral debe ser ACTIVO o PASIVO.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $revisado = null;

        if (isset($_GET['revisado']) && trim($_GET['revisado']) !== '') {
            $revisado = filter_var(
                trim($_GET['revisado']),
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );

            if ($revisado === null) {
                http_response_code(422);

                echo json_encode([
                    'success' => false,
                    'message' => 'El filtro revisado debe ser true o false.'
                ], JSON_UNESCAPED_UNICODE);

                return;
            }
        }

        $expedientes = $this->expedienteModel->listar(
            $buscar,
            $estadoExpediente,
            $estadoLaboral,
            $revisado
        );

        echo json_encode([
            'success' => true,
            'total' => count($expedientes),
            'expedientes' => $expedientes
        ], JSON_UNESCAPED_UNICODE);
    }

    public function obtenerPorId(int $idExpediente): void
    {
        if ($idExpediente <= 0) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'El ID del expediente no es válido.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $expediente = $this->expedienteModel->buscarPorId($idExpediente);

        if (!$expediente) {
            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Expediente no encontrado.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        echo json_encode([
            'success' => true,
            'expediente' => $expediente
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

        $numeroCorrelativo = trim($datos['numero_correlativo'] ?? '');
        $idPersonal = (int) ($datos['id_personal'] ?? 0);

        $idUbicacion = isset($datos['id_ubicacion'])
            && $datos['id_ubicacion'] !== ''
            && $datos['id_ubicacion'] !== null
            ? (int) $datos['id_ubicacion']
            : null;

        $estadoExpediente = strtoupper(
            trim($datos['estado_expediente'] ?? '')
        );

        $observaciones = trim($datos['observaciones'] ?? '');

        if ($numeroCorrelativo === '') {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El número correlativo es obligatorio.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if ($idPersonal <= 0) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'Debe seleccionar un personal válido.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $personal = $this->personalModel->buscarPorId($idPersonal);

        if (!$personal) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El personal seleccionado no existe.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if ($idUbicacion !== null) {
            if ($idUbicacion <= 0) {
                http_response_code(422);

                echo json_encode([
                    'success' => false,
                    'message' => 'La ubicación seleccionada no es válida.'
                ], JSON_UNESCAPED_UNICODE);

                return;
            }

            $ubicacion = $this->ubicacionModel->buscarPorId($idUbicacion);

            if (!$ubicacion) {
                http_response_code(422);

                echo json_encode([
                    'success' => false,
                    'message' => 'La ubicación seleccionada no existe.'
                ], JSON_UNESCAPED_UNICODE);

                return;
            }

            if (!$ubicacion['estado']) {
                http_response_code(422);

                echo json_encode([
                    'success' => false,
                    'message' => 'No puede asignar una ubicación inactiva.'
                ], JSON_UNESCAPED_UNICODE);

                return;
            }
        }

        $estadosPermitidos = [
            'COMPLETO',
            'INCOMPLETO',
            'DETERIORADO',
            'OBSERVADO'
        ];

        if (!in_array($estadoExpediente, $estadosPermitidos, true)) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El estado del expediente no es válido.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        try {
            $this->pdo->beginTransaction();

            $idExpediente = $this->expedienteModel->crear([
                'numero_correlativo' => $numeroCorrelativo,
                'id_personal' => $idPersonal,
                'id_ubicacion' => $idUbicacion,
                'estado_expediente' => $estadoExpediente,
                'observaciones' => $observaciones !== ''
                    ? $observaciones
                    : null
            ]);

            $expedienteCreado = $this->expedienteModel->buscarPorId(
                $idExpediente
            );

            $this->auditoriaModel->registrar([
                'id_usuario' => (int) $usuario['id_usuario'],
                'accion' => 'CREAR',
                'tabla_afectada' => 'expedientes',
                'id_registro' => $idExpediente,
                'descripcion' => 'Registro de expediente.',
                'datos_anteriores' => null,
                'datos_nuevos' => $expedienteCreado
            ]);

            $this->pdo->commit();

            http_response_code(201);

            echo json_encode([
                'success' => true,
                'message' => 'Expediente registrado correctamente.',
                'id_expediente' => $idExpediente
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            if ($e->getCode() === '23505') {
                http_response_code(409);

                echo json_encode([
                    'success' => false,
                    'message' => 'El número correlativo ya está registrado.'
                ], JSON_UNESCAPED_UNICODE);

                return;
            }

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'No se pudo registrar el expediente.'
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'No se pudo registrar el expediente.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    public function actualizar(int $idExpediente): void
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

        if ($idExpediente <= 0) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'El ID del expediente no es válido.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $expedienteExistente = $this->expedienteModel->buscarPorId(
            $idExpediente
        );

        if (!$expedienteExistente) {
            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Expediente no encontrado.'
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

        $numeroCorrelativo = trim($datos['numero_correlativo'] ?? '');
        $idPersonal = (int) ($datos['id_personal'] ?? 0);

        $idUbicacion = isset($datos['id_ubicacion'])
            && $datos['id_ubicacion'] !== ''
            && $datos['id_ubicacion'] !== null
            ? (int) $datos['id_ubicacion']
            : null;

        $estadoExpediente = strtoupper(
            trim($datos['estado_expediente'] ?? '')
        );

        $observaciones = trim($datos['observaciones'] ?? '');

        if ($numeroCorrelativo === '') {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El número correlativo es obligatorio.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if ($idPersonal <= 0) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'Debe seleccionar un personal válido.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $personal = $this->personalModel->buscarPorId($idPersonal);

        if (!$personal) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El personal seleccionado no existe.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if ($idUbicacion !== null) {
            if ($idUbicacion <= 0) {
                http_response_code(422);

                echo json_encode([
                    'success' => false,
                    'message' => 'La ubicación seleccionada no es válida.'
                ], JSON_UNESCAPED_UNICODE);

                return;
            }

            $ubicacion = $this->ubicacionModel->buscarPorId($idUbicacion);

            if (!$ubicacion) {
                http_response_code(422);

                echo json_encode([
                    'success' => false,
                    'message' => 'La ubicación seleccionada no existe.'
                ], JSON_UNESCAPED_UNICODE);

                return;
            }

            if (!$ubicacion['estado']) {
                http_response_code(422);

                echo json_encode([
                    'success' => false,
                    'message' => 'No puede asignar una ubicación inactiva.'
                ], JSON_UNESCAPED_UNICODE);

                return;
            }
        }

        $estadosPermitidos = [
            'COMPLETO',
            'INCOMPLETO',
            'DETERIORADO',
            'OBSERVADO'
        ];

        if (!in_array($estadoExpediente, $estadosPermitidos, true)) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El estado del expediente no es válido.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        try {
            $this->pdo->beginTransaction();

            $this->expedienteModel->actualizar($idExpediente, [
                'numero_correlativo' => $numeroCorrelativo,
                'id_personal' => $idPersonal,
                'id_ubicacion' => $idUbicacion,
                'estado_expediente' => $estadoExpediente,
                'observaciones' => $observaciones !== ''
                    ? $observaciones
                    : null
            ]);

            $expedienteActualizado = $this->expedienteModel->buscarPorId(
                $idExpediente
            );

            $this->auditoriaModel->registrar([
                'id_usuario' => (int) $usuario['id_usuario'],
                'accion' => 'MODIFICAR',
                'tabla_afectada' => 'expedientes',
                'id_registro' => $idExpediente,
                'descripcion' => 'Actualización de expediente.',
                'datos_anteriores' => $expedienteExistente,
                'datos_nuevos' => $expedienteActualizado
            ]);

            $this->pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Expediente actualizado correctamente.'
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            if ($e->getCode() === '23505') {
                http_response_code(409);

                echo json_encode([
                    'success' => false,
                    'message' => 'El número correlativo ya está registrado.'
                ], JSON_UNESCAPED_UNICODE);

                return;
            }

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'No se pudo actualizar el expediente.'
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'No se pudo actualizar el expediente.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    public function registrarRevision(int $idExpediente): void
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

        if ($idExpediente <= 0) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'El ID del expediente no es válido.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $expedienteExistente = $this->expedienteModel->buscarPorId(
            $idExpediente
        );

        if (!$expedienteExistente) {
            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Expediente no encontrado.'
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

        $revisadoPor = trim($datos['revisado_por'] ?? '');

        // Si no se envían, se conservan el estado y las observaciones actuales.
        $estadoExpediente = isset($datos['estado_expediente'])
            ? strtoupper(trim($datos['estado_expediente']))
            : $expedienteExistente['estado_expediente'];

        $observaciones = array_key_exists('observaciones', $datos)
            ? trim($datos['observaciones'] ?? '')
            : ($expedienteExistente['observaciones'] ?? '');

        if ($revisadoPor === '') {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'Debe indicar quién realizó la revisión física.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if (mb_strlen($revisadoPor) > 150) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El nombre de quien revisó no debe superar 150 caracteres.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $estadosPermitidos = [
            'COMPLETO',
            'INCOMPLETO',
            'DETERIORADO',
            'OBSERVADO'
        ];

        if (!in_array($estadoExpediente, $estadosPermitidos, true)) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El estado del expediente no es válido.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if ($expedienteExistente['revisado']) {
            http_response_code(409);

            echo json_encode([
                'success' => false,
                'message' => 'El expediente ya fue revisado. Anule la revisión para registrarla de nuevo.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if ($this->prestamoModel->expedienteTienePrestamoActivo($idExpediente)) {
            http_response_code(409);

            echo json_encode([
                'success' => false,
                'message' => 'El expediente está prestado. Debe devolverse antes de revisarlo.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        try {
            $this->pdo->beginTransaction();

            $this->expedienteModel->registrarRevision($idExpediente, [
                'revisado_por' => $revisadoPor,
                'id_usuario_revision' => (int) $usuario['id_usuario'],
                'estado_expediente' => $estadoExpediente,
                'observaciones' => $observaciones !== ''
                    ? $observaciones
                    : null
            ]);

            $expedienteRevisado = $this->expedienteModel->buscarPorId(
                $idExpediente
            );

            $this->auditoriaModel->registrar([
                'id_usuario' => (int) $usuario['id_usuario'],
                'accion' => 'MODIFICAR',
                'tabla_afectada' => 'expedientes',
                'id_registro' => $idExpediente,
                'descripcion' => 'Revisión física de expediente.',
                'datos_anteriores' => $expedienteExistente,
                'datos_nuevos' => $expedienteRevisado
            ]);

            $this->pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Revisión física registrada correctamente.',
                'expediente' => $expedienteRevisado
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'No se pudo registrar la revisión física.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    public function anularRevision(int $idExpediente): void
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

        if ($idExpediente <= 0) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'El ID del expediente no es válido.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $expedienteExistente = $this->expedienteModel->buscarPorId(
            $idExpediente
        );

        if (!$expedienteExistente) {
            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Expediente no encontrado.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if (!$expedienteExistente['revisado']) {
            http_response_code(409);

            echo json_encode([
                'success' => false,
                'message' => 'El expediente no tiene una revisión registrada.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        try {
            $this->pdo->beginTransaction();

            $this->expedienteModel->anularRevision($idExpediente);

            $expedienteActualizado = $this->expedienteModel->buscarPorId(
                $idExpediente
            );

            $this->auditoriaModel->registrar([
                'id_usuario' => (int) $usuario['id_usuario'],
                'accion' => 'MODIFICAR',
                'tabla_afectada' => 'expedientes',
                'id_registro' => $idExpediente,
                'descripcion' => 'Anulación de revisión física de expediente.',
                'datos_anteriores' => $expedienteExistente,
                'datos_nuevos' => $expedienteActualizado
            ]);

            $this->pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Revisión física anulada correctamente.'
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'No se pudo anular la revisión física.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}