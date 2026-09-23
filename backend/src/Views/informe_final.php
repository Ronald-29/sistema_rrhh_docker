<?php
/**
 * Informe final del trabajo de archivo (punto 8 de los términos de referencia).
 * Se imprime o se guarda como PDF desde el navegador.
 *
 * @var callable $e
 * @var string $fechaGeneracion
 * @var array $filasResumen
 * @var array $resumen
 * @var float $porcentajeRevision
 * @var array $activos
 * @var array $pasivos
 * @var array $conObservaciones
 * @var array $prestamosPendientes
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Informe final - Inventario de expedientes personales</title>
    <style>
        @page {
            size: landscape;
            margin: 12mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            color: #000;
            margin: 0;
            padding: 16px;
        }

        .barra {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 12px;
            margin-bottom: 16px;
            background: #eef2f8;
            border: 1px solid #c5d0e0;
        }

        .barra button {
            font-size: 11pt;
            padding: 6px 16px;
            cursor: pointer;
        }

        header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }

        header p {
            margin: 2px 0;
        }

        h1 {
            font-size: 15pt;
            margin: 8px 0 4px;
        }

        h2 {
            font-size: 12pt;
            margin: 22px 0 8px;
            padding-bottom: 2px;
            border-bottom: 1px solid #888;
            page-break-after: avoid;
            break-after: avoid;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #777;
            padding: 3px 5px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #d9e2f3;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .centro {
            text-align: center;
            white-space: nowrap;
        }

        table.resumen {
            width: auto;
            min-width: 45%;
        }

        .vacio {
            font-style: italic;
            color: #555;
        }

        .firmas {
            display: flex;
            justify-content: space-around;
            margin-top: 70px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .firma {
            width: 28%;
            padding-top: 4px;
            border-top: 1px solid #000;
            text-align: center;
        }

        @media print {
            .no-imprimir {
                display: none;
            }

            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="barra no-imprimir">
        <span>Para guardar en PDF: <b>Imprimir</b> y elegir <b>Guardar como PDF</b>.</span>
        <button type="button" onclick="window.print()">Imprimir</button>
    </div>

    <header>
        <p><b>EMPRESA DE APOYO A LA PRODUCCIÓN DE ALIMENTOS - EMAPA</b></p>
        <p>Archivo de Recursos Humanos</p>
        <h1>Informe final: inventario de expedientes personales</h1>
        <p>Fecha de generación: <?= $e($fechaGeneracion) ?></p>
    </header>

    <h2>1. Resumen general</h2>

    <table class="resumen">
        <thead>
            <tr>
                <th>Concepto</th>
                <th class="centro">Cantidad</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($filasResumen as [$concepto, $cantidad]): ?>
                <tr>
                    <td><?= $e($concepto) ?></td>
                    <td class="centro"><?= $e($cantidad) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p>
        Avance de la revisión física:
        <b><?= $e($resumen['total_revisados']) ?></b> de
        <b><?= $e($resumen['total_expedientes']) ?></b> expedientes
        (<?= $e(number_format($porcentajeRevision, 1, ',', '.')) ?> %).
    </p>

    <?php foreach ([['2. Inventario de archivos activos', $activos], ['3. Inventario de archivos pasivos', $pasivos]] as [$tituloSeccion, $expedientes]): ?>
        <h2><?= $e($tituloSeccion) ?> (<?= $e(count($expedientes)) ?>)</h2>

        <?php if ($expedientes === []): ?>
            <p class="vacio">No hay expedientes registrados.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th class="centro">N°</th>
                        <th>N° correlativo</th>
                        <th>Nombre completo</th>
                        <th>N° de documento</th>
                        <th>Condición</th>
                        <th>Cargo</th>
                        <th>Gestión o periodo laboral</th>
                        <th>Ubicación física</th>
                        <th>Estado</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expedientes as $indice => $fila): ?>
                        <tr>
                            <td class="centro"><?= $e($indice + 1) ?></td>
                            <td><?= $e($fila['numero_correlativo']) ?></td>
                            <td><?= $e(FormatoInventario::nombreCompleto($fila)) ?></td>
                            <td><?= $e($fila['numero_documento'] ?? '') ?></td>
                            <td><?= $e(FormatoInventario::condicion($fila)) ?></td>
                            <td><?= $e($fila['cargo'] ?? '') ?></td>
                            <td><?= $e(FormatoInventario::periodoLaboral($fila)) ?></td>
                            <td><?= $e(FormatoInventario::ubicacionFisica($fila)) ?></td>
                            <td><?= $e(FormatoInventario::estadoExpediente($fila)) ?></td>
                            <td><?= nl2br($e($fila['observaciones'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endforeach; ?>

    <h2>4. Expedientes incompletos, deteriorados o con observaciones (<?= $e(count($conObservaciones)) ?>)</h2>

    <?php if ($conObservaciones === []): ?>
        <p class="vacio">No se identificaron expedientes con observaciones.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th class="centro">N°</th>
                    <th>N° correlativo</th>
                    <th>Nombre completo</th>
                    <th>Condición</th>
                    <th>Estado</th>
                    <th>Observaciones</th>
                    <th>Ubicación física</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($conObservaciones as $indice => $fila): ?>
                    <tr>
                        <td class="centro"><?= $e($indice + 1) ?></td>
                        <td><?= $e($fila['numero_correlativo']) ?></td>
                        <td><?= $e(FormatoInventario::nombreCompleto($fila)) ?></td>
                        <td><?= $e(FormatoInventario::condicion($fila)) ?></td>
                        <td><?= $e(FormatoInventario::estadoExpediente($fila)) ?></td>
                        <td><?= nl2br($e($fila['observaciones'] ?? '')) ?></td>
                        <td><?= $e(FormatoInventario::ubicacionFisica($fila)) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h2>5. Préstamos pendientes de devolución (<?= $e(count($prestamosPendientes)) ?>)</h2>

    <?php if ($prestamosPendientes === []): ?>
        <p class="vacio">No hay préstamos pendientes de devolución.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th class="centro">N°</th>
                    <th>Tipo</th>
                    <th>N° correlativo</th>
                    <th>Servidor o ex servidor</th>
                    <th>Documento</th>
                    <th>Entregado por</th>
                    <th>Recibido por</th>
                    <th>Fecha y hora de entrega</th>
                    <th>Motivo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($prestamosPendientes as $indice => $prestamo): ?>
                    <tr>
                        <td class="centro"><?= $e($indice + 1) ?></td>
                        <td><?= $e($prestamo['tipo_prestamo'] === 'EXPEDIENTE' ? 'Expediente' : 'Documento') ?></td>
                        <td><?= $e($prestamo['numero_correlativo']) ?></td>
                        <td><?= $e(FormatoInventario::nombreCompleto($prestamo)) ?></td>
                        <td><?= $e($prestamo['nombre_documento'] ?? '') ?></td>
                        <td><?= $e($prestamo['entregado_por']) ?></td>
                        <td><?= $e($prestamo['recibido_por']) ?></td>
                        <td class="centro"><?= $e(FormatoInventario::fechaHora($prestamo['fecha_hora_entrega'])) ?></td>
                        <td><?= $e($prestamo['motivo'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="firmas">
        <div class="firma">Elaborado por</div>
        <div class="firma">Revisado por</div>
        <div class="firma">Aprobado por</div>
    </div>
</body>
</html>
