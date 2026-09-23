<?php

require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../Models/Rol.php';
require_once __DIR__ . '/../Models/Auditoria.php';

class UsuarioController
{
    private const LARGO_MINIMO_PASSWORD = 8;

    private PDO $pdo;
    private Usuario $usuarioModel;
    private Rol $rolModel;
    private Auditoria $auditoriaModel;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->usuarioModel = new Usuario($pdo);
        $this->rolModel = new Rol($pdo);
        $this->auditoriaModel = new Auditoria($pdo);
    }

    public function listar(): void
    {
        $buscar = isset($_GET['buscar']) ? trim((string) $_GET['buscar']) : null;

        $idRol = null;

        if (isset($_GET['id_rol']) && trim((string) $_GET['id_rol']) !== '') {
            $idRol = filter_var($_GET['id_rol'], FILTER_VALIDATE_INT);

            if ($idRol === false) {
                $this->responderError(422, 'El rol seleccionado no es válido.');

                return;
            }
        }

        $estado = null;

        if (isset($_GET['estado']) && trim((string) $_GET['estado']) !== '') {
            $estadoTexto = strtoupper(trim((string) $_GET['estado']));

            if (!in_array($estadoTexto, ['ACTIVO', 'INACTIVO'], true)) {
                $this->responderError(422, 'El estado debe ser ACTIVO o INACTIVO.');

                return;
            }

            $estado = $estadoTexto === 'ACTIVO';
        }

        try {
            $usuarios = $this->usuarioModel->listar($buscar, $idRol, $estado);

            echo json_encode([
                'success' => true,
                'total' => count($usuarios),
                'usuarios' => $usuarios
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            $this->responderError(500, 'No se pudo obtener la lista de usuarios.');
        }
    }

    public function listarRoles(): void
    {
        try {
            $roles = $this->rolModel->listar();

            echo json_encode([
                'success' => true,
                'total' => count($roles),
                'roles' => $roles
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            $this->responderError(500, 'No se pudieron obtener los roles.');
        }
    }

    public function obtenerPorId(int $idUsuario): void
    {
        if ($idUsuario <= 0) {
            $this->responderError(400, 'El ID del usuario no es válido.');

            return;
        }

        $usuario = $this->usuarioModel->buscarPorId($idUsuario);

        if (!$usuario) {
            $this->responderError(404, 'Usuario no encontrado.');

            return;
        }

        echo json_encode([
            'success' => true,
            'usuario' => $usuario
        ], JSON_UNESCAPED_UNICODE);
    }

    public function crear(): void
    {
        $sesion = $this->usuarioSesion();

        if (!$sesion) {
            return;
        }

        $datos = $this->leerCuerpo();

        if ($datos === null) {
            return;
        }

        $campos = $this->validarDatosUsuario($datos);

        if ($campos === null) {
            return;
        }

        $password = (string) ($datos['password'] ?? '');

        if (!$this->validarPassword($password)) {
            return;
        }

        try {
            $this->pdo->beginTransaction();

            $idUsuario = $this->usuarioModel->crear([
                'id_rol' => $campos['id_rol'],
                'nombres' => $campos['nombres'],
                'apellidos' => $campos['apellidos'],
                'nombre_usuario' => $campos['nombre_usuario'],
                'password_hash' => password_hash($password, PASSWORD_DEFAULT)
            ]);

            $usuarioCreado = $this->usuarioModel->buscarPorId($idUsuario);

            $this->auditoriaModel->registrar([
                'id_usuario' => (int) $sesion['id_usuario'],
                'accion' => 'CREAR',
                'tabla_afectada' => 'usuarios',
                'id_registro' => $idUsuario,
                'descripcion' => 'Registro de usuario del sistema.',
                'datos_anteriores' => null,
                'datos_nuevos' => $usuarioCreado
            ]);

            $this->pdo->commit();

            http_response_code(201);

            echo json_encode([
                'success' => true,
                'message' => 'Usuario registrado correctamente.',
                'id_usuario' => $idUsuario
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->revertir();

            if ($e->getCode() === '23505') {
                $this->responderError(409, 'El nombre de usuario ya está registrado.');

                return;
            }

            $this->responderError(500, 'No se pudo registrar el usuario.');
        } catch (Throwable $e) {
            $this->revertir();
            $this->responderError(500, 'No se pudo registrar el usuario.');
        }
    }

    public function actualizar(int $idUsuario): void
    {
        $sesion = $this->usuarioSesion();

        if (!$sesion) {
            return;
        }

        if ($idUsuario <= 0) {
            $this->responderError(400, 'El ID del usuario no es válido.');

            return;
        }

        $usuarioExistente = $this->usuarioModel->buscarPorId($idUsuario);

        if (!$usuarioExistente) {
            $this->responderError(404, 'Usuario no encontrado.');

            return;
        }

        $datos = $this->leerCuerpo();

        if ($datos === null) {
            return;
        }

        $campos = $this->validarDatosUsuario($datos);

        if ($campos === null) {
            return;
        }

        $cambiaRol = $campos['id_rol'] !== (int) $usuarioExistente['id_rol'];

        if ($cambiaRol && $idUsuario === (int) $sesion['id_usuario']) {
            $this->responderError(409, 'No puede cambiar su propio rol.');

            return;
        }

        // Si deja de ser administrador, debe quedar al menos otro administrador activo.
        if (
            $cambiaRol
            && $usuarioExistente['rol'] === 'Administrador'
            && $usuarioExistente['estado']
            && $this->usuarioModel->contarAdministradoresActivos($idUsuario) === 0
        ) {
            $this->responderError(409, 'Debe existir al menos un administrador activo.');

            return;
        }

        try {
            $this->pdo->beginTransaction();

            $this->usuarioModel->actualizar($idUsuario, $campos);

            $usuarioActualizado = $this->usuarioModel->buscarPorId($idUsuario);

            $this->auditoriaModel->registrar([
                'id_usuario' => (int) $sesion['id_usuario'],
                'accion' => 'MODIFICAR',
                'tabla_afectada' => 'usuarios',
                'id_registro' => $idUsuario,
                'descripcion' => 'Actualización de usuario del sistema.',
                'datos_anteriores' => $usuarioExistente,
                'datos_nuevos' => $usuarioActualizado
            ]);

            $this->pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Usuario actualizado correctamente.'
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->revertir();

            if ($e->getCode() === '23505') {
                $this->responderError(409, 'El nombre de usuario ya está registrado.');

                return;
            }

            $this->responderError(500, 'No se pudo actualizar el usuario.');
        } catch (Throwable $e) {
            $this->revertir();
            $this->responderError(500, 'No se pudo actualizar el usuario.');
        }
    }

    public function cambiarEstado(int $idUsuario): void
    {
        $sesion = $this->usuarioSesion();

        if (!$sesion) {
            return;
        }

        if ($idUsuario <= 0) {
            $this->responderError(400, 'El ID del usuario no es válido.');

            return;
        }

        $usuarioExistente = $this->usuarioModel->buscarPorId($idUsuario);

        if (!$usuarioExistente) {
            $this->responderError(404, 'Usuario no encontrado.');

            return;
        }

        $datos = $this->leerCuerpo();

        if ($datos === null) {
            return;
        }

        $estadoTexto = strtoupper(trim((string) ($datos['estado'] ?? '')));

        if (!in_array($estadoTexto, ['ACTIVO', 'INACTIVO'], true)) {
            $this->responderError(422, 'El estado debe ser ACTIVO o INACTIVO.');

            return;
        }

        $estado = $estadoTexto === 'ACTIVO';

        if (!$estado && $idUsuario === (int) $sesion['id_usuario']) {
            $this->responderError(409, 'No puede desactivar su propio usuario.');

            return;
        }

        if (
            !$estado
            && $usuarioExistente['rol'] === 'Administrador'
            && $usuarioExistente['estado']
            && $this->usuarioModel->contarAdministradoresActivos($idUsuario) === 0
        ) {
            $this->responderError(409, 'Debe existir al menos un administrador activo.');

            return;
        }

        try {
            $this->pdo->beginTransaction();

            $this->usuarioModel->cambiarEstado($idUsuario, $estado);

            $usuarioActualizado = $this->usuarioModel->buscarPorId($idUsuario);

            $this->auditoriaModel->registrar([
                'id_usuario' => (int) $sesion['id_usuario'],
                'accion' => 'MODIFICAR',
                'tabla_afectada' => 'usuarios',
                'id_registro' => $idUsuario,
                'descripcion' => $estado
                    ? 'Activación de usuario del sistema.'
                    : 'Desactivación de usuario del sistema.',
                'datos_anteriores' => $usuarioExistente,
                'datos_nuevos' => $usuarioActualizado
            ]);

            $this->pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => $estado
                    ? 'Usuario activado correctamente.'
                    : 'Usuario desactivado correctamente.'
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            $this->revertir();
            $this->responderError(500, 'No se pudo cambiar el estado del usuario.');
        }
    }

    /**
     * El administrador asigna una contraseña nueva a otro usuario.
     */
    public function cambiarPassword(int $idUsuario): void
    {
        $sesion = $this->usuarioSesion();

        if (!$sesion) {
            return;
        }

        if ($idUsuario <= 0) {
            $this->responderError(400, 'El ID del usuario no es válido.');

            return;
        }

        $usuarioExistente = $this->usuarioModel->buscarPorId($idUsuario);

        if (!$usuarioExistente) {
            $this->responderError(404, 'Usuario no encontrado.');

            return;
        }

        $datos = $this->leerCuerpo();

        if ($datos === null) {
            return;
        }

        $password = (string) ($datos['password'] ?? '');

        if (!$this->validarPassword($password)) {
            return;
        }

        try {
            $this->pdo->beginTransaction();

            $this->usuarioModel->actualizarPassword(
                $idUsuario,
                password_hash($password, PASSWORD_DEFAULT)
            );

            // La auditoría nunca guarda la contraseña ni su hash.
            $this->auditoriaModel->registrar([
                'id_usuario' => (int) $sesion['id_usuario'],
                'accion' => 'MODIFICAR',
                'tabla_afectada' => 'usuarios',
                'id_registro' => $idUsuario,
                'descripcion' => 'Asignación de contraseña nueva al usuario '
                    . $usuarioExistente['nombre_usuario'] . '.',
                'datos_anteriores' => null,
                'datos_nuevos' => null
            ]);

            $this->pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Contraseña asignada correctamente.'
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            $this->revertir();
            $this->responderError(500, 'No se pudo asignar la contraseña.');
        }
    }

    /**
     * Cualquier usuario cambia su propia contraseña indicando la actual.
     */
    public function cambiarMiPassword(): void
    {
        $sesion = $this->usuarioSesion();

        if (!$sesion) {
            return;
        }

        $datos = $this->leerCuerpo();

        if ($datos === null) {
            return;
        }

        $passwordActual = (string) ($datos['password_actual'] ?? '');
        $password = (string) ($datos['password'] ?? '');

        if ($passwordActual === '') {
            $this->responderError(422, 'Debe indicar su contraseña actual.');

            return;
        }

        if (!$this->validarPassword($password)) {
            return;
        }

        $idUsuario = (int) $sesion['id_usuario'];
        $hashActual = $this->usuarioModel->obtenerPasswordHash($idUsuario);

        if ($hashActual === false || !password_verify($passwordActual, $hashActual)) {
            $this->responderError(422, 'La contraseña actual no es correcta.');

            return;
        }

        if (password_verify($password, $hashActual)) {
            $this->responderError(422, 'La contraseña nueva debe ser distinta de la actual.');

            return;
        }

        try {
            $this->pdo->beginTransaction();

            $this->usuarioModel->actualizarPassword(
                $idUsuario,
                password_hash($password, PASSWORD_DEFAULT)
            );

            $this->auditoriaModel->registrar([
                'id_usuario' => $idUsuario,
                'accion' => 'MODIFICAR',
                'tabla_afectada' => 'usuarios',
                'id_registro' => $idUsuario,
                'descripcion' => 'Cambio de contraseña propia.',
                'datos_anteriores' => null,
                'datos_nuevos' => null
            ]);

            $this->pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Contraseña actualizada correctamente.'
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            $this->revertir();
            $this->responderError(500, 'No se pudo actualizar la contraseña.');
        }
    }

    /**
     * @return array{id_rol: int, nombres: string, apellidos: string, nombre_usuario: string}|null
     */
    private function validarDatosUsuario(array $datos): ?array
    {
        $nombres = trim((string) ($datos['nombres'] ?? ''));
        $apellidos = trim((string) ($datos['apellidos'] ?? ''));
        $nombreUsuario = trim((string) ($datos['nombre_usuario'] ?? ''));
        $idRol = (int) ($datos['id_rol'] ?? 0);

        if ($nombres === '' || $apellidos === '') {
            $this->responderError(422, 'Los nombres y apellidos son obligatorios.');

            return null;
        }

        if (mb_strlen($nombres) > 100 || mb_strlen($apellidos) > 100) {
            $this->responderError(422, 'Los nombres y apellidos no deben superar 100 caracteres.');

            return null;
        }

        if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $nombreUsuario)) {
            $this->responderError(
                422,
                'El nombre de usuario debe tener entre 3 y 50 caracteres, sin espacios ni acentos.'
            );

            return null;
        }

        if ($idRol <= 0 || !$this->rolModel->buscarPorId($idRol)) {
            $this->responderError(422, 'El rol seleccionado no es válido.');

            return null;
        }

        return [
            'id_rol' => $idRol,
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'nombre_usuario' => $nombreUsuario
        ];
    }

    private function validarPassword(string $password): bool
    {
        if (mb_strlen($password) < self::LARGO_MINIMO_PASSWORD) {
            $this->responderError(
                422,
                'La contraseña debe tener al menos ' . self::LARGO_MINIMO_PASSWORD . ' caracteres.'
            );

            return false;
        }

        return true;
    }

    private function usuarioSesion(): ?array
    {
        $usuario = $_SESSION['usuario'] ?? null;

        if (!$usuario) {
            $this->responderError(401, 'Debe iniciar sesión para realizar esta acción.');

            return null;
        }

        return $usuario;
    }

    private function leerCuerpo(): ?array
    {
        $datos = json_decode(file_get_contents('php://input'), true);

        if (!is_array($datos)) {
            $this->responderError(400, 'Solicitud inválida.');

            return null;
        }

        return $datos;
    }

    private function revertir(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    private function responderError(int $codigo, string $mensaje): void
    {
        http_response_code($codigo);

        echo json_encode([
            'success' => false,
            'message' => $mensaje
        ], JSON_UNESCAPED_UNICODE);
    }
}
