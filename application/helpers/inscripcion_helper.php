<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Helper con la lógica común de armado de datos del formulario de inscripción,
 * extraída del antiguo controlador monolítico `Inscripciones`.
 */

if (!function_exists('olim_normalizar_persona')) {
    /**
     * Normaliza los campos de persona provenientes del POST del formulario.
     *
     * @param array $post Datos crudos de $this->input->post()
     * @return array Estructura lista para participantes (incluye token_qr)
     */
    function olim_normalizar_persona($post, $token = null) {
        return [
            'nombre_completo'     => mb_strtoupper(trim($post['nombre_completo']), 'UTF-8'),
            'email'               => strtolower(trim($post['email'])),
            'telefono'            => trim($post['telefono']),
            'delegacion'          => trim($post['delegacion']),
            'sexo'                => trim($post['sexo']),
            'fecha_nacimiento'    => $post['fecha_nacimiento'],
            'grupo_sanguineo'     => trim($post['grupo_sanguineo']),
            'obra_social'         => mb_strtoupper(trim($post['obra_social']), 'UTF-8'),
            'tipo_empleado'       => trim($post['tipo_empleado']),
            'dieta_especial'      => trim($post['dieta_especial']),
            'hotel_alojamiento'   => mb_strtoupper(trim($post['hotel_alojamiento']), 'UTF-8'),
            'contacto_emergencia' => mb_strtoupper(trim($post['contacto_emergencia']), 'UTF-8'),

            // Rol: si es 'competidor' guarda 1, si es acompañante guarda 0
            'es_competidor'       => ($post['rol_asistente'] === 'competidor') ? 1 : 0,

            // Solo puede ser delegado si es competidor y tildó el checkbox
            'es_delegado'         => (isset($post['es_delegado']) && $post['rol_asistente'] === 'competidor') ? 1 : 0,
        ] + ($token !== null ? ['token_qr' => $token] : []);
    }
}

if (!function_exists('olim_disciplinas_desde_post')) {
    /**
     * Mapea dinámicamente cada categoría seleccionada con su estado de UTE,
     * tal como llega del frontend (arrays paralelos indexados).
     *
     * @param array $post            Datos crudos del POST
     * @param bool  $con_inscripcion Incluir id_inscripcion (modo edición)
     * @return array Lista de disciplinas con datos UTE
     */
    function olim_disciplinas_desde_post($post, $con_inscripcion = false) {
        $deportes_seleccionados = [];

        if (($post['rol_asistente'] ?? '') === 'competidor' && isset($post['categoria_id'])) {
            foreach ($post['categoria_id'] as $index => $cat_id) {
                if (empty($cat_id)) {
                    continue;
                }
                $disciplina = [
                    'id_deporte'   => isset($post['deporte_id'][$index]) ? $post['deporte_id'][$index] : null,
                    'id_categoria' => $cat_id,
                    'tiene_ute'    => isset($post['tiene_ute'][$index]) ? (int) $post['tiene_ute'][$index] : 0,
                    'necesita_ute' => isset($post['necesita_ute'][$index]) ? (int) $post['necesita_ute'][$index] : 0,
                    'detalle_ute'  => isset($post['detalle_ute'][$index]) ? mb_strtoupper(trim($post['detalle_ute'][$index]), 'UTF-8') : '',
                ];
                if ($con_inscripcion) {
                    $disciplina = ['id_inscripcion' => isset($post['id_inscripcion'][$index]) ? $post['id_inscripcion'][$index] : null] + $disciplina;
                }
                $deportes_seleccionados[] = $disciplina;
            }
        }

        return $deportes_seleccionados;
    }
}

if (!function_exists('olim_generar_token_qr')) {
    /**
     * Token criptográficamente seguro para el QR de acreditación.
     * (Reemplaza al antiguo sha1(dni + salta fija + time()), predecible.)
     */
    function olim_generar_token_qr() {
        return bin2hex(random_bytes(32));
    }
}

if (!function_exists('olim_datos_prueba_inscripcion')) {
    /**
     * Blanco de pruebas: datos fijos para testear la carga sin frontend.
     * (Conservado del controlador original.)
     */
    function olim_datos_prueba_inscripcion() {
        return [
            'dni'                 => '99888777',
            'nombre_completo'     => 'Juan Carlos Prueba',
            'email'               => 'juan.prueba@correo.com',
            'telefono'            => '2954123456',
            'delegacion'          => 'La Pampa',
            'sexo'                => 'Masculino',
            'fecha_nacimiento'    => '1995-05-15',
            'grupo_sanguineo'     => '0+',
            'obra_social'         => 'Sempre',
            'tipo_empleado'       => 'Planta Permanente',
            'dieta_especial'      => 'Sin restricciones',
            'hotel_alojamiento'   => 'Hotel Central',
            'contacto_emergencia' => 'Maria Gomez - 2954667788',
            'rol_asistente'       => 'competidor',
            'es_delegado'         => '1',
            'deporte_id'          => ['1', '3'],
            'categoria_id'        => ['13', '15'],
            'tiene_ute'           => ['1', '0'],
            'necesita_ute'        => ['0', '1'],
            'detalle_ute'         => ['Equipo Los Pampeanos FC', ''],
        ];
    }
}
