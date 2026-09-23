<?php

class Usuario
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function buscarPorNombreUsuario(string $nombreUsuario): array|false
    {
        $sql = "
            SELECT
                u.id_usuario,
                u.id_rol,
                u.nombres,
                u.apellidos,
                u.nombre_usuario,
                u.password_hash,
                u.estado,
                u.ultimo_acceso,
                r.nombre AS rol
            FROM usuarios u
            INNER JOIN roles r
                ON r.id_rol = u.id_rol
            WHERE u.nombre_usuario = :nombre_usuario
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':nombre_usuario' => $nombreUsuario
        ]);

        return $stmt->fetch();
    }
    public function actualizarUltimoAcceso(int $idUsuario): void
    {
        $sql = "
        UPDATE usuarios
        SET ultimo_acceso = CURRENT_TIMESTAMP
        WHERE id_usuario = :id_usuario
    ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id_usuario' => $idUsuario
        ]);
    }

    // Las consultas de gestión nunca devuelven password_hash.
    private const CAMPOS = "
        u.id_usuario,
        u.id_rol,
        u.nombres,
        u.apellidos,
        u.nombre_usuario,
        u.estado,
        u.ultimo_acceso,
        u.fecha_creacion,
        u.fecha_actualizacion,
        r.nombre AS rol
    ";

    public function listar(?string $buscar = null, ?int $idRol = null, ?bool $estado = null): array
    {
        $sql = "
            SELECT " . self::CAMPOS . "
            FROM usuarios u
            INNER JOIN roles r
                ON r.id_rol = u.id_rol
            WHERE 1 = 1
        ";

        $parametros = [];

        if ($buscar !== null && $buscar !== '') {
            $sql .= "
                AND (
                    u.nombre_usuario ILIKE :buscar
                    OR u.nombres ILIKE :buscar
                    OR u.apellidos ILIKE :buscar
                )
            ";

            $parametros[':buscar'] = '%' . $buscar . '%';
        }

        if ($idRol !== null) {
            $sql .= " AND u.id_rol = :id_rol";
            $parametros[':id_rol'] = $idRol;
        }

        if ($estado !== null) {
            $sql .= $estado
                ? " AND u.estado IS TRUE"
                : " AND u.estado IS FALSE";
        }

        $sql .= "
            ORDER BY
                u.estado DESC,
                u.nombre_usuario ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    public function buscarPorId(int $idUsuario): array|false
    {
        $sql = "
            SELECT " . self::CAMPOS . "
            FROM usuarios u
            INNER JOIN roles r
                ON r.id_rol = u.id_rol
            WHERE u.id_usuario = :id_usuario
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id_usuario' => $idUsuario
        ]);

        return $stmt->fetch();
    }

    public function crear(array $datos): int
    {
        $sql = "
            INSERT INTO usuarios (
                id_rol,
                nombres,
                apellidos,
                nombre_usuario,
                password_hash
            )
            VALUES (
                :id_rol,
                :nombres,
                :apellidos,
                :nombre_usuario,
                :password_hash
            )
            RETURNING id_usuario
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id_rol' => $datos['id_rol'],
            ':nombres' => $datos['nombres'],
            ':apellidos' => $datos['apellidos'],
            ':nombre_usuario' => $datos['nombre_usuario'],
            ':password_hash' => $datos['password_hash']
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function actualizar(int $idUsuario, array $datos): bool
    {
        $sql = "
            UPDATE usuarios
            SET
                id_rol = :id_rol,
                nombres = :nombres,
                apellidos = :apellidos,
                nombre_usuario = :nombre_usuario,
                fecha_actualizacion = CURRENT_TIMESTAMP
            WHERE id_usuario = :id_usuario
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id_rol' => $datos['id_rol'],
            ':nombres' => $datos['nombres'],
            ':apellidos' => $datos['apellidos'],
            ':nombre_usuario' => $datos['nombre_usuario'],
            ':id_usuario' => $idUsuario
        ]);
    }

    public function cambiarEstado(int $idUsuario, bool $estado): bool
    {
        $sql = "
            UPDATE usuarios
            SET
                estado = :estado,
                fecha_actualizacion = CURRENT_TIMESTAMP
            WHERE id_usuario = :id_usuario
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':estado' => $estado ? 'true' : 'false',
            ':id_usuario' => $idUsuario
        ]);
    }

    public function actualizarPassword(int $idUsuario, string $passwordHash): bool
    {
        $sql = "
            UPDATE usuarios
            SET
                password_hash = :password_hash,
                fecha_actualizacion = CURRENT_TIMESTAMP
            WHERE id_usuario = :id_usuario
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':password_hash' => $passwordHash,
            ':id_usuario' => $idUsuario
        ]);
    }

    public function obtenerPasswordHash(int $idUsuario): string|false
    {
        $sql = "
            SELECT password_hash
            FROM usuarios
            WHERE id_usuario = :id_usuario
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id_usuario' => $idUsuario
        ]);

        return $stmt->fetchColumn();
    }

    /**
     * Administradores activos, sin contar al usuario indicado.
     * Sirve para no dejar el sistema sin ningún administrador.
     */
    public function contarAdministradoresActivos(?int $exceptoIdUsuario = null): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM usuarios u
            INNER JOIN roles r
                ON r.id_rol = u.id_rol
            WHERE u.estado IS TRUE
            AND r.nombre = 'Administrador'
        ";

        $parametros = [];

        if ($exceptoIdUsuario !== null) {
            $sql .= " AND u.id_usuario <> :id_usuario";
            $parametros[':id_usuario'] = $exceptoIdUsuario;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametros);

        return (int) $stmt->fetchColumn();
    }
}
