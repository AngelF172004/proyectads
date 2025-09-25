<?php
// login.php
require_once dirname(__DIR__) . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Si ya hay sesión, manda al inicio (o a mis_mascotas.php si prefieres)
if (!empty($_SESSION['id_usuario'])) {
  header('Location: ../index.php');
  exit;
}

$error = null;

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email    = trim($_POST['email'] ?? '');
  $password = $_POST['password']   ?? '';

  if ($email === '' || $password === '') {
    $error = 'Ingresa tu correo y contraseña.';
  } else {
    try {
      // Busca por correo
      $stmt = $pdo->prepare("SELECT id_usuario, correo, password_hash FROM usuarios WHERE correo = :correo LIMIT 1");
      $stmt->execute([':correo' => $email]);
      $user = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($user && password_verify($password, $user['password_hash'])) {
        // Autenticado: guardar sesión consistente
        $_SESSION['id_usuario'] = (int)$user['id_usuario'];
        $_SESSION['correo']     = $user['correo'];

        // Redirige a inicio (o mis_mascotas.php)
        header('Location: ../index.php');
        exit;
      } else {
        $error = 'Credenciales incorrectas.';
      }
    } catch (Throwable $e) {
      $error = 'Error de autenticación: ' . $e->getMessage();
    }
  }
}

// Para navbar
$user_email = $_SESSION['correo'] ?? null;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Iniciar sesión</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../estilo/login.css">
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
        <?php if (!empty($user_email)): ?>
          <li><span style="font-weight:700;"><?= htmlspecialchars($user_email) ?></span></li>
          <li><a href="logout.php">Salir</a></li>
        <?php else: ?>
          <li><a href="#" style="font-weight:700;">Cuenta</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </header>

  <!-- Mensaje -->
  <?php if (!empty($error)): ?>
    <div class="toast" role="alert"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- Layout principal -->
  <main class="wrap">
    <section class="card" aria-labelledby="title-login">
      <h1 id="title-login" class="card__title">Iniciar sesión</h1>

      <form method="post" class="form-grid" autocomplete="off" novalidate>
        <div class="field">
          <label for="email">Email</label>
          <input id="email" type="email" name="email" placeholder="tucorreo@ejemplo.com" required
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>

        <div class="field">
          <label for="password">Contraseña</label>
          <div style="position: relative;">
            <input id="password" type="password" name="password" placeholder="Tu contraseña" required>
            <button type="button" id="togglePassword" 
                    style="position:absolute; right:8px; top:50%; transform:translateY(-50%);
                           background:none; border:none; cursor:pointer; font-size:16px;">
              👁️
            </button>
          </div>
        </div>

        <div class="row">
          <input id="rec" type="checkbox" name="remember">
          <label for="rec">Recordarme en este equipo</label>
        </div>

        <button class="btn" type="submit">Entrar</button>

        <div class="foot">¿No tienes cuenta?
          <a href="registrar_usuario.php">Registrarse</a>
        </div>
      </form>
    </section>

    <aside class="side">
      <div class="hero">
        <div class="hero__placeholder">
          <div class="hero__emoji">🔒</div>
          <div>Accede para continuar con tu evaluación o tus mascotas</div>
        </div>
      </div>
    </aside>
  </main>

  <!-- Script mostrar/ocultar contraseña -->
  <script>
    const toggle = document.getElementById("togglePassword");
    const input  = document.getElementById("password");

    toggle.addEventListener("click", () => {
      const isPassword = input.type === "password";
      input.type = isPassword ? "text" : "password";
      toggle.textContent = isPassword ? "🙈" : "👁️";
    });
  </script>

</body>
</html>
