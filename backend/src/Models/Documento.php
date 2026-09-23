<?php

class Documento
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listarPorExpediente(int $idExpediente): array
    {
        $sql = "
            SELECT
                d.id_documento,
                d.id_expediente,
                d.nombre_documento,
                d.tipo_documento,
                d.numero_documento,
                d.fecha_documento,
                d.estado_documento,
                d.observaciones,
                d.fecha_registro,
                d.fecha_actualizacion
            FROM documentos d
            WHERE d.id_expediente = :id_expediente
            ORDER BY
                d.fecha_documento DESC NULLS LAST,
                d.nombre_documento ASC
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id_expediente' => $idExpediente
        ]);

        return $stmt->fetchAll();
    }

    public function buscarPorId(int $idDocumento): array|false
    {
        $sql = "
            SELECT
                d.id_documento,
                d.id_expediente,
                d.nombre_documento,
                d.tipo_documento,
                d.numero_documento,
                d.fecha_documento,
                d.estado_documento,
                d.observaciones,
                d.fecha_registro,
                d.fecha_actualizacion,
                e.numero_correlativo,
                p.nombres,
                p.apellido_paterno,
                p.apellido_materno
            FROM documentos d
            INNER JOIN expedientes e
                ON e.id_expediente = d.id_expediente
            INNER JOIN personal p
                ON p.id_personal = e.id_personal
            WHERE d.id_documento = :id_documento
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id_documento' => $idDocumento
        ]);

        return $stmt->fetch();
    }

    public function crear(array $datos): int
    {
        $sql = "
            INSERT INTO documentos (
                id_expediente,
                nombre_documento,
                tipo_documento,
                numero_documento,
                fecha_documento,
                estado_documento,
                observaciones
            )
            VALUES (
                :id_expediente,
                :nombre_documento,
                :tipo_documento,
                :numero_documento,
                :fecha_documento,
                :estado_documento,
                :observaciones
            )
            RETURNING id_documento
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id_expediente' => $datos['id_expediente'],
            ':nombre_documento' => $datos['nombre_documento'],
            ':tipo_documento' => $datos['tipo_documento'],
            ':numero_documento' => $datos['numero_documento'],
            ':fecha_documento' => $datos['fecha_documento'],
            ':estado_documento' => $datos['estado_documento'],
            ':observaciones' => $datos['observaciones']
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function actualizar(int $idDocumento, array $datos): bool
    {
        $sql = "
        UPDATE documentos
        SET
            id_expediente = :id_expediente,
            nombre_documento = :nombre_documento,
            tipo_documento = :tipo_documento,
            numero_documento = :numero_documento,
            fecha_documento = :fecha_documento,
            estado_documento = :estado_documento,
            observaciones = :observaciones,
            fecha_actualizacion = CURRENT_TIMESTAMP
        WHERE id_documento = :id_documento
    ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id_expediente' => $datos['id_expediente'],
            ':nombre_documento' => $datos['nombre_documento'],
            ':tipo_documento' => $datos['tipo_documento'],
            ':numero_documento' => $datos['numero_documento'],
            ':fecha_documento' => $datos['fecha_documento'],
            ':estado_documento' => $datos['estado_documento'],
            ':observaciones' => $datos['observaciones'],
            ':id_documento' => $idDocumento
        ]);
    }
}
