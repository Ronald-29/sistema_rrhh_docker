<?php

require_once __DIR__ . '/../Models/Personal.php';
require_once __DIR__ . '/../Models/Auditoria.php';

class PersonalController
{
    private PDO $pdo;
    private Personal $personalModel;
    private Auditoria $auditoriaModel;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->personalModel = new Personal($pdo);
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

    public function listar(): void
    {
        $buscar = trim($_GET['buscar'] ?? '');
        $estado = strtoupper(trim($_GET['estado'] ?? ''));

        if ($estado !== '' && !in_array($estado, ['ACTIVO', 'PASIVO'], true)) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El estado laboral debe ser ACTIVO o PASIVO.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $personal = $this->personalModel->listar(
            $buscar !== '' ? $buscar : null,
            $estado !== '' ? $estado : null
        );

        echo json_encode([
            'success' => true,
            'total' => count($personal),
            'personal' => $personal
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

        $nombres = trim($datos['nombres'] ?? '');
        $apellidoPaterno = trim($datos['apellido_paterno'] ?? '');
        $apellidoMaterno = trim($datos['apellido_materno'] ?? '');
        $numeroDocumento = trim($datos['numero_documento'] ?? '');
        $cargo = trim($datos['cargo'] ?? '');
        $fechaIngreso = $datos['fecha_ingreso'] ?? null;
        $fechaRetiro = $datos['fecha_retiro'] ?? null;
        $estadoLaboral = strtoupper(trim($datos['estado_laboral'] ?? ''));

        if ($nombres === '' || $cargo === '' || $estadoLaboral === '') {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'Nombres, cargo y estado laboral son obligatorios.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if (!in_array($estadoLaboral, ['ACTIVO', 'PASIVO'], true)) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El estado laboral debe ser ACTIVO o PASIVO.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if ($fechaIngreso === '') {
            $fechaIngreso = null;
        }

        if ($fechaRetiro === '') {
            $fechaRetiro = null;
        }

        if ($estadoLaboral === 'ACTIVO' && $fechaRetiro !== null) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El personal ACTIVO no puede tener fecha de retiro.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if (!$this->fechaValida($fechaIngreso) || !$this->fechaValida($fechaRetiro)) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'Las fechas deben tener el formato AAAA-MM-DD y ser válidas.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if (
            $fechaIngreso !== null &&
            $fechaRetiro !== null &&
            $fechaRetiro < $fechaIngreso
        ) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'La fecha de retiro no puede ser anterior a la fecha de ingreso.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        try {
            $this->pdo->beginTransaction();

            $idPersonal = $this->personalModel->crear([
                'nombres' => $nombres,
                'apellido_paterno' => $apellidoPaterno !== '' ? $apellidoPaterno : null,
                'apellido_materno' => $apellidoMaterno !== '' ? $apellidoMaterno : null,
                'numero_documento' => $numeroDocumento !== '' ? $numeroDocumento : null,
                'cargo' => $cargo,
                'fecha_ingreso' => $fechaIngreso,
                'fecha_retiro' => $fechaRetiro,
                'estado_laboral' => $estadoLaboral
            ]);

            $personalCreado = $this->personalModel->buscarPorId($idPersonal);

            $this->auditoriaModel->registrar([
                'id_usuario' => (int) $usuario['id_usuario'],
                'accion' => 'CREAR',
                'tabla_afectada' => 'personal',
                'id_registro' => $idPersonal,
                'descripcion' => 'Registro de personal.',
                'datos_anteriores' => null,
                'datos_nuevos' => $personalCreado
            ]);

            $this->pdo->commit();

            http_response_code(201);

            echo json_encode([
                'success' => true,
                'message' => 'Personal registrado correctamente.',
                'id_personal' => $idPersonal
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            if ($e->getCode() === '23505') {
                http_response_code(409);

                echo json_encode([
                    'success' => false,
                    'message' => 'Ya existe una persona registrada con ese número de documento.'
                ], JSON_UNESCAPED_UNICODE);

                return;
            }

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'No se pudo registrar el personal.'
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'No se pudo registrar el personal.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    public function obtenerPorId(int $idPersonal): void
    {
        if ($idPersonal <= 0) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'El ID del personal no es válido.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $personal = $this->personalModel->buscarPorId($idPersonal);

        if (!$personal) {
            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Personal no encontrado.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        echo json_encode([
            'success' => true,
            'personal' => $personal
        ], JSON_UNESCAPED_UNICODE);
    }

    public function actualizar(int $idPersonal): void
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

        if ($idPersonal <= 0) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'El ID del personal no es válido.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $personalExistente = $this->personalModel->buscarPorId($idPersonal);

        if (!$personalExistente) {
            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Personal no encontrado.'
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

        $nombres = trim($datos['nombres'] ?? '');
        $apellidoPaterno = trim($datos['apellido_paterno'] ?? '');
        $apellidoMaterno = trim($datos['apellido_materno'] ?? '');
        $numeroDocumento = trim($datos['numero_documento'] ?? '');
        $cargo = trim($datos['cargo'] ?? '');
        $fechaIngreso = $datos['fecha_ingreso'] ?? null;
        $fechaRetiro = $datos['fecha_retiro'] ?? null;
        $estadoLaboral = strtoupper(trim($datos['estado_laboral'] ?? ''));

        if ($nombres === '' || $cargo === '' || $estadoLaboral === '') {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'Nombres, cargo y estado laboral son obligatorios.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if (!in_array($estadoLaboral, ['ACTIVO', 'PASIVO'], true)) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El estado laboral debe ser ACTIVO o PASIVO.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if ($fechaIngreso === '') {
            $fechaIngreso = null;
        }

        if ($fechaRetiro === '') {
            $fechaRetiro = null;
        }

        if ($estadoLaboral === 'ACTIVO' && $fechaRetiro !== null) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El personal ACTIVO no puede tener fecha de retiro.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if (!$this->fechaValida($fechaIngreso) || !$this->fechaValida($fechaRetiro)) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'Las fechas deben tener el formato AAAA-MM-DD y ser válidas.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if (
            $fechaIngreso !== null &&
            $fechaRetiro !== null &&
            $fechaRetiro < $fechaIngreso
        ) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'La fecha de retiro no puede ser anterior a la fecha de ingreso.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        try {
            $this->pdo->beginTransaction();

            $this->personalModel->actualizar($idPersonal, [
                'nombres' => $nombres,
                'apellido_paterno' => $apellidoPaterno !== '' ? $apellidoPaterno : null,
                'apellido_materno' => $apellidoMaterno !== '' ? $apellidoMaterno : null,
                'numero_documento' => $numeroDocumento !== '' ? $numeroDocumento : null,
                'cargo' => $cargo,
                'fecha_ingreso' => $fechaIngreso,
                'fecha_retiro' => $fechaRetiro,
                'estado_laboral' => $estadoLaboral
            ]);

            $personalActualizado = $this->personalModel->buscarPorId($idPersonal);

            $this->auditoriaModel->registrar([
                'id_usuario' => (int) $usuario['id_usuario'],
                'accion' => 'MODIFICAR',
                'tabla_afectada' => 'personal',
                'id_registro' => $idPersonal,
                'descripcion' => 'Actualización de datos del personal.',
                'datos_anteriores' => $personalExistente,
                'datos_nuevos' => $personalActualizado
            ]);

            $this->pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Personal actualizado correctamente.'
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            if ($e->getCode() === '23505') {
                http_response_code(409);

                echo json_encode([
                    'success' => false,
                    'message' => 'Ya existe una persona registrada con ese número de documento.'
                ], JSON_UNESCAPED_UNICODE);

                return;
            }

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'No se pudo actualizar el personal.'
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'No se pudo actualizar el personal.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}