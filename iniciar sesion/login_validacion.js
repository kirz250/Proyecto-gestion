document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.querySelector('form');
    // Busca el texto de bienvenida usando la clase exacta de tu HTML
    const textoBienvenida = document.querySelector('.text-muted.small.mb-0');

    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            // 1. Limpiar alertas previas para que no se acumulen en pantalla
            const alertaExistente = document.querySelector('.alert-validacion');
            if (alertaExistente) alertaExistente.remove();

            // 2. Capturar los inputs del HTML (Cuidado con mayúsculas/minúsculas en los IDs)
            const usuarioInput = document.getElementById('inputUsuario');
            const contrasenaInput = document.getElementById('inputContrasena');

            const usuario = usuarioInput ? usuarioInput.value.trim() : "";
            const contrasena = contrasenaInput ? contrasenaInput.value.trim() : "";

            let errores = [];

            // 3. Validaciones básicas en el lado del cliente
            if (usuario === "" || contrasena === "") {
                errores.push("Por favor, rellene todos los campos para ingresar al sistema.");
            }

            // 📌 NUEVA VALIDACIÓN: Límite máximo de 20 caracteres para el Usuario
            if (usuario !== "" && usuario.length > 20) {
                errores.push("El usuario no puede superar los 20 caracteres.");
            }

            // 📌 NUEVA VALIDACIÓN: Límite máximo de 20 caracteres para la Contraseña
            if (contrasena !== "" && contrasena.length > 20) {
                errores.push("La contraseña no puede superar los 20 caracteres.");
            }

            // 4. Si hay errores, frenamos el envío y pintamos la alerta con Bootstrap
            if (errores.length > 0) {
                event.preventDefault(); // Detiene el envío por completo

                const divAlerta = document.createElement('div');
                divAlerta.className = 'alert alert-danger alert-dismissible fade show mt-3 alert-validacion shadow-sm border-0';
                divAlerta.style.borderLeft = "5px solid #dc3545";

                let htmlContent = `
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-circle-exclamation me-3 fs-5"></i>
                        <div>
                            <strong>Error de validación:</strong>
                            <ul class="mb-0 small ps-3">`;
                
                errores.forEach(error => {
                    htmlContent += `<li>${error}</li>`;
                });

                htmlContent += `
                            </ul>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
                
                divAlerta.innerHTML = htmlContent;

                // Insertar la alerta justo debajo del texto de bienvenida
                if (textoBienvenida) {
                    textoBienvenida.insertAdjacentElement('afterend', divAlerta);
                } else {
                    // Plan B por si acaso: al principio del formulario
                    loginForm.prepend(divAlerta);
                }
            } else {
                /* ¡Ojo aquí! Si el formulario es válido, dejamos que continúe su curso normal.
                   El navegador usará la ruta que definiste en el action de tu HTML 
                   (que ahora es dinámico gracias al $_SERVER['PHP_SELF'] que pusimos).
                */
                console.log("Formulario válido. Enviando datos al servidor...");
            }
        });
    }
});