<?php

require_once __DIR__ . '/../Models/Reporte.php';
require_once __DIR__ . '/../Services/InformeInventario.php';

class ReporteController
{
    private Reporte $reporteModel;
    private InformeInventario $informeInventario;

    public function __construct(PDO $pdo)
    {
        $this->reporteModel = new Reporte($pdo);
        $this->informeInventario = new InformeInventario($pdo);
    }

    public function inventario(): void
    {
        $estadoLaboral = isset($_GET['estado_laboral'])
            ? strtoupper(trim((string) $_GET['estado_laboral']))
            : null;

        $estadoExpediente = isset($_GET['estado_expediente'])
            ? strtoupper(trim((string) $_GET['estado_expediente']))
            : null;

        if ($estadoLaboral === '') {
            $estadoLaboral = null;
        }

        if ($estadoExpediente === '') {
            $estadoExpediente = null;
        }

        $estadosLaboralesPermitidos = [
            'ACTIVO',
            'PASIVO'
        ];

        if (
            $estadoLaboral !== null
            && !in_array(
                $estadoLaboral,
                $estadosLaboralesPermitidos,
                true
            )
        ) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El estado laboral no es válido.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $estadosExpedientePermitidos = [
            'COMPLETO',
            'INCOMPLETO',
            'DETERIORADO',
            'OBSERVADO'
        ];

        if (
            $estadoExpediente !== null
            && !in_array(
                $estadoExpediente,
                $estadosExpedientePermitidos,
                true
            )
        ) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El estado del expediente no es válido.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        try {
            $inventario = $this->reporteModel->obtenerInventario(
                $estadoLaboral,
                $estadoExpediente
            );

            echo json_encode([
                'success' => true,
                'total' => count($inventario),
                'filtros' => [
                    'estado_laboral' => $estadoLaboral,
                    'estado_expediente' => $estadoExpediente
                ],
                'inventario' => $inventario
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'No se pudo obtener el inventario.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    public function resumenInventario(): void
    {
        try {
            $resumen = $this->reporteModel->obtenerResumenInventario();

            echo json_encode([
                'success' => true,
                'resumen' => $resumen
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'No se pudo obtener el resumen del inventario.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    public function prestamosPendientes(): void
    {
        try {
            $prestamos = $this->reporteModel->obtenerPrestamosPendientes();

            echo json_encode([
                'success' => true,
                'total' => count($prestamos),
                'prestamos_pendientes' => $prestamos
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'No se pudieron obtener los préstamos pendientes.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    public function resumenPrestamos(): void
    {
        try {
            $resumen = $this->reporteModel->obtenerResumenPrestamos();

            echo json_encode([
                'success' => true,
                'resumen' => $resumen
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'No se pudo obtener el resumen de préstamos.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    public function exportarInventarioExcel(): void
    {
        try {
            $contenido = $this->informeInventario->generarExcel();
        } catch (Throwable $e) {
            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'No se pudo generar el inventario en Excel.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $nombreArchivo = 'inventario_expedientes_'
            . FormatoInventario::ahora()->format('Y-m-d')
            . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Content-Length: ' . strlen($contenido));
        header('Cache-Control: no-store');

        echo $contenido;
    }

    public function informeFinal(): void
    {
        try {
            $html = $this->informeInventario->generarInformeHtml();
        } catch (Throwable $e) {
            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'No se pudo generar el informe final.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store');

        echo $html;
    }

    public function rotulos(): void
    {
        $tipo = isset($_GET['tipo']) && trim((string) $_GET['tipo']) !== ''
            ? strtolower(trim((string) $_GET['tipo']))
            : 'carpeta';

        $estadoLaboral = isset($_GET['estado_laboral'])
            && trim((string) $_GET['estado_laboral']) !== ''
            ? strtoupper(trim((string) $_GET['estado_laboral']))
            : null;

        $idUbicacion = null;

        if (!in_array($tipo, InformeInventario::TIPOS_ROTULO, true)) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El tipo de rótulo debe ser carpeta, caja o archivador.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if (
            $estadoLaboral !== null
            && !in_array($estadoLaboral, ['ACTIVO', 'PASIVO'], true)
        ) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'El estado laboral no es válido.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if (
            isset($_GET['id_ubicacion'])
            && trim((string) $_GET['id_ubicacion']) !== ''
        ) {
            $idUbicacion = filter_var(
                $_GET['id_ubicacion'],
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            if ($idUbicacion === false) {
                http_response_code(422);

                echo json_encode([
                    'success' => false,
                    'message' => 'La ubicación seleccionada no es válida.'
                ], JSON_UNESCAPED_UNICODE);

                return;
            }
        }

        try {
            $html = $this->informeInventario->generarRotulosHtml(
                $tipo,
                $estadoLaboral,
                $idUbicacion
            );
        } catch (Throwable $e) {
            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'No se pudieron generar los rótulos.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store');

        echo $html;
    }
}