<?php
require_once dirname(__DIR__) . '/config.php';

// ===== [CAMBIO] Bootstrap de sesión y nombres consistentes =====
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
// Antes usabas $_SESSION['user_id']; en las demás pantallas usas 'id_usuario' y 'correo'.
// Unificamos a 'id_usuario' y 'correo' para que sea consistente con login/index/mis_mascotas.
if (empty($_SESSION['id_usuario'])) {
  header("Location: login.php");
  exit;
}
$user_id    = (int)$_SESSION['id_usuario'];      // <- consistente
$user_email = $_SESSION['correo'] ?? null;       // <- para mostrar en navbar

$errores = [];
$ok = null;

/* ---------- Catálogos fijos ---------- */
$cat_especie = $pdo->query("SELECT especie_id, nombre FROM cat_especie ORDER BY nombre")->fetchAll();
$cat_sexo    = $pdo->query("SELECT sexo_id, nombre FROM cat_sexo ORDER BY sexo_id")->fetchAll();
$cat_tamano  = $pdo->query("SELECT tamano_id, nombre FROM cat_tamano ORDER BY tamano_id")->fetchAll();
$cat_tipo    = $pdo->query("SELECT tipo_pelo_id, nombre FROM cat_tipo_pelo ORDER BY tipo_pelo_id")->fetchAll();
$cat_patron  = $pdo->query("SELECT patron_pelo_id, nombre FROM cat_patron_pelo ORDER BY patron_pelo_id")->fetchAll();
$cat_color   = $pdo->query("SELECT color_pelo_id, nombre FROM cat_color_pelo ORDER BY nombre")->fetchAll();

/* ---------- Dependientes por especie ---------- */
$especie_elegida = (int)($_POST['especie_id'] ?? 0);

/* Razas */
$raza_por_especie = [];
if ($especie_elegida) {
  $stmt = $pdo->prepare("SELECT raza_id, nombre FROM cat_raza WHERE especie_id = ? ORDER BY nombre");
  $stmt->execute([$especie_elegida]);
  $raza_por_especie = $stmt->fetchAll();
}

/* Vacunas por especie */
$vacunas = [];
if ($especie_elegida) {
  $stmt = $pdo->prepare("SELECT vacuna_id, nombre FROM cat_vacuna WHERE especie_id = ? ORDER BY nombre");
  $stmt->execute([$especie_elegida]);
  $vacunas = $stmt->fetchAll();
}

/* ---------- Guardar ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
  $nombre     = trim($_POST['nombre'] ?? '');
  $especie_id = (int)($_POST['especie_id'] ?? 0);
  $raza_id    = (int)($_POST['raza_id']    ?? 0);
  $sexo_id    = (int)($_POST['sexo_id']    ?? 0);
  $nac        = trim($_POST['fecha_nacimiento'] ?? '');
  $peso_kg    = trim($_POST['peso_kg'] ?? '');
  $tamano_id  = (int)($_POST['tamano_id'] ?? 0);
  $tipo_pelo_id   = (int)($_POST['tipo_pelo_id'] ?? 0);
  $patron_pelo_id = (int)($_POST['patron_pelo_id'] ?? 0);
  $colores        = (array)($_POST['colores'] ?? []);     // array de color_pelo_id
  $ester      = isset($_POST['esterilizado']) ? (int)$_POST['esterilizado'] : 0;
  $usa_aprox  = !empty($_POST['usa_edad_aprox']) ? 1 : 0;
  $edad_aprox = trim($_POST['edad_aprox_anios'] ?? '');
  $ultima_desparasitacion = trim($_POST['ultima_desparasitacion'] ?? '');
  $senias     = trim($_POST['senias_particulares'] ?? '');
  $microchip_tipo = trim($_POST['microchip_tipo'] ?? '');
  $microchip_num  = trim($_POST['microchip'] ?? '');
  $ruac       = trim($_POST['ruac'] ?? '');
  $notas_adic = trim($_POST['notas_adicionales'] ?? '');

  // Un (1) registro de vacuna opcional en el alta
  $vacuna_id  = (int)($_POST['vacuna_id'] ?? 0);
  $vac_fecha  = trim($_POST['vac_fecha'] ?? '');
  $vac_vig    = trim($_POST['vac_vigencia'] ?? '');
  $vac_lote   = trim($_POST['vac_lote'] ?? '');
  $vac_aplic  = trim($_POST['vac_aplicador'] ?? '');

  // ------- Validaciones mínimas -------
  if ($nombre === '')      $errores[] = "El nombre de la mascota es obligatorio.";
  if (!$especie_id)        $errores[] = "Selecciona una especie.";

  // fecha vs edad aprox
  $fecha_sql = null; $edad_sql = null;
  if ($usa_aprox) {
    if ($edad_aprox !== '' && is_numeric($edad_aprox) && $edad_aprox >= 0 && $edad_aprox <= 50) {
      $edad_sql = number_format((float)$edad_aprox, 1, '.', '');
    } else {
      $errores[] = "Indica una edad aproximada válida (0–50).";
    }
  } else {
    if ($nac !== '') {
      $f = date('Y-m-d', strtotime($nac));
      if ($f && $f <= date('Y-m-d')) $fecha_sql = $f;
      else $errores[] = "Fecha de nacimiento inválida.";
    }
  }

  // peso
  $peso_sql = null;
  if ($peso_kg !== '') {
    if (!is_numeric($peso_kg) || $peso_kg < 0 || $peso_kg > 200) {
      $errores[] = "Peso debe ser un número válido (0–200 kg).";
    } else {
      $peso_sql = number_format((float)$peso_kg, 2, '.', '');
    }
  }

  // desparasitación
  $desp_sql = null;
  if ($ultima_desparasitacion !== '') {
    $d = date('Y-m-d', strtotime($ultima_desparasitacion));
    if ($d && $d <= date('Y-m-d')) $desp_sql = $d; else $errores[] = "Fecha de desparasitación inválida.";
  }

  // validar raza/sexo si vienen
  if ($sexo_id) {
    $ok_sexo = $pdo->prepare("SELECT 1 FROM cat_sexo WHERE sexo_id = ? LIMIT 1");
    $ok_sexo->execute([$sexo_id]);
    if (!$ok_sexo->fetch()) $errores[] = "Sexo inválido.";
  }
  if ($raza_id) {
    $ok_raza = $pdo->prepare("SELECT 1 FROM cat_raza WHERE raza_id = ? AND especie_id = ? LIMIT 1");
    $ok_raza->execute([$raza_id, $especie_id]);
    if (!$ok_raza->fetch()) $errores[] = "Raza inválida para la especie.";
  }

  // ------- Fotos (mínimo 1) -------
  $rutas_fotos = [];
  if (!empty($_FILES['fotos']['name'][0])) {
    $dir = dirname(__DIR__) . '/uploads/mascotas';
    if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
    foreach ($_FILES['fotos']['name'] as $i => $nombreArchivo) {
      if ($_FILES['fotos']['error'][$i] !== UPLOAD_ERR_OK) continue;
      $ext = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
      if (!in_array($ext, ['jpg','jpeg','png','webp','gif'])) continue;
      $base = 'masc_' . time() . '_' . mt_rand(1000,9999) . '_' . $i . '.' . $ext;
      $dest = $dir . '/' . $base;
      if (move_uploaded_file($_FILES['fotos']['tmp_name'][$i], $dest)) {
        $rutas_fotos[] = 'uploads/mascotas/' . $base;
      }
    }
  }
  if (empty($rutas_fotos)) { $errores[] = "Debes subir al menos una foto."; }

  if (!$errores) {
    try {
      // Insert mascota
      $ins = $pdo->prepare("
        INSERT INTO mascota
          (usuario_id, especie_id, raza_id, nombre, sexo_id, fecha_nacimiento, peso_kg, tamano_id, tipo_pelo_id,
           patron_pelo_id, ultima_desparasitacion, esterilizado, usa_edad_aprox, edad_aprox_anios,
           microchip, microchip_tipo, ruac, senias_particulares, notas_adicionales, foto_path, asegurado, creado_en)
        VALUES
          (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, NOW())
      ");
      $ins->execute([
        $user_id, $especie_id, ($raza_id ?: null), $nombre, ($sexo_id ?: null),
        $fecha_sql, $peso_sql, ($tamano_id ?: null), ($tipo_pelo_id ?: null),
        ($patron_pelo_id ?: null), $desp_sql, $ester, $usa_aprox, $edad_sql,
        ($microchip_num ?: null), ($microchip_tipo ?: null), ($ruac ?: null),
        ($senias ?: null), ($notas_adic ?: null),
        isset($_POST['asegurado']) ? 1 : 0
      ]);
      $mascota_id = (int)$pdo->lastInsertId();

      // Colores múltiples
      if ($colores) {
        $insC = $pdo->prepare("INSERT IGNORE INTO mascota_color (mascota_id, color_pelo_id) VALUES (?, ?)");
        foreach ($colores as $cid) {
          $cid = (int)$cid; if ($cid>0) $insC->execute([$mascota_id, $cid]);
        }
      }

      // Fotos (la primera como principal)
      $insF = $pdo->prepare("INSERT INTO mascota_foto (mascota_id, path_relativo, es_principal) VALUES (?, ?, ?)");
      foreach ($rutas_fotos as $i => $ruta) {
        $insF->execute([$mascota_id, $ruta, $i === 0 ? 1 : 0]);
      }

      // Vacuna (opcional) con vigencia
      if ($vacuna_id && $vac_fecha !== '') {
        $fechaVac = date('Y-m-d', strtotime($vac_fecha));
        $vigVac   = $vac_vig ? date('Y-m-d', strtotime($vac_vig)) : null;
        if ($fechaVac && $fechaVac <= date('Y-m-d')) {
          $insV = $pdo->prepare("
            INSERT INTO mascota_vacuna (mascota_id, vacuna_id, fecha_aplicacion, vigencia, lote, aplicador, observaciones)
            VALUES (?, ?, ?, ?, ?, ?, ?)
          ");
          $insV->execute([$mascota_id, $vacuna_id, $fechaVac, $vigVac, ($vac_lote?:null), ($vac_aplic?:null), null]);
        }
      }

      $ok = "Mascota guardada correctamente.";
      $_POST = [];
      $especie_elegida = 0; $raza_por_especie = []; $vacunas = [];
    } catch (Throwable $e) {
      $errores[] = "Error al guardar: " . $e->getMessage();
    }
  }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Agregar mascota</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../estilo/mascota.css">
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
        <li><a href="mis_mascotas.php" style="font-weight:700;">Mascota</a></li>
        <!-- ===== [CAMBIO] Mostrar correo + salir si hay sesión ===== -->
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
    <a href="../index.php">Cuenta</a> › <a href="mis_mascotas.php">Mascotas</a> › <span>Agregar</span>
  </div>

  <?php if ($ok): ?>
    <div class="toast" role="alert"><?= htmlspecialchars($ok) ?></div>
  <?php endif; ?>
  <?php if ($errores): ?>
    <div class="toast" style="background:#fef2f2;border-color:#fecaca;color:#7f1d1d" role="alert">
      <?php foreach ($errores as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <main class="wrap">
    <section class="card" aria-labelledby="title-masc">
      <h1 id="title-masc" class="card__title">Agregar mascota</h1>

      <form method="post" class="form-grid" enctype="multipart/form-data" autocomplete="off">
        <div class="field">
          <label for="nombre">Nombre</label>
          <input id="nombre" name="nombre" required placeholder="Ej. Loki"
                 value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
        </div>

        <div class="field two">
          <div>
            <label for="especie_id">Especie</label>
            <select id="especie_id" name="especie_id" required onchange="this.form.submit()">
              <option value="">-- Selecciona --</option>
              <?php foreach ($cat_especie as $e): ?>
                <option value="<?= $e['especie_id'] ?>" <?= ($especie_elegida == $e['especie_id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($e['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label for="raza_id">Raza (opcional)</label>
            <select id="raza_id" name="raza_id">
              <option value="">-- Selecciona --</option>
              <?php foreach ($raza_por_especie as $r): ?>
                <option value="<?= $r['raza_id'] ?>" <?= (($_POST['raza_id'] ?? '') == $r['raza_id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($r['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="field three">
          <div>
            <label for="sexo_id">Sexo</label>
            <select id="sexo_id" name="sexo_id">
              <option value="">-- Selecciona --</option>
              <?php foreach ($cat_sexo as $s): ?>
                <option value="<?= $s['sexo_id'] ?>" <?= (($_POST['sexo_id'] ?? '') == $s['sexo_id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($s['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label for="peso_kg">Peso (kg)</label>
            <input id="peso_kg" name="peso_kg" placeholder="Ej. 6.7"
                   value="<?= htmlspecialchars($_POST['peso_kg'] ?? '') ?>">
          </div>
          <div>
            <label>Esterilizado</label>
            <div class="tags">
              <label class="chip"><input type="radio" name="esterilizado" value="1" <?= (($_POST['esterilizado'] ?? '0')==='1')?'checked':''; ?>> Sí</label>
              <label class="chip"><input type="radio" name="esterilizado" value="0" <?= (!isset($_POST['esterilizado']) || ($_POST['esterilizado'] ?? '0')==='0')?'checked':''; ?>> No</label>
            </div>
          </div>
        </div>

        <div class="field three">
          <div>
            <label for="tamano_id">Tamaño</label>
            <select id="tamano_id" name="tamano_id">
              <option value="">-- Selecciona --</option>
              <?php foreach ($cat_tamano as $t): ?>
                <option value="<?= $t['tamano_id'] ?>" <?= (($_POST['tamano_id'] ?? '') == $t['tamano_id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($t['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label for="tipo_pelo_id">Tipo de pelo</label>
            <select id="tipo_pelo_id" name="tipo_pelo_id">
              <option value="">-- Selecciona --</option>
              <?php foreach ($cat_tipo as $tp): ?>
                <option value="<?= $tp['tipo_pelo_id'] ?>" <?= (($_POST['tipo_pelo_id'] ?? '') == $tp['tipo_pelo_id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($tp['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label for="patron_pelo_id">Patrón del pelaje</label>
            <select id="patron_pelo_id" name="patron_pelo_id">
              <option value="">-- Selecciona --</option>
              <?php foreach ($cat_patron as $pp): ?>
                <option value="<?= $pp['patron_pelo_id'] ?>" <?= (($_POST['patron_pelo_id'] ?? '') == $pp['patron_pelo_id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($pp['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Colores (múltiple) -->
        <fieldset>
          <legend>Color o colores del pelo</legend>
          <div class="tags">
            <?php foreach ($cat_color as $c): ?>
              <label class="tag">
                <input type="checkbox" name="colores[]" value="<?= $c['color_pelo_id'] ?>"
                  <?= in_array($c['color_pelo_id'], (array)($_POST['colores'] ?? [])) ? 'checked' : '' ?>>
                <?= htmlspecialchars($c['nombre']) ?>
              </label>
            <?php endforeach; ?>
          </div>
        </fieldset>

        <div class="field two">
          <div>
            <label for="fecha_nacimiento">Fecha de nacimiento</label>
            <input id="fecha_nacimiento" type="date" name="fecha_nacimiento"
                   value="<?= htmlspecialchars($_POST['fecha_nacimiento'] ?? '') ?>" <?= !empty($_POST['usa_edad_aprox'])?'disabled':''; ?>>
            <div class="row" style="margin-top:6px">
              <input id="usa_edad_aprox" type="checkbox" name="usa_edad_aprox" onchange="this.form.submit()" <?= !empty($_POST['usa_edad_aprox']) ? 'checked' : '' ?>>
              <label for="usa_edad_aprox" class="label-inline">No conozco la fecha; usar edad aproximada</label>
            </div>
          </div>
          <div>
            <label for="edad_aprox_anios">Edad aproximada (años)</label>
            <input id="edad_aprox_anios" name="edad_aprox_anios" placeholder="Ej. 2.5"
                   value="<?= htmlspecialchars($_POST['edad_aprox_anios'] ?? '') ?>" <?= empty($_POST['usa_edad_aprox'])?'disabled':''; ?>>
          </div>
        </div>

        <div class="field two">
          <div>
            <label for="ultima_desparasitacion">Fecha de última desparasitación</label>
            <input id="ultima_desparasitacion" type="date" name="ultima_desparasitacion"
                   value="<?= htmlspecialchars($_POST['ultima_desparasitacion'] ?? '') ?>">
          </div>
          <div>
            <label for="ruac">RUAC</label>
            <input id="ruac" name="ruac" placeholder="Código RUAC"
                   value="<?= htmlspecialchars($_POST['ruac'] ?? '') ?>">
          </div>
        </div>

        <div class="field two">
          <div>
            <label for="microchip_tipo">Tipo de chip</label>
            <input id="microchip_tipo" name="microchip_tipo" placeholder="p.ej. FDX-B">
          </div>
          <div>
            <label for="microchip">Número de chip</label>
            <input id="microchip" name="microchip" placeholder="Ej. 14294129840921"
                   value="<?= htmlspecialchars($_POST['microchip'] ?? '') ?>">
          </div>
        </div>

        <div class="field">
          <label for="senias_particulares">Señas particulares</label>
          <textarea id="senias_particulares" name="senias_particulares" rows="2"
            placeholder="Manchas, cicatrices, colita corta, etc."><?= htmlspecialchars($_POST['senias_particulares'] ?? '') ?></textarea>
        </div>

        <!-- Una vacuna opcional (aplicada) -->
        <?php if ($vacunas): ?>
        <fieldset>
          <legend>Cuadro de vacunación (opcional)</legend>
          <div class="field three">
            <div>
              <label for="vacuna_id">Vacuna aplicada</label>
              <select id="vacuna_id" name="vacuna_id">
                <option value="">-- Selecciona --</option>
                <?php foreach ($vacunas as $v): ?>
                  <option value="<?= $v['vacuna_id'] ?>" <?= (($_POST['vacuna_id'] ?? '') == $v['vacuna_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($v['nombre']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label for="vac_fecha">Fecha de inoculación</label>
              <input id="vac_fecha" type="date" name="vac_fecha" value="<?= htmlspecialchars($_POST['vac_fecha'] ?? '') ?>">
            </div>
            <div>
              <label for="vac_vigencia">Vigencia</label>
              <input id="vac_vigencia" type="date" name="vac_vigencia" value="<?= htmlspecialchars($_POST['vac_vigencia'] ?? '') ?>">
            </div>
          </div>
          <div class="field two">
            <div>
              <label for="vac_lote">Lote</label>
              <input id="vac_lote" name="vac_lote" value="<?= htmlspecialchars($_POST['vac_lote'] ?? '') ?>">
            </div>
            <div>
              <label for="vac_aplicador">Aplicador</label>
              <input id="vac_aplicador" name="vac_aplicador" value="<?= htmlspecialchars($_POST['vac_aplicador'] ?? '') ?>">
            </div>
          </div>
        </fieldset>
        <?php endif; ?>

        <!-- Fotos (mínimo 1) -->
        <div class="field">
          <label for="fotos">Fotos (al menos una)</label>
          <input id="fotos" type="file" name="fotos[]" accept=".jpg,.jpeg,.png,.webp,.gif" multiple onchange="previewImgs(this)">
          <div class="preview" id="preview"></div>
        </div>

        <div class="field">
          <label for="notas_adicionales">Notas adicionales</label>
          <textarea id="notas_adicionales" name="notas_adicionales" rows="3"
            placeholder="Observaciones extra"><?= htmlspecialchars($_POST['notas_adicionales'] ?? '') ?></textarea>
        </div>

        <div class="actions">
          <a class="btn ghost" href="mis_mascotas.php">Cancelar</a>
          <button class="btn" type="submit" name="guardar" value="1">Guardar mascota</button>
        </div>
      </form>
    </section>

    <aside class="side">
      <div class="hero">
        <div class="hero__placeholder">
          <div class="hero__emoji">🐕</div>
          <div>Completa los datos y guarda tu mascota</div>
        </div>
      </div>
    </aside>
  </main>

  <script>
    function previewImgs(input){
      const cont = document.getElementById('preview');
      cont.innerHTML = '';
      if (!input.files) return;
      Array.from(input.files).forEach(f=>{
        const ext = f.name.split('.').pop().toLowerCase();
        if (!['jpg','jpeg','png','webp','gif'].includes(ext)) return;
        const img = document.createElement('img');
        img.className = 'preview__img';
        img.src = URL.createObjectURL(f);
        cont.appendChild(img);
      });
    }
  </script>
</body>
</html>
