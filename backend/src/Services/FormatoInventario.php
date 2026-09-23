<?php

/**
 * Formatos compartidos por el Excel, el informe final y los rótulos,
 * para que los tres muestren los mismos datos de la misma forma.
 */
class FormatoInventario
{
    public const ZONA_HORARIA = 'America/La_Paz';

    public static function nombreCompleto(array $fila): string
    {
        $partes = array_filter([
            trim((string) ($fila['apellido_paterno'] ?? '')),
            trim((string) ($fila['apellido_materno'] ?? '')),
            trim((string) ($fila['nombres'] ?? ''))
        ], fn (string $parte): bool => $parte !== '');

        return implode(' ', $partes);
    }

    public static function fecha(?string $valor): string
    {
        if ($valor === null || $valor === '') {
            return '';
        }

        $fecha = date_create($valor);

        return $fecha ? $fecha->format('d/m/Y') : '';
    }

    public static function fechaHora(?string $valor): string
    {
        if ($valor === null || $valor === '') {
            return '';
        }

        $fecha = date_create($valor);

        return $fecha ? $fecha->format('d/m/Y H:i') : '';
    }

    public static function ahora(): DateTimeImmutable
    {
        return new DateTimeImmutable(
            'now',
            new DateTimeZone(self::ZONA_HORARIA)
        );
    }

    /**
     * Gestión o periodo laboral "cuando corresponda" (términos de referencia, punto 5).
     */
    public static function periodoLaboral(array $fila): string
    {
        $ingreso = self::fecha($fila['fecha_ingreso'] ?? null);
        $retiro = self::fecha($fila['fecha_retiro'] ?? null);

        if ($ingreso !== '' && $retiro !== '') {
            return $ingreso . ' al ' . $retiro;
        }

        if ($ingreso !== '') {
            return 'Desde ' . $ingreso;
        }

        if ($retiro !== '') {
            return 'Hasta ' . $retiro;
        }

        return '';
    }

    public static function ubicacionFisica(array $fila): string
    {
        if (empty($fila['id_ubicacion'])) {
            return 'Sin ubicación asignada';
        }

        $partes = [];

        $etiquetas = [
            'ambiente' => '',
            'estante' => 'Estante ',
            'archivador' => 'Archivador ',
            'caja' => 'Caja ',
            'carpeta' => 'Carpeta '
        ];

        foreach ($etiquetas as $campo => $etiqueta) {
            $valor = trim((string) ($fila[$campo] ?? ''));

            if ($valor !== '') {
                $partes[] = $etiqueta . $valor;
            }
        }

        return $partes !== []
            ? implode(' / ', $partes)
            : 'Sin ubicación asignada';
    }

    public static function condicion(array $fila): string
    {
        return ($fila['estado_laboral'] ?? '') === 'ACTIVO'
            ? 'Activo'
            : 'Pasivo';
    }

    public static function estadoExpediente(array $fila): string
    {
        return match ($fila['estado_expediente'] ?? '') {
            'COMPLETO' => 'Completo',
            'INCOMPLETO' => 'Incompleto',
            'DETERIORADO' => 'Deteriorado',
            'OBSERVADO' => 'Observado',
            default => (string) ($fila['estado_expediente'] ?? '')
        };
    }

    public static function revision(array $fila): string
    {
        if (empty($fila['revisado'])) {
            return 'Pendiente';
        }

        return trim(
            'Revisado ' . self::fecha($fila['fecha_revision'] ?? null)
            . ' por ' . ($fila['revisado_por'] ?? '')
        );
    }

    /**
     * Expedientes incompletos, deteriorados o con observaciones (punto 6).
     */
    public static function tieneObservaciones(array $fila): bool
    {
        return ($fila['estado_expediente'] ?? 'COMPLETO') !== 'COMPLETO'
            || trim((string) ($fila['observaciones'] ?? '')) !== '';
    }

    /**
     * Orden natural ("EXP-2" antes que "EXP-10") que ignora mayúsculas y
     * acentos, para que "Álvarez" quede antes que "Choque".
     */
    public static function compararTexto(string $a, string $b): int
    {
        return strnatcasecmp(self::sinAcentos($a), self::sinAcentos($b));
    }

    private static function sinAcentos(string $texto): string
    {
        return strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n~',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N~'
        ]);
    }
}
