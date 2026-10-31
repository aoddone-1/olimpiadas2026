<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// TCPDF ya viene cargado por la librería 'Pdf' del controlador; por si acaso:
if (!class_exists('TCPDF')) {
    require_once APPPATH . 'libraries/tcpdf/tcpdf.php';
}

class MYPDF extends TCPDF {
    // Propiedades para recibir los datos desde el controlador
    public $datos = [];
    public $formato = 'lista';
    public $dia_filtro = null;
    public $deporte_filtro = null; // nombre del deporte si el reporte está filtrado (null = todos)
    public $ancho_franja = 1;
    public $nombre_archivo = 'Nombre_Archivo.pdf';
    public $titulo_encabezado = 'FIXTURE DE COMPETENCIA';
    /** Delegación cuando el reporte lo descarga un delegado (null = reporte general). */
    public $delegacion_filtro = null;
    /** 'deporte' (Deporte → Categoría → Fecha → Hora) u 'horario' (Fecha → Hora → Deporte). */
    public $orden_reporte = 'deporte';
    /** Flag del controlador: TRUE cuando el reporte lo descarga un delegado. */
    public $modo_delegado = FALSE;
    /** Rango oficial de la competencia (AAAA-MM-DD). El cuadro del fixture usa
     *  TODOS los días del calendario como columnas —aunque no tengan partidos—
     *  y no solo los días cargados. Mismo criterio que el selector de días de
     *  la pantalla admin/fixture.php (INICIO_COMPETENCIA / FIN_COMPETENCIA);
     *  si algún partido cae fuera del rango, su día se agrega igual. */
    public $fecha_inicio_competencia = '2026-11-02';
    public $fecha_fin_competencia    = '2026-11-06';
    /** Cuándo se genera el PDF: 'inicio' (por defecto) recorre el calendario
     *  oficial desde su primer día; 'hoy' arranca en la fecha actual (siempre
     *  que caiga dentro del rango) para no imprimir días ya jugados. */
    public $cuando_reporte = 'inicio';
    /** Título alternativo para formato 'planilla' (mesas de control). */
    public $titulo_planilla = null;
    /**
     * Nombre del deporte cuando la planilla se descarga POR DEPORTE
     * (Inscripciones/descargar_planilla_deporte). Sirve como título general
     * del PDF; cada categoría igual imprime su propio título en su hoja.
     */
    public $deporte_planilla = null;
    /** Con ?nombres=cortos se abrevian los integrantes con iniciales
     *  ("María P. G."); por defecto (y con ?nombres=completos) se imprimen los
     *  nombres completos ("María Paula Gómez"). Solo aplica al reporte del
     *  delegado, que es el único que lista participantes. */
    public $nombres_completos = TRUE;
    /**
     * Filas en blanco extra que se agregan al pie de cada planilla (partidos o
     * participantes) por si en la mesa de control hay que anotar cruces o
     * inscriptos de último momento. Solo afecta a formato='planilla'.
     */
    public static $filas_vacias_planilla = 20;

    /** Nombre de UN participante como "Nombre Apellido".
     *  En la base de datos los nombres se guardan como "Apellido, nombre(s)"
     *  (una sola coma dentro de cada nombre). Al listar varios integrantes no
     *  podemos separarlos con coma —el lector no distingue dónde termina uno y
     *  empieza el siguiente—, así que en las planillas se separan con " - ".
     *  Para que esa coma interna del "Apellido, nombre" no se confunda con un
     *  separador más, cada nombre se reordena a "Nombre Apellido" ANTES de
     *  unirlos con guiones: si no, el " - " y la coma terminan dándole al
     *  lector la impresión de que también se partió el apellido del nombre.
     *  Nombres sin coma ("Ana Pérez") se devuelven intactos. Solo afecta a
     *  nombres de participantes; los nombres de equipos pasan por acá igual
     *  pero quedan sin cambios porque no traen coma. */
    public static function _nombre_normalizado($n) {
        $n = trim(preg_replace('/\s+/u', ' ', (string) $n));
        if ($n === '') return '';
        // Forma "Apellido, nombre(s)": invertir a "nombre(s) Apellido".
        if (strpos($n, ',') !== FALSE) {
            $pos  = strpos($n, ',');
            $ape  = trim(substr($n, 0, $pos));
            $nom  = trim(substr($n, $pos + 1));
            if ($ape !== '' && $nom !== '') return $nom . ' ' . $ape;
            return $ape !== '' ? $ape : $nom;
        }
        return $n;
    }

    /** Nombre corto (pila + iniciales): "María Paula Gómez" → "María P. G.". */
    public static function _nombre_corto($n) {
        // Normalizar primero: si llega "Gómez, María Paula" no tiene sentido
        // tomar "Gómez," como pila e iniciales del resto.
        $n = self::_nombre_normalizado($n);
        $n = trim(preg_replace('/\s+/u', ' ', (string) $n));
        if ($n === '') return '';
        $partes = preg_split('/\s+/u', $n);
        if (count($partes) < 2) return $n;
        $primero = array_shift($partes);
        $iniciales = array();
        foreach ($partes as $p) {
            $ini = mb_substr($p, 0, 1, 'UTF-8');
            if ($ini !== '') $iniciales[] = mb_strtoupper($ini, 'UTF-8') . '.';
        }
        return $iniciales ? $primero . ' ' . implode(' ', $iniciales) : $primero;
    }

    public function Header() {
        $this->Image('assets/img/header.jpg', 30, 15, 100, '', '', '', 'C', false, 50, '', false, false,0, false, false, false);
        
        
        
    }

    public function Body() {
        $html = "";
        $esc = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };

        /** Orden garantizado: Deporte → Categoría → Fecha → Hora. */
        usort($this->datos, function ($a, $b) {
            return [
                strtolower($a['nombre_deporte'] ?? ''),
                strtolower($a['nombre_categoria'] ?? ''),
                (string) ($a['fecha_competencia'] ?? ''),
                (string) ($a['hora_inicio'] ?? ''),
                (int) ($a['numero_fecha'] ?? 0)
            ] <=> [
                strtolower($b['nombre_deporte'] ?? ''),
                strtolower($b['nombre_categoria'] ?? ''),
                (string) ($b['fecha_competencia'] ?? ''),
                (string) ($b['hora_inicio'] ?? ''),
                (int) ($b['numero_fecha'] ?? 0)
            ];
        });

        /** Filtrar por día si corresponde */
        if (!empty($this->dia_filtro)) {
            $this->datos = array_values(array_filter($this->datos, function ($f) {
                return substr((string) ($f['fecha_competencia'] ?? ''), 0, 10) === $this->dia_filtro;
            }));
        }

        $deporte_cat = function ($f) {
            return trim(($f['nombre_deporte'] ?? '') . ' ' . ($f['nombre_categoria'] ?? '') . ' ' . ($f['genero_categoria'] ?? ''));
        };

        $horario = function ($f) {
            $hi = substr((string) ($f['hora_inicio'] ?? ''), 0, 5);
            $hf = substr((string) ($f['hora_fin'] ?? ''), 0, 5);
            return $hi . (($hf !== '' && $hf !== '00:00') ? ' – ' . $hf : '');
        };

        /**
         * RESOLUCIÓN DE CADA LADO DEL ENFRENTAMIENTO:
         *   - Reporte general (superadmin/admin): muestra siempre el EQUIPO
         *     ("Losa Tenis vs Los Pumas").
         *   - Reporte del DELEGADO (flag modo_delegado que pone el controlador
         *     al descargar desde su sesión): en los deportes de ENFRENTAMIENTO
         *     DE EQUIPOS muestra "EQUIPO (jugador 1, jugador 2)" de cada lado,
         *     para que el delegado vea SIEMPRE los equipos y además los
         *     INTEGRANTES de cada uno ("Losa Tenis (pepe, maria, juan) vs
         *     Los Pumas (nora, martina)"). Si un equipo no tiene integrantes
         *     cargados, se imprime solo su nombre (nunca queda vacío).
         *     En los deportes individuales / jornada masiva el modelo ya trae
         *     resueltos los nombres de los competidores en ute_X_nombre, así
         *     que se imprime tal cual.
         *
         * IMPORTANTE: la decisión se toma SOLO por el flag del controlador
         * (modo_delegado). Las filas del reporte general traen jugadores_ute_X
         * cargados por Fixture_model (los usan otros reportes), y antes se
         * imprimían siempre: por eso el superadmin veía todos los integrantes.
         */

        /** Nombres de los integrantes de un lado del enfrentamiento. Para
         *  equipos salen de jugadores_ute_X (Fixture_model); si esa lista
         *  viene vacía se despeja el prefijo "Equipo: J1, J2" que arma el
         *  modelo sobre el nombre del slot. Devuelve array de nombres. */
        $_integrantes_raw = function ($f, $lado) {
            $lista = array();
            foreach ((array) ($f['jugadores_ute_' . $lado] ?? array()) as $j) {
                $j = trim((string) $j);
                if ($j === '') continue;
                $lista[] = $j;
            }
            if ($lista) return $lista;
            $nombre = trim((string) ($f['ute_' . $lado . '_nombre'] ?? ''));
            if ($nombre === '') return array();
            // Solo hay lista si el texto trae la forma "Nombre: J1, J2"; si no,
            // el nombre es el del EQUIPO y no debe tratarse como participante.
            if (strpos($nombre, ': ') === FALSE) return array();
            $despejado = trim((string) preg_replace('/^[^:]+:\s*/u', '', $nombre));
            if ($despejado === '') return array();
            return array_values(array_filter(array_map('trim', explode(',', $despejado)), function ($x) {
                return $x !== '';
            }));
        };

        /** Nombre del EQUIPO del lado $lado (sin el prefijo ": integrantes"
         *  que a veces arma el modelo). */
        $equipo_lado = function ($f, $lado) {
            $nombre = trim((string) ($f['ute_' . $lado . '_nombre'] ?? ''));
            if (strpos($nombre, ': ') !== FALSE) {
                $pos = strpos($nombre, ': ');
                $eq = trim(substr($nombre, 0, $pos));
                if ($eq !== '') return $eq;
            }
            return $nombre;
        };

        /** Texto del lado $lado según el tipo de deporte:
         *  - equipos de enfrentamiento → "Equipo (integrantes...)";
         *  - resto (individual / masivo) → lo que resuelve el modelo. */
        $integrantes_lado = function ($f, $lado) use ($_integrantes_raw, $equipo_lado) {
            $modalidad = strtoupper(trim((string) ($f['modalidad_competencia'] ?? '')));
            $es_equipo = $modalidad === 'ENFRENTAMIENTO';
            if ($es_equipo) {
                $equipo = $equipo_lado($f, $lado);
                $crudos = $_integrantes_raw($f, $lado);
                if ($crudos) {
                    $out = array();
                    foreach ($crudos as $j) {
                        $out[] = $this->nombres_completos ? $j : self::_nombre_corto($j);
                    }
                    $lista_txt = implode(' - ', $out);
                    // El nombre del equipo SIEMPRE va delante: el delegado tiene
                    // que ver qué equipo enfrenta a cuál, y debajo sus integrantes.
                    // En modo delegado (tabla del fixture) el equipo va en NEGRITA
                    // y los integrantes aparte, en gris "muted" (#7f8c8d), más
                    // chico (6pt vs 7pt de la celda) e inclinado —el equivalente
                    // al estilo "mute" del resto del reporte—, para que la lista
                    // de participantes no compita visualmente con el nombre del
                    // equipo. En otros contextos se mantiene "Equipo (jugadores)".
                    if ($this->modo_delegado && $equipo !== '') {
                        return '<b>' . $equipo . '</b><br/>'
                            . '<span style="color:#7f8c8d; font-size:6pt; font-style:italic;">'
                            . $lista_txt . '</span>';
                    }
                    return $equipo !== '' ? $equipo . ' (' . $lista_txt . ')' : $lista_txt;
                }
                // Sin integrantes cargados: se muestra el nombre del equipo.
                return $equipo;
            }
            return trim((string) ($f['ute_' . $lado . '_nombre'] ?? ''));
        };

        /** Enfrentamiento en HTML: el reporte GENERAL (superadmin/admin) siempre
         *  muestra el EQUIPO ("Equipo A vs Equipo B"). El reporte del DELEGADO
         *  muestra los INTEGRANTES de cada equipo.
         *  OJO: $integrantes_lado() ahora puede devolver HTML (<b>, <span>) en
         *  modo delegado; por eso se escapa SOLO cuando no trae etiquetas, para
         *  que el marcado no se imprima literalmente en la tabla del fixture. */
        $sanear_lado = function ($v) {
            if ($v === '') return '';
            // Ya viene escapado/etiquetado por integrantes_lado (HTML propio).
            if (strpos($v, '<b>') !== FALSE || strpos($v, '<span') !== FALSE) {
                return $v;
            }
            return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
        };
        $equipos = function ($f) use ($integrantes_lado, $sanear_lado) {
            $e1 = $this->modo_delegado ? $integrantes_lado($f, 1) : trim((string) ($f['ute_1_nombre'] ?? ''));
            $e2 = $this->modo_delegado ? $integrantes_lado($f, 2) : trim((string) ($f['ute_2_nombre'] ?? ''));
            $e1 = $sanear_lado($e1);
            $e2 = $sanear_lado($e2);

            if ($e1 !== '' && $e2 !== '') return $e1 . ' <br/><span style="color:#95a5a6;">vs</span> <br/>' . $e2;
            if ($e2 !== '') return $e2;
            if ($e1 !== '') return $e1;
            return NULL;
        };

        /** Variante en texto plano (sin HTML) del enfrentamiento. Si el lado
         *  vino con HTML (modo delegado), se le quitan las etiquetas y se
         *  separa el nombre del equipo de sus integrantes con un guion largo. */
        $a_texto = function ($v) use ($sanear_lado) {
            $v = $sanear_lado($v);
            $txt = str_replace(array('<br/>', '<br />'), ' — ', $v);
            $txt = strip_tags($txt);
            return trim(preg_replace('/\s+/u', ' ', html_entity_decode($txt, ENT_QUOTES, 'UTF-8')));
        };
        $equipos_texto = function ($f) use ($integrantes_lado, $a_texto) {
            $t1 = $this->modo_delegado ? $a_texto($integrantes_lado($f, 1)) : trim((string) ($f['ute_1_nombre'] ?? ''));
            $t2 = $this->modo_delegado ? $a_texto($integrantes_lado($f, 2)) : trim((string) ($f['ute_2_nombre'] ?? ''));
            if ($t1 !== '' && $t2 !== '') return $t1 . ' vs ' . $t2;
            if ($t2 !== '') return $t2;
            if ($t1 !== '') return $t1;
            return NULL;
        };

        /** DÍAS DEL CUADRO: calendario oficial de la competencia (inicio→fin),
         *  no solo los días que tienen partidos cargados. Así un día sin
         *  actividad sigue apareciendo como columna con su nombre en el
         *  encabezado (LUN | MAR | MIÉ ...), con las celdas vacías. Si algún
         *  partido cae fuera del rango oficial, su día se agrega igual para
         *  que no quede invisible (mismo criterio que el selector de días de
         *  la pantalla de fixture). En modo filtro por día (?dia=...) se imprime
         *  únicamente ese día. */
        $dias = array();
        $ini_cal = (string) $this->fecha_inicio_competencia;
        $fin_cal = (string) $this->fecha_fin_competencia;
        if ($this->dia_filtro === null
            && preg_match('/^\d{4}-\d{2}-\d{2}$/', $ini_cal)
            && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fin_cal)) {
            // "¿Cuándo?" del filtro de días de la pantalla: 'hoy' arranca el
            // calendario en la fecha actual (si cae dentro del rango oficial);
            // con 'inicio' se imprimen todos los días de la competencia.
            if ((string) $this->cuando_reporte === 'hoy') {
                $hoy = date('Y-m-d');
                if ($hoy > $ini_cal && $hoy <= $fin_cal) $ini_cal = $hoy;
            }
            for ($ts = strtotime($ini_cal); $ts <= strtotime($fin_cal); $ts = strtotime('+1 day', $ts)) {
                $dias[date('Y-m-d', $ts)] = true;
            }
        }
        foreach ($this->datos as $fila) {
            $f = substr((string) ($fila['fecha_competencia'] ?? ''), 0, 10);
            if ($f !== '') $dias[$f] = true;
        }
        $dias = array_keys($dias);
        sort($dias);

        $fase_bonito = function ($fase) {
            $map = array(
                'GRUPO' => 'Fase de Grupos', '16AVOS' => '16avos de Final', 'OCTAVOS' => 'Octavos de Final',
                'CUARTOS' => 'Cuartos de Final', 'SEMIFINAL' => 'Semifinal', 'TERCER_PUESTO' => '3er Puesto',
                'FINAL' => 'Final', 'JORNADA_UNICA' => 'Jornada Única'
            );
            return isset($map[$fase]) ? $map[$fase] : (string) $fase;
        };

        $n_dias = count($dias);
        $rango_txt = $n_dias
            ? 'Desde ' . date('d/m/Y', strtotime($dias[0])) . ' hasta ' . date('d/m/Y', strtotime($dias[$n_dias - 1]))
            : 'Sin fechas cargadas';
        
        $this->titulo_encabezado = 'FIXTURE DE COMPETENCIA — ' . NOMBRE_META
            . ($this->delegacion_filtro ? ' — DELEGACIÓN ' . strtoupper((string) $this->delegacion_filtro) : '')
            . ($this->deporte_filtro ? ' — ' . strtoupper((string) $this->deporte_filtro) : '');

        // ====== FORMATO PLANILLA (mesas de control): reporte propio, no cuadro ======
        if ($this->formato === 'planilla') {
            $this->_body_planilla();
            return;
        }

        // ====== FORMATO DEPORTE: UNA TABLA POR DEPORTE CON TODOS SUS PARTIDOS ======
        // Nace de la queja de que el cuadro general (días × franjas horarias)
        // mezclado con todos los deportes no es cómodo de leer. Acá cada
        // deporte tiene su propia tabla/hoja con todos sus partidos ordenados
        // por Categoría → Fecha → Hora, y CADA CATEGORÍA se identifica con un
        // COLOR distinto (chapa al lado del nombre y borde izquierdo de la
        // fila) para poder seguirla de un vistazo dentro de la tabla.
        // El color solo aplica a los deportes de ENFRENTAMIENTO; en los de
        // LARGADA ÚNICA / jornada masiva (Running, Ciclismo, Pesca...) no hay
        // cruces que diferenciar, así que la fila va neutra —con el nombre del
        // deporte alcanza—.
        if ($this->formato === 'deporte') {
            $this->_body_por_deporte();
            return;
        }

        // ====== COLORES ======
        $c_primary   = '#1e3a5f';
        $c_secondary = '#3498db';
        $c_light     = '#ecf0f1';
        $c_border    = '#bdc3c7';
        $c_text      = '#2c3e50';
        $c_muted     = '#7f8c8d';
        $c_white     = '#ffffff';
        $c_alt       = '#f8f9fa';

        // ====== FORMATO CUADRO - EXACTAMENTE 5 DÍAS ======
        $ancho = max(1, (int) $this->ancho_franja);
        $min_hora = null;
        $max_hora = null;
        foreach ($this->datos as $fila) {
            $hi = (string) ($fila['hora_inicio'] ?? '');
            $hf = (string) ($fila['hora_fin'] ?? '');
            if ($hi === '') continue;
            $h_ini = (int) substr($hi, 0, 2);
            $h_fin = $hf !== '' ? (int) substr($hf, 0, 2) : $h_ini;
            if ((int) substr($hf, 3, 2) > 0 || $h_fin === $h_ini) $h_fin++;
            if ($min_hora === null || $h_ini < $min_hora) $min_hora = $h_ini;
            if ($max_hora === null || $h_fin > $max_hora) $max_hora = $h_fin;
        }
        if ($min_hora === null) { $min_hora = 8; $max_hora = 9; }
        
        $min_hora = intdiv($min_hora, $ancho) * $ancho;
        $max_hora = intdiv($max_hora - $min_hora - 1, $ancho) * $ancho + $ancho + $min_hora;
        $n_frajas = intdiv($max_hora - $min_hora, $ancho);

        // Distribuir partidos
        $celdas = array();
        foreach ($this->datos as $fila) {
            $fecha = substr((string) ($fila['fecha_competencia'] ?? ''), 0, 10);
            if ($fecha === '') continue;
            $h_ini = (int) substr((string) ($fila['hora_inicio'] ?? '0'), 0, 2);
            $idx = intdiv(max(0, $h_ini - $min_hora), $ancho);
            if ($idx >= $n_frajas) $idx = $n_frajas - 1;
            $celdas[$idx][$fecha][] = $fila;
        }

        // ====== FORZAR 5 DÍAS POR PÁGINA ======
        $por_bloque = 5;
        $bloques = array();
        for ($b = 0; $b < $n_dias; $b += $por_bloque) {
            $bloques[] = array_slice($dias, $b, $por_bloque);
        }
        if (!$bloques) $bloques = array(array());
        
        $orientacion = 'L'; // Siempre horizontal para 5 días
        
        $dias_completos = array('Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado');
        $dias_cortos    = array('DOM', 'LUN', 'MAR', 'MIÉ', 'JUE', 'VIE', 'SÁB');

        // Anchos: horario 12%, cada día 17.6% (5 días = 88%)
        $w_horario = 16.7;
        $w_col = (100 - $w_horario) / 5;

        foreach ($bloques as $bi => $bloque) {
            if ($bi > 0) {
                $this->AddPage($orientacion, 'A4');
            }
            
            // Encabezado
            $html_bloque = '<div style="text-align:center; font-size:16pt; font-weight:bold; color:' . $c_primary . '; padding:5px 0 8px 0;">'
                        . 'Fixture de Competencia — Cuadro General'
                        . ($this->delegacion_filtro ? ' — Delegación ' . $esc($this->delegacion_filtro) : '')
                        . ($this->deporte_filtro ? ' — ' . $esc($this->deporte_filtro) : '')
                        . '</div>'
                        . '<div style="text-align:center; font-size:9pt; color:' . $c_muted . '; padding-bottom:12px;">' 
                        . $esc($rango_txt) .'</div>';
              
            $this->writeHTMLCell(0, 70, 300, 15, $html_bloque, 0, 1, 0, true, 'J', true);

            $html_bloque ="";
            if ($n_dias > $por_bloque) {
                $html_bloque .= '<div style="text-align:right; font-size:8pt; color:' . $c_muted . '; padding-bottom:8px;">'
                            . 'Página ' . ($bi + 1) . ' de ' . count($bloques) 
                            . ' &nbsp;·&nbsp; Días ' . ($bi * $por_bloque + 1) . '–' 
                            . ($bi * $por_bloque + count($bloque)) . ' de ' . $n_dias . '</div>';
            }

            if (!$bloque) {
                $html_bloque .= '<p style="text-align:center; font-size:10pt; color:' . $c_muted . ';">No hay partidos cargados.</p>';
                $this->writeHTML($html_bloque, true, false, true, false, '');
                continue;
            }

            // Completar con columnas vacías si hay menos de 5 días
            while (count($bloque) < 5) {
                $bloque[] = null;
            }

            // Tabla principal
            $html_bloque .= '<table cellpadding="3" cellspacing="0" border="1" style="border-color:' . $c_border . '; border-collapse:collapse; width:100%; font-size:7pt; color:' . $c_text . ';">';
            
            // Encabezado de días
            $html_bloque .= '<thead>'
                        . '<tr style="background-color:' . $c_primary . '; color:' . $c_white . ';">'
                        . '<th style="width:' . $w_horario . '%; padding:8px 4px; text-align:center; border:1px solid ' . $c_border . '; font-size:9pt;">HORARIO</th>';
            
            foreach ($bloque as $f) {
                if ($f === null) {
                    $html_bloque .= '<th style="width:' . $w_col . '%; padding:6px 4px; text-align:center; border:1px solid ' . $c_border . '; background-color:' . $c_light . ';"></th>';
                } else {
                    $ts = strtotime($f);
                    $nombre_dia = $dias_cortos[(int) date('w', $ts)];
                    $fecha_corta = date('d/m', $ts);
                    $html_bloque .= '<th style="width:' . $w_col . '%; padding:6px 4px; text-align:center; border:1px solid ' . $c_border . ';">'
                                . '<div style="font-size:11pt; font-weight:bold; letter-spacing:1px;">' . $nombre_dia . '</div>'
                                . '<div style="font-size:8pt; opacity:0.9;">' . $fecha_corta . '</div>'
                                . '</th>';
                }
            }
            $html_bloque .= '</tr></thead><tbody>';

            // Filas de franjas horarias
            for ($i = 0; $i < $n_frajas; $i++) {
                $h_ini = $min_hora + $i * $ancho;
                $h_fin = $h_ini + $ancho;
                $bg_franja = ($i % 2) ? $c_white : $c_alt;
                
                $html_bloque .= '<tr style="background-color:' . $bg_franja . ';">';
                
                // Columna horario
                $html_bloque .= '<td style="background-color:' . $c_light . '; font-weight:bold; text-align:center; padding:6px 4px; border:1px solid ' . $c_border . '; font-size:9pt; color:' . $c_primary . ';">'
                            . sprintf('%02d:00<br/>–<br/>%02d:00', $h_ini % 24, $h_fin % 24)
                            . '</td>';

                // Columnas de días
                foreach ($bloque as $f) {
                    if ($f === null) {
                        $html_bloque .= '<td style="background-color:' . $c_light . '; border:1px solid ' . $c_border . ';">&nbsp;</td>';
                        continue;
                    }

                    $partidos = isset($celdas[$i][$f]) ? $celdas[$i][$f] : array();
                    
                    if (empty($partidos)) {
                        $html_bloque .= '<td style="background-color:' . $c_white . '; border:1px solid ' . $c_border . '; color:' . $c_muted . '; text-align:center; font-style:italic; font-size:6pt;">—</td>';
                    } else {
                        $cel = '';
                        foreach ($partidos as $idx_p => $p) {
                            $dc = trim(($p['nombre_deporte'] ?? '') . ' - Cat.' . ($p['nombre_categoria'] ?? '') );
                            $sub_info = "Hora de Inicio: ".sprintf('%02d:00 hrs', $p['hora_inicio']);//trim($fase_bonito($p['fase'] ?? '') . (!empty($p['numero_fecha']) ? ' · F' . (int) $p['numero_fecha'] : ''));
                            
                            if ($idx_p > 0) {
                                $cel .= '<hr style="border:none; border-top:1px dashed ' . $c_border . '; margin:4px 0;"/>';
                            }
                            
                            $cel .= '<div style="margin-bottom:2px;">'
                                . '<div style="font-size:7pt; font-weight:bold; color:' . $c_secondary . ';">' . $esc($dc) . '</div>';
                            // Enfrentamiento. En modo delegado muestra los PARTICIPANTES
                            // del equipo propio (sin el nombre del equipo) vs el equipo rival.
                            $vs_html = $equipos($p);
                            if ($vs_html === NULL || trim((string) $vs_html) === '') {
                                $vs_texto = $equipos_texto($p);
                                $vs_html = ($vs_texto !== NULL && trim((string) $vs_texto) !== '')
                                    ? $esc($vs_texto) : NULL;
                            }
                            if ($vs_html != null) {
                                $cel .= '<div style="font-size:7pt; margin:2px 0;">' . $vs_html . '</div>';
                            }
                            
                            if ($sub_info !== '') {
                                $cel .= '<div style="font-size:6pt; color:' . $c_muted . '; font-style:italic;">' . $esc($sub_info) . '</div>';
                            }
                            
                            if (!empty($p['lugar_nombre'])) {
                                $cel .= '<div style="font-size:6pt; color:' . $c_muted . ';"> Lugar: ' . $esc($p['lugar_nombre']) . '</div>';
                            }
                            
                            $cel .= '</div>';
                        }
                        $html_bloque .= '<td style="padding:3px; border:1px solid ' . $c_border . '; vertical-align:top;">' . $cel . '</td>';
                    }
                }
                $html_bloque .= '</tr>';
            }
            $html_bloque .= '</tbody></table>';
            
            $this->writeHTML($html_bloque, true, false, true, false, '');
        }
    }

    /**
     * Paleta fija (fondo, texto) para diferenciar CATEGORÍAS dentro de la
     * tabla de un deporte. Colores pastel con buen contraste de lectura; se
     * asignan de a uno por categoría (si hubiera más categorías que colores,
     * la paleta vuelve a empezar). Se comparten entre el formato 'deporte' y
     * el 'lista' para que el mismo color signifique lo mismo en ambos reportes.
     */
    public static $paleta_categorias = array(
        array('#e3f2fd', '#1565c0'), // azul
        array('#e8f5e9', '#2e7d32'), // verde
        array('#fff3e0', '#e65100'), // naranja
        array('#f3e5f5', '#7b1fa2'), // violeta
        array('#e0f7fa', '#00838f'), // cielorraso
        array('#fce4ec', '#ad1457'), // rosa
        array('#fffde7', '#8d6e00'), // amarillo
        array('#ede7f6', '#4527a0'), // índigo claro
        array('#efebe9', '#5d4037'), // marrón
        array('#eceff1', '#455a64'), // gris azulado
        array('#f1f8e9', '#558b2f'), // verde manzana
        array('#fbe9e7', '#bf360c'), // terracota
    );

    /** Color de la categoría $idx (0-based) de la paleta: array(fondo, texto). */
    public static function _color_categoria($idx) {
        $paleta = self::$paleta_categorias;
        return $paleta[((int) $idx) % count($paleta)];
    }

    /**
     * ¿Deporte de LARGADA ÚNICA / jornada masiva? Basta que UNA fila del
     * bloque sea JORNADA_UNICA o MASIVO_TIEMPO (mismo criterio que la planilla
     * de mesa de control). En estos deportes no hay "enfrentamientos" que
     * distinguir por color —todos largan juntos—, así que se imprime solo el
     * nombre del deporte y la fila queda neutra.
     */
    public static function _bloque_es_masivo($filas) {
        foreach ((array) $filas as $f) {
            $modalidad = strtoupper(trim((string) ($f['modalidad_competencia'] ?? '')));
            $fase      = strtoupper(trim((string) ($f['fase'] ?? '')));
            if ($modalidad === 'MASIVO_TIEMPO' || $fase === 'JORNADA_UNICA') {
                return TRUE;
            }
        }
        return FALSE;
    }

    /** Etiqueta legible de la fase del partido. */
    public static function _fase_bonito($fase) {
        $map = array(
            'GRUPO' => 'Fase de Grupos', '16AVOS' => '16avos de Final', 'OCTAVOS' => 'Octavos de Final',
            'CUARTOS' => 'Cuartos de Final', 'SEMIFINAL' => 'Semifinal', 'TERCER_PUESTO' => '3er Puesto',
            'FINAL' => 'Final', 'JORNADA_UNICA' => 'Jornada Única'
        );
        return isset($map[$fase]) ? $map[$fase] : (string) $fase;
    }

    /**
     * ===== FORMATO DEPORTE: UNA TABLA POR DEPORTE =====
     * Reclamo de los usuarios: el cuadro general (días × franjas, todos los
     * deportes mezclados) no es cómodo. Este reporte imprime UNA TABLA por
     * deporte (cada deporte arranca en hoja nueva) con TODOS sus partidos,
     * ordenados Categoría → Fecha → Hora. Dentro de la tabla, cada categoría
     * tiene su COLOR (chapa en la columna Categoría + borde izquierdo de la
     * fila), para seguirla de un vistazo cruzando días y horarios.
     *   - Deportes de ENFRENTAMIENTO: filas coloreadas por categoría.
     *   - Deportes de LARGADA ÚNICA (jornada masiva): sin color —el nombre del
     *     deporte ya alcanza—, y la celda muestra la largada/jornada con sus
     *     competidores en vez del "A vs B".
     * Columnas: HORA | CATEGORÍA | FASE/JORNADA | ENFRENTAMIENTO | LUGAR.
     */
    public function _body_por_deporte() {
        $esc = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };

        // Orden interno: Deporte → Categoría → Fecha → Hora (Body() ya lo aplicó,
        // pero se refuerza acá porque este reporte agrupa por deporte).
        usort($this->datos, function ($a, $b) {
            return [
                strtolower($a['nombre_deporte'] ?? ''),
                strtolower($a['nombre_categoria'] ?? ''),
                (string) ($a['fecha_competencia'] ?? ''),
                (string) ($a['hora_inicio'] ?? ''),
                (int) ($a['numero_fecha'] ?? 0)
            ] <=> [
                strtolower($b['nombre_deporte'] ?? ''),
                strtolower($b['nombre_categoria'] ?? ''),
                (string) ($b['fecha_competencia'] ?? ''),
                (string) ($b['hora_inicio'] ?? ''),
                (int) ($b['numero_fecha'] ?? 0)
            ];
        });

        // Agrupar: Deporte → Categoría → [filas]. La clave usa id cuando existe
        // (evita juntar categorías homónimas de deportes distintos).
        $por_deporte = array();
        foreach ($this->datos as $fila) {
            $dep = trim((string) ($fila['nombre_deporte'] ?? ''));
            if ($dep === '') $dep = 'Sin deporte';
            $dep_key = strtolower($dep);

            $cat = trim((string) ($fila['nombre_categoria'] ?? ''));
            if (($fila['genero_categoria'] ?? '') !== '') {
                $cat = trim($cat . ' (' . $fila['genero_categoria'] . ')');
            }
            if ($cat === '') $cat = 'Única';
            $cat_key = (int) ($fila['id_categoria'] ?? 0) ?: strtolower($dep . '|' . $cat);

            $por_deporte[$dep_key]['nombre'] = $dep;
            $por_deporte[$dep_key]['cats'][$cat_key]['nombre'] = $cat;
            $por_deporte[$dep_key]['cats'][$cat_key]['filas'][] = $fila;
        }
        ksort($por_deporte);

        // ===== COLORES (misma paleta que el resto del reporte) =====
        $c_primary   = '#1e3a5f';
        $c_secondary = '#3498db';
        $c_light     = '#ecf0f1';
        $c_border    = '#bdc3c7';
        $c_text      = '#2c3e50';
        $c_muted     = '#7f8c8d';
        $c_white     = '#ffffff';
        $c_alt       = '#f8f9fa';

        $dias_completos = array('Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado');

        /** Texto horario "HH:MM – HH:MM". */
        $horario_txt = function ($f) {
            $hi = substr((string) ($f['hora_inicio'] ?? ''), 0, 5);
            $hf = substr((string) ($f['hora_fin'] ?? ''), 0, 5);
            return $hi . (($hf !== '' && $hf !== '00:00') ? ' – ' . $hf : '');
        };

        /** Día bonito: "SÁB 07/11" (para poder leer la fecha dentro de la fila). */
        $dia_bonito = function ($f) use ($dias_completos, $esc) {
            $iso = substr((string) ($f['fecha_competencia'] ?? ''), 0, 10);
            if ($iso === '') return '&nbsp;';
            $ts = strtotime($iso);
            if ($ts === FALSE) return $esc($iso);
            $nom = strtoupper($dias_completos[(int) date('w', $ts)]);
            return $nom . '<br/><span style="font-size:6pt;">' . date('d/m/Y', $ts) . '</span>';
        };

        /** Lado del enfrentamiento como HTML. Con integrantes cargados por el
         *  modelo (jugadores_ute_X) se imprime el EQUIPO en negrita y debajo los
         *  INTEGRANTES achicados/muted; si no, se despeja el prefijo
         *  "Equipo: J1, J2" que a veces arma el modelo sobre el nombre. */
        $lado_html = function ($f, $lado) {
            $modalidad = strtoupper(trim((string) ($f['modalidad_competencia'] ?? '')));
            $es_equipo = $modalidad === 'ENFRENTAMIENTO';
            $nombre = trim((string) ($f['ute_' . $lado . '_nombre'] ?? ''));

            // Integrantes que trae el modelo en una lista aparte.
            $crudos = array();
            foreach ((array) ($f['jugadores_ute_' . $lado] ?? array()) as $j) {
                $j = trim((string) $j);
                if ($j !== '') $crudos[] = $j;
            }
            if ($crudos) {
                $equipo = $nombre;
                if (strpos($equipo, ': ') !== FALSE) {
                    $eq = trim(substr($equipo, 0, strpos($equipo, ': ')));
                    if ($eq !== '') $equipo = $eq;
                }
                $out = array();
                foreach ($crudos as $j) {
                    $out[] = $this->nombres_completos ? $j : self::_nombre_corto($j);
                }
                $html = '';
                if ($equipo !== '') $html .= '<b>' . htmlspecialchars($equipo, ENT_QUOTES, 'UTF-8') . '</b><br/>';
                $html .= '<span style="color:#7f8c8d; font-size:6pt; font-style:italic;">'
                       . htmlspecialchars(implode(' - ', $out), ENT_QUOTES, 'UTF-8') . '</span>';
                return $html;
            }

            if ($nombre === '') return '';
            $equipo = $nombre;
            $integrantes = array();
            if ($es_equipo && strpos($nombre, ': ') !== FALSE) {
                $pos = strpos($nombre, ': ');
                $eq = trim(substr($nombre, 0, $pos));
                if ($eq !== '') $equipo = $eq;
                $integrantes = array_values(array_filter(array_map('trim', explode(',', trim(substr($nombre, $pos + 2))))));
            }
            if (!$es_equipo || !$integrantes) {
                return $this->nombres_completos ? htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8')
                                                : htmlspecialchars(self::_nombre_corto($nombre), ENT_QUOTES, 'UTF-8');
            }
            $html = '<b>' . htmlspecialchars($equipo, ENT_QUOTES, 'UTF-8') . '</b>';
            $out = array();
            foreach ($integrantes as $j) {
                $out[] = $this->nombres_completos ? $j : self::_nombre_corto($j);
            }
            $html .= '<br/><span style="color:#7f8c8d; font-size:6pt; font-style:italic;">'
                   . htmlspecialchars(implode(' - ', $out), ENT_QUOTES, 'UTF-8') . '</span>';
            return $html;
        };

        /** Celda "ENFRENTAMIENTO": A vs B (enfrentamiento) o lista de
         *  competidores (largada única).
         *  Además se CONCATENA el "Nombre de la prueba" (campo nombre_prueba,
         *  el mismo que se carga al crear el partido y que aparece como
         *  "Prueba:" en las planillas de mesa de control). Se pide así porque
         *  muchas veces en ese nombre se anotan datos del cruce —cancha, mesa,
         *  serie, "GRAN FINAL", etc.— y sin él la columna queda coja.
         *  Se imprime arriba del enfrentamiento, en una línea chica/muted para
         *  no competir con los nombres de los equipos, y SOLO si el partido lo
         *  tiene cargado (si viene vacío, la celda se ve igual que antes). */
        $vs_html = function ($f) use ($lado_html, $esc) {
            $es_masivo = self::_bloque_es_masivo(array($f));
            $l1 = $lado_html($f, 1);
            $l2 = $lado_html($f, 2);

            // Nombre de la prueba concatenado como encabezado de la celda.
            

            if ($es_masivo) {
                // Largada única: no hay "vs"; se listan los competidores del slot.
                $todos = array();
                foreach (array($l1, $l2) as $x) if ($x !== '') $todos[] = $x;
                return $prueba_html
                    . ($todos ? implode(', ', $todos) : '<span style="color:#7f8c8d;">Jornada masiva</span>');
            }
            if ($l1 !== '' && $l2 !== '') {
                return $prueba_html
                    . $l1 . ' &nbsp;<span style="color:#95a5a6; font-weight:bold;">vs</span>&nbsp; ' . $l2;
            }
            if ($l1 !== '' || $l2 !== '') {
                return $prueba_html . ($l1 !== '' ? $l1 : $l2);
            }
            return $prueba_html . '<span style="color:#7f8c8d;">Por definir</span>';
        };

        // ====== ÍNDICE DE COLORES POR CATEGORÍA (global al reporte) ======
        // Así una categoría mantiene SU color aunque el deporte ocupe varias
        // hojas. Los deportes de largada única no consumen color.
        $colores_cat = array();
        $sig_color = 0;
        foreach ($por_deporte as $dk => $bloque) {
            foreach ($bloque['cats'] as $ck => $cat) {
                if (self::_bloque_es_masivo($cat['filas'])) continue;
                if (!isset($colores_cat[$ck])) $colores_cat[$ck] = self::_color_categoria($sig_color++);
            }
        }

        $primer_deporte = TRUE;
        foreach ($por_deporte as $dk => $bloque) {
            // Cada deporte arranca en hoja nueva (menos el primero, que usa la
            // página que ya abrió el script al final de la plantilla).
            if (!$primer_deporte) {
                $this->AddPage('L', 'LEGAL');
            }
            $primer_deporte = FALSE;

            $total_partidos = 0;
            foreach ($bloque['cats'] as $cat) $total_partidos += count($cat['filas']);
            $deporte_masivo = FALSE;
            foreach ($bloque['cats'] as $cat) {
                if (self::_bloque_es_masivo($cat['filas'])) $deporte_masivo = TRUE;
            }

            // ---------- Título del deporte ----------
            // (sin emoji: TCPDF con DejaVu no renderiza bien los pictogramas)
            $html = '<div style="text-align:left; font-size:15pt; font-weight:bold; color:' . $c_primary . '; padding:2px 0 2px 0;">'
                  . strtoupper($esc($bloque['nombre']))
                  . '</div>'
                  . '<div style="text-align:left; font-size:8pt; color:' . $c_muted . '; padding-bottom:6px;">'
                  . count($bloque['cats']) . ' categoría/s · ' . $total_partidos . ' partido/s'
                  . ($this->delegacion_filtro ? ' · Delegación ' . $esc($this->delegacion_filtro) : '')
                  . ($deporte_masivo ? ' · <i>Largada única: sin colores por categoría</i>' : '')
                  . '</div>';

            // ---------- Leyenda de categorías (solo enfrentamientos) ----------
            $leyenda = '';
            foreach ($bloque['cats'] as $ck => $cat) {
                if (!isset($colores_cat[$ck])) continue; // masivo: va sin color
                list($bg, $fg) = $colores_cat[$ck];
                $leyenda .= '<span style="display:inline-block; margin-right:10px; padding:2px 6px;'
                          . ' background-color:' . $bg . '; color:' . $fg . '; border:1px solid ' . $fg . ';'
                          . ' font-size:7pt; font-weight:bold;">' . $esc($cat['nombre']) . '</span>';
            }
            if ($leyenda !== '') {
                $html .= '<div style="padding-bottom:8px;">' . $leyenda . '</div>';
            }

            // ---------- Tabla del deporte ----------
            $html .= '<table cellpadding="4" cellspacing="0" border="1" style="border-color:' . $c_border
                  . '; border-collapse:collapse; width:100%; font-size:7.5pt; color:' . $c_text . ';">'
                  . '<thead><tr style="background-color:' . $c_primary . '; color:' . $c_white . ';">'
                  . '<th style="width:16%; text-align:center; border:1px solid ' . $c_border . ';">DÍA</th>'
                  . '<th style="width:9%;  text-align:center; border:1px solid ' . $c_border . ';">HORA</th>'
                  . '<th style="width:16%; text-align:center; border:1px solid ' . $c_border . ';">CATEGORÍA</th>'
                  . '<th style="width:14%; text-align:center; border:1px solid ' . $c_border . ';">FASE / JORNADA</th>'
                  . '<th  style="width:35%;text-align:center; border:1px solid ' . $c_border . ';">ENFRENTAMIENTO</th>'
                  . '<th style="width:10%; text-align:center; border:1px solid ' . $c_border . ';">LUGAR</th>'
                  . '</tr></thead><tbody>';

            $i = 0;
            foreach ($bloque['cats'] as $ck => $cat) {
                $es_masivo = self::_bloque_es_masivo($cat['filas']);
                // Color de la categoría SOLO en enfrentamientos; en largada
                // única la fila va neutra (no hace falta diferenciar por color).
                $color = (!$es_masivo && isset($colores_cat[$ck])) ? $colores_cat[$ck] : null;

                foreach ($cat['filas'] as $f) {
                    $bg_fila = $color ? $color[0] : (($i % 2) ? $c_white : $c_alt);
                    $borde_izq = $color
                        ? 'border-left:4px solid ' . $color[1] . ';'
                        : 'border-left:1px solid ' . $c_border . ';';
                    $html .= '<tr style="background-color:' . $bg_fila . ';">';

                    // DÍA
                    $html .= '<td style="width:16%;' . $borde_izq . ' border-top:1px solid ' . $c_border . '; border-right:1px solid ' . $c_border . '; border-bottom:1px solid ' . $c_border . '; text-align:center;">'
                          . $dia_bonito($f) . '</td>';

                    // HORA
                    $html .= '<td style="width:9%;border:1px solid ' . $c_border . '; text-align:center; white-space:nowrap;">'
                          . $esc($horario_txt($f)) . '</td>';

                    // CATEGORÍA (chapa de color en los enfrentamientos)
                    if ($color) {
                        $etiqueta = '<span style="display:inline-block; width:8px; height:8px; background-color:'
                                  . $color[1] . '; border:1px solid ' . $color[1] . ';"></span> '
                                  . '<b style="color:' . $color[1] . ';">' . $esc($cat['nombre']) . '</b>';
                    } else {
                        // Largada única: basta el nombre del deporte/categoría sin color.
                        $etiqueta = $esc($cat['nombre']);
                    }
                    $html .= '<td style="width:16%;border:1px solid ' . $c_border . ';">' . $etiqueta . '</td>';

                    // FASE / JORNADA
                    
                    $prueba = trim((string) ($f['nombre_prueba'] ?? ''));
                    
                    $html .= '<td style="width:14%;border:1px solid ' . $c_border . '; font-size:7pt;">' . $esc($prueba).'</td>';

                    // ENFRENTAMIENTO / LARGADA
                    $html .= '<td style="width:35%;border:1px solid ' . $c_border . ';">' . $vs_html($f) . '</td>';

                    // LUGAR
                    $html .= '<td style="width:10%;border:1px solid ' . $c_border . '; font-size:7pt; text-align:center;">'
                          . (!empty($f['lugar_nombre']) ? $esc($f['lugar_nombre']) : '<span style="color:' . $c_muted . '">—</span>')
                          . '</td>';

                    $html .= '</tr>';
                    $i++;
                }
            }
            $html .= '</tbody></table>';

            $this->writeHTML($html, true, false, true, false, '');
        }

        if (empty($por_deporte)) {
            $this->writeHTML('<p style="text-align:center; font-size:10pt; color:' . $c_muted . ';">No hay partidos cargados.</p>',
                true, false, true, false, '');
        }
    }

    /**
     * ===== FORMATO PLANILLA (mesas de control) =====
     * UNA CATEGORÍA POR HOJA: las filas llegan agrupadas por categoría (puede
     * venir más de una cuando la planilla se descarga por deporte, desde
     * Inscripciones/descargar_planilla_deporte). Para cada categoría se abre
     * una hoja nueva (AddPage) con su propio título y su planilla:
     *   - Deportes de enfrentamiento: "Equipo A (integrantes) vs Equipo B
     *     (integrantes)" + casilleros GANADOR / MARCADOR.
     *   - Deportes masivos (JORNADA_UNICA): lista de PARTICIPANTES con
     *     casilleros POS / TIEMPO o PUNTAJE.
     */
    public function _body_planilla() {
        // Orden de la planilla. En deportes de ENFRENTAMIENTO DE EQUIPOS la
        // unidad de agrupamiento es la JORNADA (numero_fecha): las filas se
        // ordenan primero por número de jornada y, dentro de cada una, por
        // fecha/hora — así el subencabezado "Jornada N" agrupa TODOS sus
        // partidos aunque ocurran en días distintos o a horas intercaladas
        // con otras jornadas (p. ej. Grupo C en CANCHA 4 y CANCHA 5).
        // Para el resto de los deportes se conserva el orden cronológico
        // (fecha → hora → jornada).
        $_es_enfrentamiento = FALSE;
        foreach ($this->datos as $f) {
            if (strtoupper(trim((string) ($f['modalidad_competencia'] ?? ''))) === 'ENFRENTAMIENTO') {
                $_es_enfrentamiento = TRUE;
                break;
            }
        }
        if ($_es_enfrentamiento) {
            usort($this->datos, function ($a, $b) {
                return [
                    (int) ($a['numero_fecha'] ?? 0),
                    (string) ($a['fecha_competencia'] ?? ''),
                    (string) ($a['hora_inicio'] ?? ''),
                    (int) ($a['id_fixture'] ?? 0),
                ] <=> [
                    (int) ($b['numero_fecha'] ?? 0),
                    (string) ($b['fecha_competencia'] ?? ''),
                    (string) ($b['hora_inicio'] ?? ''),
                    (int) ($b['id_fixture'] ?? 0),
                ];
            });
        } else {
            // Orden cronológico: fecha → hora → jornada
            usort($this->datos, function ($a, $b) {
                return [
                    (string) ($a['fecha_competencia'] ?? ''),
                    (string) ($a['hora_inicio'] ?? ''),
                    (int) ($a['numero_fecha'] ?? 0),
                    (int) ($a['id_fixture'] ?? 0),
                ] <=> [
                    (string) ($b['fecha_competencia'] ?? ''),
                    (string) ($b['hora_inicio'] ?? ''),
                    (int) ($b['numero_fecha'] ?? 0),
                    (int) ($b['id_fixture'] ?? 0),
                ];
            });
        }

        // Agrupar las filas por categoría conservando el orden cronológico
        // (primera aparición = orden de las hojas del PDF).
        $grupos = array();
        foreach ($this->datos as $f) {
            $clave = (int) ($f['id_categoria'] ?? 0) ?: ('nom:' . strtolower((string) ($f['nombre_categoria'] ?? '')));
            if (!isset($grupos[$clave])) $grupos[$clave] = array();
            $grupos[$clave][] = $f;
        }
        if (!$grupos) $grupos = array(array());

        $_primero = TRUE;
        foreach ($grupos as $filas) {
            if (!$_primero) {
                // Cada categoría arranca en una hoja nueva.
                $this->AddPage('P', 'A4');
            }
            $_primero = FALSE;
            $this->_planilla_categoria($filas);
        }
    }

    /**
     * Planilla de mesa de control de UNA categoría (una hoja del PDF).
     * Contenido idéntico al que antes generaba _body_planilla() para una sola
     * categoría; ahora recibe solo las filas de esa categoría en $datos.
     */
    public function _planilla_categoria($datos_categoria) {
        $esc = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };

        $datos_prev = $this->datos;
        $this->datos = $datos_categoria;

        /**
         * ¿Planilla de deporte MASIVO? Se decide mirando TODAS las filas de la
         * categoría, no solo la primera: basta que una jornada sea
         * JORNADA_UNICA o que el deporte sea MASIVO_TIEMPO. Antes se leía
         * únicamente $datos[0], y si el orden cronológico ponía adelante otra
         * fila (o el campo llegaba con otro formato), la planilla masiva caía
         * al formato de partidos y NO imprimía la lista de participantes para
         * cargar tiempo/puntaje.
         */
        $es_masivo = FALSE;
        foreach ($this->datos as $f) {
            $modalidad = strtoupper(trim((string) ($f['modalidad_competencia'] ?? '')));
            $fase      = strtoupper(trim((string) ($f['fase'] ?? '')));
            if ($modalidad === 'MASIVO_TIEMPO' || $fase === 'JORNADA_UNICA') {
                $es_masivo = TRUE;
                break;
            }
        }

        $fase_bonito = function ($fase) {
            $map = array(
                'GRUPO' => 'Fase de Grupos', '16AVOS' => '16avos de Final', 'OCTAVOS' => 'Octavos de Final',
                'CUARTOS' => 'Cuartos de Final', 'SEMIFINAL' => 'Semifinal', 'TERCER_PUESTO' => '3er Puesto',
                'FINAL' => 'Final', 'JORNADA_UNICA' => 'Jornada Única'
            );
            return isset($map[$fase]) ? $map[$fase] : (string) $fase;
        };

        // Título de la hoja: nombre de la categoría (Deporte Categoría). Si el
        // controlador lo proveyó se usa titulo_planilla (caso histórico de
        // planilla descargada por categoría); en la planilla por deporte se
        // compone una etiqueta por categoría, prefijada con el nombre del
        // deporte cuando viene en $this->deporte_planilla.
        $fila_ref = !empty($this->datos) ? $this->datos[0] : array();
        $titulo_cat = trim(($fila_ref['nombre_deporte'] ?? '') . ' ' . ($fila_ref['nombre_categoria'] ?? ''));
        if ($titulo_cat === '' && $this->deporte_planilla) {
            $titulo_cat = trim((string) $this->deporte_planilla);
        }
        if ($this->titulo_planilla) {
            $titulo = $this->titulo_planilla;
        } elseif ($this->deporte_planilla) {
            // Planilla por deporte: una hoja por categoría con su propio título.
            $titulo = $titulo_cat !== ''
                ? strtoupper($titulo_cat)
                : ('PLANILLA — ' . strtoupper((string) $this->deporte_planilla));
        } else {
            $titulo = $titulo_cat !== ''
                ? strtoupper($titulo_cat)
                : ('PLANILLA DE MESA DE CONTROL — ' . NOMBRE_META);
        }
        $html = '<div style="text-align:right; font-size:15pt; font-weight:bold; color:#1e3a5f; padding:2px 0 4px 0;">'
              . $esc($titulo) . '</div>';
              
        $this->writeHTMLCell(0, 70, 300, 15, $html, 0, 1, 0, true, 'J', true);
        $html = "";
        /**
         * TRAMPITA DE LAS JORNADAS (solo planillas de mesa de control):
         * numero_fecha se usa como divisor de grupos. La regla es:
         *   - Si TODOS los partidos de la jornada están en fase 'GRUPO', el
         *     subencabezado dice "Grupo A" (jornada 1), "Grupo B" (jornada 2),
         *     ... (con letras sucesivas).
         *   - Si en esa jornada NO hay ningún partido en fase 'GRUPO' (o sea,
         *     arrancaron cruces de eliminación directa), se reemplaza la
         *     palabra "Jornada" por "Eliminatorias".
         *   - Si la jornada tiene GRUPO mezclado con otras fases, conserva la
         *     etiqueta clásica "Jornada N".
         */
        $_etiqueta_jornada = function ($num_jornada) {
            $hay_grupo = FALSE;
            $todas_grupo = TRUE;
            foreach ($this->datos as $fx) {
                if ((int) ($fx['numero_fecha'] ?? 0) !== $num_jornada) continue;
                if (strtoupper(trim((string) ($fx['fase'] ?? ''))) === 'GRUPO') {
                    $hay_grupo = TRUE;
                } else {
                    $todas_grupo = FALSE;
                }
            }
            // Jornada 1 → Grupo A, 2 → B, ... (si se agotan las Z, sigue AA...).
            $letra_grupo = function ($n) {
                static $abecedario = array('A','B','C','D','E','F','G','H','I','J','K','L','M',
                                           'N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
                if ($n >= 1 && $n <= count($abecedario)) return $abecedario[$n - 1];
                return chr(64 + intdiv($n - 1, 26)) . '-' . $abecedario[($n - 1) % 26];
            };
            if ($todas_grupo && $hay_grupo && $num_jornada > 0) {
                return 'Grupo ' . $letra_grupo($num_jornada);
            }
            if (!$hay_grupo && !$todas_grupo) {
                // Ningún partido de esta jornada es de fase GRUPOS.
                return $num_jornada > 0 ? 'Eliminatorias ' . $num_jornada : 'Eliminatorias';
            }
            return $num_jornada > 0 ? 'Jornada ' . $num_jornada : 'Jornada';
        };

        $borde = 'border:1px solid #bdc3c7;';
        $c_head = '#1e3a5f';

        if ($es_masivo) {
            // ---- Planilla de participantes (una sola jornada) ----
            // Fuente principal: Resultado_model::obtener_competidores_por_categoria,
            // el MISMO criterio que usa la carga de resultados en pantalla:
            //   - si la categoría tiene UTEs/equipos → los equipos;
            //   - si NO tiene UTEs → los inscriptos individuales (que antes no
            //     salían nunca porque solo se leía $f['utes_categoria'], siempre
            //     vacío en ese caso, y la jornada masiva no tiene slots id_ute).
            $participantes = array();
            // Integrantes de cada competidor, para mostrar "Equipo X (Ana Pérez,
            // Juan Gómez)" en deportes por equipos como pesca. Clave = misma que
            // la de $participantes.
            $integrantes_map = array();
            $competidores_resueltos = FALSE;
            $id_categoria_planilla = 0;
            foreach ($this->datos as $chk) {
                if (!empty($chk['id_categoria'])) {
                    $id_categoria_planilla = (int) $chk['id_categoria'];
                    break;
                }
            }
            /**
             * IMPORTANTE (causa del HTTP 500 en jornadas únicas): esta vista se
             * renderiza DENTRO del flujo de Output() de TCPDF. Cualquier echo /
             * warning / fatal generado acá queda embebido en el stream binario
             * del PDF y rompe el render ('D' entrega basura → error del navegador).
             * Por eso todo el tramo de models va envuelto en try/catch y con los
             * errores de display desactivados: si algo falla, la planilla igual
             * se imprime usando los participantes que traen las filas del fixture.
             */
            if ($id_categoria_planilla > 0) {
                $_display_errors_prev = @ini_set('display_errors', '0');
                $_error_reporting_prev = error_reporting(0);
                try {
                    // OJO: MYPDF extiende TCPDF, NO CI_Controller — `$this->load`
                    // no existe acá y provocaba error fatal (HTTP 500). Los
                    // modelos se toman del controller instance. Además pueden
                    // venir YA cargados por el controlador: cargarlos de nuevo
                    // en CI3 emitía "Duplicate property" (ruido que corrompía el
                    // PDF), así que solo se carga si falta.
                    $CI =& get_instance();
                    if (!isset($CI->Resultado_model)) {
                        $CI->load->model('Resultado_model');
                    }
                    $met = 'obtener_competidores_por_categoria';
                    if (isset($CI->Resultado_model) && method_exists($CI->Resultado_model, $met)) {
                        foreach ((array) $CI->Resultado_model->$met($id_categoria_planilla) as $c) {
                            $nombre = trim((string) ($c['nombre'] ?? ''));
                            if ($nombre === '') continue;
                            $clave = (int) ($c['id'] ?? 0);
                            if (!$clave) $clave = 'n:' . mb_strtolower($nombre, 'UTF-8');
                            $participantes[$clave] = $nombre;
                            // Equipos/duplicas (EQUIPO): traer los integrantes ya
                            // resueltos por el modelo ('integrantes' como string
                            // 'Ana, Juan', o como array de filas con nombre_completo).
                            if (($c['tipo'] ?? '') === 'EQUIPO' || !empty($c['cantidad'])) {
                                $miembros_raw = $c['integrantes'] ?? '';
                                $miembros = array();
                                if (is_array($miembros_raw)) {
                                    foreach ($miembros_raw as $m) {
                                        $mn = trim((string) (is_array($m) ? ($m['nombre_completo'] ?? '') : $m));
                                        if ($mn !== '') $miembros[] = $mn;
                                    }
                                } elseif (trim((string) $miembros_raw) !== '') {
                                    foreach (explode(',', (string) $miembros_raw) as $mn) {
                                        $mn = trim($mn);
                                        if ($mn !== '') $miembros[] = $mn;
                                    }
                                }
                                if ($miembros) $integrantes_map[$clave] = $miembros;
                            }
                        }
                        $competidores_resueltos = TRUE;
                    }
                } catch (Throwable $e) {
                    // Silencioso a propósito: la planilla no debe morir por esto.
                    $competidores_resueltos = FALSE;
                }
                error_reporting($_error_reporting_prev);
                @ini_set('display_errors', $_display_errors_prev);
            }

            // Fallback / consolidación con lo que trae cada fila del fixture.
            foreach ($this->datos as $f) {
                foreach ((array) ($f['utes_categoria'] ?? array()) as $u) {
                    if (!empty($u['id_ute'])) $participantes[(int) $u['id_ute']] = $u['nombre_ute'];
                }
                if (!empty($f['id_ute_1']) && !empty($f['ute_1_nombre'])) {
                    $participantes[(int) $f['id_ute_1']] = $f['ute_1_nombre'];
                }
                if (!empty($f['id_ute_2']) && !empty($f['ute_2_nombre'])) {
                    $participantes[(int) $f['id_ute_2']] = $f['ute_2_nombre'];
                }
            }
            // Si el modelo no devolvió nada y las filas tampoco traen competidores
            // (jornada masiva con id_ute_1/id_ute_2 en NULL), última chance:
            // individuales de la categoría vía Fixture_model.
            if (!$participantes && !$competidores_resueltos && $id_categoria_planilla > 0) {
                $_display_errors_prev = @ini_set('display_errors', '0');
                $_error_reporting_prev = error_reporting(0);
                try {
                    $CI =& get_instance();
                    if (!isset($CI->Fixture_model)) {
                        $CI->load->model('Fixture_model');
                    }
                    if (isset($CI->Fixture_model) && method_exists($CI->Fixture_model, 'individuales_de_categoria')) {
                        foreach ((array) $CI->Fixture_model->individuales_de_categoria($id_categoria_planilla) as $p) {
                            $nombre = trim((string) ($p['nombre_completo'] ?? ''));
                            if ($nombre !== '') $participantes['p:' . $nombre] = $nombre;
                        }
                    }
                } catch (Throwable $e) {
                    // Sin individuales disponibles: igual se imprime la planilla vacía.
                }
                error_reporting($_error_reporting_prev);
                @ini_set('display_errors', $_display_errors_prev);
            }
            asort($participantes);

            $fila_partido = $this->datos[0];

            // --- Integrantes pendientes de resolver (equipos que llegaron por el
            // fallback del fixture y no tienen integrantes en el mapa). Se resuelven
            // con Fixture_model::integrantes_de_ute(), igual que la planilla de
            // partidos. Todo protegido: si falla, se imprime sin los () igualmente.
            $utas_sin_integrantes = array();
            foreach ($participantes as $clave => $_n) {
                if (is_int($clave) && $clave > 0 && !isset($integrantes_map[$clave])) {
                    $utas_sin_integrantes[] = $clave;
                }
            }
            if ($utas_sin_integrantes) {
                $_display_errors_prev = @ini_set('display_errors', '0');
                $_error_reporting_prev = error_reporting(0);
                try {
                    $CI =& get_instance();
                    if (!isset($CI->Fixture_model)) {
                        $CI->load->model('Fixture_model');
                    }
                    if (isset($CI->Fixture_model) && method_exists($CI->Fixture_model, 'integrantes_de_ute')) {
                        foreach (array_unique($utas_sin_integrantes) as $uid) {
                            $miembros = array();
                            foreach ((array) $CI->Fixture_model->integrantes_de_ute($uid) as $m) {
                                $mn = trim((string) (is_array($m) ? ($m['nombre_completo'] ?? '') : $m));
                                if ($mn !== '') $miembros[] = $mn;
                            }
                            if ($miembros) $integrantes_map[$uid] = $miembros;
                        }
                    }
                } catch (Throwable $e) {
                    // Sin integrantes: la planilla sale igual, solo con el nombre del equipo.
                }
                error_reporting($_error_reporting_prev);
                @ini_set('display_errors', $_display_errors_prev);
            }

            $html = '<table cellpadding="4" cellspacing="0" border="0" style="width:100%; font-size:8pt; color:#2c3e50; margin-bottom:6px;">'
                   . '<tr>'
                   . '<td style="width:50%;"><b>Prueba:</b> ' . $esc($fila_partido['nombre_prueba'] ?? '') . '</td>'
                   . '<td style="width:50%;"><b>Fecha/Hora:</b> ' . $esc(date('d/m/Y', strtotime((string) ($fila_partido['fecha_competencia'] ?? 'now'))) . ' · ' . substr((string) ($fila_partido['hora_inicio'] ?? ''), 0, 5)) . '</td>'
                   . '</tr><tr>'
                   . '<td><b>Lugar:</b> ' . $esc($fila_partido['lugar_nombre'] ?? 'A confirmar') . '</td>'
                   . '<td><b>Participantes:</b> ' . count($participantes) . '</td>'
                   . '</tr></table>';

            $html .= '<table cellpadding="4" cellspacing="0" border="1" style="border-color:#bdc3c7; border-collapse:collapse; width:100%; font-size:8.5pt; color:#2c3e50;">'
                   . '<thead><tr style="background-color:' . $c_head . '; color:#ffffff;">'
                   . '<th style="' . $borde . ' text-align:center; width:8%;">N°</th>'
                   . '<th style="' . $borde . ' text-align:center; width:50%;">PARTICIPANTE</th>'
                   . '<th style="' . $borde . ' text-align:center; width:42%;">TIEMPO / PUNTAJE</th>'
                   . '</tr></thead><tbody>';
            $i = 0;
            foreach ($participantes as $clave => $nombre) {
                $bg = ($i % 2) ? '#ffffff' : '#f8f9fa';
                // Equipos/duplicas: integrantes entre paréntesis debajo del nombre
                // ("Los Pescadores" / "Ana Pérez, Juan Gómez"), como en la planilla
                // de partidos. Los individuales salen sin nada extra.
                $celular = $esc($nombre);
                if (!empty($integrantes_map[$clave])) {
                    // Los integrantes se separan con " - " (no con coma): en la
                    // base de datos los nombres se guardan como "Apellido, nombre"
                    // y usando comas no se distinguía dónde terminaba un
                    // participante y dónde empezaba el siguiente. Además cada
                    // nombre se normaliza a "Nombre Apellido" para que la coma
                    // interna del formato de la BD no vuelva a confundirse con
                    // un separador.
                    $norm = array_map(array('MYPDF', '_nombre_normalizado'), $integrantes_map[$clave]);
                    $celular .= '<br><span style="font-size:7.5pt; color:#7f8c8d;">('
                              . $esc(implode(' - ', $norm)) . ')</span>';
                }
                $html .= '<tr style="background-color:' . $bg . ';">'
                       . '<td style="' . $borde . ' text-align:center; width:8%;"></td>'
                       . '<td style="' . $borde . ' width:50%;">' . $celular . '</td>'
                       . '<td style="' . $borde . ' width:42%;"></td>'
                       . '</tr>';
                $i++;
            }
            if (!$i) {
                $html .= '<tr><td colspan="3" style="' . $borde . ' text-align:center; color:#7f8c8d;">No hay participantes inscriptos en esta categoría.</td></tr>';
            }
            // Filas vacías extra: por si en la mesa hay que anotar inscriptos
            // de último momento (no modifica el reporte, solo la planilla).
            for ($v = 0; $v < self::$filas_vacias_planilla; $v++) {
                $bg = (($i + $v) % 2) ? '#ffffff' : '#f8f9fa';
                $html .= '<tr style="background-color:' . $bg . ';">'
                       . '<td style="' . $borde . ' text-align:center; width:8%;"></td>'
                       . '<td style="' . $borde . ' width:50%;">&nbsp;</td>'
                       . '<td style="' . $borde . ' width:42%;"></td>'
                       . '</tr>';
            }
            $html .= '</tbody></table>';
        } else {
            $fila_partido = $this->datos[0];

            // ---- Mini tabla de equipos de la categoría (solo deportes de
            // enfrentamiento de EQUIPOS). Se imprime ANTES del encabezado de la
            // planilla, con dos columnas (EQUIPO / INTEGRANTES), para ahorrar
            // espacio: así las celdas EQUIPO A / EQUIPO B de abajo llevan solo
            // el nombre del equipo, sin repetir los integrantes en cada fila.
            $modalidad_ref = strtoupper(trim((string) ($fila_partido['modalidad_competencia'] ?? '')));
            $equipos_tabla = array();   // clave => nombre del equipo
            $integrantes_eq = array();   // clave => array de nombres
            if ($modalidad_ref === 'ENFRENTAMIENTO') {
                $id_cat_equipos = 0;
                foreach ($this->datos as $chk) {
                    if (!empty($chk['id_categoria'])) {
                        $id_cat_equipos = (int) $chk['id_categoria'];
                        break;
                    }
                }
                // Fuente principal: los equipos de la categoría ya resueltos por
                // Resultado_model (mismo criterio que usa la planilla masiva).
                // Todo protegido igual que allí: cualquier salida/ruido dentro
                // del render de TCPDF corrompería el PDF.
                if ($id_cat_equipos > 0) {
                    $_display_errors_prev = @ini_set('display_errors', '0');
                    $_error_reporting_prev = error_reporting(0);
                    try {
                        $CI =& get_instance();
                        if (!isset($CI->Resultado_model)) {
                            $CI->load->model('Resultado_model');
                        }
                        if (isset($CI->Resultado_model) && method_exists($CI->Resultado_model, 'obtener_competidores_por_categoria')) {
                            foreach ((array) $CI->Resultado_model->obtener_competidores_por_categoria($id_cat_equipos) as $c) {
                                if (strtoupper(trim((string) ($c['tipo'] ?? ''))) !== 'EQUIPO') continue;
                                $nombre_eq = trim((string) ($c['nombre'] ?? ''));
                                if ($nombre_eq === '') continue;
                                $clave_eq = (int) ($c['id'] ?? 0) ?: ('n:' . mb_strtolower($nombre_eq, 'UTF-8'));
                                $equipos_tabla[$clave_eq] = $nombre_eq;
                                $miembros_raw = $c['integrantes'] ?? '';
                                $miembros = array();
                                if (is_array($miembros_raw)) {
                                    foreach ($miembros_raw as $m) {
                                        $mn = trim((string) (is_array($m) ? ($m['nombre_completo'] ?? '') : $m));
                                        if ($mn !== '') $miembros[] = $mn;
                                    }
                                } elseif (trim((string) $miembros_raw) !== '') {
                                    foreach (explode(',', (string) $miembros_raw) as $mn) {
                                        $mn = trim($mn);
                                        if ($mn !== '') $miembros[] = $mn;
                                    }
                                }
                                if ($miembros) $integrantes_eq[$clave_eq] = $miembros;
                            }
                        }
                    } catch (Throwable $e) {
                        // Silencioso a propósito: la planilla no debe morir por esto.
                    }
                    error_reporting($_error_reporting_prev);
                    @ini_set('display_errors', $_display_errors_prev);
                }
                // Fallback: equipos que aparecen en las filas del fixture y no
                // estaban en el mapa (con integrantes desde jugadores_ute_X).
                foreach ($this->datos as $f) {
                    foreach (array(1, 2) as $lado) {
                        $eq = trim((string) ($f['ute_' . $lado . '_nombre'] ?? ''));
                        if ($eq === '') continue;
                        $clave_eq = !empty($f['id_ute_' . $lado]) ? (int) $f['id_ute_' . $lado] : 'n:' . mb_strtolower($eq, 'UTF-8');
                        if (isset($equipos_tabla[$clave_eq])) continue;
                        $equipos_tabla[$clave_eq] = $eq;
                        $miembros = array();
                        foreach ((array) ($f['jugadores_ute_' . $lado] ?? array()) as $j) {
                            $j = trim((string) $j);
                            if ($j !== '') $miembros[] = $this->nombres_completos ? $j : self::_nombre_corto($j);
                        }
                        if ($miembros) $integrantes_eq[$clave_eq] = $miembros;
                    }
                }
                asort($equipos_tabla);
            }

            if ($equipos_tabla) {
                $html = '<table cellpadding="4" cellspacing="0" border="1" style="border-color:#bdc3c7; border-collapse:collapse; width:100%; font-size:8pt; color:#2c3e50; margin-bottom:6px;">'
                       . '<thead><tr style="background-color:' . $c_head . '; color:#ffffff;">'
                       . '<th style="' . $borde . ' text-align:center; width:30%;">EQUIPO</th>'
                       . '<th style="' . $borde . ' text-align:center; width:70%;">INTEGRANTES</th>'
                       . '</tr></thead><tbody>';
                $k = 0;
                foreach ($equipos_tabla as $clave_eq => $nombre_eq) {
                    $bg = ($k % 2) ? '#ffffff' : '#f8f9fa';
                    $html .= '<tr style="background-color:' . $bg . ';">'
                           . '<td style="' . $borde . ' width:30%;"><b>' . $esc($nombre_eq) . '</b></td>'
                           . '<td style="' . $borde . ' width:70%;">'
                           . (isset($integrantes_eq[$clave_eq])
                                // Integrantes separados con " - " y cada nombre
                                // normalizado a "Nombre Apellido" (en la BD se
                                // guarda "Apellido, nombre" y esa coma interna
                                // confundía al lector).
                                ? $esc(implode(' - ', array_map(array('MYPDF', '_nombre_normalizado'), $integrantes_eq[$clave_eq])))
                                : '&nbsp;')
                           . '</td></tr>';
                    $k++;
                }
                $html .= '</tbody></table>';
            }
            // Nota: se CONCATENA ($html .=) para no borrar la mini tabla de
            // equipos que va antes del encabezado de la planilla.
            $html .= '<br/><hr/><br/><table cellpadding="4" cellspacing="0" border="0" style="width:100%; font-size:8pt; color:#2c3e50; margin-bottom:6px;">'
                   . '<tr>'
                   . '<td><b>Lugar:</b> ' . $esc($fila_partido['lugar_nombre'] ?? 'A confirmar') . '</td>'
                   . '<td><b>Partidos:</b> ' . count($this->datos) . '</td>'
                   . '</tr></table>';
            // ---- Planilla de partidos ----
            $html .= '<table cellpadding="4" cellspacing="0" border="1" style="border-color:#bdc3c7; border-collapse:collapse; width:100%; font-size:8pt; color:#2c3e50;">'
                   . '<thead><tr style="background-color:' . $c_head . '; color:#ffffff;">'
                   . '<th style="' . $borde . ' width:5%;">Nº</th>'
                   . '<th style="' . $borde . ' width:14%;">FASE / GRUPO</th>'
                   . '<th style="' . $borde . ' width:13%;">DÍA / HORA</th>'
                   . '<th style="' . $borde . ' width:24%;">EQUIPO A</th>'
                   . '<th style="' . $borde . ' width:24%;">EQUIPO B</th>'
                   . '<th style="' . $borde . ' width:20%;">MARCADOR / GANADOR</th>'
                   . '</tr></thead><tbody>';

            $n = 0;
            $jornada_actual = null;
            // En enfrentamiento de equipos las filas ya vienen ordenadas por
            // numero_fecha (ver _body_planilla), así que se agrupa por JORNADA:
            // el subencabezado dice "Jornada N" y debajo quedan TODOS los
            // partidos de esa jornada, aunque se jueguen en días/canchas
            // distintos. Si además la jornada abarca más de un día, se aclara
            // el rango de fechas entre paréntesis.
            $modalidad_equipos = strtoupper(trim((string) ($fila_partido['modalidad_competencia'] ?? ''))) === 'ENFRENTAMIENTO';
            // Mapa de equipos listados arriba: se expone al closure $integrantes
            // para que las celdas EQUIPO A/B omitan los integrantes repetidos.
            $GLOBALS['planilla_equipos_map'] = $equipos_tabla;
            foreach ($this->datos as $f) {
                $n++;
                $fecha = substr((string) ($f['fecha_competencia'] ?? ''), 0, 10);
                $num_jornada = (int) ($f['numero_fecha'] ?? 0);
                // Clave de agrupamiento: jornada (enfrentamiento de equipos) o día (resto).
                if ($modalidad_equipos) {
                    $clave_grupo = 'J' . $num_jornada;
                } else {
                    $clave_grupo = $fecha;
                }
                // Subencabezado cada vez que cambia la jornada / el día
                if ($clave_grupo !== $jornada_actual) {
                    $jornada_actual = $clave_grupo;
                    if ($modalidad_equipos) {
                        // Fechas distintas dentro de esta jornada (para aclarar
                        // en el subencabezado cuando la jornada juega en 2 días).
                        $fechas_jornada = array();
                        foreach ($this->datos as $fx) {
                            if ((int) ($fx['numero_fecha'] ?? 0) === $num_jornada) {
                                $fj = substr((string) ($fx['fecha_competencia'] ?? ''), 0, 10);
                                if ($fj !== '') $fechas_jornada[$fj] = date('d/m/Y', strtotime($fj));
                            }
                        }
                        $etiqueta_jornada = $_etiqueta_jornada($num_jornada);
                        if (count($fechas_jornada) > 1) {
                            $etiqueta_jornada .= ' (' . implode(' · ', array_values($fechas_jornada)) . ')';
                        } elseif (count($fechas_jornada) === 1) {
                            $etiqueta_jornada .= ' (' . reset($fechas_jornada) . ')';
                        }
                        $html .= '<tr><td colspan="7" style="' . $borde . ' background-color:#ecf0f1; font-weight:bold; color:#1e3a5f; text-align:center;">'
                               . $esc($etiqueta_jornada) . '</td></tr>';
                    } else {
                        $html .= '<tr><td colspan="7" style="' . $borde . ' background-color:#ecf0f1; font-weight:bold; color:#1e3a5f; text-align:center;">'
                               . $esc($fecha !== '' ? date('d/m/Y', strtotime($fecha)) : 'Sin fecha') . '</td></tr>';
                    }
                }

                $integrantes = function ($lado) use ($f) {
                    // Con la mini tabla de equipos arriba ya no se repiten los
                    // integrantes en cada celda: solo se listan cuando el equipo
                    // NO está en esa tabla (p. ej. "Cruce pendiente" o equipos
                    // que llegaron por fuera del padrón de la categoría).
                    if (!empty($GLOBALS['planilla_equipos_map'])) {
                        $uid = !empty($f['id_ute_' . $lado]) ? (int) $f['id_ute_' . $lado] : 0;
                        $eq = trim((string) ($f['ute_' . $lado . '_nombre'] ?? ''));
                        $clave = $uid ?: ('n:' . mb_strtolower($eq, 'UTF-8'));
                        if (isset($GLOBALS['planilla_equipos_map'][$clave])) return array();
                    }
                    $lista = array();
                    foreach ((array) ($f['jugadores_ute_' . $lado] ?? array()) as $j) {
                        $j = trim((string) $j);
                        if ($j !== '') $lista[] = $this->nombres_completos ? $j : self::_nombre_corto($j);
                    }
                    return $lista;
                };
                $lado_html = function ($campo_nombre, $lado) use ($f, $integrantes, $esc) {
                    $equipo = trim((string) ($f[$campo_nombre] ?? ''));
                    if ($equipo === '' && !empty($f['id_ute_' . $lado])) $equipo = 'Cruce pendiente';
                    $out = '<b>' . ($equipo !== '' ? $esc($equipo) : '&nbsp;') . '</b>';
                    $jug = $integrantes($lado);
                    if ($jug) {
                        // Integrantes separados con " - ": los nombres se guardan
                        // como "Apellido, nombre" en la BD y con comas no se
                        // distinguía un participante del siguiente. Cada nombre
                        // además se normaliza a "Nombre Apellido" para que esa
                        // coma interna no vuelva a confundirse con un separador.
                        $jug_norm = array_map(array('MYPDF', '_nombre_normalizado'), $jug);
                        $out .= '<br/><span style="font-size:6.5pt; color:#7f8c8d;">' . $esc(implode(' - ', $jug_norm)) . '</span>';
                    }
                    return $out;
                };

                $fase = $fase_bonito($f['fase'] ?? '');
                if (!empty($f['numero_fecha']) && stripos($fase, 'grupo') !== false) {
                    $fase .= ' · F' . (int) $f['numero_fecha'];
                }
                $horario = date('d/m/Y', strtotime((string) ($f['fecha_competencia'] ?? 'now'))).' '.substr((string) ($f['hora_inicio'] ?? ''), 0, 5)
                         . '–' . substr((string) ($f['hora_fin'] ?? ''), 0, 5);
                $bg = ($n % 2) ? '#ffffff' : '#f8f9fa';

                $html .= '<tr style="background-color:' . $bg . ';">'
                       . '<td style="' . $borde . ' text-align:center;width:5%;">' . $n . '</td>'
                       . '<td style="' . $borde . ' width:14%;">' . $esc($fase)
                       . (!empty($f['nombre_prueba']) ? '<br/><span style="font-size:6.5pt; color:#7f8c8d;">' . $esc($f['nombre_prueba']) . '</span>' : '')
                       . '</td>'
                       . '<td style="' . $borde . ' text-align:center;width:13%;">' . $esc($horario) . '</td>'
                       . '<td style="' . $borde . ' width:24%;">' . $lado_html('ute_1_nombre', 1) . '</td>'
                       . '<td style="' . $borde . ' width:24%;">' . $lado_html('ute_2_nombre', 2) . '</td>'
                       . '<td style="' . $borde . ' width:20%;"></td>'
                       . '</tr>';
            }
            // Filas vacías extra: por si en la mesa hay que anotar partidos
            // que se agregan en el momento (no modifica el reporte, solo la planilla).
            for ($v = 0; $v < self::$filas_vacias_planilla; $v++) {
                $bg = (($n + $v) % 2) ? '#ffffff' : '#f8f9fa';
                $html .= '<tr style="background-color:' . $bg . ';">'
                       . '<td style="' . $borde . ' text-align:center;width:5%;">&nbsp;</td>'
                       . '<td style="' . $borde . ' width:14%;"></td>'
                       . '<td style="' . $borde . ' width:13%;"></td>'
                       . '<td style="' . $borde . ' width:24%;"></td>'
                       . '<td style="' . $borde . ' width:24%;"></td>'
                       . '<td style="' . $borde . ' width:20%;"></td>'
                       . '</tr>';
            }
            $GLOBALS['planilla_equipos_map'] = NULL;
            $html .= '</tbody></table>';
        }

        // Espacio de firma para la mesa de control
        $html .= '<br/><br/><br/><table cellpadding="4" cellspacing="0" border="0" style="width:100%; font-size:8pt; color:#2c3e50; margin-top:14px;">'
               . '<tr>'
               . '<td style="width:50%; text-align:center; border-top:1px solid #2c3e50;">Mesa de control (firma)</td>'
               . '<td style="width:50%; text-align:center; border-top:1px solid #2c3e50;">árbitro / supervisor (firma)</td>'
               . '</tr></table>';

        $this->writeHTML($html, true, false, true, false, '');

        // Restaurar los datos completos (el bucle de _body_planilla recorre
        // todas las categorías del deporte).
        $this->datos = $datos_prev;
    }

    public function Footer() {
        // Pie de página: separador, nombre del sitio a la izquierda y paginación a la derecha
        $this->SetY(-16);
        $this->SetDrawColor(203, 213, 225);
        $this->SetLineWidth(0.2);
        $this->Line($this->lMargin, $this->GetY(), $this->w - $this->rMargin, $this->GetY());

        $this->SetFont('dejavusans', '', 7);
        $this->SetTextColor(127, 140, 141);
        $this->Cell(0, 8, NOMBRE_SITIO, 0, 0, 'L');
        $this->Cell(0, 8, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'R');
    }
}

// ==========================================
// CONFIGURACIÓN Y EJECUCIÓN DEL PDF
// ==========================================

// create new PDF document
$pdf = new MYPDF('L', PDF_UNIT, 'LEGAL', true, 'UTF-8', false);

// ⚠️ IMPORTANTE: Asigna aquí tus variables externas antes de generar el cuerpo
// Descomenta y ajusta según cómo recibas las variables desde tu controlador:
$pdf->datos = $datos;
$pdf->formato = $formato;
$pdf->dia_filtro = $dia_filtro;
$pdf->deporte_filtro = isset($deporte_filtro) ? $deporte_filtro : null;
$pdf->delegacion_filtro = isset($delegacion_filtro) ? $delegacion_filtro : null;
$pdf->modo_delegado = !empty($modo_delegado);
$pdf->nombres_completos = !empty($nombres_completos);
// Rango oficial de la competencia (si el controlador lo manda) y "cuándo":
// con cuando='hoy' el cuadro arranca en la fecha actual en vez del día 1.
$pdf->fecha_inicio_competencia = !empty($fecha_inicio_competencia) ? $fecha_inicio_competencia : $pdf->fecha_inicio_competencia;
$pdf->fecha_fin_competencia    = !empty($fecha_fin_competencia) ? $fecha_fin_competencia : $pdf->fecha_fin_competencia;
$pdf->cuando_reporte = (isset($cuando_reporte) && $cuando_reporte === 'hoy') ? 'hoy' : 'inicio';
$pdf->ancho_franja = $ancho_franja;
$pdf->nombre_archivo = $nombre_archivo;
$pdf->titulo_planilla = isset($titulo_planilla) ? $titulo_planilla : null;
$pdf->deporte_planilla = isset($deporte_planilla) ? $deporte_planilla : null;

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor(NOMBRE_SITIO);
$pdf->SetTitle('Fixture ' . NOMBRE_META
    . (isset($delegacion_filtro) && $delegacion_filtro ? ' — Delegación ' . $delegacion_filtro : '')
    . (isset($deporte_filtro) && $deporte_filtro ? ' — ' . $deporte_filtro : ''));
$pdf->SetSubject('Fixture de competencia');
$pdf->SetKeywords('TCPDF, PDF, OLIMPIADAS, VIVIENDAS');

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(25, 100, 25);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(30);

// set auto page breaks: margen inferior amplio para que la tabla nunca toque el footer
$pdf->SetAutoPageBreak(TRUE, 28);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/es.php')) {
    require_once(dirname(__FILE__).'/lang/es.php');
    $pdf->setLanguageArray($l);
}

// ---------------------------------------------------------

// set default font subsetting mode
$pdf->setFontSubsetting(true);

// Set font
$pdf->SetFont('dejavusans', '', 14, '', true);

// Add a page (la planilla de mesa de control se imprime en A4 vertical)
if ($formato === 'planilla') {
    $pdf->AddPage('P', 'A4');
} else {
    $pdf->AddPage('L','LEGAL');
}

// Generar el contenido del cuerpo (aquí se ejecuta toda la lógica HTML)
$pdf->Body();

// ---------------------------------------------------------

// Close and output PDF document
$pdf->Output($pdf->nombre_archivo, 'I');

//============================================================+
// END OF FILE
//============================================================+