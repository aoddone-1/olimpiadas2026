<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Traits_Inscripcion_Disciplinas
 *
 * Validación de negocio de las disciplinas deportivas del formulario
 * (existencia de categoría, coherencia deporte/categoría, anti-duplicados
 * y cupos máximos). Extraída del monolito Inscripciones.php; las consultas
 * ahora delegan en Categoria_model y Participante_model en lugar de usar
 * $this->db directamente desde el controlador.
 */
trait Traits_Inscripcion_Disciplinas {

    /**
     * Valida y normaliza las disciplinas enviadas. Devuelve:
     *   ok (bool), errores (array), disciplinas (array listo para el modelo).
     *
     * Requiere que el controlador haya cargado Categoria_model y
     * Participante_model.
     */
    protected function _procesar_disciplinas($post, $id_a_excluir = NULL) {
        $result = ['ok' => TRUE, 'errores' => [], 'disciplinas' => []];

        if (($post['rol_asistente'] ?? '') !== 'competidor') {
            return $result; // Los acompañantes no cargan disciplinas
        }

        $cat_ids = (isset($post['categoria_id']) && is_array($post['categoria_id'])) ? $post['categoria_id'] : [];
        $ins_ids = (isset($post['id_inscripcion']) && is_array($post['id_inscripcion'])) ? $post['id_inscripcion'] : [];

        $vistos = [];
        foreach ($cat_ids as $i => $cat_raw) {
            $cat_id = (int) $cat_raw;
            if ($cat_id <= 0) continue;

            // 1) Anti-duplicados dentro del propio formulario
            if (in_array($cat_id, $vistos, TRUE)) {
                $result['errores'][] = 'Ten&eacute;s categor&iacute;as repetidas en el formulario.';
                $result['ok'] = FALSE;
                continue;
            }
            $vistos[] = $cat_id;

            // 2) La categoría debe existir
            $cat = $this->Categoria_model->obtener_por_id($cat_id);
            if (!$cat) {
                $result['errores'][] = 'Una de las categorías seleccionadas no existe.';
                $result['ok'] = FALSE;
                continue;
            }

            // 3) El deporte informado debe coincidir con el de la categoría
            $dep_id = isset($post['deporte_id'][$i]) ? (int) $post['deporte_id'][$i] : 0;
            if ($dep_id > 0 && (int) $cat['id_deporte'] !== $dep_id) {
                $result['errores'][] = 'La categoría &quot;' . htmlspecialchars($cat['nombre_categoria'], ENT_QUOTES, 'UTF-8') . '&quot; no pertenece al deporte seleccionado.';
                $result['ok'] = FALSE;
                continue;
            }

            // 4) Cupo máximo: contar inscriptos excluyendo lo que ya es del participante
            $cupo_maximo = (int) ($cat['cupo_maximo'] ?? 0);
            if ($cupo_maximo > 0) {
                $inscriptos = $this->Participante_model->contar_inscriptos_en_categoria(
                    $cat_id,
                    $id_a_excluir
                );
                if ($inscriptos >= $cupo_maximo) {
                    $result['errores'][] = 'La categoría &quot;' . htmlspecialchars($cat['nombre_categoria'], ENT_QUOTES, 'UTF-8') . '&quot; no tiene cupos disponibles.';
                    $result['ok'] = FALSE;
                    continue;
                }
            }

            // 5) Normalización segura de UTE
            $result['disciplinas'][] = [
                'id_inscripcion' => !empty($ins_ids[$i]) ? (int) $ins_ids[$i] : NULL,
                'id_deporte'     => (int) $cat['id_deporte'],
                'id_categoria'   => $cat_id,
                'tiene_ute'      => !empty($post['tiene_ute'][$i]) ? 1 : 0,
                'necesita_ute'   => !empty($post['necesita_ute'][$i]) ? 1 : 0,
                'detalle_ute'    => mb_strtoupper(trim($post['detalle_ute'][$i] ?? ''), 'UTF-8'),
            ];
        }

        if (empty($result['disciplinas'])) {
            $result['errores'][] = 'Como competidor deb&eacute;s seleccionar al menos una disciplina.';
            $result['ok'] = FALSE;
        }

        return $result;
    }
}
