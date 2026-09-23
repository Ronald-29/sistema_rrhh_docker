<?php
/**
 * Rótulos imprimibles para carpetas, cajas y archivadores
 * (punto 7 de los términos de referencia).
 *
 * @var callable $e
 * @var string $tipo carpeta | caja | archivador
 * @var string $fechaGeneracion
 * @var array $expedientes Solo para rótulos de carpeta.
 * @var array $grupos Solo para rótulos de caja o archivador.
 */

$titulos = [
    'carpeta' => 'Rótulos de carpetas',
    'caja' => 'Rótulos de cajas',
    'archivador' => 'Rótulos de archivadores'
];

$cantidad = $tipo === 'carpeta' ? count($expedientes) : count($grupos);

$valorONumero = static fn (string $valor): string => $valor !== '' ? $valor : 'S/N';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title><?= $e($titulos[$tipo]) ?> - Archivo RRHH EMAPA</title>
    <style>
        @page {
            margin: 10mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            margin: 0;
            padding: 16px;
        }

        .barra {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            padding: 10px 12px;
            margin-bottom: 16px;
            font-size: 10pt;
            background: #eef2f8;
            border: 1px solid #c5d0e0;
        }

        .barra button {
            font-size: 11pt;
            padding: 6px 16px;
            cursor: pointer;
        }

        .hoja {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 6mm;
        }

        .hoja.caja,
        .hoja.archivador {
            grid-template-columns: 1fr;
        }

        .rotulo {
            border: 2px solid #000;
            padding: 4mm 5mm;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .cabecera {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 4mm;
            padding-bottom: 2mm;
            margin-bottom: 2mm;
            font-size: 8pt;
            font-weight: bold;
            letter-spacing: 0.03em;
            border-bottom: 1px solid #000;
        }

        .condicion {
            padding: 0.5mm 3mm;
            font-size: 10pt;
            border: 2px solid #000;
            white-space: nowrap;
        }

        /* PASIVO en negro para distinguirlo también en impresoras sin color. */
        .condicion.pasivos,
        .condicion.pasivo {
            color: #fff;
            background: #000;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .principal {
            font-size: 22pt;
            font-weight: bold;
            line-height: 1.1;
        }

        .hoja.caja .principal,
        .hoja.archivador .principal {
            font-size: 30pt;
        }

        .nombre {
            margin: 1mm 0 2mm;
            font-size: 13pt;
            font-weight: bold;
        }

        .dato {
            margin: 0.8mm 0;
            font-size: 9.5pt;
        }

        .dato b {
            display: inline-block;
            min-width: 26mm;
        }

        .lista {
            columns: 2;
            column-gap: 8mm;
            margin: 3mm 0 0;
            padding: 2mm 0 0;
            font-size: 8pt;
            list-style: none;
            border-top: 1px dashed #000;
        }

        .lista li {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .vacio {
            font-style: italic;
            color: #555;
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
        <span>
            <b><?= $e($titulos[$tipo]) ?></b>: <?= $e($cantidad) ?>.
            Generado el <?= $e($fechaGeneracion) ?>.
            Al imprimir, active <b>Gráficos de fondo</b> para que "PASIVO" salga en negro.
        </span>
        <button type="button" onclick="window.print()">Imprimir</button>
    </div>

    <?php if ($cantidad === 0): ?>
        <p class="vacio">
            <?= $tipo === 'carpeta'
                ? 'No hay expedientes para los filtros seleccionados.'
                : 'No hay expedientes con ubicación asignada para los filtros seleccionados.' ?>
        </p>
    <?php endif; ?>

    <div class="hoja <?= $e($tipo) ?>">
        <?php if ($tipo === 'carpeta'): ?>
            <?php foreach ($expedientes as $fila): ?>
                <?php $condicion = $fila['estado_laboral'] === 'ACTIVO' ? 'ACTIVO' : 'PASIVO'; ?>
                <div class="rotulo">
                    <div class="cabecera">
                        <span>EMAPA · ARCHIVO DE RECURSOS HUMANOS</span>
                        <span class="condicion <?= $e(mb_strtolower($condicion)) ?>"><?= $e($condicion) ?></span>
                    </div>
                    <div class="principal">N° <?= $e($fila['numero_correlativo']) ?></div>
                    <div class="nombre"><?= $e(FormatoInventario::nombreCompleto($fila)) ?></div>
                    <?php if (!empty($fila['numero_documento'])): ?>
                        <div class="dato"><b>N° documento:</b> <?= $e($fila['numero_documento']) ?></div>
                    <?php endif; ?>
                    <div class="dato"><b>Cargo:</b> <?= $e($fila['cargo'] ?? '') ?></div>
                    <?php if (FormatoInventario::periodoLaboral($fila) !== ''): ?>
                        <div class="dato"><b>Periodo:</b> <?= $e(FormatoInventario::periodoLaboral($fila)) ?></div>
                    <?php endif; ?>
                    <div class="dato"><b>Ubicación:</b> <?= $e(FormatoInventario::ubicacionFisica($fila)) ?></div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <?php foreach ($grupos as $grupo): ?>
                <?php $ubicacion = $grupo['ubicacion']; ?>
                <div class="rotulo">
                    <div class="cabecera">
                        <span>EMAPA · ARCHIVO DE RECURSOS HUMANOS</span>
                        <span class="condicion <?= $e($grupo['condicion'] === 'PASIVOS' ? 'pasivos' : '') ?>"><?= $e($grupo['condicion']) ?></span>
                    </div>

                    <?php if ($tipo === 'caja'): ?>
                        <div class="principal">CAJA <?= $e($valorONumero($ubicacion['caja'])) ?></div>
                    <?php else: ?>
                        <div class="principal">ARCHIVADOR <?= $e($valorONumero($ubicacion['archivador'])) ?></div>
                    <?php endif; ?>

                    <?php if ($ubicacion['ambiente'] !== ''): ?>
                        <div class="dato"><b>Ambiente:</b> <?= $e($ubicacion['ambiente']) ?></div>
                    <?php endif; ?>
                    <div class="dato"><b>Estante:</b> <?= $e($valorONumero($ubicacion['estante'])) ?></div>
                    <?php if ($tipo === 'caja'): ?>
                        <div class="dato"><b>Archivador:</b> <?= $e($valorONumero($ubicacion['archivador'])) ?></div>
                    <?php else: ?>
                        <div class="dato"><b>Cajas:</b> <?= $e(implode(', ', $grupo['cajas'])) ?></div>
                    <?php endif; ?>
                    <div class="dato">
                        <b>Expedientes:</b> <?= $e($grupo['total']) ?>
                        (activos: <?= $e($grupo['activos']) ?>, pasivos: <?= $e($grupo['pasivos']) ?>)
                    </div>
                    <div class="dato">
                        <b>Correlativos:</b>
                        <?= $e($grupo['correlativo_inicial']) ?>
                        <?php if ($grupo['total'] > 1): ?> al <?= $e($grupo['correlativo_final']) ?><?php endif; ?>
                    </div>
                    <div class="dato">
                        <b>Nombres:</b>
                        <?= $e($grupo['nombre_inicial']) ?>
                        <?php if ($grupo['total'] > 1): ?> a <?= $e($grupo['nombre_final']) ?><?php endif; ?>
                    </div>

                    <?php if ($tipo === 'caja'): ?>
                        <ul class="lista">
                            <?php foreach ($grupo['expedientes'] as $fila): ?>
                                <li>
                                    <?= $e($fila['numero_correlativo']) ?> -
                                    <?= $e(FormatoInventario::nombreCompleto($fila)) ?>
                                    (<?= $e(FormatoInventario::condicion($fila)) ?>)
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
