<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Traits_Inscripcion_Reglas
 *
 * Reglas de validación server-side del formulario de inscripción, antes
 * duplicadas entre `guardar()`, `guardar_modificacion()` y
 * `guardar_nueva_inscripcion()` del monolito Inscripciones.php.
 */
trait Traits_Inscripcion_Reglas {

    protected function _reglas_validacion_inscripcion() {
        return [
            ['field' => 'dni',                 'label' => 'DNI',                 'rules' => 'required|trim|max_length[20]|regex_match[/^[0-9]+$/]'],
            ['field' => 'nombre_completo',     'label' => 'Nombre completo',     'rules' => 'required|trim|max_length[150]'],
            ['field' => 'email',               'label' => 'Correo',              'rules' => 'required|trim|max_length[150]|valid_email'],
            ['field' => 'telefono',            'label' => 'Teléfono',            'rules' => 'required|trim|max_length[30]'],
            ['field' => 'delegacion',          'label' => 'Delegación',          'rules' => 'required|trim|max_length[100]'],
            ['field' => 'sexo',                'label' => 'Sexo',                'rules' => 'required|trim|max_length[20]'],
            ['field' => 'fecha_nacimiento',    'label' => 'Fecha nacimiento',    'rules' => 'required|trim|max_length[10]'],
            ['field' => 'grupo_sanguineo',     'label' => 'Grupo sanguíneo',     'rules' => 'required|trim|max_length[5]'],
            ['field' => 'obra_social',         'label' => 'Obra social',         'rules' => 'required|trim|max_length[100]'],
            ['field' => 'tipo_empleado',       'label' => 'Tipo de empleado',    'rules' => 'required|trim|max_length[50]'],
            ['field' => 'dieta_especial',      'label' => 'Dieta especial',      'rules' => 'required|trim|max_length[100]'],
            ['field' => 'hotel_alojamiento',   'label' => 'Hotel',               'rules' => 'required|trim|max_length[100]'],
            ['field' => 'contacto_emergencia', 'label' => 'Contacto emergencia', 'rules' => 'required|trim|max_length[150]'],
            ['field' => 'rol_asistente',       'label' => 'Rol',                 'rules' => 'required|trim|in_list[competidor,acompanante]'],
        ];
    }
}
