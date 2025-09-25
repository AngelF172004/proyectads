<?php
require_once dirname(__DIR__) . '/conexion.php';

$ok = null;
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // ---- Datos principales
  $nombres           = trim($_POST['nombres']           ?? '');
  $apellido_paterno  = trim($_POST['apellido_paterno']  ?? '');
  $ap2               = trim($_POST['apellido2']         ?? '');
  $correo            = trim($_POST['correo']            ?? '');
  $sexo_id           = trim($_POST['sexo_id']           ?? '');
  $fecha_nacimiento  = trim($_POST['fecha_nacimiento']  ?? '');

  // Teléfonos (puede venir string o array)
  $telefono      = $_POST['telefono']      ?? [];
  $telefono_tipo = $_POST['telefono_tipo'] ?? [];
  if (!is_array($telefono))      { $telefono = [$telefono]; }
  if (!is_array($telefono_tipo)) { $telefono_tipo = [$telefono_tipo]; }
  $telefonos_str = implode(',', $telefono);
  $tipos_str     = implode(',', $telefono_tipo);

  // ---- Domicilio
  $tipo_dom   = trim($_POST['tipo_domicilio'] ?? '');
  $calle      = trim($_POST['dom_calle']      ?? '');
  $num_ext    = trim($_POST['dom_num_ext']    ?? '');
  $num_int    = trim($_POST['dom_num_int']    ?? '');
  $colonia    = trim($_POST['dom_colonia']    ?? '');
  $municipio  = trim($_POST['dom_municipio']  ?? '');
  // [CAMBIO] Eliminados del POST: dom_ciudad, dom_estado, dom_pais
  $cp         = trim($_POST['dom_cp']         ?? '');

  // ---- Contraseña (hash)
  $password   = $_POST['password']  ?? '';
  $confirmar  = $_POST['confirmar'] ?? '';

  // validaciones
  if (strlen($password) < 6) {
    echo "Error al insertar usuario: La contraseña debe tener al menos 6 caracteres.";
    exit;
  }
  if ($confirmar !== '' && $password !== $confirmar) {
    echo "Error al insertar usuario: Las contraseñas no coinciden.";
    exit;
  }

  // generar hash para almacenar de forma segura
  $password_hash = password_hash($password, PASSWORD_BCRYPT);

  // (OPCIONAL - solo para fines académicos) guardar copia visible
  $password_visible = $password;

  // ---- Foto (opcional) - solo preparamos datos; movemos archivo después del INSERT
  $tieneFoto = isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] !== UPLOAD_ERR_NO_FILE;
  $foto       = $_FILES['foto_perfil'] ?? null;
  $fotoNombreFinal = null;
  $fotoUrlRel = null;
  $fotoMime   = null;
  $fotoSize   = null;
  $absPath    = null;

  if ($tieneFoto) {
    if ($foto['error'] !== UPLOAD_ERR_OK) {
      echo "Error al insertar usuario: Error al subir la imagen.";
      exit;
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($foto['tmp_name']);
    $permitidos = ['image/jpeg','image/png','image/webp'];
    if (!in_array($mime, $permitidos, true)) {
      echo "Error al insertar usuario: Formato de imagen no permitido (solo JPG/PNG/WEBP).";
      exit;
    }
    if ($foto['size'] > 5 * 1024 * 1024) {
      echo "Error al insertar usuario: La imagen excede 5MB.";
      exit;
    }
    $ext = pathinfo($foto['name'], PATHINFO_EXTENSION);
    $fotoNombreFinal = bin2hex(random_bytes(8)) . '.' . strtolower($ext ?: 'jpg');
    $fotoUrlRel = '/uploads/usuarios/' . $fotoNombreFinal;
    $absPath    = dirname(__DIR__) . $fotoUrlRel; // ../uploads/usuarios/...
    $dir        = dirname($absPath);
    if (!is_dir($dir)) {
      if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
        echo "Error al insertar usuario: No se pudo crear la carpeta de uploads.";
        exit;
      }
    }
    $fotoMime = $mime;
    $fotoSize = (int)$foto['size'];
  }

  try {
    // --- Transacción para consistencia
    $pdo->beginTransaction();

    // [CAMBIO] INSERT sin columnas ciudad, estado, pais
    $query = "INSERT INTO usuarios (
        nombre, apellido_paterno, apellido_materno, correo, sexo, fecha_de_nacimiento,
        telefono, telefono_tipo, tipo_domicilio, calle, no_exterior,
        no_interior, colonia, municipio, codigo_postal,
        password_hash
      ) VALUES (
        :nombres, :apellido_paterno, :apellido2, :correo, :sexo_id, :fecha_nacimiento,
        :telefono, :telefono_tipo, :tipo_dom, :calle, :num_ext,
        :num_int, :colonia, :municipio, :cp,
        :password_hash
      )
      RETURNING id_usuario";

    $ins = $pdo->prepare($query);
    $ins->bindParam(':nombres',           $nombres);
    $ins->bindParam(':apellido_paterno',  $apellido_paterno);
    $ins->bindParam(':apellido2',         $ap2);
    $ins->bindParam(':correo',            $correo);
    $ins->bindParam(':sexo_id',           $sexo_id);
    $ins->bindParam(':fecha_nacimiento',  $fecha_nacimiento);
    $ins->bindParam(':telefono',          $telefonos_str);
    $ins->bindParam(':telefono_tipo',     $tipos_str);
    $ins->bindParam(':tipo_dom',          $tipo_dom);
    $ins->bindParam(':calle',             $calle);
    $ins->bindParam(':num_ext',           $num_ext);
    $ins->bindParam(':num_int',           $num_int);
    $ins->bindParam(':colonia',           $colonia);
    $ins->bindParam(':municipio',         $municipio);
    $ins->bindParam(':cp',                $cp);
    $ins->bindParam(':password_hash',     $password_hash);

    $ins->execute();
    $idUsuario = (int)$ins->fetchColumn();

    // Si hay foto: mover a disco, registrar en foto_usuario y enlazar en usuarios
    if ($tieneFoto) {
      if (!move_uploaded_file($foto['tmp_name'], $absPath)) {
        $pdo->rollBack();
        echo "Error al insertar usuario: No se pudo guardar la imagen en disco.";
        exit;
      }

      // Inserta metadatos de la foto como principal
      $sqlFoto = "INSERT INTO foto_usuario
        (user_id, file_name, url_rel, mime_type, size_bytes, es_principal)
        VALUES (:uid, :fn, :url, :mime, :sz, TRUE)
        RETURNING id";
      $stf = $pdo->prepare($sqlFoto);
      $stf->execute([
        ':uid'  => $idUsuario,
        ':fn'   => $fotoNombreFinal,
        ':url'  => $fotoUrlRel,
        ':mime' => $fotoMime,
        ':sz'   => $fotoSize
      ]);
      $idFoto = (int)$stf->fetchColumn();

      // Enlazar en usuarios.id_foto_dueno
      $upd = $pdo->prepare("UPDATE usuarios SET id_foto_dueno = :fid WHERE id_usuario = :uid");
      $upd->execute([':fid' => $idFoto, ':uid' => $idUsuario]);
    }

    $pdo->commit();
    echo "Usuario creado (ID: $idUsuario)" . ($tieneFoto ? " con foto de perfil" : "");
  } catch (PDOException $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    echo "Error al insertar usuario: " . $e->getMessage();
  }
}
?>
