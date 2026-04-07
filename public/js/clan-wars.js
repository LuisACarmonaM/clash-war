// ==================================================
// ========== GUERRA DE CLANES - FORMULARIO ========
// ==================================================

document.addEventListener("DOMContentLoaded", function () {
    const fileInput = document.getElementById("war_images");
    const fileCount = document.getElementById("file-count");
    const uploadForm = document.getElementById("uploadForm");
    const submitBtn = document.getElementById("submitBtn");
    const formLimpiar = document.getElementById("form-limpiar-bd");

    if (formLimpiar) {
        formLimpiar.addEventListener("submit", function (event) {
            // Detiene el envío automático del formulario
            event.preventDefault();

            // Lanza el SweetAlert
            Swal.fire({
                title: "⚠️ ¿Borrar todos los datos?",
                text: "Esta acción no se puede deshacer.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#e74c3c",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Limpiar",
                cancelButtonText: "Cancelar",
                background: "#fff", // Puedes cambiar el color de fondo si tienes modo oscuro
            }).then((result) => {
                // Si el usuario hace clic en "Sí"
                if (result.isConfirmed) {
                    formLimpiar.submit(); // Ahora sí envía el formulario a la ruta de Laravel
                }
            });
        });
    }
    if (fileInput) {
        fileInput.addEventListener("change", function () {
            const count = this.files.length;
            fileCount.textContent =
                count > 0 ? `📂 ${count} imágenes seleccionadas` : "";
        });
    }

    if (uploadForm) {
        uploadForm.addEventListener("submit", function (event) {
            // Verificamos si el formulario es válido (que haya subido fotos, elegido semana, etc)
            if (!uploadForm.checkValidity()) {
                return; // Si falta algo, dejamos que el navegador muestre sus alertas normales
            }

            // Lanzamos el SweetAlert de carga
            Swal.fire({
                title: "⏳ Procesando Imágenes...",
                html: "Por favor no cierres esta ventana.<br>El sistema está leyendo los puntos línea por línea.",
                allowOutsideClick: false, // Evita que se cierre si dan clic afuera
                allowEscapeKey: false, // Evita que se cierre con la tecla ESC
                showConfirmButton: false, // Oculta el botón de "OK"
                didOpen: () => {
                    Swal.showLoading(); // Activa la animación del círculo girando
                },
            });

            // Nota: No usamos event.preventDefault() aquí,
            // así que el formulario seguirá su viaje a Laravel de forma normal por detrás.
        });
    }
});
