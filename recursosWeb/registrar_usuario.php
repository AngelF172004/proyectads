<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Crear cuenta</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../estilo/registro.css">
  <script src="../js/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    .btn { margin-top: 8px; margin-right: 6px; }
    .tel-row { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; margin-bottom: 10px; }
    .tel-row .btn { margin-left: 8px; height: fit-content; }
    /* [CAMBIO] Estilos ligeros para ver/ocultar y feedback */
    .pwd-wrap { position: relative; }
    .toggle-pass { position:absolute; right:8px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; font-size:14px; }
    .hint { font-size:.9rem; margin-top:4px; opacity:.9; }
    .ok { color:#065f46; }   /* verde */
    .bad { color:#b91c1c; }  /* rojo */
  </style>
</head>
<body>

  <header class="header">
    <nav class="nav">
      <div class="logo">🐾 <span>Petlife</span></div>
      <ul class="menu">
        <li><a href="../index.php">Inicio</a></li>
        <li><a href="../index.php#servicios">Servicios</a></li>
        <li><a href="../index.php#acerca">Acerca de</a></li>
        <li><a href="login.php" style="font-weight:700;">Cuenta</a></li>
      </ul>
    </nav>
  </header>

  <main class="wrap">
    <section class="card" aria-labelledby="title-reg">
      <h1 id="title-reg" class="card__title">Crear cuenta</h1>

      <form method="post" id="formuser" class="form-grid" enctype="multipart/form-data" autocomplete="off">
        
        <!-- Nombres y apellidos -->
        <div class="field">
          <label for="nombres">Nombres</label>
          <input id="nombres" name="nombres" required placeholder="Ej. Fernanda">
        </div>
        <div class="field two">
          <div>
            <label for="apellido_paterno">Primer apellido</label>
            <input id="apellido_paterno" name="apellido_paterno" required placeholder="Ej. García">
          </div>
          <div>
            <label for="apellido2">Segundo apellido</label>
            <input id="apellido2" name="apellido2" placeholder="Opcional">
          </div>
        </div>

        <!-- Contacto principal -->
        <div class="field">
          <label for="correo">Correo personal</label>
          <input id="correo" type="email" name="correo" required placeholder="tucorreo@ejemplo.com">
        </div>

        <!-- Sexo y fecha de nacimiento -->
        <div class="field two">
          <div>
            <label for="sexo_id">Sexo</label>
            <select id="sexo_id" name="sexo_id">
              <option value="">Selecciona</option>
              <option value="Hombre">Hombre</option>
              <option value="Mujer">Mujer</option>
            </select>
          </div>
          <div>
            <label for="fecha_nacimiento">Fecha de nacimiento</label>
            <input id="fecha_nacimiento" type="date" name="fecha_nacimiento">
          </div>
        </div>

        <!-- Teléfonos (dinámicos) -->
        <fieldset>
          <legend>Teléfonos de contacto</legend>
          <div id="tels">
            <div class="field two tel-row">
              <div>
                <label>Teléfono</label>
                <input name="telefono[]" class="telefono" placeholder="10 dígitos">
              </div>
              <div>
                <label>Tipo</label>
                <select name="telefono_tipo[]" class="tipo-tel" onchange="toggleOtro(this)">
                  <option value="">Selecciona</option>
                  <option value="casa">Casa</option>
                  <option value="trabajo">Trabajo</option>
                  <option value="otro">Otro</option>
                </select>
                <input type="text" name="telefono_otro[]" class="otro-tel" style="display:none;" placeholder="Especificar tipo">
              </div>
              <button type="button" class="btn ghost" onclick="removeTel(this)">❌ Quitar</button>
            </div>
          </div>
          <button type="button" class="btn ghost" onclick="addTel()">➕ Agregar otro teléfono</button>
        </fieldset>

        <!-- Domicilio -->
        <fieldset>
          <legend>Domicilio</legend>
          <div class="field">
            <label for="tipo_domicilio">Tipo de domicilio</label>
            <input id="tipo_domicilio" name="tipo_domicilio" placeholder="casa, departamento, otro">
          </div>
          <div class="field two">
            <div>
              <label for="dom_calle">Calle</label>
              <input id="dom_calle" name="dom_calle" required>
            </div>
            <div>
              <label for="dom_num_ext">No. exterior</label>
              <input id="dom_num_ext" name="dom_num_ext" required>
            </div>
          </div>
          <div class="field two">
            <div>
              <label for="dom_num_int">No. interior</label>
              <input id="dom_num_int" name="dom_num_int">
            </div>
            <div>
              <label for="dom_colonia">Colonia</label>
              <input id="dom_colonia" name="dom_colonia" required>
            </div>
          </div>
          <div class="field two">
            <div>
              <label for="dom_municipio">Municipio/Alcaldía</label>
              <input id="dom_municipio" name="dom_municipio">
            </div>
            <!-- Eliminados Ciudad, Estado, País -->
            <div>
              <label for="dom_cp">Código Postal</label>
              <input id="dom_cp" name="dom_cp" required>
            </div>
          </div>
        </fieldset>

        <!-- Foto de perfil -->
        <div class="field">
          <label for="foto_perfil">Foto de perfil</label>
          <input id="foto_perfil" type="file" name="foto_perfil"
                 accept=".jpg,.jpeg,.png,.webp" onchange="previewImg(this)">
          <div class="preview" id="preview"></div>
        </div>

        <!-- Acceso -->
        <div class="field two">
          <div>
            <label for="password">Contraseña</label>
            <!-- [CAMBIO] contenedor con botón mostrar/ocultar -->
            <div class="pwd-wrap">
              <input id="password" type="password" name="password" required placeholder="Mínimo 6 caracteres">
              <button type="button" class="toggle-pass" data-target="#password">👁️</button>
            </div>
            <small class="hint">Mínimo 6 caracteres.</small>
          </div>
          <div>
            <label for="confirmar">Confirmar contraseña</label>
            <!-- [CAMBIO] contenedor con botón mostrar/ocultar -->
            <div class="pwd-wrap">
              <input id="confirmar" type="password" name="confirmar" required placeholder="Repite tu contraseña">
              <button type="button" class="toggle-pass" data-target="#confirmar">👁️</button>
            </div>
            <!-- [CAMBIO] indicador en vivo -->
            <small id="matchMsg" class="hint"></small>
          </div>
        </div>

        <button class="btn" type="submit">Crear cuenta</button>
        <div class="foot">¿Ya tienes cuenta? <a href="login.php">Iniciar sesión</a></div>
      </form>
    </section>
  </main>

<script>
$(document).ready(function() {
  $('#formuser').submit(function(event) {
    event.preventDefault();

    var pass = $('#password').val();
    var conf = $('#confirmar').val();

    if (!pass || pass.length < 6) {
      Swal.fire({icon:'warning', title:'Contraseña muy corta', text:'Mínimo 6 caracteres'});
      return;
    }
    if (pass !== conf) {
      Swal.fire({icon:'warning', title:'Las contraseñas no coinciden'});
      return;
    }

    var fd = new FormData();
    fd.append('nombres', $('#nombres').val());
    fd.append('apellido_paterno', $('#apellido_paterno').val());
    fd.append('apellido2', $('#apellido2').val());
    fd.append('correo', $('#correo').val());
    fd.append('sexo_id', $('#sexo_id').val());
    fd.append('fecha_nacimiento', $('#fecha_nacimiento').val());

    document.querySelectorAll('input[name="telefono[]"]').forEach(el => fd.append('telefono[]', el.value));
    document.querySelectorAll('select[name="telefono_tipo[]"]').forEach(el => fd.append('telefono_tipo[]', el.value));
    document.querySelectorAll('input[name="telefono_otro[]"]').forEach(el => fd.append('telefono_otro[]', el.value));

    fd.append('tipo_domicilio', $('#tipo_domicilio').val());
    fd.append('dom_calle', $('#dom_calle').val());
    fd.append('dom_num_ext', $('#dom_num_ext').val());
    fd.append('dom_num_int', $('#dom_num_int').val());
    fd.append('dom_colonia', $('#dom_colonia').val());
    fd.append('dom_municipio', $('#dom_municipio').val());
    fd.append('dom_cp', $('#dom_cp').val());
    // Eliminados dom_ciudad, dom_estado y dom_pais

    fd.append('password', pass);
    fd.append('confirmar', conf);

    var file = document.getElementById('foto_perfil').files[0];
    if (file) fd.append('foto_perfil', file);

    $.ajax({
      type: 'POST',
      url: 'user.php',
      data: fd,
      processData: false,
      contentType: false,
      success: function(response) {
        Swal.fire({title:"Registro exitoso!", text:response, icon:"success"});
        $('#formuser')[0].reset();
        $('#preview').html('');
        $('#matchMsg').text('').removeClass('ok bad');
      },
      error: function(xhr, status, error) {
        Swal.fire({title:"Error", text:xhr.responseText || error, icon:"error"});
      }
    });
  });

  // [CAMBIO] Mostrar/ocultar contraseña para ambos campos
  $(document).on('click', '.toggle-pass', function(){
    const target = $(this).data('target');
    const $inp = $(target);
    if (!$inp.length) return;
    const isPwd = $inp.attr('type') === 'password';
    $inp.attr('type', isPwd ? 'text' : 'password');
    $(this).text(isPwd ? '🙈' : '👁️');
  });

  // [CAMBIO] Indicador en vivo de coincidencia
  function checkMatch() {
    const p1 = $('#password').val();
    const p2 = $('#confirmar').val();
    const $msg = $('#matchMsg');
    $msg.removeClass('ok bad');
    if (!p1 && !p2) { $msg.text(''); return; }
    if (p2.length === 0) { $msg.text(''); return; }

    if (p1 === p2) {
      $msg.text('Las contraseñas coinciden.').addClass('ok');
    } else {
      $msg.text('Las contraseñas no coinciden.').addClass('bad');
    }
  }
  $('#password, #confirmar').on('input', checkMatch);
});

function toggleOtro(select){
  const inputOtro = select.parentElement.querySelector('.otro-tel');
  inputOtro.style.display = (select.value === 'otro') ? 'block' : 'none';
  if(select.value !== 'otro') inputOtro.value = '';
}

function addTel(){
  const wrap = document.getElementById('tels');
  const div = document.createElement('div');
  div.className = 'field two tel-row';
  div.innerHTML = `
    <div>
      <label>Teléfono</label>
      <input name="telefono[]" class="telefono" placeholder="10 dígitos">
    </div>
    <div>
      <label>Tipo</label>
      <select name="telefono_tipo[]" class="tipo-tel" onchange="toggleOtro(this)">
        <option value="">Selecciona</option>
        <option value="casa">Casa</option>
        <option value="trabajo">Trabajo</option>
        <option value="otro">Otro</option>
      </select>
      <input type="text" name="telefono_otro[]" class="otro-tel" style="display:none;" placeholder="Especificar tipo">
    </div>
    <button type="button" class="btn ghost" onclick="removeTel(this)">❌ Quitar</button>
  `;
  wrap.appendChild(div);
}

function removeTel(button){
  const row = button.closest('.tel-row');
  row.remove();
}

function previewImg(input){
  const cont = document.getElementById('preview');
  cont.innerHTML = '';
  if (input.files && input.files[0]) {
    const img = document.createElement('img');
    img.className = 'preview__img';
    img.src = URL.createObjectURL(input.files[0]);
    cont.appendChild(img);
  }
}
</script>

</body>
</html>
