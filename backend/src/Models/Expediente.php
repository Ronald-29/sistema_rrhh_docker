<?php

class Expediente
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listar(
        ?string $buscar = null,
        ?string $estadoExpediente = null,
        ?string $estadoLaboral = null,
        ?bool $revisado = null
    ): array {
        $sql = "
        SELECT
            e.id_expediente,
            e.numero_correlativo,
            e.id_personal,
            e.id_ubicacion,
            e.estado_expediente,
            e.observaciones,
            e.revisado,
            e.revisado_por,
            e.fecha_revision,
            e.fecha_registro,
            e.fecha_actualizacion,
            p.nombres,
            p.apellido_paterno,
            p.apellido_materno,
            p.numero_documento,
            p.cargo,
            p.estado_laboral,
            u.ambiente,
            u.estante,
            u.archivador,
            u.caja,
            u.carpeta
        FROM expedientes e
        INNER JOIN personal p
            ON p.id_personal = e.id_personal
        LEFT JOIN ubicaciones u
            ON u.id_ubicacion = e.id_ubicacion
        WHERE 1 = 1
    ";

        $parametros = [];

        if ($buscar !== null && $buscar !== '') {
            $sql .= "
            AND (
                e.numero_correlativo ILIKE :buscar
                OR p.nombres ILIKE :buscar
                OR p.apellido_paterno ILIKE :buscar
                OR p.apellido_materno ILIKE :buscar
                OR p.numero_documento ILIKE :buscar
                OR p.cargo ILIKE :buscar
                OR u.ambiente ILIKE :buscar
                OR u.estante ILIKE :buscar
                OR u.archivador ILIKE :buscar
                OR u.caja ILIKE :buscar
                OR u.carpeta ILIKE :buscar
            )
        ";

            $parametros[':buscar'] = '%' . $buscar . '%';
        }

        if ($estadoExpediente !== null && $estadoExpediente !== '') {
            $sql .= " AND e.estado_expediente = :estado_expediente";
            $parametros[':estado_expediente'] = $estadoExpediente;
        }

        if ($estadoLaboral !== null && $estadoLaboral !== '') {
            $sql .= " AND p.estado_laboral = :estado_laboral";
            $parametros[':estado_laboral'] = $estadoLaboral;
        }

        if ($revisado !== null) {
            $sql .= $revisado
                ? " AND e.revisado = TRUE"
                : " AND e.revisado = FALSE";
        }

        $sql .= "
        ORDER BY
            p.apellido_paterno ASC,
            p.apellido_materno ASC,
            p.nombres ASC,
            e.numero_correlativo ASC
    ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    public function buscarPorId(int $idExpediente): array|false
    {
        $sql = "
            SELECT
                e.id_expediente,
                e.numero_correlativo,
                e.id_personal,
                e.id_ubicacion,
                e.estado_expediente,
                e.observaciones,
                e.revisado,
                e.revisado_por,
                e.fecha_revision,
                e.id_usuario_revision,
                ur.nombre_usuario AS usuario_revision,
                e.fecha_registro,
                e.fecha_actualizacion,
                p.nombres,
                p.apellido_paterno,
                p.apellido_materno,
                p.numero_documento,
                p.cargo,
                p.estado_laboral,
                u.ambiente,
                u.estante,
                u.archivador,
                u.caja,
                u.carpeta
            FROM expedientes e
            INNER JOIN personal p
                ON p.id_personal = e.id_personal
            LEFT JOIN ubicaciones u
                ON u.id_ubicacion = e.id_ubicacion
            LEFT JOIN usuarios ur
                ON ur.id_usuario = e.id_usuario_revision
            WHERE e.id_expediente = :id_expediente
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id_expediente' => $idExpediente
        ]);

        return $stmt->fetch();
    }

    public function crear(array $datos): int
    {
        $sql = "
            INSERT INTO expedientes (
                numero_correlativo,
                id_personal,
                id_ubicacion,
                estado_expediente,
                observaciones
            )
            VALUES (
                :numero_correlativo,
                :id_personal,
                :id_ubicacion,
                :estado_expediente,
                :observaciones
            )
            RETURNING id_expediente
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':numero_correlativo' => $datos['numero_correlativo'],
            ':id_personal' => $datos['id_personal'],
            ':id_ubicacion' => $datos['id_ubicacion'],
            ':estado_expediente' => $datos['estado_expediente'],
            ':observaciones' => $datos['observaciones']
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function actualizar(int $idExpediente, array $datos): bool
    {
        $sql = "
        UPDATE expedientes
        SET
            numero_correlativo = :numero_correlativo,
            id_personal = :id_personal,
            id_ubicacion = :id_ubicacion,
            estado_expediente = :estado_expediente,
            observaciones = :observaciones,
            fecha_actualizacion = CURRENT_TIMESTAMP
        WHERE id_expediente = :id_expediente
    ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':numero_correlativo' => $datos['numero_correlativo'],
            ':id_personal' => $datos['id_personal'],
            ':id_ubicacion' => $datos['id_ubicacion'],
            ':estado_expediente' => $datos['estado_expediente'],
            ':observaciones' => $datos['observaciones'],
            ':id_expediente' => $idExpediente
        ]);
    }

    public function registrarRevision(int $idExpediente, array $datos): bool
    {
        $sql = "
        UPDATE expedientes
        SET
            revisado = TRUE,
            revisado_por = :revisado_por,
            fecha_revision = CURRENT_TIMESTAMP,
            id_usuario_revision = :id_usuario_revision,
            estado_expediente = :estado_expediente,
            observaciones = :observaciones,
            fecha_actualizacion = CURRENT_TIMESTAMP
        WHERE id_expediente = :id_expediente
    ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':revisado_por' => $datos['revisado_por'],
            ':id_usuario_revision' => $datos['id_usuario_revision'],
            ':estado_expediente' => $datos['estado_expediente'],
            ':observaciones' => $datos['observaciones'],
            ':id_expediente' => $idExpediente
        ]);
    }

    public function anularRevision(int $idExpediente): bool
    {
        $sql = "
        UPDATE expedientes
        SET
            revisado = FALSE,
            revisado_por = NULL,
            fecha_revision = NULL,
            id_usuario_revision = NULL,
            fecha_actualizacion = CURRENT_TIMESTAMP
        WHERE id_expediente = :id_expediente
    ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id_expediente' => $idExpediente
        ]);
    }
}
