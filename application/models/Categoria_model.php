<?php
class Categoria_model extends CI_Model {
    public function get_by_deporte($id_deporte) {
        $this->db->where('id_deporte', $id_deporte);
        return $this->db->get('categorias')->result_array();
    }

    public function insertar_categoria_desde_post($post_data) {
        // Validación de negocio interna del modelo
        if (empty($post_data['id_deporte']) || empty($post_data['nombre_categoria'])) {
            return FALSE;
        }

        $datos = array(
            'id_deporte'        => intval($post_data['id_deporte']),
            'nombre_categoria'  => trim($post_data['nombre_categoria']),
            'cupo_maximo'       => !empty($post_data['cupo_maximo']) ? intval($post_data['cupo_maximo']) : 0,
            'id_lugar'          => !empty($post_data['id_lugar']) ? intval($post_data['id_lugar']) : NULL,
            'dia_competencia'   => !empty($post_data['dia_competencia']) ? $post_data['dia_competencia'] : NULL,
            'hora_competencia'  => !empty($post_data['hora_competencia']) ? $post_data['hora_competencia'] : NULL
        );

        // Sistema de definición POR CATEGORÍA (sql/migracion_puntaje_categoria.sql):
        // hay categorías de un mismo deporte que definen por FINAL + 3er puesto
        // y otras por tabla de puntos (3/1/0). Se guardan solo si las columnas
        // existen, para no romper en bases todavía sin el ALTER.
        if ($this->columna_existe('sistema_definicion')) {
            $datos['sistema_definicion'] = (isset($post_data['sistema_definicion'])
                && strtoupper(trim((string) $post_data['sistema_definicion'])) === 'PUNTAJE')
                ? 'PUNTAJE' : 'FASE_FINAL';
        }
        if ($this->columna_existe('puntosVictoria')) {
            $pv = isset($post_data['puntos_victoria']) ? intval($post_data['puntos_victoria']) : 3;
            $datos['puntosVictoria'] = ($pv >= 0 && $pv <= 10) ? $pv : 3;
        }
        if ($this->columna_existe('puntosEmpate')) {
            $pe = isset($post_data['puntos_empate']) ? intval($post_data['puntos_empate']) : 1;
            $datos['puntosEmpate'] = ($pe >= 0 && $pe <= 10) ? $pe : 1;
        }

        return $this->db->insert('categorias', $datos);
    }

    /** ¿Existe una columna en `categorias`? (tolera bases sin el ALTER). */
    public function columna_existe($columna) {
        static $cache = array();
        $key = strtolower((string) $columna);
        if (!isset($cache[$key])) {
            try {
                $q = $this->db->query(
                    "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE()
                       AND TABLE_NAME = 'categorias'
                       AND COLUMN_NAME = ?",
                    array($columna)
                );
                $cache[$key] = (bool) ($q && $q->num_rows() > 0);
            } catch (Throwable $e) {
                log_message('error', '[Categorías] no se pudo verificar columna ' . $columna . ': ' . $e->getMessage());
                $cache[$key] = false;
            }
        }
        return $cache[$key];
    }

    public function eliminar_categoria($id_categoria) {
        $this->db->where('id_categoria', $id_categoria);
        return $this->db->delete('categorias'); // Cambiá 'categorias' por el nombre real de tu tabla
    }

    public function actualizar_categoria($id_categoria, $data) {
        // Sistema de definición POR CATEGORÍA: el controlador manda los campos
        // crudos del POST; acá se filtran/normalizan y se descartan si las
        // columnas todavía no existen en la base (sql/migracion_puntaje_categoria.sql).
        if (array_key_exists('sistema_definicion', $data)) {
            if ($this->columna_existe('sistema_definicion')) {
                $data['sistema_definicion'] = (strtoupper(trim((string) $data['sistema_definicion'])) === 'PUNTAJE')
                    ? 'PUNTAJE' : 'FASE_FINAL';
            } else {
                unset($data['sistema_definicion']);
            }
        }
        foreach (array('puntos_victoria' => 'puntosVictoria', 'puntos_empate' => 'puntosEmpate') as $post_key => $col) {
            if (array_key_exists($post_key, $data)) {
                if ($this->columna_existe($col)) {
                    $v = intval($data[$post_key]);
                    $data[$col] = ($v >= 0 && $v <= 10) ? $v : ($col === 'puntosVictoria' ? 3 : 1);
                }
                unset($data[$post_key]);
            }
        }

        $this->db->where('id_categoria', $id_categoria);
        return $this->db->update('categorias', $data);
    }
}