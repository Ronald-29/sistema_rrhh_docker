<?php

require_once __DIR__ . '/FormatoInventario.php';

/**
 * Genera archivos .xlsx sin librerías externas.
 *
 * PHP en este servidor no tiene la extensión zip (requerida por PhpSpreadsheet),
 * así que el ZIP del .xlsx se arma a mano y se comprime con zlib (gzdeflate).
 *
 * Cada hoja tiene: título (fila 1), subtítulo (fila 2), encabezados (fila 4)
 * y los datos desde la fila 5, con filtros, bordes y el encabezado inmovilizado.
 */
class GeneradorExcel
{
    private const FILA_ENCABEZADOS = 4;

    private const ESTILO_TITULO = 1;
    private const ESTILO_SUBTITULO = 2;
    private const ESTILO_ENCABEZADO = 3;
    private const ESTILO_TEXTO = 4;
    private const ESTILO_NUMERO = 5;

    private array $hojas = [];

    /**
     * @param string[] $encabezados
     * @param array<int, array<int, string|int|float|null>> $filas
     * @param float[] $anchos Ancho de cada columna en caracteres.
     */
    public function agregarHoja(
        string $nombre,
        string $titulo,
        string $subtitulo,
        array $encabezados,
        array $filas,
        array $anchos
    ): void {
        // Excel no permite estos caracteres ni más de 31 en el nombre de la hoja.
        $nombre = mb_substr(
            str_replace(['\\', '/', '?', '*', '[', ']', ':'], ' ', $nombre),
            0,
            31
        );

        $this->hojas[] = [
            'nombre' => $nombre,
            'titulo' => $titulo,
            'subtitulo' => $subtitulo,
            'encabezados' => array_values($encabezados),
            'filas' => array_map('array_values', array_values($filas)),
            'anchos' => array_values($anchos)
        ];
    }

    public function generar(): string
    {
        if ($this->hojas === []) {
            throw new LogicException('El libro de Excel debe tener al menos una hoja.');
        }

        $archivos = [
            '[Content_Types].xml' => $this->xmlTiposContenido(),
            '_rels/.rels' => $this->xmlRelacionesRaiz(),
            'xl/workbook.xml' => $this->xmlLibro(),
            'xl/_rels/workbook.xml.rels' => $this->xmlRelacionesLibro(),
            'xl/styles.xml' => $this->xmlEstilos()
        ];

        foreach ($this->hojas as $indice => $hoja) {
            $archivos['xl/worksheets/sheet' . ($indice + 1) . '.xml'] = $this->xmlHoja($hoja);
        }

        return $this->crearZip($archivos);
    }

    private function xmlTiposContenido(): string
    {
        $hojas = '';

        foreach (array_keys($this->hojas) as $indice) {
            $hojas .= '<Override PartName="/xl/worksheets/sheet' . ($indice + 1) . '.xml"'
                . ' ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $hojas
            . '</Types>';
    }

    private function xmlRelacionesRaiz(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function xmlLibro(): string
    {
        $hojas = '';
        $nombresDefinidos = '';

        foreach ($this->hojas as $indice => $hoja) {
            $hojas .= '<sheet name="' . $this->escapar($hoja['nombre']) . '"'
                . ' sheetId="' . ($indice + 1) . '" r:id="rId' . ($indice + 1) . '"/>';

            $referenciaHoja = "'" . str_replace("'", "''", $hoja['nombre']) . "'";
            $ultimaColumna = $this->letraColumna(count($hoja['encabezados']));
            $ultimaFila = self::FILA_ENCABEZADOS + count($hoja['filas']);

            $nombresDefinidos .= '<definedName name="_xlnm._FilterDatabase" localSheetId="' . $indice . '" hidden="1">'
                . $this->escapar($referenciaHoja . '!$A$' . self::FILA_ENCABEZADOS . ':$' . $ultimaColumna . '$' . $ultimaFila)
                . '</definedName>'
                . '<definedName name="_xlnm.Print_Titles" localSheetId="' . $indice . '">'
                . $this->escapar($referenciaHoja . '!$' . self::FILA_ENCABEZADOS . ':$' . self::FILA_ENCABEZADOS)
                . '</definedName>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<bookViews><workbookView/></bookViews>'
            . '<sheets>' . $hojas . '</sheets>'
            . '<definedNames>' . $nombresDefinidos . '</definedNames>'
            . '</workbook>';
    }

    private function xmlRelacionesLibro(): string
    {
        $relaciones = '';

        foreach (array_keys($this->hojas) as $indice) {
            $relaciones .= '<Relationship Id="rId' . ($indice + 1) . '"'
                . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet"'
                . ' Target="worksheets/sheet' . ($indice + 1) . '.xml"/>';
        }

        $idEstilos = count($this->hojas) + 1;

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $relaciones
            . '<Relationship Id="rId' . $idEstilos . '"'
            . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles"'
            . ' Target="styles.xml"/>'
            . '</Relationships>';
    }

    private function xmlEstilos(): string
    {
        // El orden de <xf> debe coincidir con las constantes ESTILO_*.
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="4">'
            . '<font><sz val="10"/><name val="Arial"/></font>'
            . '<font><b/><sz val="14"/><name val="Arial"/></font>'
            . '<font><i/><sz val="10"/><color rgb="FF555555"/><name val="Arial"/></font>'
            . '<font><b/><sz val="10"/><name val="Arial"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFD9E2F3"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border>'
            . '<left style="thin"><color rgb="FF999999"/></left>'
            . '<right style="thin"><color rgb="FF999999"/></right>'
            . '<top style="thin"><color rgb="FF999999"/></top>'
            . '<bottom style="thin"><color rgb="FF999999"/></bottom>'
            . '<diagonal/>'
            . '</border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="6">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'
            . '<alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1">'
            . '<alignment vertical="top" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1">'
            . '<alignment horizontal="center" vertical="top"/></xf>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private function xmlHoja(array $hoja): string
    {
        $totalColumnas = count($hoja['encabezados']);
        $ultimaColumna = $this->letraColumna($totalColumnas);
        $ultimaFila = self::FILA_ENCABEZADOS + count($hoja['filas']);

        $columnas = '';

        foreach ($hoja['anchos'] as $indice => $ancho) {
            $numero = $indice + 1;
            $columnas .= '<col min="' . $numero . '" max="' . $numero . '"'
                . ' width="' . (float) $ancho . '" customWidth="1"/>';
        }

        $filasXml = $this->xmlFila(1, [$hoja['titulo']], self::ESTILO_TITULO)
            . $this->xmlFila(2, [$hoja['subtitulo']], self::ESTILO_SUBTITULO)
            . $this->xmlFila(self::FILA_ENCABEZADOS, $hoja['encabezados'], self::ESTILO_ENCABEZADO);

        foreach ($hoja['filas'] as $indice => $fila) {
            $filasXml .= $this->xmlFila(
                self::FILA_ENCABEZADOS + 1 + $indice,
                $fila,
                self::ESTILO_TEXTO
            );
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>'
            . '<dimension ref="A1:' . $ultimaColumna . $ultimaFila . '"/>'
            . '<sheetViews><sheetView workbookViewId="0">'
            . '<pane ySplit="' . self::FILA_ENCABEZADOS . '" topLeftCell="A' . (self::FILA_ENCABEZADOS + 1) . '"'
            . ' activePane="bottomLeft" state="frozen"/>'
            . '</sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="13"/>'
            . ($columnas !== '' ? '<cols>' . $columnas . '</cols>' : '')
            . '<sheetData>' . $filasXml . '</sheetData>'
            . '<autoFilter ref="A' . self::FILA_ENCABEZADOS . ':' . $ultimaColumna . $ultimaFila . '"/>'
            . '<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.3" footer="0.3"/>'
            . '<pageSetup orientation="landscape" fitToWidth="1" fitToHeight="0"/>'
            . '</worksheet>';
    }

    private function xmlFila(int $numeroFila, array $valores, int $estilo): string
    {
        $celdas = '';

        foreach ($valores as $indice => $valor) {
            $referencia = $this->letraColumna($indice + 1) . $numeroFila;

            if (is_int($valor) || is_float($valor)) {
                $estiloNumero = $estilo === self::ESTILO_TEXTO
                    ? self::ESTILO_NUMERO
                    : $estilo;

                $celdas .= '<c r="' . $referencia . '" s="' . $estiloNumero . '"><v>' . $valor . '</v></c>';

                continue;
            }

            $texto = $this->limpiarTexto((string) ($valor ?? ''));

            $celdas .= '<c r="' . $referencia . '" s="' . $estilo . '" t="inlineStr">'
                . '<is><t xml:space="preserve">' . $this->escapar($texto) . '</t></is></c>';
        }

        return '<row r="' . $numeroFila . '">' . $celdas . '</row>';
    }

    private function letraColumna(int $numero): string
    {
        $letras = '';

        while ($numero > 0) {
            $resto = ($numero - 1) % 26;
            $letras = chr(65 + $resto) . $letras;
            $numero = intdiv($numero - 1, 26);
        }

        return $letras;
    }

    private function limpiarTexto(string $texto): string
    {
        if (!mb_check_encoding($texto, 'UTF-8')) {
            $texto = mb_convert_encoding($texto, 'UTF-8', 'ISO-8859-1');
        }

        // Caracteres de control no permitidos en XML.
        $texto = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $texto) ?? '';

        // Una celda de Excel admite hasta 32767 caracteres.
        return mb_substr($texto, 0, 32767);
    }

    private function escapar(string $texto): string
    {
        return htmlspecialchars($texto, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * @param array<string, string> $archivos Ruta dentro del ZIP => contenido.
     */
    private function crearZip(array $archivos): string
    {
        $contenidoZip = '';
        $directorioCentral = '';
        $cantidad = 0;

        [$horaDos, $fechaDos] = $this->fechaHoraDos(FormatoInventario::ahora());

        foreach ($archivos as $ruta => $contenido) {
            $comprimido = gzdeflate($contenido, 6);

            if ($comprimido === false) {
                throw new RuntimeException('No se pudo comprimir el archivo de Excel.');
            }

            $crc = crc32($contenido);
            $desplazamiento = strlen($contenidoZip);

            $contenidoZip .= pack(
                'VvvvvvVVVvv',
                0x04034b50,
                20,
                0,
                8,
                $horaDos,
                $fechaDos,
                $crc,
                strlen($comprimido),
                strlen($contenido),
                strlen($ruta),
                0
            ) . $ruta . $comprimido;

            $directorioCentral .= pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014b50,
                20,
                20,
                0,
                8,
                $horaDos,
                $fechaDos,
                $crc,
                strlen($comprimido),
                strlen($contenido),
                strlen($ruta),
                0,
                0,
                0,
                0,
                0,
                $desplazamiento
            ) . $ruta;

            $cantidad++;
        }

        return $contenidoZip
            . $directorioCentral
            . pack(
                'VvvvvVVv',
                0x06054b50,
                0,
                0,
                $cantidad,
                $cantidad,
                strlen($directorioCentral),
                strlen($contenidoZip),
                0
            );
    }

    /**
     * @return array{0: int, 1: int} Hora y fecha en formato MS-DOS.
     */
    private function fechaHoraDos(DateTimeImmutable $fecha): array
    {
        $hora = ((int) $fecha->format('G') << 11)
            | ((int) $fecha->format('i') << 5)
            | intdiv((int) $fecha->format('s'), 2);

        $dia = (((int) $fecha->format('Y') - 1980) << 9)
            | ((int) $fecha->format('n') << 5)
            | (int) $fecha->format('j');

        return [$hora, $dia];
    }
}
