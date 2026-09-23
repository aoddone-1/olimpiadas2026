<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Traits_Inscripcion_Persona
 *
 * Normalización de los datos personales del formulario, antes duplicada
 * literalmente en guardar(), guardar_modificacion() y
 * guardar_nueva_inscripcion() del monolito Inscripciones.php.
 *
 * Requiere el helper `_limpiar()` provisto por OLIM_Controller.
 */
trait Traits_Inscripcion_Persona {

    /**
     * Construye la estructura lista para la tabla `participantes` a partir
     * del POST ya validado. No incluye dni/token/fecha: eso lo decide cada
     * acción según el caso de uso.
     */
    protected function _normalizar_persona($post) {
        $clean = function ($k, $default = '') use ($post) {
            return $this->_limpiar($post[$k] ?? $default);
        };

        $es_competidor = ($clean('rol_asistente') === 'competidor');

        return [
            'nombre_completo'     => mb_strtoupper($clean('nombre_completo'), 'UTF-8'),
            'email'               => strtolower($clean('email')),
            'telefono'            => $clean('telefono'),
            'delegacion'          => $clean('delegacion'),
            'sexo'                => $clean('sexo'),
            'fecha_nacimiento'    => $clean('fecha_nacimiento'),
            'grupo_sanguineo'     => $clean('grupo_sanguineo'),
            'obra_social'         => mb_strtoupper($clean('obra_social'), 'UTF-8'),
            'tipo_empleado'       => $clean('tipo_empleado'),
            'dieta_especial'      => $clean('dieta_especial'),
            'hotel_alojamiento'   => mb_strtoupper($clean('hotel_alojamiento'), 'UTF-8'),
            'contacto_emergencia' => mb_strtoupper($clean('contacto_emergencia'), 'UTF-8'),
            'es_competidor'       => $es_competidor ? 1 : 0,
            'es_delegado'         => (!empty($post['es_delegado']) && $es_competidor) ? 1 : 0,
        ];
    }
}
