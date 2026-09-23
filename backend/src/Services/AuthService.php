<?php

require_once __DIR__ . '/../Models/Usuario.php';

class AuthService
{
    private Usuario $usuarioModel;

    public function __construct(PDO $pdo)
    {
        $this->usuarioModel = new Usuario($pdo);
    }

    public function autenticar(string $nombreUsuario, string $password): array|false
    {
        $usuario = $this->usuarioModel->buscarPorNombreUsuario($nombreUsuario);

        if (!$usuario) {
            return false;
        }

        if (!$usuario['estado']) {
            return false;
        }

        if (!password_verify($password, $usuario['password_hash'])) {
            return false;
        }

        $this->usuarioModel->actualizarUltimoAcceso((int) $usuario['id_usuario']);

        unset($usuario['password_hash']);

        return $usuario;
    }
}