<?php

require_once __DIR__ . '/../Services/AuthService.php';

class AuthController
{
    private AuthService $authService;

    public function __construct(PDO $pdo)
    {
        $this->authService = new AuthService($pdo);
    }

    public function login(): void
    {
        $datos = json_decode(file_get_contents('php://input'), true);

        if (!is_array($datos)) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Solicitud inválida.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $nombreUsuario = trim($datos['nombre_usuario'] ?? '');
        $password = $datos['password'] ?? '';

        if ($nombreUsuario === '' || $password === '') {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El usuario y la contraseña son obligatorios.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $usuario = $this->authService->autenticar(
            $nombreUsuario,
            $password
        );

        if (!$usuario) {
            http_response_code(401);

            echo json_encode([
                'success' => false,
                'message' => 'Usuario o contraseña incorrectos.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        session_regenerate_id(true);

        $_SESSION['usuario'] = [
            'id_usuario' => $usuario['id_usuario'],
            'nombre_usuario' => $usuario['nombre_usuario'],
            'nombres' => $usuario['nombres'],
            'apellidos' => $usuario['apellidos'],
            'id_rol' => $usuario['id_rol'],
            'rol' => $usuario['rol']
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Inicio de sesión correcto.',
            'usuario' => $usuario
        ], JSON_UNESCAPED_UNICODE);
    }

    public function me(): void
    {
        if (!isset($_SESSION['usuario'])) {
            http_response_code(401);

            echo json_encode([
                'success' => false,
                'message' => 'No hay una sesión activa.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        echo json_encode([
            'success' => true,
            'usuario' => $_SESSION['usuario']
        ], JSON_UNESCAPED_UNICODE);
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        echo json_encode([
            'success' => true,
            'message' => 'Sesión cerrada correctamente.'
        ], JSON_UNESCAPED_UNICODE);
    }
}
