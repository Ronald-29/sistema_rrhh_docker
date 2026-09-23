<?php

require_once __DIR__ . '/../Models/Documento.php';
require_once __DIR__ . '/../Models/Expediente.php';
require_once __DIR__ . '/../Models/Auditoria.php';

class DocumentoController
{
    private PDO $pdo;
    private Documento $documentoModel;
    private Expediente $expedienteModel;
    private Auditoria $auditoriaModel;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->documentoModel = new Documento($pdo);
        $this->expedienteModel = new Expediente($pdo);
        $this->auditoriaModel = new Auditoria($pdo);
    }

    private function fechaValida(?string $fecha): bool
    {
        if ($fecha === null || $fecha === '') {
            return true;
        }

        $fechaObjeto = DateTime::createFromFormat('Y-m-d', $fecha);

        return $fechaObjeto !== false
            && $fechaObjeto->format('Y-m-d') === $fecha;
    }

    public function listarPorExpediente(int $idExpediente): void
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

        $documentos = $this->documentoModel->listarPorExpediente(
            $idExpediente
        );

        echo json_encode([
            'success' => true,
            'total' => count($documentos),
            'documentos' => $documentos
        ], JSON_UNESCAPED_UNICODE);
    }

    public function obtenerPorId(int $idDocumento): void
    {
        if ($idDocumento <= 0) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'El ID del documento no es válido.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $documento = $this->documentoModel->buscarPorId($idDocumento);

        if (!$documento) {
            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Documento no encontrado.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        echo json_encode([
            'success' => true,
            'documento' => $documento
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

        $idExpediente = (int) ($datos['id_expediente'] ?? 0);
        $nombreDocumento = trim($datos['nombre_documento'] ?? '');
        $tipoDocumento = trim($datos['tipo_documento'] ?? '');
        $numeroDocumento = trim($datos['numero_documento'] ?? '');

        $fechaDocumento = isset($datos['fecha_documento'])
            ? trim((string) $datos['fecha_documento'])
            : null;

        $estadoDocumento = strtoupper(
            trim($datos['estado_documento'] ?? 'DISPONIBLE')
        );

        $observaciones = trim($datos['observaciones'] ?? '');

        if ($idExpediente <= 0) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'Debe seleccionar un expediente válido.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $expediente = $this->expedienteModel->buscarPorId($idExpediente);

        if (!$expediente) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El expediente seleccionado no existe.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if ($nombreDocumento === '') {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El nombre del documento es obligatorio.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if ($fechaDocumento === '') {
            $fechaDocumento = null;
        }

        if (!$this->fechaValida($fechaDocumento)) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'La fecha del documento no es válida. Use el formato YYYY-MM-DD.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $estadosPermitidos = [
            'DISPONIBLE',
            'DETERIORADO',
            'EXTRAVIADO'
        ];

        if (!in_array($estadoDocumento, $estadosPermitidos, true)) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El estado del documento no es válido.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        try {
            $this->pdo->beginTransaction();

            $idDocumento = $this->documentoModel->crear([
                'id_expediente' => $idExpediente,
                'nombre_documento' => $nombreDocumento,
                'tipo_documento' => $tipoDocumento !== ''
                    ? $tipoDocumento
                    : null,
                'numero_documento' => $numeroDocumento !== ''
                    ? $numeroDocumento
                    : null,
                'fecha_documento' => $fechaDocumento,
                'estado_documento' => $estadoDocumento,
                'observaciones' => $observaciones !== ''
                    ? $observaciones
                    : null
            ]);

            $documentoCreado = $this->documentoModel->buscarPorId(
                $idDocumento
            );

            $this->auditoriaModel->registrar([
                'id_usuario' => (int) $usuario['id_usuario'],
                'accion' => 'CREAR',
                'tabla_afectada' => 'documentos',
                'id_registro' => $idDocumento,
                'descripcion' => 'Registro de documento.',
                'datos_anteriores' => null,
                'datos_nuevos' => $documentoCreado
            ]);

            $this->pdo->commit();

            http_response_code(201);

            echo json_encode([
                'success' => true,
                'message' => 'Documento registrado correctamente.',
                'id_documento' => $idDocumento
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'No se pudo registrar el documento.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    public function actualizar(int $idDocumento): void
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

        if ($idDocumento <= 0) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'El ID del documento no es válido.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $documentoActual = $this->documentoModel->buscarPorId($idDocumento);

        if (!$documentoActual) {
            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Documento no encontrado.'
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

        $idExpediente = (int) ($datos['id_expediente'] ?? 0);
        $nombreDocumento = trim($datos['nombre_documento'] ?? '');
        $tipoDocumento = trim($datos['tipo_documento'] ?? '');
        $numeroDocumento = trim($datos['numero_documento'] ?? '');

        $fechaDocumento = isset($datos['fecha_documento'])
            ? trim((string) $datos['fecha_documento'])
            : null;

        $estadoDocumento = strtoupper(
            trim($datos['estado_documento'] ?? '')
        );

        $observaciones = trim($datos['observaciones'] ?? '');

        if ($idExpediente <= 0) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'Debe seleccionar un expediente válido.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $expediente = $this->expedienteModel->buscarPorId($idExpediente);

        if (!$expediente) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El expediente seleccionado no existe.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if ($nombreDocumento === '') {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El nombre del documento es obligatorio.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if ($fechaDocumento === '') {
            $fechaDocumento = null;
        }

        if (!$this->fechaValida($fechaDocumento)) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'La fecha del documento no es válida. Use el formato YYYY-MM-DD.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $estadosPermitidos = [
            'DISPONIBLE',
            'DETERIORADO',
            'EXTRAVIADO'
        ];

        if (!in_array($estadoDocumento, $estadosPermitidos, true)) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El estado del documento no es válido.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        try {
            $this->pdo->beginTransaction();

            $this->documentoModel->actualizar(
                $idDocumento,
                [
                    'id_expediente' => $idExpediente,
                    'nombre_documento' => $nombreDocumento,
                    'tipo_documento' => $tipoDocumento !== ''
                        ? $tipoDocumento
                        : null,
                    'numero_documento' => $numeroDocumento !== ''
                        ? $numeroDocumento
                        : null,
                    'fecha_documento' => $fechaDocumento,
                    'estado_documento' => $estadoDocumento,
                    'observaciones' => $observaciones !== ''
                        ? $observaciones
                        : null
                ]
            );

            $documentoActualizado = $this->documentoModel->buscarPorId(
                $idDocumento
            );

            $this->auditoriaModel->registrar([
                'id_usuario' => (int) $usuario['id_usuario'],
                'accion' => 'MODIFICAR',
                'tabla_afectada' => 'documentos',
                'id_registro' => $idDocumento,
                'descripcion' => 'Actualización de documento.',
                'datos_anteriores' => $documentoActual,
                'datos_nuevos' => $documentoActualizado
            ]);

            $this->pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Documento actualizado correctamente.'
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'No se pudo actualizar el documento.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}