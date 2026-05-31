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
}