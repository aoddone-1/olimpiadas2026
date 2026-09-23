<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * OLIM_Autoload
 *
 * Hook pre_controller que registra un autoloader para los traits del
 * proyecto ubicados en application/libraries/traits/.
 *
 * CodeIgniter 3 solo autoloadea clases bajo application/libraries/ de forma
 * "plana" (una clase = un archivo directamente en libraries/), por lo que los
 * traits en subcarpetas no se resuelven solos. Sin este hook, controllers
 * como Publica/Backend fallan con "trait not found" al usar `use Traits_...`.
 */
class OLIM_Autoload {

    public function register() {
        spl_autoload_register(function ($class) {
            // Solo traits conocidos del proyecto
            if (strpos($class, 'Traits_Inscripcion_') !== 0) {
                return;
            }
            $archivo = APPPATH . 'libraries/traits/' . $class . '.php';
            if (is_file($archivo)) {
                require_once $archivo;
            }
        });
    }
}
