<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($titulo_pagina) ? $titulo_pagina : 'Panel' ?> - <?= NOMBRE_META; ?></title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="<?= base_url('css/style.css') ?>" rel="stylesheet">
    <?php if (!empty($usa_sweetalert)): ?>
    <!-- SweetAlert2 (confirmaciones estilizadas donde el panel la usa, ej.: Premiación) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php endif; ?>
</head>
<body>

<?php $this->load->view('admin/header_admin'); ?>

<div class="container mb-5 mt-4">
    <?php if (!empty($mensaje_exito = $this->session->flashdata('mensaje_exito'))): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($mensaje_exito) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if (!empty($mensaje_error = $this->session->flashdata('mensaje_error'))): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($mensaje_error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if (!empty($success = $this->session->flashdata('success'))): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if (!empty($error = $this->session->flashdata('error'))): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <?php
    // La vista parcial ($panel) puede hacer push de modales al array $modals
    // (definido por el controlador) para que se rendericen UNA sola vez al final,
    // evitando IDs duplicados cuando varios paneles comparten un mismo modal.
    if (isset($panel)) {
        $this->load->view('admin/' . $panel);
    }
    ?>
</div>

<?php if (!empty($modals)): ?>
    <?php foreach ($modals as $modal): ?>
        <?php $this->load->view('admin/modals/' . $modal); ?>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Bootstrap JS al final del body (igual que las vistas originales): los
     &lt;script&gt; de cada panel esperan DOMContentLoaded para usar `bootstrap`. -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
