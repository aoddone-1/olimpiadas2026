<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Usuarios - <?= NOMBRE_META; ?></title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="<?= base_url('css/style.css') ?>" rel="stylesheet">
    <!-- SweetAlert2: confirmaciones estilizadas (con fallback a native si no carga) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php $this->load->view('admin/header_admin'); ?>

<?php
// Colores por rol para los badges
$colores_rol = array(
    'superadmin'       => 'bg-danger',
    'admin'            => 'bg-primary',
    'staff'            => 'bg-success',
    'mesa_control'     => 'bg-info text-dark',
    'carga_resultados' => 'bg-warning text-dark',
    'solo_lectura'     => 'bg-secondary',
    'delegado'         => 'bg-dark',
);
?>

<!-- Mensajes flash -->
<?php if ($this->session->flashdata('mensaje_exito')): ?>
    <div class="alert alert-success alert-dismissible fade show mx-4 mt-3 shadow-sm rounded-pill py-2 small fw-semibold" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> <?= $this->session->flashdata('mensaje_exito'); ?>
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
<?php endif; ?>
<?php if ($this->session->flashdata('mensaje_error')): ?>
    <div class="alert alert-danger alert-dismissible fade show mx-4 mt-3 shadow-sm rounded-pill py-2 small fw-semibold" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= $this->session->flashdata('mensaje_error'); ?>
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
<?php endif; ?>

<!-- PANEL GESTOR DE USUARIOS -->
<div class="card fx-card shadow-sm border-0 mb-4 card-inscriptos mx-4">
    <div class="card-header fx-card-header fw-bold text-secondary d-flex flex-column gap-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="fx-header-icon d-inline-flex align-items-center justify-content-center">
                    <i class="bi bi-person-gear text-danger"></i>
                </span>
                <span class="fx-header-titulo">Gestor de Usuarios <span class="badge bg-danger ms-2" style="font-size:0.65rem;">SOLO SUPERADMIN</span></span>
            </div>
            <button class="btn btn-sm btn-danger rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalNuevoUsuario">
                <i class="bi bi-person-plus-fill me-1"></i> Nuevo Usuario
            </button>
        </div>
    </div>
</div>

<div class="container-fluid px-4 mb-5">
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-0 pt-3 pb-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="fw-bold text-dark m-0">
                <i class="bi bi-people-fill text-primary me-2"></i>Usuarios del sistema (<?= count($usuarios); ?>)
            </h6>
            <input type="text" id="filtro_usuarios" class="form-control form-control-sm" style="max-width: 260px;"
                   placeholder="Buscar por usuario, nombre o rol...">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tabla_usuarios">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Nombre visible</th>
                            <th>Rol</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usuarios)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No hay usuarios cargados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($usuarios as $u): ?>
                                <?php
                                    $es_yo = ($u['id_usuario'] == $yo_id);
                                    $rol_badge = $colores_rol[$u['rol']] ?? 'bg-secondary';
                                ?>
                                <tr data-busqueda="<?= strtolower(htmlspecialchars($u['username'] . ' ' . $u['nombre_usuario'] . ' ' . $u['rol'], ENT_QUOTES, 'UTF-8')) ?>">
                                    <td class="text-muted small"><?= intval($u['id_usuario']) ?></td>
                                    <td class="fw-bold">
                                        <?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?>
                                        <?php if ($es_yo): ?><span class="badge bg-success-subtle text-success ms-1" style="font-size:0.65rem;">vos</span><?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($u['nombre_usuario'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="badge <?= $rol_badge ?>"><?= strtoupper(str_replace('_', ' ', $u['rol'])) ?></span></td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <!-- Editar rol / nombre -->
                                            <button class="btn btn-outline-primary btn-xs" title="Editar rol y nombre"
                                                    data-bs-toggle="modal" data-bs-target="#modalEditarUsuario"
                                                    data-id="<?= $u['id_usuario'] ?>"
                                                    data-username="<?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-nombre="<?= htmlspecialchars($u['nombre_usuario'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                    data-rol="<?= htmlspecialchars($u['rol'], ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <!-- Cambiar contraseña -->
                                            <button class="btn btn-outline-warning btn-xs" title="Cambiar contraseña"
                                                    data-bs-toggle="modal" data-bs-target="#modalPasswordUsuario"
                                                    data-id="<?= $u['id_usuario'] ?>"
                                                    data-username="<?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="bi bi-key-fill"></i>
                                            </button>
                                            <!-- Eliminar -->
                                            <?php if (!$es_yo): ?>
                                            <a href="<?= base_url('Inscripciones/eliminar_usuario/' . $u['id_usuario']) ?>"
                                               class="btn btn-outline-danger btn-xs eliminar-usuario"
                                               title="Eliminar usuario"
                                               data-username="<?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="bi bi-trash-fill"></i>
                                            </a>
                                            <?php else: ?>
                                            <button class="btn btn-outline-secondary btn-xs" disabled title="No podés eliminarte a vos mismo">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ================= MODAL: NUEVO USUARIO ================= -->
<div class="modal fade" id="modalNuevoUsuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= base_url('Inscripciones/crear_usuario') ?>" class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-person-plus-fill me-2"></i>Nuevo Usuario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold small">Nombre de usuario (login) *</label>
                    <input type="text" name="username" class="form-control" required maxlength="50"
                           placeholder="ej: mesa_voley" autocomplete="off">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Contraseña * (mínimo 4 caracteres)</label>
                    <input type="text" name="password" class="form-control" required minlength="4"
                           placeholder="La verá el usuario al iniciar sesión" autocomplete="new-password">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Nombre visible</label>
                    <input type="text" name="nombre_usuario" class="form-control" maxlength="100"
                           placeholder="Ej: Juan Pérez (opcional)">
                </div>
                <div class="mb-1">
                    <label class="form-label fw-bold small">Rol *</label>
                    <select name="rol" class="form-select" required>
                        <?php foreach ($roles as $clave => $etiqueta): ?>
                            <option value="<?= $clave ?>"><?= $etiqueta ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text small">El rol define qué pantallas y acciones puede usar el usuario.</div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 rounded-bottom-4">
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3"><i class="bi bi-check-circle me-1"></i> Crear usuario</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= MODAL: EDITAR USUARIO ================= -->
<div class="modal fade" id="modalEditarUsuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= base_url('Inscripciones/editar_usuario') ?>" class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Editar Usuario: <span id="edit_username_label"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id_usuario" id="edit_id_usuario">
                <div class="mb-3">
                    <label class="form-label fw-bold small">Nombre visible</label>
                    <input type="text" name="nombre_usuario" id="edit_nombre_usuario" class="form-control" maxlength="100">
                </div>
                <div class="mb-1">
                    <label class="form-label fw-bold small">Rol *</label>
                    <select name="rol" id="edit_rol" class="form-select" required>
                        <?php foreach ($roles as $clave => $etiqueta): ?>
                            <option value="<?= $clave ?>"><?= $etiqueta ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text small">Atención: cambiar el rol cambia los permisos del usuario de inmediato.</div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 rounded-bottom-4">
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3"><i class="bi bi-check-circle me-1"></i> Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= MODAL: CAMBIAR CONTRASEÑA ================= -->
<div class="modal fade" id="modalPasswordUsuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= base_url('Inscripciones/cambiar_password_usuario') ?>" class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-key-fill me-2"></i>Cambiar contraseña: <span id="pass_username_label"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id_usuario" id="pass_id_usuario">
                <div class="mb-1">
                    <label class="form-label fw-bold small">Nueva contraseña * (mínimo 4 caracteres)</label>
                    <input type="text" name="nuevo_password" id="pass_nuevo" class="form-control" required minlength="4"
                           autocomplete="new-password" placeholder="La verá el usuario al iniciar sesión">
                    <div class="form-text small">Los login actuales guardan la contraseña en texto plano; se mantiene el mismo formato por compatibilidad.</div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 rounded-bottom-4">
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-warning btn-sm rounded-pill px-3"><i class="bi bi-check-circle me-1"></i> Cambiar contraseña</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // --- Rellenar modal EDITAR con los datos del botón ---
    var modalEditar = document.getElementById('modalEditarUsuario');
    if (modalEditar) {
        modalEditar.addEventListener('show.bs.modal', function (event) {
            var boton = event.relatedTarget;
            document.getElementById('edit_id_usuario').value      = boton.getAttribute('data-id');
            document.getElementById('edit_username_label').textContent = boton.getAttribute('data-username');
            document.getElementById('edit_nombre_usuario').value  = boton.getAttribute('data-nombre');
            document.getElementById('edit_rol').value             = boton.getAttribute('data-rol');
        });
    }

    // --- Rellenar modal PASSWORD ---
    var modalPass = document.getElementById('modalPasswordUsuario');
    if (modalPass) {
        modalPass.addEventListener('show.bs.modal', function (event) {
            var boton = event.relatedTarget;
            document.getElementById('pass_id_usuario').value       = boton.getAttribute('data-id');
            document.getElementById('pass_username_label').textContent = boton.getAttribute('data-username');
            document.getElementById('pass_nuevo').value            = '';
        });
    }

    // --- Confirmación antes de eliminar (SweetAlert2 con fallback nativo) ---
    document.querySelectorAll('.eliminar-usuario').forEach(function (enlace) {
        enlace.addEventListener('click', function (e) {
            e.preventDefault();
            var url = this.getAttribute('href');
            var nombre = this.getAttribute('data-username');
            var mensaje = '¿Eliminar el usuario "' + nombre + '"? Esta acción no se puede deshacer.';
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Eliminar usuario',
                    text: mensaje,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then(function (resultado) {
                    if (resultado.isConfirmed) { window.location.href = url; }
                });
            } else {
                if (confirm(mensaje)) { window.location.href = url; }
            }
        });
    });

    // --- Filtro rápido de la tabla ---
    var filtro = document.getElementById('filtro_usuarios');
    if (filtro) {
        filtro.addEventListener('input', function () {
            var valor = this.value.toLowerCase().trim();
            document.querySelectorAll('#tabla_usuarios tbody tr[data-busqueda]').forEach(function (fila) {
                fila.style.display = (valor === '' || fila.getAttribute('data-busqueda').indexOf(valor) !== -1) ? '' : 'none';
            });
        });
    }
});
</script>
</body>
</html>
