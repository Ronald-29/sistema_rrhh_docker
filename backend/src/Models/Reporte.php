<?php

class Reporte
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function obtenerInventario(
        ?string $estadoLaboral = null,
        ?string $estadoExpediente = null,
        ?int $idUbicacion = null
    ): array {
        $sql = "
            SELECT
                e.id_expediente,
                e.numero_correlativo,
                p.id_personal,
                p.nombres,
                p.apellido_paterno,
                p.apellido_materno,
                p.numero_documento,
                p.cargo,
                p.fecha_ingreso,
                p.fecha_retiro,
                p.estado_laboral,
                e.estado_expediente,
                e.observaciones,
                e.revisado,
                e.revisado_por,
                e.fecha_revision,
                u.id_ubicacion,
                u.ambiente,
                u.estante,
                u.archivador,
                u.caja,
                u.carpeta,
                u.descripcion AS descripcion_ubicacion,
                e.fecha_registro,
                e.fecha_actualizacion
            FROM expedientes e
            INNER JOIN personal p
                ON p.id_personal = e.id_personal
            LEFT JOIN ubicaciones u
                ON u.id_ubicacion = e.id_ubicacion
            WHERE 1 = 1
        ";

        $parametros = [];

        if ($estadoLaboral !== null && $estadoLaboral !== '') {
            $sql .= " AND p.estado_laboral = :estado_laboral";
            $parametros[':estado_laboral'] = $estadoLaboral;
        }

        if ($estadoExpediente !== null && $estadoExpediente !== '') {
            $sql .= " AND e.estado_expediente = :estado_expediente";
            $parametros[':estado_expediente'] = $estadoExpediente;
        }

        if ($idUbicacion !== null) {
            $sql .= " AND e.id_ubicacion = :id_ubicacion";
            $parametros[':id_ubicacion'] = $idUbicacion;
        }

        $sql .= "
            ORDER BY
                p.estado_laboral ASC,
                p.apellido_paterno ASC NULLS LAST,
                p.apellido_materno ASC NULLS LAST,
                p.nombres ASC,
                e.numero_correlativo ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    public function obtenerResumenInventario(): array
    {
        $sql = "
            SELECT
                COUNT(*) AS total_expedientes,

                COUNT(*) FILTER (
                    WHERE p.estado_laboral = 'ACTIVO'
                ) AS total_activos,

                COUNT(*) FILTER (
                    WHERE p.estado_laboral = 'PASIVO'
                ) AS total_pasivos,

                COUNT(*) FILTER (
                    WHERE e.estado_expediente = 'COMPLETO'
                ) AS total_completos,

                COUNT(*) FILTER (
                    WHERE e.estado_expediente = 'INCOMPLETO'
                ) AS total_incompletos,

                COUNT(*) FILTER (
                    WHERE e.estado_expediente = 'DETERIORADO'
                ) AS total_deteriorados,

                COUNT(*) FILTER (
                    WHERE e.estado_expediente = 'OBSERVADO'
                ) AS total_observados,

                COUNT(*) FILTER (
                    WHERE e.id_ubicacion IS NULL
                ) AS total_sin_ubicacion,

                COUNT(*) FILTER (
                    WHERE e.revisado = TRUE
                ) AS total_revisados,

                COUNT(*) FILTER (
                    WHERE e.revisado = FALSE
                ) AS total_pendientes_revision

            FROM expedientes e
            INNER JOIN personal p
                ON p.id_personal = e.id_personal
        ";

        $stmt = $this->pdo->query($sql);
        $resultado = $stmt->fetch();

        return [
            'total_expedientes' => (int) $resultado['total_expedientes'],
            'total_activos' => (int) $resultado['total_activos'],
            'total_pasivos' => (int) $resultado['total_pasivos'],
            'total_completos' => (int) $resultado['total_completos'],
            'total_incompletos' => (int) $resultado['total_incompletos'],
            'total_deteriorados' => (int) $resultado['total_deteriorados'],
            'total_observados' => (int) $resultado['total_observados'],
            'total_sin_ubicacion' => (int) $resultado['total_sin_ubicacion'],
            'total_revisados' => (int) $resultado['total_revisados'],
            'total_pendientes_revision' => (int) $resultado['total_pendientes_revision']
        ];
    }

    public function obtenerPrestamosPendientes(): array
    {
        $sql = "
            SELECT
                pr.id_prestamo,
                CASE
                    WHEN pr.id_expediente IS NOT NULL
                        THEN 'EXPEDIENTE'
                    WHEN pr.id_documento IS NOT NULL
                        THEN 'DOCUMENTO'
                END AS tipo_prestamo,
                pr.id_expediente,
                pr.id_documento,
                COALESCE(
                    e_directo.id_expediente,
                    e_documento.id_expediente
                ) AS expediente_relacionado,
                COALESCE(
                    e_directo.numero_correlativo,
                    e_documento.numero_correlativo
                ) AS numero_correlativo,
                d.nombre_documento,
                d.tipo_documento,
                d.numero_documento AS numero_documento_archivo,
                p.id_personal,
                p.nombres,
                p.apellido_paterno,
                p.apellido_materno,
                p.numero_documento AS documento_personal,
                p.cargo,
                p.estado_laboral,
                pr.entregado_por,
                pr.recibido_por,
                pr.fecha_hora_entrega,
                pr.motivo,
                pr.observaciones_entrega,
                pr.estado,
                pr.id_usuario_registro,
                u.nombre_usuario AS usuario_registro
            FROM prestamos pr
            LEFT JOIN expedientes e_directo
                ON e_directo.id_expediente = pr.id_expediente
            LEFT JOIN documentos d
                ON d.id_documento = pr.id_documento
            LEFT JOIN expedientes e_documento
                ON e_documento.id_expediente = d.id_expediente
            INNER JOIN personal p
                ON p.id_personal = COALESCE(
                    e_directo.id_personal,
                    e_documento.id_personal
                )
            LEFT JOIN usuarios u
                ON u.id_usuario = pr.id_usuario_registro
            WHERE pr.estado = 'PRESTADO'
            ORDER BY
                pr.fecha_hora_entrega ASC,
                pr.id_prestamo ASC
        ";

        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll();
    }

    public function obtenerResumenPrestamos(): array
    {
        $sql = "
            SELECT
                COUNT(*) FILTER (
                    WHERE estado = 'PRESTADO'
                ) AS total_pendientes,

                COUNT(*) FILTER (
                    WHERE estado = 'PRESTADO'
                    AND id_expediente IS NOT NULL
                ) AS expedientes_prestados,

                COUNT(*) FILTER (
                    WHERE estado = 'PRESTADO'
                    AND id_documento IS NOT NULL
                ) AS documentos_prestados,

                COUNT(*) FILTER (
                    WHERE estado = 'DEVUELTO'
                ) AS total_devueltos,

                COUNT(*) AS total_movimientos
            FROM prestamos
        ";

        $stmt = $this->pdo->query($sql);
        $resultado = $stmt->fetch();

        return [
            'total_pendientes' => (int) $resultado['total_pendientes'],
            'expedientes_prestados' => (int) $resultado['expedientes_prestados'],
            'documentos_prestados' => (int) $resultado['documentos_prestados'],
            'total_devueltos' => (int) $resultado['total_devueltos'],
            'total_movimientos' => (int) $resultado['total_movimientos']
        ];
    }
}