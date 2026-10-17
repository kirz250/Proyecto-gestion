<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$mensaje_exito = isset($_GET['registro']) && $_GET['registro'] == 'exitoso';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Registro - PAE</title>
    
    <link href="../startbootstrap-sb-admin-gh-pages/css/styles.css" rel="stylesheet" />
    <link href="../fontawesome-free-7.2.0-web/css/all.min.css" rel="stylesheet" />
    
    <style>
        body {
            background-image: url('../photo_5037406883823684672_y.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }

        .blur-overlay-global {
            background: linear-gradient(145deg, rgba(30, 34, 41, 0.5) 0%, rgba(17, 20, 24, 0.65) 100%);
            backdrop-filter: blur(3px);
            -webkit-backdrop-filter: blur(8px);
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: 1;
        }

        .main-content-container {
            position: relative;
            z-index: 2;
            min-height: 100vh;
        }

        .register-card {
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            padding: 2.5rem;
            margin: 2rem auto;
        }

        .form-floating .form-control:focus { 
            border-color: #0d6efd; 
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15); 
        }
        .btn-register-hover { 
            transition: all 0.2s ease-in-out; 
        }
        .btn-register-hover:hover { 
            transform: translateY(-1px); 
            box-shadow: 0 6px 12px rgba(13, 110, 253, 0.25) !important; 
        }
        .password-toggle { 
            position: absolute; 
            right: 15px; 
            top: 50%; 
            transform: translateY(-50%); 
            cursor: pointer; 
            z-index: 10; 
        }
    </style>
</head>

<body>

    <div class="blur-overlay-global"></div>

    <div class="container main-content-container d-flex align-items-center justify-content-center">
        
        <div class="register-card">
            
            <?php if ($mensaje_exito): ?>
                <div class="alert alert-success alert-dismissible fade show text-dark shadow-sm" role="alert">
                    <i class="fa-solid fa-circle-check me-2 text-success"></i> <strong>¡Registro exitoso!</strong> Ya puedes <a href="login.php" class="alert-link text-primary text-decoration-none fw-bold">iniciar sesión</a>.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="mb-4 text-center">
                <h3 class="fw-bold text-primary mb-1"><i class="fa-solid fa-wheat-awn text-success me-2"></i> PAE</h3>
                <h4 class="fw-bold text-dark mb-1">Crear Cuenta</h4>
                <p class="text-muted small mb-0">Sistema de Gestión Alimentaria e Inventario.</p>
            </div>

            <form method="post" action="registrar.php" id="formRegistro" class="needs-validation" novalidate>
                
                <input type="hidden" name="boton_registro" value="1" />

                <!-- Cédula -->
                <div class="form-floating mb-3 text-dark">
                    <input class="form-control" id="inputCedula" name="cedula" type="text" 
                           pattern="[0-9]{7,8}" maxlength="8"
                           placeholder="C.I." required 
                           oninput="this.value = this.value.replace(/[^0-9]/g, '').substring(0,8)" />
                    <label for="inputCedula"><i class="fa-solid fa-id-card me-2 text-primary opacity-75"></i>Cédula de Identidad</label>
                    <div class="invalid-feedback" id="cedulaFeedback">Por favor, ingresa una cédula válida (7 a 8 dígitos numéricos).</div>
                </div>

                <!-- Usuario (Solo letras, sin espacios, números ni caracteres raros) -->
                <div class="form-floating mb-3 text-dark">
                    <input class="form-control" id="inputFirstName" name="nombre_de_usuario" type="text" 
                           minlength="4" maxlength="20" pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ]+"
                           placeholder="Ej. maria" required 
                           oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ]/g, '')" />
                    <label for="inputFirstName"><i class="fa-solid fa-user me-2 text-primary opacity-75"></i>Nombre de usuario</label>
                    <div class="invalid-feedback" id="usuarioFeedback">Debe tener entre 4 y 20 caracteres (solo letras, sin números ni símbolos).</div>
                </div>
                
                <!-- Email -->
                <div class="form-floating mb-3 text-dark">
                    <input class="form-control" id="inputEmail" name="email" type="email" 
                           placeholder="Email" required />
                    <label for="inputEmail"><i class="fa-solid fa-envelope me-2 text-primary opacity-75"></i>Correo electrónico</label>
                    <div class="invalid-feedback">Por favor, introduce una dirección de correo válida (ejemplo@correo.com).</div>
                </div>
                
                <!-- Contraseña -->
                <div class="form-floating mb-3 text-dark position-relative">
                    <input class="form-control" id="inputPassword" name="contrasena" type="password" 
                           minlength="6" maxlength="20" placeholder="Pass" required />
                    <label for="inputPassword"><i class="fa-solid fa-lock me-2 text-muted"></i>Contraseña</label>
                    <i class="fa-solid fa-eye password-toggle text-muted" onclick="togglePass('inputPassword')"></i>
                    <div class="invalid-feedback">La contraseña debe tener entre 6 y 20 caracteres.</div>
                </div>
                
                <!-- Confirmar Contraseña (Ahora con maxlength="20") -->
                <div class="form-floating mb-4 text-dark position-relative">
                    <input class="form-control" id="inputPasswordConfirm" name="confirmar_contraseña" type="password" 
                           maxlength="20" placeholder="Confirm" required />
                    <label for="inputPasswordConfirm"><i class="fa-solid fa-shield-halved me-2 text-muted"></i>Confirmar contraseña</label>
                    <i class="fa-solid fa-eye password-toggle text-muted" onclick="togglePass('inputPasswordConfirm')"></i>
                    <div class="invalid-feedback" id="confirmFeedback">Las contraseñas no coinciden o superan los 20 caracteres.</div>
                </div>
                
                <div class="d-grid gap-2 mb-3">
                    <button class="btn btn-primary btn-lg shadow btn-register-hover fw-bold py-3" type="button" onclick="validarYPasarAModal()">
                        Registrar Usuario
                    </button>
                </div>

                <div class="text-center small">
                    <a href="login.php" class="text-decoration-none fw-bold text-primary">¿Ya tienes cuenta? Inicia sesión</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Preguntas -->
    <div class="modal fade" id="modalSeguridad" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg text-dark">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold">Preguntas de Seguridad</h5>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">Estas preguntas le ayudarán a recuperar su contraseña en caso de olvido.</p>
                    
                    <div class="form-floating mb-3">
                        <input class="form-control" id="modalColor" name="color_favorito" type="text" placeholder="Color" form="formRegistro" required />
                        <label for="modalColor">¿Cuál es tu color favorito?</label>
                        <div class="text-danger small d-none" id="errColor">Esta respuesta es obligatoria.</div>
                    </div>
                    <div class="form-floating mb-3">
                        <input class="form-control" id="modalPelicula" name="pelicula_favorita" type="text" placeholder="Pelicula" form="formRegistro" required />
                        <label for="modalPelicula">¿Cuál es tu película favorita?</label>
                        <div class="text-danger small d-none" id="errPelicula">Esta respuesta es obligatoria.</div>
                    </div>
                    <div class="form-floating mb-2">
                        <input class="form-control" id="modalMascota" name="mascota_nombre" type="text" placeholder="Mascota" form="formRegistro" required />
                        <label for="modalMascota">¿Cómo se llama tu mascota?</label>
                        <div class="text-danger small d-none" id="errMascota">Esta respuesta es obligatoria.</div>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Regresar</button>
                    <button type="button" id="btnConfirmar" class="btn btn-primary fw-bold">Confirmar y Registrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
    function togglePass(id) {
        const input = document.getElementById(id);
        if(input) input.type = (input.type === "password") ? "text" : "password";
    }

    document.addEventListener('DOMContentLoaded', function() {
        const inputs = [
            document.getElementById('inputCedula'),
            document.getElementById('inputFirstName'),
            document.getElementById('inputEmail'),
            document.getElementById('inputPassword'),
            document.getElementById('inputPasswordConfirm')
        ];

        inputs.forEach(input => {
            if (!input) return;

            ['input', 'blur'].forEach(evt => {
                input.addEventListener(evt, function() {
                    validarCampoEspecifico(input);
                });
            });
        });

        const modalInputs = ['modalColor', 'modalPelicula', 'modalMascota'];
        modalInputs.forEach(id => {
            const el = document.getElementById(id);
            const errEl = document.getElementById('err' + id.replace('modal', ''));
            if(el) {
                el.addEventListener('input', function() {
                    if (el.value.trim() !== "") {
                        errEl.classList.add('d-none');
                        el.classList.remove('is-invalid');
                        el.classList.add('is-valid');
                    } else {
                        errEl.classList.remove('d-none');
                        el.classList.add('is-invalid');
                        el.classList.remove('is-valid');
                    }
                });
            }
        });
    });

    function validarCampoEspecifico(input) {
        if (!input) return false;

        const pass = document.getElementById('inputPassword');
        const passConfirm = document.getElementById('inputPasswordConfirm');
        const confirmFeedback = document.getElementById('confirmFeedback');

        // Tratamiento de contraseñas y coincidencia de longitud
        if (input.id === 'inputPasswordConfirm' || input.id === 'inputPassword') {
            if (passConfirm.value.length > 20) {
                passConfirm.setCustomValidity("La contraseña no puede superar los 20 caracteres.");
                if (confirmFeedback) confirmFeedback.textContent = "La contraseña no puede superar los 20 caracteres.";
            } else if (pass.value !== passConfirm.value && passConfirm.value !== "") {
                passConfirm.setCustomValidity("Las contraseñas no coinciden.");
                if (confirmFeedback) confirmFeedback.textContent = "Las contraseñas no coinciden.";
            } else {
                passConfirm.setCustomValidity("");
            }
        }

        if (input.checkValidity()) {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
            return true;
        } else {
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
            return false;
        }
    }

    function validarYPasarAModal() {
        const form = document.getElementById('formRegistro');
        const camposBase = [
            document.getElementById('inputCedula'),
            document.getElementById('inputFirstName'),
            document.getElementById('inputEmail'),
            document.getElementById('inputPassword'),
            document.getElementById('inputPasswordConfirm')
        ];

        let formularioBaseValido = true;

        camposBase.forEach(input => {
            const esValido = validarCampoEspecifico(input);
            if (!esValido) {
                formularioBaseValido = false;
            }
        });

        if (formularioBaseValido) {
            form.classList.remove('was-validated');
            const modalElement = document.getElementById('modalSeguridad');
            const myModal = new bootstrap.Modal(modalElement);
            myModal.show();
        } else {
            const primerError = document.querySelector('.is-invalid');
            if (primerError) {
                primerError.focus();
            }
        }
    }

    document.getElementById('btnConfirmar').addEventListener('click', function() {
        const btn = this;
        const form = document.getElementById('formRegistro');
        
        const color = document.getElementById('modalColor');
        const pelicula = document.getElementById('modalPelicula');
        const mascota = document.getElementById('modalMascota');

        let modalValido = true;

        if (color.value.trim() === "") {
            document.getElementById('errColor').classList.remove('d-none');
            color.classList.add('is-invalid');
            modalValido = false;
        } else { 
            document.getElementById('errColor').classList.add('d-none'); 
            color.classList.remove('is-invalid');
            color.classList.add('is-valid');
        }

        if (pelicula.value.trim() === "") {
            document.getElementById('errPelicula').classList.remove('d-none');
            pelicula.classList.add('is-invalid');
            modalValido = false;
        } else { 
            document.getElementById('errPelicula').classList.add('d-none'); 
            pelicula.classList.remove('is-invalid');
            pelicula.classList.add('is-valid');
        }

        if (mascota.value.trim() === "") {
            document.getElementById('errMascota').classList.remove('d-none');
            mascota.classList.add('is-invalid');
            modalValido = false;
        } else { 
            document.getElementById('errMascota').classList.add('d-none'); 
            mascota.classList.remove('is-invalid');
            mascota.classList.add('is-valid');
        }

        if (modalValido) {
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Procesando...';
            btn.disabled = true;
            form.submit(); 
        }
    });
    </script>
</body>
</html>