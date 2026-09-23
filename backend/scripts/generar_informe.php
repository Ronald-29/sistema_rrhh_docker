<?php

// Genera los entregables del trabajo de archivo sin iniciar sesión en la API:
//   php scripts/generar_informe.php
// Los archivos quedan en storage/informes/AAAA-MM-DD_HHMM/.

date_default_timezone_set('America/La_Paz');

$pdo = require __DIR__ . '/../config/database.php';

require_once __DIR__ . '/../src/Services/InformeInventario.php';

$informe = new InformeInventario($pdo);

$carpeta = dirname(__DIR__) . '/storage/informes/'
    . FormatoInventario::ahora()->format('Y-m-d_Hi');

if (!is_dir($carpeta) && !mkdir($carpeta, 0775, true)) {
    echo "No se pudo crear la carpeta: " . $carpeta . PHP_EOL;
    exit(1);
}

$archivos = [
    'inventario_expedientes.xlsx' => fn () => $informe->generarExcel(),
    'informe_final.html' => fn () => $informe->generarInformeHtml(),
    'rotulos_carpetas.html' => fn () => $informe->generarRotulosHtml('carpeta'),
    'rotulos_cajas.html' => fn () => $informe->generarRotulosHtml('caja'),
    'rotulos_archivadores.html' => fn () => $informe->generarRotulosHtml('archivador')
];

try {
    foreach ($archivos as $nombre => $generar) {
        $ruta = $carpeta . '/' . $nombre;

        if (file_put_contents($ruta, $generar()) === false) {
            throw new RuntimeException('No se pudo escribir ' . $ruta);
        }

        echo "Generado: " . realpath($ruta) . PHP_EOL;
    }

    echo PHP_EOL;
    echo "Abra los archivos .html en el navegador para imprimirlos o guardarlos como PDF." . PHP_EOL;
} catch (Throwable $e) {
    echo "Error al generar los informes." . PHP_EOL;
    echo $e->getMessage() . PHP_EOL;
    exit(1);
}
