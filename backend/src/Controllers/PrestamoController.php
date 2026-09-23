<?php

require_once __DIR__ . '/../Models/Prestamo.php';
require_once __DIR__ . '/../Models/Expediente.php';
require_once __DIR__ . '/../Models/Documento.php';
require_once __DIR__ . '/../Models/Auditoria.php';

class PrestamoController
{
    private PDO $pdo;
    private Prestamo $prestamoModel;
    private Expediente $expedienteModel;
    private Documento $documentoModel;
    private Auditoria $auditoriaModel;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->prestamoModel = new Prestamo($pdo);
        $this->expedienteModel = new Expediente($pdo);
        $this->documentoModel = new Documento($pdo);
        $this->auditoriaModel = new Auditoria($pdo);
    }

    public function listar(): void
    {
        $estado = isset($_GET['estado'])
            ? strtoupper(trim($_GET['estado']))
            : null;

        if ($estado !== null && !in_array($estado, ['PRESTADO', 'DEVUELTO'], true)) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El estado del préstamo no es válido.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $prestamos = $this->prestamoModel->listar($estado);

        echo json_encode([
            'success' => true,
            'total' => count($prestamos),
            'prestamos' => $prestamos
        ], JSON_UNESCAPED_UNICODE);
    }

    public function obtenerPorId(int $idPrestamo): void
    {
        if ($idPrestamo <= 0) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El identificador del préstamo no es válido.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $prestamo = $this->prestamoModel->buscarPorId($idPrestamo);

        if (!$prestamo) {
            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Préstamo no encontrado.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        echo json_encode([
            'success' => true,
            'prestamo' => $prestamo
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
                'message' => 'El contenido enviado no es válido.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $idExpediente = isset($datos['id_expediente']) && $datos['id_expediente'] !== null
            ? filter_var($datos['id_expediente'], FILTER_VALIDATE_INT)
            : null;

        $idDocumento = isset($datos['id_documento']) && $datos['id_documento'] !== null
            ? filter_var($datos['id_documento'], FILTER_VALIDATE_INT)
            : null;

        $entregadoPor = trim((string) ($datos['entregado_por'] ?? ''));
        $recibidoPor = trim((string) ($datos['recibido_por'] ?? ''));
        $motivo = trim((string) ($datos['motivo'] ?? ''));
        $observacionesEntrega = trim((string) ($datos['observaciones_entrega'] ?? ''));

        $tieneExpediente = $idExpediente !== null && $idExpediente !== false;
        $tieneDocumento = $idDocumento !== null && $idDocumento !== false;

        if ($tieneExpediente === $tieneDocumento) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'Debe seleccionar un expediente o un documento, pero no ambos.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if ($tieneExpediente && $idExpediente <= 0) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El identificador del expediente no es válido.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if ($tieneDocumento && $idDocumento <= 0) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El identificador del documento no es válido.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if ($entregadoPor === '' || $recibidoPor === '') {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'Debe indicar quién entrega y quién recibe.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $expediente = null;
        $documento = null;

        if ($tieneExpediente) {
            $expediente = $this->expedienteModel->buscarPorId((int) $idExpediente);

            if (!$expediente) {
                http_response_code(404);

                echo json_encode([
                    'success' => false,
                    'message' => 'Expediente no encontrado.'
                ], JSON_UNESCAPED_UNICODE);

                return;
            }

            if ($this->prestamoModel->expedienteTienePrestamoActivo((int) $idExpediente)) {
                http_response_code(409);

                echo json_encode([
                    'success' => false,
                    'message' => 'El expediente ya tiene un préstamo pendiente de devolución.'
                ], JSON_UNESCAPED_UNICODE);

                return;
            }

            if ($this->prestamoModel->expedienteTieneDocumentoPrestado((int) $idExpediente)) {
                http_response_code(409);

                echo json_encode([
                    'success' => false,
                    'message' => 'No se puede prestar el expediente porque contiene uno o más documentos prestados.'
                ], JSON_UNESCAPED_UNICODE);

                return;
            }
        }

        if ($tieneDocumento) {
            $documento = $this->documentoModel->buscarPorId((int) $idDocumento);

            if (!$documento) {
                http_response_code(404);

                echo json_encode([
                    'success' => false,
                    'message' => 'Documento no encontrado.'
                ], JSON_UNESCAPED_UNICODE);

                return;
            }

            if ($documento['estado_documento'] !== 'DISPONIBLE') {
                http_response_code(409);

                echo json_encode([
                    'success' => false,
                    'message' => 'El documento no se encuentra disponible para préstamo.'
                ], JSON_UNESCAPED_UNICODE);

                return;
            }

            if ($this->prestamoModel->documentoTieneExpedientePrestado((int) $idDocumento)) {
                http_response_code(409);

                echo json_encode([
                    'success' => false,
                    'message' => 'No se puede prestar el documento porque su expediente se encuentra prestado.'
                ], JSON_UNESCAPED_UNICODE);

                return;
            }
        }

        try {
            $this->pdo->beginTransaction();

            $idPrestamo = $this->prestamoModel->crear([
                'id_expediente' => $tieneExpediente ? (int) $idExpediente : null,
                'id_documento' => $tieneDocumento ? (int) $idDocumento : null,
                'entregado_por' => $entregadoPor,
                'recibido_por' => $recibidoPor,
                'motivo' => $motivo !== '' ? $motivo : null,
                'observaciones_entrega' => $observacionesEntrega !== '' ? $observacionesEntrega : null,
                'id_usuario_registro' => (int) $usuario['id_usuario']
            ]);

            if ($tieneDocumento) {
                $sql = "
                    UPDATE documentos
                    SET
                        estado_documento = 'PRESTADO',
                        fecha_actualizacion = CURRENT_TIMESTAMP
                    WHERE id_documento = :id_documento
                    AND estado_documento = 'DISPONIBLE'
                ";

                $stmt = $this->pdo->prepare($sql);

                $stmt->execute([
                    ':id_documento' => (int) $idDocumento
                ]);

                if ($stmt->rowCount() === 0) {
                    throw new RuntimeException('No se pudo actualizar el estado del documento.');
                }
            }

            $prestamoRegistrado = $this->prestamoModel->buscarPorId($idPrestamo);

            $this->auditoriaModel->registrar([
                'id_usuario' => (int) $usuario['id_usuario'],
                'accion' => 'PRESTAR',
                'tabla_afectada' => 'prestamos',
                'id_registro' => $idPrestamo,
                'descripcion' => $tieneExpediente
                    ? 'Registro de préstamo de expediente.'
                    : 'Registro de préstamo de documento.',
                'datos_anteriores' => null,
                'datos_nuevos' => $prestamoRegistrado ?: [
                    'id_prestamo' => $idPrestamo,
                    'id_expediente' => $tieneExpediente ? (int) $idExpediente : null,
                    'id_documento' => $tieneDocumento ? (int) $idDocumento : null,
                    'entregado_por' => $entregadoPor,
                    'recibido_por' => $recibidoPor,
                    'estado' => 'PRESTADO'
                ]
            ]);

            $this->pdo->commit();

            http_response_code(201);

            echo json_encode([
                'success' => true,
                'message' => 'Préstamo registrado correctamente.',
                'id_prestamo' => $idPrestamo
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            if ($e->getCode() === '23505') {
                http_response_code(409);

                echo json_encode([
                    'success' => false,
                    'message' => 'El expediente o documento ya tiene un préstamo pendiente de devolución.'
                ], JSON_UNESCAPED_UNICODE);

                return;
            }

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'No se pudo registrar el préstamo.'
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'No se pudo registrar el préstamo.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    public function devolver(int $idPrestamo): void
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

        if ($idPrestamo <= 0) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El identificador del préstamo no es válido.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $prestamo = $this->prestamoModel->buscarPorId($idPrestamo);

        if (!$prestamo) {
            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Préstamo no encontrado.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if ($prestamo['estado'] !== 'PRESTADO') {
            http_response_code(409);

            echo json_encode([
                'success' => false,
                'message' => 'El préstamo ya fue devuelto.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $datos = json_decode(file_get_contents('php://input'), true);

        if (!is_array($datos)) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'El contenido enviado no es válido.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $devueltoPor = trim((string) ($datos['devuelto_por'] ?? ''));
        $recibidoDevolucionPor = trim((string) ($datos['recibido_devolucion_por'] ?? ''));
        $observacionesDevolucion = trim((string) ($datos['observaciones_devolucion'] ?? ''));

        if ($devueltoPor === '' || $recibidoDevolucionPor === '') {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'Debe indicar quién devuelve y quién recibe la devolución.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        try {
            $this->pdo->beginTransaction();

            $resultado = $this->prestamoModel->devolver($idPrestamo, [
                'devuelto_por' => $devueltoPor,
                'recibido_devolucion_por' => $recibidoDevolucionPor,
                'observaciones_devolucion' => $observacionesDevolucion !== ''
                    ? $observacionesDevolucion
                    : null
            ]);

            if (!$resultado) {
                throw new RuntimeException('No se pudo registrar la devolución.');
            }

            if ($prestamo['id_documento'] !== null) {
                $sql = "
                    UPDATE documentos
                    SET
                        estado_documento = 'DISPONIBLE',
                        fecha_actualizacion = CURRENT_TIMESTAMP
                    WHERE id_documento = :id_documento
                    AND estado_documento = 'PRESTADO'
                ";

                $stmt = $this->pdo->prepare($sql);

                $stmt->execute([
                    ':id_documento' => (int) $prestamo['id_documento']
                ]);

                if ($stmt->rowCount() === 0) {
                    throw new RuntimeException('No se pudo actualizar el estado del documento.');
                }
            }

            $prestamoDevuelto = $this->prestamoModel->buscarPorId($idPrestamo);

            $this->auditoriaModel->registrar([
                'id_usuario' => (int) $usuario['id_usuario'],
                'accion' => 'DEVOLVER',
                'tabla_afectada' => 'prestamos',
                'id_registro' => $idPrestamo,
                'descripcion' => $prestamo['id_expediente'] !== null
                    ? 'Registro de devolución de expediente.'
                    : 'Registro de devolución de documento.',
                'datos_anteriores' => $prestamo,
                'datos_nuevos' => $prestamoDevuelto ?: [
                    'id_prestamo' => $idPrestamo,
                    'devuelto_por' => $devueltoPor,
                    'recibido_devolucion_por' => $recibidoDevolucionPor,
                    'estado' => 'DEVUELTO'
                ]
            ]);

            $this->pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Devolución registrada correctamente.'
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'No se pudo registrar la devolución.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}