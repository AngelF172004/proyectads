<?php
require_once dirname(__DIR__) . '/conexion.php';

// ===== [CAMBIO] Bootstrap de sesión y protección de ruta =====
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (empty($_SESSION['id_usuario'])) {
  header("Location: login.php");
  exit;
}
$id_usuario = (int)$_SESSION['id_usuario'];
$user_email = $_SESSION['correo'] ?? null;

$mascotas = [];
$error = null; // ===== [CAMBIO] inicializa $error para evitar notices

try {
  $stmt = $pdo->prepare("SELECT * FROM v_mis_mascotas WHERE usuario_id = ? ORDER BY creado_en DESC");
  $stmt->execute([$id_usuario]);
  $mascotas = $stmt->fetchAll();
} catch (Throwable $e) {
  $error = "Error al cargar mascotas: " . $e->getMessage();
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Mis mascotas</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../estilo/mis_mascotas.css">
</head>
<body>

  <!-- Navbar -->
  <header class="header">
    <nav class="nav">
      <div class="logo">🐾 <span>Petlife</span></div>
      <ul class="menu">
        <li><a href="../index.php">Inicio</a></li>
        <li><a href="../index.php#servicios">Servicios</a></li>
        <li><a href="../index.php#acerca">Acerca de</a></li>

        <!-- ===== [CAMBIO] Navbar con sesión: correo + salir; pestaña Mascota activa -->
        <li><a href="#" style="font-weight:700;">Mascota</a></li>
        <?php if (!empty($user_email)): ?>
          <li><span style="font-weight:700;"><?= htmlspecialchars($user_email) ?></span></li>
          <li><a href="logout.php">Salir</a></li>
        <?php else: ?>
          <li><a href="login.php">Cuenta</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </header>

  <!-- Migas -->
  <div class="crumbs">
    <a href="../index.php">Cuenta</a> › <span>Mascotas</span>
  </div>

  <?php if (!empty($error)): ?>
    <div class="toast" role="alert"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <main class="wrap">

    <div class="head">
      <h1 class="title">Mis mascotas</h1>
      <a class="btn" href="agregar_mascota.php">Agregar mascota</a>
    </div>

    <?php if (!$mascotas): ?>
      <section class="empty">
        <div class="empty__card">
          <div class="empty__emoji">🐾</div>
          <h2>No tienes mascotas registradas</h2>
          <p class="muted">Agrega tu primera mascota para comenzar.</p>
          <a class="btn" href="agregar_mascota.php">Agregar mascota</a>
        </div>
      </section>
    <?php else: ?>
      <section class="grid">
        <?php foreach ($mascotas as $m): ?>
          <article class="card">
            <div class="card__media">
              <!-- Si tuvieras foto, pon <img src="ruta" alt=""> -->
              <div class="card__placeholder">🐶</div>
            </div>
            <div class="card__body">
              <h3 class="card__title"><?= htmlspecialchars($m['nombre_mascota']) ?></h3>
              <p class="card__meta">
                <?= htmlspecialchars($m['especie'] ?? '—') ?>
                <?= !empty($m['raza']) ? ' · ' . htmlspecialchars($m['raza']) : '' ?>
              </p>

              <div class="card__badges">
                <?php if (!empty($m['sexo'])): ?><span class="badge"><?= htmlspecialchars(strtolower($m['sexo'])) ?></span><?php endif; ?>
                <?php if ($m['peso_kg'] !== null): ?><span class="badge"><?= htmlspecialchars($m['peso_kg']) ?> kg</span><?php endif; ?>
                <?php if (!empty($m['asegurado'])): ?><span class="badge">Asegurado</span><?php endif; ?>
              </div>

              <div class="card__actions">
                <a class="btn ghost" href="agregar_mascota.php?edit=<?= (int)$m['mascota_id'] ?>">Editar</a>
                <form method="post" action="#" onsubmit="return confirm('¿Eliminar mascota?');">
                  <button class="btn danger" type="button" onclick="alert('Implementa aquí la eliminación en tu backend')">Eliminar</button>
                </form>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </section>
    <?php endif; ?>

  </main>

</body>
</html>
