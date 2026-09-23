<?php

class Personal
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listar(?string $buscar = null, ?string $estado = null): array
    {
        $sql = "
        SELECT
            id_personal,
            nombres,
            apellido_paterno,
            apellido_materno,
            numero_documento,
            cargo,
            fecha_ingreso,
            fecha_retiro,
            estado_laboral,
            fecha_creacion,
            fecha_actualizacion
        FROM personal
        WHERE 1 = 1
    ";

        $parametros = [];

        if ($buscar !== null && $buscar !== '') {
            $sql .= "
            AND (
                nombres ILIKE :buscar
                OR apellido_paterno ILIKE :buscar
                OR apellido_materno ILIKE :buscar
                OR numero_documento ILIKE :buscar
                OR cargo ILIKE :buscar
            )
        ";

            $parametros[':buscar'] = '%' . $buscar . '%';
        }

        if ($estado !== null && $estado !== '') {
            $sql .= " AND estado_laboral = :estado";
            $parametros[':estado'] = $estado;
        }

        $sql .= "
        ORDER BY
            apellido_paterno ASC,
            apellido_materno ASC,
            nombres ASC
    ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    public function crear(array $datos): int
    {
        $sql = "
        INSERT INTO personal (
            nombres,
            apellido_paterno,
            apellido_materno,
            numero_documento,
            cargo,
            fecha_ingreso,
            fecha_retiro,
            estado_laboral
        )
        VALUES (
            :nombres,
            :apellido_paterno,
            :apellido_materno,
            :numero_documento,
            :cargo,
            :fecha_ingreso,
            :fecha_retiro,
            :estado_laboral
        )
        RETURNING id_personal
    ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':nombres' => $datos['nombres'],
            ':apellido_paterno' => $datos['apellido_paterno'],
            ':apellido_materno' => $datos['apellido_materno'],
            ':numero_documento' => $datos['numero_documento'],
            ':cargo' => $datos['cargo'],
            ':fecha_ingreso' => $datos['fecha_ingreso'],
            ':fecha_retiro' => $datos['fecha_retiro'],
            ':estado_laboral' => $datos['estado_laboral']
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function buscarPorId(int $idPersonal): array|false
    {
        $sql = "
        SELECT
            id_personal,
            nombres,
            apellido_paterno,
            apellido_materno,
            numero_documento,
            cargo,
            fecha_ingreso,
            fecha_retiro,
            estado_laboral,
            fecha_creacion,
            fecha_actualizacion
        FROM personal
        WHERE id_personal = :id_personal
        LIMIT 1
    ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id_personal' => $idPersonal
        ]);

        return $stmt->fetch();
    }

    public function actualizar(int $idPersonal, array $datos): bool
    {
        $sql = "
        UPDATE personal
        SET
            nombres = :nombres,
            apellido_paterno = :apellido_paterno,
            apellido_materno = :apellido_materno,
            numero_documento = :numero_documento,
            cargo = :cargo,
            fecha_ingreso = :fecha_ingreso,
            fecha_retiro = :fecha_retiro,
            estado_laboral = :estado_laboral,
            fecha_actualizacion = CURRENT_TIMESTAMP
        WHERE id_personal = :id_personal
    ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':nombres' => $datos['nombres'],
            ':apellido_paterno' => $datos['apellido_paterno'],
            ':apellido_materno' => $datos['apellido_materno'],
            ':numero_documento' => $datos['numero_documento'],
            ':cargo' => $datos['cargo'],
            ':fecha_ingreso' => $datos['fecha_ingreso'],
            ':fecha_retiro' => $datos['fecha_retiro'],
            ':estado_laboral' => $datos['estado_laboral'],
            ':id_personal' => $idPersonal
        ]);
    }
}
