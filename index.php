<?php
/*
 * index.php — Landing page / Inicio de Petlife
 * Estructura de carpetas:
 *   /index.php
 *   /estilo/                 -> CSS (home.css)
 *   /recursosWeb/            -> PHP secundarios (login, registrar_usuario, mis_mascotas, etc.)
 *   /recursosVisuales/       -> Imágenes (logo, hero)
 */
?>

<?php
// ===== [CAMBIO] Bootstrap de sesión (lee si hay usuario logueado) =====
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
$user_id      = $_SESSION['id_usuario'] ?? null;
$user_email   = $_SESSION['correo']     ?? null;
$is_logged_in = !empty($user_id);
?>

<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Petlife — IA para Bienestar Canino</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- CSS en /estilo -->
  <link rel="stylesheet" href="estilo/home.css">
  <!--integracion de jquery-->
  <script src="js/jquery-3.7.1.min.js"></script>
  <!--integracion de swalalert-->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- ===== [CAMBIO] Exponer estado de sesión al JS del cliente (opcional, para coordinar con tu dropdown) ===== -->
  <script>
    window.PL_SESSION = {
      logged: <?php echo $is_logged_in ? 'true' : 'false'; ?>,
      email:  <?php echo $is_logged_in ? json_encode($user_email) : 'null'; ?>
    };
  </script>
</head>
<body>
  <!-- ===== NAVBAR ===== -->
  <header class="header">
    <nav class="nav" aria-label="Principal">
      <!-- LOGO (opcional): descomenta y apunta a /recursosVisuales
           <img src="recursosVisuales/logo-petlife.svg" alt="Petlife" class="logo-img">
      -->
      <a class="logo" href="index.php" aria-label="Ir al inicio">🐾 <span>Petlife</span></a>

      <ul class="menu">
        <li><a href="index.php" aria-current="page"><strong>Inicio</strong></a></li>
        <li><a href="recursosWeb/servicios.php">Servicios</a></li>
        <li><a href="recursosWeb/acerca.php">Acerca de</a></li>

        <?php if ($is_logged_in): ?>
          <!-- ===== [CAMBIO] Vista cuando HAY sesión: mostrar correo + salir ===== -->
          <li><span style="font-weight:700;"><?= htmlspecialchars($user_email) ?></span></li>
          <li><a href="recursosWeb/mis_mascotas.php">Mascota</a></li>
          <li><a href="recursosWeb/logout.php">Salir</a></li>
        <?php else: ?>
          <!-- "Cuenta": contenido del dropdown lo rellena JS según estado -->
          <li class="menu-cuenta">
            <!-- Fallback si JS falla: lleva a registro -->
            <a id="ctaCuenta" href="recursosWeb/registrar_usuario.php" aria-haspopup="true" aria-expanded="false">Cuenta</a>
            <div id="cuentaMenu" class="dropdown" hidden role="menu" aria-label="Menú de cuenta"></div>
          </li>
        <?php endif; ?>
      </ul>
    </nav>
  </header>

  <!-- ===== HERO ===== -->
  <main class="wrap">
    <section class="hero">
      <div class="hero__text">
        <h1 class="hero__title">IA para<br><span>Bienestar Canino</span></h1>

        <p class="hero__lead">
          Desarrollamos soluciones tecnológicas que fortalecen la práctica veterinaria con IA
          para predecir la esperanza de vida y generar planes preventivos personalizados.
        </p>

        <div class="hero__actions">
          <!-- Este href se ajusta con JS según estado -->
          <a id="btnEvaluacion" class="btn" href="recursosWeb/registrar_usuario.php">Iniciar evaluación</a>
          <a class="btn ghost" href="recursosWeb/servicios.php">Ver servicios</a>
        </div>

        <!-- Enlace directo a LOGIN para evitar perder acceso aunque el menú no se despliegue -->
        <p class="hero__hint">
          ¿Ya tienes cuenta? <a id="linkLoginDirecto" href="recursosWeb/login.php">Inicia sesión</a>
        </p>
      </div>

      <!-- Ilustración / hero -->
      <div class="hero__art" aria-hidden="true">
        <!-- Si usarás imagen, descomenta y apunta a /recursosVisuales -->
        <!-- <img src="recursosVisuales/hero-vet-dog.png" alt="" class="art__img"> -->
        <div class="art__img">🐶</div>
      </div>
    </section>
  </main>

  <!-- ===== JS (solo frontend) ===== -->
  <script>
    /* ========= Estado (demo sin backend) ========= */
    function isLogged(){ // SOLO sesión activa
      try {
        // ===== [CAMBIO] Prioriza estado de sesión del servidor si existe =====
        if (window.PL_SESSION && typeof window.PL_SESSION.logged === 'boolean') {
          return window.PL_SESSION.logged;
        }
        return localStorage.getItem('pl_user_logged_in') === '1';
      } catch(e){ return false; }
    }
    function isRegistered(){ // registro (no implica sesión)
      try { return localStorage.getItem('pl_user_registered') === '1'; }
      catch(e){ return false; }
    }

    /* ========= Referencias ========= */
    const cuentaLink = document.getElementById('ctaCuenta');
    const cuentaMenu = document.getElementById('cuentaMenu');
    const btnEval    = document.getElementById('btnEvaluacion');

    /* ========= Helpers dropdown ========= */
    function openDropdown(){ cuentaMenu?.removeAttribute('hidden'); cuentaLink?.setAttribute('aria-expanded','true'); }
    function closeDropdown(){ cuentaMenu?.setAttribute('hidden','');  cuentaLink?.setAttribute('aria-expanded','false'); }

    /* ========= Render del menú de "Cuenta" =========
       - NO logueado:      Iniciar sesión / Registrarse
       - SÍ logueado:      Mascota (perfiles) / Cerrar sesión
       Nota: si el header ya mostró sesión vía PHP, este bloque solo afecta el CTA del hero.
    */
    function renderCuentaMenu(){
      const logged = isLogged();
      const registered = isRegistered();

      // Si hay sesión del servidor, solo ajustamos el CTA del hero y salimos
      if (window.PL_SESSION && window.PL_SESSION.logged) {
        btnEval?.setAttribute('href', 'recursosWeb/mis_mascotas.php');
        return;
      }

      if (!cuentaMenu || !cuentaLink) {
        // No hay dropdown en el DOM (por ejemplo, cuando ya se mostró sesión por PHP)
        // Ajustamos únicamente el CTA:
        btnEval?.setAttribute('href', logged ? 'recursosWeb/mis_mascotas.php' : (registered ? 'recursosWeb/login.php' : 'recursosWeb/registrar_usuario.php'));
        return;
      }

      if (!logged){
        // Menú de acceso
        cuentaMenu.innerHTML = `
          <a role="menuitem" href="recursosWeb/login.php">Iniciar sesión</a>
          <a role="menuitem" href="recursosWeb/registrar_usuario.php">Registrarse</a>
        `;
        // Fallback del link principal
        cuentaLink.setAttribute('href', 'recursosWeb/registrar_usuario.php');

        // CTA del hero:
        btnEval?.setAttribute('href', registered ? 'recursosWeb/login.php' : 'recursosWeb/registrar_usuario.php');
      } else {
        // Menú autenticado
        cuentaMenu.innerHTML = `
          <a role="menuitem" href="recursosWeb/mis_mascotas.php">Mascota</a>
          <a role="menuitem" href="recursosWeb/logout.php" id="logoutLink">Cerrar sesión</a>
        `;
        cuentaLink.setAttribute('href', 'recursosWeb/mis_mascotas.php');
        btnEval?.setAttribute('href', 'recursosWeb/mis_mascotas.php');

        // "Cerrar sesión" (modo localStorage, si no se usa PHP)
        setTimeout(() => {
          const logout = document.getElementById('logoutLink');
          if (logout){
            logout.addEventListener('click', (e) => {
              // En entorno real, el link va a logout.php; esto es por si te quedaste en modo demo
              try { localStorage.removeItem('pl_user_logged_in'); } catch(e){}
              closeDropdown();
              renderCuentaMenu();
            }, { once: true });
          }
        }, 0);
      }
      // Siempre cerramos al re-render para evitar estados inconsistentes
      closeDropdown();
    }

    /* ========= Toggler del dropdown ========= */
    if (cuentaLink) {
      cuentaLink.addEventListener('click', (ev) => {
        ev.preventDefault(); // evita navegar; solo abre/cierra el menú
        if (!cuentaMenu) return;
        const isOpen = !cuentaMenu.hasAttribute('hidden');
        isOpen ? closeDropdown() : openDropdown();
      });
    }
    document.addEventListener('click', (ev) => {
      if (!cuentaMenu || cuentaMenu.hasAttribute('hidden')) return;
      const inside = ev.target === cuentaMenu || cuentaMenu.contains(ev.target) || ev.target === cuentaLink;
      if (!inside) closeDropdown();
    });
    document.addEventListener('keydown', (e) => {
      if (!cuentaMenu) return;
      if (e.key === 'Escape' && !cuentaMenu.hasAttribute('hidden')) closeDropdown();
    });
    window.addEventListener('storage', (e) => {
      if (['pl_user_registered','pl_user_logged_in'].includes(e.key)) renderCuentaMenu();
    });

    /* ========= Init ========= */
    (function init(){
      renderCuentaMenu();
    })();
  </script>
</body>
</html>
