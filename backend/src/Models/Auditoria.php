<?php

class Auditoria
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function registrar(array $datos): int
    {
        $sql = "
            INSERT INTO auditoria (
                id_usuario,
                accion,
                tabla_afectada,
                id_registro,
                descripcion,
                datos_anteriores,
                datos_nuevos
            )
            VALUES (
                :id_usuario,
                :accion,
                :tabla_afectada,
                :id_registro,
                :descripcion,
                CAST(:datos_anteriores AS JSONB),
                CAST(:datos_nuevos AS JSONB)
            )
            RETURNING id_auditoria
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id_usuario' => $datos['id_usuario'] ?? null,
            ':accion' => $datos['accion'],
            ':tabla_afectada' => $datos['tabla_afectada'],
            ':id_registro' => $datos['id_registro'] ?? null,
            ':descripcion' => $datos['descripcion'] ?? null,
            ':datos_anteriores' => $this->convertirJson($datos['datos_anteriores'] ?? null),
            ':datos_nuevos' => $this->convertirJson($datos['datos_nuevos'] ?? null)
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function listar(
        ?string $accion = null,
        ?string $tablaAfectada = null,
        ?int $idUsuario = null
    ): array {
        $sql = "
            SELECT
                a.id_auditoria,
                a.id_usuario,
                a.accion,
                a.tabla_afectada,
                a.id_registro,
                a.descripcion,
                a.datos_anteriores,
                a.datos_nuevos,
                a.fecha_hora,
                u.nombre_usuario,
                u.nombres,
                u.apellidos
            FROM auditoria a
            LEFT JOIN usuarios u
                ON u.id_usuario = a.id_usuario
            WHERE 1 = 1
        ";

        $parametros = [];

        if ($accion !== null && $accion !== '') {
            $sql .= " AND a.accion = :accion";
            $parametros[':accion'] = $accion;
        }

        if ($tablaAfectada !== null && $tablaAfectada !== '') {
            $sql .= " AND a.tabla_afectada = :tabla_afectada";
            $parametros[':tabla_afectada'] = $tablaAfectada;
        }

        if ($idUsuario !== null) {
            $sql .= " AND a.id_usuario = :id_usuario";
            $parametros[':id_usuario'] = $idUsuario;
        }

        $sql .= " ORDER BY a.fecha_hora DESC, a.id_auditoria DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    public function buscarPorId(int $idAuditoria): array|false
    {
        $sql = "
            SELECT
                a.id_auditoria,
                a.id_usuario,
                a.accion,
                a.tabla_afectada,
                a.id_registro,
                a.descripcion,
                a.datos_anteriores,
                a.datos_nuevos,
                a.fecha_hora,
                u.nombre_usuario,
                u.nombres,
                u.apellidos
            FROM auditoria a
            LEFT JOIN usuarios u
                ON u.id_usuario = a.id_usuario
            WHERE a.id_auditoria = :id_auditoria
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id_auditoria' => $idAuditoria
        ]);

        return $stmt->fetch();
    }

    private function convertirJson(mixed $datos): ?string
    {
        if ($datos === null) {
            return null;
        }

        return json_encode(
            $datos,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
}