<?php

require_once __DIR__ . '/../Models/Auditoria.php';

class AuditoriaController
{
    private Auditoria $auditoriaModel;

    public function __construct(PDO $pdo)
    {
        $this->auditoriaModel = new Auditoria($pdo);
    }

    public function listar(): void
    {
        $accion = isset($_GET['accion'])
            ? strtoupper(trim($_GET['accion']))
            : null;

        $tablaAfectada = isset($_GET['tabla'])
            ? trim($_GET['tabla'])
            : null;

        $idUsuario = null;

        if (isset($_GET['id_usuario']) && $_GET['id_usuario'] !== '') {
            $idUsuarioValidado = filter_var(
                $_GET['id_usuario'],
                FILTER_VALIDATE_INT
            );

            if ($idUsuarioValidado === false || $idUsuarioValidado <= 0) {
                http_response_code(422);

                echo json_encode([
                    'success' => false,
                    'message' => 'El identificador del usuario no es válido.'
                ], JSON_UNESCAPED_UNICODE);

                return;
            }

            $idUsuario = (int) $idUsuarioValidado;
        }

        if (
            $accion !== null
            && !in_array(
                $accion,
                ['CREAR', 'MODIFICAR', 'ELIMINAR', 'PRESTAR', 'DEVOLVER'],
                true
            )
        ) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'La acción de auditoría no es válida.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        try {
            $registros = $this->auditoriaModel->listar(
                $accion,
                $tablaAfectada,
                $idUsuario
            );

            foreach ($registros as &$registro) {
                $registro['datos_anteriores'] = $this->decodificarJson(
                    $registro['datos_anteriores']
                );

                $registro['datos_nuevos'] = $this->decodificarJson(
                    $registro['datos_nuevos']
                );
            }

            unset($registro);

            echo json_encode([
                'success' => true,
                'total' => count($registros),
                'auditoria' => $registros
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'No se pudo consultar la auditoría.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    public function obtenerPorId(int $idAuditoria): void
    {
        if ($idAuditoria <= 0) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El identificador de auditoría no es válido.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        try {
            $registro = $this->auditoriaModel->buscarPorId($idAuditoria);

            if (!$registro) {
                http_response_code(404);

                echo json_encode([
                    'success' => false,
                    'message' => 'Registro de auditoría no encontrado.'
                ], JSON_UNESCAPED_UNICODE);

                return;
            }

            $registro['datos_anteriores'] = $this->decodificarJson(
                $registro['datos_anteriores']
            );

            $registro['datos_nuevos'] = $this->decodificarJson(
                $registro['datos_nuevos']
            );

            echo json_encode([
                'success' => true,
                'auditoria' => $registro
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'No se pudo consultar el registro de auditoría.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    private function decodificarJson(mixed $datos): mixed
    {
        if ($datos === null || $datos === '') {
            return null;
        }

        if (is_array($datos)) {
            return $datos;
        }

        $resultado = json_decode((string) $datos, true);

        return json_last_error() === JSON_ERROR_NONE
            ? $resultado
            : $datos;
    }
}