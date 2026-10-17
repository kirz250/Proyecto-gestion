document.addEventListener('DOMContentLoaded', function() {
    const registroForm = document.querySelector('form');
    const cardHeader = document.querySelector('.card-header');

    if (registroForm) {
        registroForm.addEventListener('submit', function(event) {
            // 1. Limpiar alertas previas
            const alertaExistente = document.querySelector('.alert-validacion');
            if (alertaExistente) alertaExistente.remove();

            // 2. Capturar los campos mediante sus IDs del HTML
            const nombre = document.getElementById('inputFirstName').value.trim();
            const email = document.getElementById('inputEmail').value.trim();
            const pass1 = document.getElementById('inputPassword').value.trim();
            const pass2 = document.getElementById('inputPasswordConfirm').value.trim();

            let errores = [];

            // 3. Validaciones de los campos
            if (nombre === "" || email === "" || pass1 === "" || pass2 === "") {
                errores.push("Todos los campos son obligatorios.");
            }

            // 📌 NUEVA VALIDACIÓN: Límite máximo de 20 caracteres para el Nombre de usuario
            if (nombre !== "" && nombre.length > 20) {
                errores.push("El nombre de usuario no puede superar los 20 caracteres.");
            }

            // Validación simple de formato de email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email !== "" && !emailRegex.test(email)) {
                errores.push("El formato del correo electrónico no es válido.");
            }

            // Validación de longitud de contraseña (Mínimo 6)
            if (pass1 !== "" && pass1.length < 6) {
                errores.push("La contraseña debe tener al menos 6 caracteres.");
            }

            // 📌 NUEVA VALIDACIÓN: Límite máximo de 20 caracteres para la Contraseña
            if (pass1 !== "" && pass1.length > 20) {
                errores.push("La contraseña no puede superar los 20 caracteres.");
            }

            // Validación de coincidencia de contraseñas
            if (pass1 !== pass2) {
                errores.push("Las contraseñas no coinciden.");
            }

            // 4. Mostrar errores si existen
            if (errores.length > 0) {
                event.preventDefault(); // Detiene el envío a insertar_registro.php

                const divAlerta = document.createElement('div');
                divAlerta.className = 'alert alert-danger alert-dismissible fade show mx-3 mt-2 alert-validacion';
                divAlerta.style.borderLeft = "5px solid #dc3545";

                let htmlContent = `
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-circle-exclamation me-2"></i>
                        <div>
                            <strong>Error en el registro:</strong>
                            <ul class="mb-0 small">`;
                
                errores.forEach(error => {
                    htmlContent += `<li>${error}</li>`;
                });

                htmlContent += `</ul></div></div>`;
                
                divAlerta.innerHTML = htmlContent;

                // Insertar debajo del título "Crear Cuenta"
                cardHeader.insertAdjacentElement('afterend', divAlerta);
                
                // Subir al inicio de la tarjeta para ver el error
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    }
});