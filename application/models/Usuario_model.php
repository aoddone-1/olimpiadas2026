<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Usuario_model extends CI_Model {

    public function login($usuario, $password) {
        // Buscamos por el nombre de columna correcto de tu tabla: 'username'
        $this->db->where('username', $usuario);
        $query = $this->db->get('usuarios');
        
        if ($query->num_rows() == 1) {
            $user = $query->row_array();
            
            // Comparación directa en texto plano para desarrollo
            if ($password === $user['password']) {
                return $user;
            }
        }
        return FALSE;
    }
    
    /**
     * Verifica si un usuario es delegado
     */
    public function es_delegado($id_usuario) {
        $this->db->where('id_usuario', $id_usuario);
        $this->db->where('rol', 'delegado');
        $query = $this->db->get('usuarios');
        return $query->num_rows() > 0;
    }

    // ====================================================================
    // GESTOR DE USUARIOS (solo superadmin)
    // ====================================================================

    /**
     * Roles válidos de la tabla usuarios (mismo ENUM del esquema).
     */
    public function roles_validos() {
        return array(
            'superadmin'      => 'Superadmin (acceso total)',
            'admin'           => 'Admin (gestión deportiva)',
            'staff'           => 'Staff (mesa / acreditación)',
            'mesa_control'    => 'Mesa de control',
            'carga_resultados'=> 'Carga de resultados',
            'solo_lectura'    => 'Solo lectura',
            'delegado'        => 'Delegado (panel propio)',
        );
    }

    /**
     * Lista todos los usuarios para el gestor (nunca expone la contraseña).
     */
    public function obtener_todos() {
        $this->db->select('id_usuario, username, rol, nombre_usuario');
        $this->db->order_by('rol', 'ASC');
        $this->db->order_by('username', 'ASC');
        return $this->db->get('usuarios')->result_array();
    }

    /**
     * Busca un usuario por ID (sin contraseña).
     */
    public function obtener_por_id($id_usuario) {
        $this->db->select('id_usuario, username, rol, nombre_usuario');
        $this->db->where('id_usuario', $id_usuario);
        return $this->db->get('usuarios')->row_array();
    }

    /**
     * ¿Ya existe un usuario con ese nombre de usuario?
     * (excluyendo opcionalmente un id, para el caso de edición)
     */
    public function username_existe($username, $id_a_excluir = NULL) {
        $this->db->where('username', $username);
        if (!empty($id_a_excluir)) {
            $this->db->where('id_usuario !=', $id_a_excluir);
        }
        return $this->db->count_all_results('usuarios') > 0;
    }

    /**
     * Crea un usuario nuevo. Devuelve el id insertado o FALSE.
     */
    public function crear($data) {
        $this->db->insert('usuarios', $data);
        return $this->db->insert_id();
    }

    /**
     * Actualiza rol y/o nombre visible de un usuario.
     */
    public function actualizar($id_usuario, $data) {
        $this->db->where('id_usuario', $id_usuario);
        return $this->db->update('usuarios', $data);
    }

    /**
     * Cambia la contraseña de un usuario.
     */
    public function cambiar_password($id_usuario, $nueva_password) {
        $this->db->where('id_usuario', $id_usuario);
        return $this->db->update('usuarios', array('password' => $nueva_password));
    }

    /**
     * Elimina un usuario.
     */
    public function eliminar($id_usuario) {
        $this->db->where('id_usuario', $id_usuario);
        return $this->db->delete('usuarios');
    }

    /**
     * Cantidad de superadmins activos en el sistema (para evitar que el
     * último superadmin se auto-descienda o se auto-elimine y quede
     * el sistema sin administradores).
     */
    public function contar_superadmins() {
        $this->db->where('rol', 'superadmin');
        return $this->db->count_all_results('usuarios');
    }
}