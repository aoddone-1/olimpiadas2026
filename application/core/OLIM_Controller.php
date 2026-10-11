<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * OLIM_Controller
 *
 * Controlador base del proyecto "Olimpiadas IPV La Pampa 2026".
 * Centraliza las verificaciones de acceso que antes estaban repetidas
 * en el monolito `Inscripciones.php`:
 *
 *   - _requerir_organizador() : staff/organizador logueado (redirige a login).
 *   - _requerir_superadmin()  : organizador con rol superadmin.
 *   - _requerir_roles()       : organizador con uno de los roles permitidos.
 *   - _auth_admin_json()      : gate de autorización para endpoints AJAX
 *                               (responde JSON {ok:false} en lugar de redirigir).
 *
 * Roles de `usuarios.rol` y qué pueden hacer:
 *   superadmin        : acceso total (Control Total, Premiación, usuarios).
 *   admin             : gestión deportiva completa + fixture/resultados/premiación.
 *   staff             : acreditación e inscripciones (no toca fixture/premiación).
 *   carga_resultados  : carga/edita resultados y genera fixture; sin premiación.
 *   solo_lectura      : consulta paneles y descarga reportes, NO modifica nada.
 *   delegado          : panel propio de su delegación (sesión is_delegado).
 *
 * Todas las acciones heredadas están disponibles bajo la URL original
 * `Inscripciones/...` gracias a los re-mapeos definidos en config/routes.php,
 * por lo que la reestructuración no rompe enlaces existentes.
 */
class OLIM_Controller extends CI_Controller {

    /** Ruta interna a la que se redirige cuando el acceso es denegado. */
    protected $login_url = 'Auth/login';

    /** Roles que pueden gestionar el fixture (ver, generar, editar, borrar). */
    protected $roles_fixture = array('superadmin', 'admin', 'carga_resultados');

    /** Roles que pueden cargar/editar/borrar resultados. */
    protected $roles_resultados = array('superadmin', 'admin', 'carga_resultados');

    /** Roles que pueden gestionar la premiación. */
    protected $roles_premiacion = array('superadmin', 'admin');

    /** Roles que pueden modificar inscripciones/acreditaciones. */
    protected $roles_edicion_inscripcion = array('superadmin', 'admin', 'staff');

    public function __construct() {
        parent::__construct();
        $this->load->library('Pdf');
        $this->load->model('Deporte_model');
        $this->load->model('Categoria_model');
    }

    /** ¿El usuario actual es staff/organizador? */
    protected function _es_organizador() {
        return (bool) $this->session->userdata('is_organizador');
    }

    /** ¿El usuario actual es delegado? */
    protected function _es_delegado() {
        return (bool) $this->session->userdata('is_delegado');
    }

    /** Exige sesión de organizador; si no, redirige al login. */
    protected function _requerir_organizador() {
        if (!$this->_es_organizador()) {
            redirect($this->login_url);
        }
    }

    /** Exige sesión de organizador con rol superadmin. */
    protected function _requerir_superadmin() {
        $this->_requerir_organizador();
        if ($this->session->userdata('user_rol') !== 'superadmin') {
            redirect($this->login_url);
        }
    }

    /** Rol del usuario logueado (o NULL si no hay sesión de organizador). */
    protected function _rol_actual() {
        return $this->session->userdata('user_rol');
    }

    /** ¿El rol actual está en la lista permitida? */
    protected function _es_rol(array $roles_permitidos) {
        return in_array($this->_rol_actual(), $roles_permitidos, TRUE);
    }

    /** ¿El usuario actual solo puede leer (rol 'solo_lectura')? */
    protected function _es_solo_lectura() {
        return $this->_rol_actual() === 'solo_lectura';
    }

    /**
     * Exige sesión de organizador con uno de los roles permitidos;
     * si no, redirige al login.
     */
    protected function _requerir_roles(array $roles_permitidos) {
        $this->_requerir_organizador();
        if (!$this->_es_rol($roles_permitidos)) {
            redirect($this->login_url);
        }
    }

    /**
     * Gate JSON: exige uno de los roles permitidos. Devuelve TRUE si autoriza;
     * en caso contrario emite {ok:false, error:'No autorizado'} y devuelve FALSE.
     */
    protected function _auth_roles_json(array $roles_permitidos) {
        if (!$this->_es_organizador() || !$this->_es_rol($roles_permitidos)) {
            $this->_json_error_gate();
            return false;
        }
        return true;
    }

    /**
     * Gate JSON para escrituras (POST): además de exigir uno de los roles
     * permitidos, bloquea al rol 'solo_lectura', que únicamente consulta.
     */
    protected function _auth_escritura_json(array $roles_permitidos) {
        if (!$this->_auth_roles_json($roles_permitidos)) {
            return false;
        }
        if ($this->_es_solo_lectura()) {
            $this->_json_error_gate();
            return false;
        }
        return true;
    }

    /** Respuesta JSON estándar de "No autorizado" para endpoints AJAX. */
    private function _json_error_gate() {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array('ok' => false, 'error' => 'No autorizado')));
    }

    /**
     * Gate de autorización para endpoints AJAX de administración.
     * Devuelve TRUE si el usuario es organizador con rol superadmin/admin;
     * en caso contrario emite la respuesta JSON de error y devuelve FALSE.
     */
    protected function _auth_admin_json() {
        if (!$this->_es_organizador()
            || !in_array($this->session->userdata('user_rol'), array('superadmin', 'admin'))) {
            $this->_json(array('ok' => false, 'error' => 'No autorizado'));
            return false;
        }
        return true;
    }

    /** Envía una respuesta JSON uniforme desde un endpoint AJAX. */
    protected function _json($datos, $codigo_http = 200) {
        $this->output
            ->set_content_type('application/json')
            ->set_status_header($codigo_http)
            ->set_output(json_encode($datos));
    }

    /** Registra el error en log y responde JSON {ok:false} con detalle. */
    protected function _json_error($etiqueta, $mensaje, $detalle = '') {
        log_message('error', '[' . $etiqueta . '] ' . $mensaje . ($detalle !== '' ? ' :: ' . $detalle : ''));
        $this->_json(array(
            'ok'      => false,
            'error'   => $mensaje,
            'detalle' => $detalle,
        ));
    }

    /**
     * Normaliza un texto de formulario: recorta espacios y aplica XSS cleaning
     * defensivo. Centraliza el closure `$clean` que estaba duplicado en las
     * tres acciones de guardado del monolito.
     */
    protected function _limpiar($valor, $default = '') {
        return trim((string) $this->security->xss_clean($valor ?? $default));
    }
}
