<?php

class Rol
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listar(): array
    {
        $sql = "
            SELECT
                id_rol,
                nombre,
                descripcion,
                estado
            FROM roles
            WHERE estado IS TRUE
            ORDER BY id_rol ASC
        ";

        return $this->pdo->query($sql)->fetchAll();
    }

    public function buscarPorId(int $idRol): array|false
    {
        $sql = "
            SELECT
                id_rol,
                nombre,
                descripcion,
                estado
            FROM roles
            WHERE id_rol = :id_rol
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id_rol' => $idRol
        ]);

        return $stmt->fetch();
    }
}
