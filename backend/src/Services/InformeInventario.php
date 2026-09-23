<?php

require_once __DIR__ . '/../Models/Reporte.php';
require_once __DIR__ . '/FormatoInventario.php';
require_once __DIR__ . '/GeneradorExcel.php';

/**
 * Entregables del trabajo de archivo (términos de referencia de EMAPA):
 * - Punto 8: inventario consolidado en Excel e informe final imprimible.
 * - Punto 7: rótulos imprimibles para carpetas, cajas y archivadores.
 */
class InformeInventario
{
    public const TIPOS_ROTULO = ['carpeta', 'caja', 'archivador'];

    private Reporte $reporteModel;

    public function __construct(PDO $pdo)
    {
        $this->reporteModel = new Reporte($pdo);
    }

    public function generarExcel(): string
    {
        $inventario = $this->reporteModel->obtenerInventario();
        $subtitulo = 'EMAPA - Archivo de Recursos Humanos. Generado el '
            . FormatoInventario::ahora()->format('d/m/Y H:i');

        $excel = new GeneradorExcel();

        $excel->agregarHoja(
            'Resumen',
            'Resumen del inventario de expedientes personales',
            $subtitulo,
            ['Concepto', 'Cantidad'],
            $this->filasResumen(),
            [50, 14]
        );

        $encabezadosInventario = [
            'N°',
            'N° correlativo',
            'Nombre completo',
            'N° de documento',
            'Condición',
            'Cargo',
            'Gestión o periodo laboral',
            'Ubicación física',
            'Estado del expediente',
            'Revisión física',
            'Observaciones'
        ];

        $anchosInventario = [6, 15, 34, 15, 11, 28, 24, 42, 14, 28, 42];

        foreach (['ACTIVO' => 'Activos', 'PASIVO' => 'Pasivos'] as $estadoLaboral => $nombreHoja) {
            $filas = [];

            foreach ($this->filtrarPorEstadoLaboral($inventario, $estadoLaboral) as $indice => $fila) {
                $filas[] = [
                    $indice + 1,
                    (string) $fila['numero_correlativo'],
                    FormatoInventario::nombreCompleto($fila),
                    (string) ($fila['numero_documento'] ?? ''),
                    FormatoInventario::condicion($fila),
                    (string) ($fila['cargo'] ?? ''),
                    FormatoInventario::periodoLaboral($fila),
                    FormatoInventario::ubicacionFisica($fila),
                    FormatoInventario::estadoExpediente($fila),
                    FormatoInventario::revision($fila),
                    (string) ($fila['observaciones'] ?? '')
                ];
            }

            $excel->agregarHoja(
                $nombreHoja,
                'Inventario de archivos ' . mb_strtolower($nombreHoja),
                $subtitulo,
                $encabezadosInventario,
                $filas,
                $anchosInventario
            );
        }

        $filasObservaciones = [];

        foreach ($this->filtrarConObservaciones($inventario) as $indice => $fila) {
            $filasObservaciones[] = [
                $indice + 1,
                (string) $fila['numero_correlativo'],
                FormatoInventario::nombreCompleto($fila),
                FormatoInventario::condicion($fila),
                FormatoInventario::estadoExpediente($fila),
                (string) ($fila['observaciones'] ?? ''),
                FormatoInventario::ubicacionFisica($fila)
            ];
        }

        $excel->agregarHoja(
            'Observaciones',
            'Expedientes incompletos, deteriorados o con observaciones',
            $subtitulo,
            [
                'N°',
                'N° correlativo',
                'Nombre completo',
                'Condición',
                'Estado del expediente',
                'Observaciones',
                'Ubicación física'
            ],
            $filasObservaciones,
            [6, 15, 34, 11, 14, 50, 42]
        );

        return $excel->generar();
    }

    public function generarInformeHtml(): string
    {
        $inventario = $this->reporteModel->obtenerInventario();
        $resumen = $this->reporteModel->obtenerResumenInventario();

        $porcentajeRevision = $resumen['total_expedientes'] > 0
            ? round($resumen['total_revisados'] * 100 / $resumen['total_expedientes'], 1)
            : 0;

        return $this->renderizar('informe_final', [
            'fechaGeneracion' => FormatoInventario::ahora()->format('d/m/Y H:i'),
            'filasResumen' => $this->filasResumen(),
            'resumen' => $resumen,
            'porcentajeRevision' => $porcentajeRevision,
            'activos' => $this->filtrarPorEstadoLaboral($inventario, 'ACTIVO'),
            'pasivos' => $this->filtrarPorEstadoLaboral($inventario, 'PASIVO'),
            'conObservaciones' => $this->filtrarConObservaciones($inventario),
            'prestamosPendientes' => $this->reporteModel->obtenerPrestamosPendientes()
        ]);
    }

    public function generarRotulosHtml(
        string $tipo,
        ?string $estadoLaboral = null,
        ?int $idUbicacion = null
    ): string {
        if (!in_array($tipo, self::TIPOS_ROTULO, true)) {
            throw new InvalidArgumentException('Tipo de rótulo no válido.');
        }

        $inventario = $this->reporteModel->obtenerInventario(
            $estadoLaboral,
            null,
            $idUbicacion
        );

        return $this->renderizar('rotulos', [
            'tipo' => $tipo,
            'fechaGeneracion' => FormatoInventario::ahora()->format('d/m/Y H:i'),
            'expedientes' => $tipo === 'carpeta' ? $inventario : [],
            'grupos' => $tipo === 'carpeta'
                ? []
                : $this->agruparPorUbicacion($inventario, $tipo)
        ]);
    }

    /**
     * @return array<int, array{0: string, 1: int}>
     */
    private function filasResumen(): array
    {
        $resumen = $this->reporteModel->obtenerResumenInventario();
        $resumenPrestamos = $this->reporteModel->obtenerResumenPrestamos();

        return [
            ['Total de expedientes', $resumen['total_expedientes']],
            ['Archivos activos', $resumen['total_activos']],
            ['Archivos pasivos', $resumen['total_pasivos']],
            ['Expedientes completos', $resumen['total_completos']],
            ['Expedientes incompletos', $resumen['total_incompletos']],
            ['Expedientes deteriorados', $resumen['total_deteriorados']],
            ['Expedientes observados', $resumen['total_observados']],
            ['Expedientes sin ubicación asignada', $resumen['total_sin_ubicacion']],
            ['Expedientes con revisión física', $resumen['total_revisados']],
            ['Expedientes pendientes de revisión física', $resumen['total_pendientes_revision']],
            ['Préstamos pendientes de devolución', $resumenPrestamos['total_pendientes']]
        ];
    }

    private function filtrarPorEstadoLaboral(array $inventario, string $estadoLaboral): array
    {
        return array_values(array_filter(
            $inventario,
            fn (array $fila): bool => $fila['estado_laboral'] === $estadoLaboral
        ));
    }

    private function filtrarConObservaciones(array $inventario): array
    {
        return array_values(array_filter(
            $inventario,
            [FormatoInventario::class, 'tieneObservaciones']
        ));
    }

    /**
     * Agrupa los expedientes por caja (ambiente, estante, archivador y caja)
     * o por archivador (ambiente, estante y archivador).
     */
    private function agruparPorUbicacion(array $inventario, string $tipo): array
    {
        $campos = $tipo === 'caja'
            ? ['ambiente', 'estante', 'archivador', 'caja']
            : ['ambiente', 'estante', 'archivador'];

        $grupos = [];

        foreach ($inventario as $fila) {
            if (empty($fila['id_ubicacion'])) {
                continue;
            }

            $ubicacion = [];

            foreach ($campos as $campo) {
                $ubicacion[$campo] = trim((string) ($fila[$campo] ?? ''));
            }

            $clave = implode('|', $ubicacion);

            $grupos[$clave] ??= [
                'ubicacion' => $ubicacion,
                'expedientes' => []
            ];

            $grupos[$clave]['expedientes'][] = $fila;
        }

        $comparar = [FormatoInventario::class, 'compararTexto'];

        uksort($grupos, $comparar);

        foreach ($grupos as &$grupo) {
            $expedientes = $grupo['expedientes'];

            usort(
                $expedientes,
                fn (array $a, array $b): int => FormatoInventario::compararTexto(
                    (string) $a['numero_correlativo'],
                    (string) $b['numero_correlativo']
                )
            );

            $nombres = array_map([FormatoInventario::class, 'nombreCompleto'], $expedientes);
            usort($nombres, $comparar);

            $activos = count(array_filter(
                $expedientes,
                fn (array $fila): bool => $fila['estado_laboral'] === 'ACTIVO'
            ));

            $pasivos = count($expedientes) - $activos;

            $cajas = array_unique(array_map(
                fn (array $fila): string => trim((string) ($fila['caja'] ?? '')) !== ''
                    ? trim((string) $fila['caja'])
                    : 'S/N',
                $expedientes
            ));

            usort($cajas, $comparar);

            $grupo['expedientes'] = $expedientes;
            $grupo['total'] = count($expedientes);
            $grupo['activos'] = $activos;
            $grupo['pasivos'] = $pasivos;
            $grupo['condicion'] = match (true) {
                $activos > 0 && $pasivos > 0 => 'ACTIVOS Y PASIVOS',
                $activos > 0 => 'ACTIVOS',
                default => 'PASIVOS'
            };
            $grupo['correlativo_inicial'] = (string) $expedientes[0]['numero_correlativo'];
            $grupo['correlativo_final'] = (string) end($expedientes)['numero_correlativo'];
            $grupo['nombre_inicial'] = $nombres[0];
            $grupo['nombre_final'] = end($nombres);
            $grupo['cajas'] = $cajas;
        }

        unset($grupo);

        return array_values($grupos);
    }

    private function renderizar(string $vista, array $datos): string
    {
        $e = static fn (mixed $valor): string => htmlspecialchars(
            (string) $valor,
            ENT_QUOTES,
            'UTF-8'
        );

        extract($datos, EXTR_SKIP);

        ob_start();

        try {
            require __DIR__ . '/../Views/' . $vista . '.php';
        } catch (Throwable $error) {
            ob_end_clean();

            throw $error;
        }

        return (string) ob_get_clean();
    }
}
