<?php

class AuthMiddleware
{
    private static ?PDO $pdo = null;

    /**
     * Conexión para revalidar la sesión en cada petición. Se llama una vez en index.php.
     */
    public static function usarConexion(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    public static function verificarAutenticacion(): array
    {
        if (!isset($_SESSION['usuario'])) {
            http_response_code(401);

            echo json_encode([
                'success' => false,
                'codigo' => 'SESION_REQUERIDA',
                'message' => 'Debe iniciar sesión para acceder.'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        return self::revalidarUsuario($_SESSION['usuario']);
    }

    public static function verificarRol(array $rolesPermitidos): array
    {
        $usuario = self::verificarAutenticacion();

        if (!in_array($usuario['rol'], $rolesPermitidos, true)) {
            http_response_code(403);

            echo json_encode([
                'success' => false,
                'message' => 'No tiene permisos para realizar esta acción.'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        return $usuario;
    }

    /**
     * Relee el usuario de la base de datos: si fue desactivado o le cambiaron el rol,
     * el cambio se aplica de inmediato y no recién al volver a iniciar sesión.
     */
    private static function revalidarUsuario(array $usuarioSesion): array
    {
        if (self::$pdo === null) {
            return $usuarioSesion;
        }

        try {
            $stmt = self::$pdo->prepare("
                SELECT
                    u.id_usuario,
                    u.id_rol,
                    u.nombres,
                    u.apellidos,
                    u.nombre_usuario,
                    u.estado,
                    r.nombre AS rol
                FROM usuarios u
                INNER JOIN roles r
                    ON r.id_rol = u.id_rol
                WHERE u.id_usuario = :id_usuario
                LIMIT 1
            ");

            $stmt->execute([
                ':id_usuario' => (int) $usuarioSesion['id_usuario']
            ]);

            $usuario = $stmt->fetch();
        } catch (Throwable $e) {
            // Ante un problema de conexión se mantiene la sesión: la alternativa
            // sería dejar a todos fuera del sistema por una falla pasajera.
            return $usuarioSesion;
        }

        if (!$usuario || !$usuario['estado']) {
            $_SESSION = [];
            session_destroy();

            http_response_code(401);

            echo json_encode([
                'success' => false,
                'codigo' => 'USUARIO_DESACTIVADO',
                'message' => 'Su usuario fue desactivado. Consulte con el administrador.'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $_SESSION['usuario'] = [
            'id_usuario' => $usuario['id_usuario'],
            'nombre_usuario' => $usuario['nombre_usuario'],
            'nombres' => $usuario['nombres'],
            'apellidos' => $usuario['apellidos'],
            'id_rol' => $usuario['id_rol'],
            'rol' => $usuario['rol']
        ];

        return $_SESSION['usuario'];
    }
}
