<?php

class Ubicacion
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listar(?string $buscar = null, ?bool $estado = null): array
    {
        $sql = "
        SELECT
            id_ubicacion,
            ambiente,
            estante,
            archivador,
            caja,
            carpeta,
            descripcion,
            estado,
            fecha_creacion
        FROM ubicaciones
        WHERE 1 = 1
    ";

        $parametros = [];

        if ($buscar !== null && $buscar !== '') {
            $sql .= "
            AND (
                ambiente ILIKE :buscar
                OR estante ILIKE :buscar
                OR archivador ILIKE :buscar
                OR caja ILIKE :buscar
                OR carpeta ILIKE :buscar
                OR descripcion ILIKE :buscar
            )
        ";

            $parametros[':buscar'] = '%' . $buscar . '%';
        }

        if ($estado !== null) {
            $sql .= $estado
                ? " AND estado IS TRUE"
                : " AND estado IS FALSE";
        }

        $sql .= "
        ORDER BY
            ambiente ASC,
            estante ASC,
            archivador ASC,
            caja ASC,
            carpeta ASC
    ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    public function buscarPorId(int $idUbicacion): array|false
    {
        $sql = "
            SELECT
                id_ubicacion,
                ambiente,
                estante,
                archivador,
                caja,
                carpeta,
                descripcion,
                estado,
                fecha_creacion
            FROM ubicaciones
            WHERE id_ubicacion = :id_ubicacion
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id_ubicacion' => $idUbicacion
        ]);

        return $stmt->fetch();
    }

    public function crear(array $datos): int
    {
        $sql = "
            INSERT INTO ubicaciones (
                ambiente,
                estante,
                archivador,
                caja,
                carpeta,
                descripcion
            )
            VALUES (
                :ambiente,
                :estante,
                :archivador,
                :caja,
                :carpeta,
                :descripcion
            )
            RETURNING id_ubicacion
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':ambiente' => $datos['ambiente'],
            ':estante' => $datos['estante'],
            ':archivador' => $datos['archivador'],
            ':caja' => $datos['caja'],
            ':carpeta' => $datos['carpeta'],
            ':descripcion' => $datos['descripcion']
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function actualizar(int $idUbicacion, array $datos): bool
    {
        $sql = "
        UPDATE ubicaciones
        SET
            ambiente = :ambiente,
            estante = :estante,
            archivador = :archivador,
            caja = :caja,
            carpeta = :carpeta,
            descripcion = :descripcion
        WHERE id_ubicacion = :id_ubicacion
    ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':ambiente' => $datos['ambiente'],
            ':estante' => $datos['estante'],
            ':archivador' => $datos['archivador'],
            ':caja' => $datos['caja'],
            ':carpeta' => $datos['carpeta'],
            ':descripcion' => $datos['descripcion'],
            ':id_ubicacion' => $idUbicacion
        ]);
    }

    public function cambiarEstado(int $idUbicacion, bool $estado): bool
    {
        $sql = "
        UPDATE ubicaciones
        SET estado = :estado
        WHERE id_ubicacion = :id_ubicacion
    ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->bindValue(
            ':estado',
            $estado,
            PDO::PARAM_BOOL
        );

        $stmt->bindValue(
            ':id_ubicacion',
            $idUbicacion,
            PDO::PARAM_INT
        );

        return $stmt->execute();
    }
}
