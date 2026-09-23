<?php

class Prestamo
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function crear(array $datos): int
    {
        $sql = "
            INSERT INTO prestamos (
                id_expediente,
                id_documento,
                entregado_por,
                recibido_por,
                motivo,
                observaciones_entrega,
                estado,
                id_usuario_registro
            )
            VALUES (
                :id_expediente,
                :id_documento,
                :entregado_por,
                :recibido_por,
                :motivo,
                :observaciones_entrega,
                'PRESTADO',
                :id_usuario_registro
            )
            RETURNING id_prestamo
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id_expediente' => $datos['id_expediente'],
            ':id_documento' => $datos['id_documento'],
            ':entregado_por' => $datos['entregado_por'],
            ':recibido_por' => $datos['recibido_por'],
            ':motivo' => $datos['motivo'],
            ':observaciones_entrega' => $datos['observaciones_entrega'],
            ':id_usuario_registro' => $datos['id_usuario_registro']
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function buscarPorId(int $idPrestamo): array|false
    {
        $sql = "
            SELECT
                pr.id_prestamo,
                pr.id_expediente,
                pr.id_documento,
                pr.entregado_por,
                pr.recibido_por,
                pr.fecha_hora_entrega,
                pr.motivo,
                pr.observaciones_entrega,
                pr.devuelto_por,
                pr.recibido_devolucion_por,
                pr.fecha_hora_devolucion,
                pr.observaciones_devolucion,
                pr.estado,
                pr.id_usuario_registro,
                u.nombre_usuario AS usuario_registro
            FROM prestamos pr
            INNER JOIN usuarios u
                ON u.id_usuario = pr.id_usuario_registro
            WHERE pr.id_prestamo = :id_prestamo
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id_prestamo' => $idPrestamo
        ]);

        return $stmt->fetch();
    }

    public function listar(?string $estado = null): array
    {
        $sql = "
            SELECT
                pr.id_prestamo,
                pr.id_expediente,
                pr.id_documento,
                pr.entregado_por,
                pr.recibido_por,
                pr.fecha_hora_entrega,
                pr.motivo,
                pr.devuelto_por,
                pr.recibido_devolucion_por,
                pr.fecha_hora_devolucion,
                pr.estado,
                pr.id_usuario_registro,
                u.nombre_usuario AS usuario_registro,
                e.numero_correlativo,
                d.nombre_documento,
                d.numero_documento
            FROM prestamos pr
            INNER JOIN usuarios u
                ON u.id_usuario = pr.id_usuario_registro
            LEFT JOIN expedientes e
                ON e.id_expediente = pr.id_expediente
            LEFT JOIN documentos d
                ON d.id_documento = pr.id_documento
            WHERE 1 = 1
        ";

        $parametros = [];

        if ($estado !== null && $estado !== '') {
            $sql .= " AND pr.estado = :estado";
            $parametros[':estado'] = $estado;
        }

        $sql .= " ORDER BY pr.fecha_hora_entrega DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    public function expedienteTienePrestamoActivo(int $idExpediente): bool
    {
        $sql = "
            SELECT EXISTS (
                SELECT 1
                FROM prestamos
                WHERE id_expediente = :id_expediente
                AND estado = 'PRESTADO'
            )
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id_expediente' => $idExpediente
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public function expedienteTieneDocumentoPrestado(int $idExpediente): bool
    {
        $sql = "
            SELECT EXISTS (
                SELECT 1
                FROM prestamos pr
                INNER JOIN documentos d
                    ON d.id_documento = pr.id_documento
                WHERE d.id_expediente = :id_expediente
                AND pr.estado = 'PRESTADO'
            )
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id_expediente' => $idExpediente
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public function documentoTieneExpedientePrestado(int $idDocumento): bool
    {
        $sql = "
            SELECT EXISTS (
                SELECT 1
                FROM documentos d
                INNER JOIN prestamos pr
                    ON pr.id_expediente = d.id_expediente
                WHERE d.id_documento = :id_documento
                AND pr.estado = 'PRESTADO'
            )
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id_documento' => $idDocumento
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public function devolver(int $idPrestamo, array $datos): bool
    {
        $sql = "
            UPDATE prestamos
            SET
                devuelto_por = :devuelto_por,
                recibido_devolucion_por = :recibido_devolucion_por,
                fecha_hora_devolucion = CURRENT_TIMESTAMP,
                observaciones_devolucion = :observaciones_devolucion,
                estado = 'DEVUELTO'
            WHERE id_prestamo = :id_prestamo
            AND estado = 'PRESTADO'
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':devuelto_por' => $datos['devuelto_por'],
            ':recibido_devolucion_por' => $datos['recibido_devolucion_por'],
            ':observaciones_devolucion' => $datos['observaciones_devolucion'],
            ':id_prestamo' => $idPrestamo
        ]);

        return $stmt->rowCount() > 0;
    }
}