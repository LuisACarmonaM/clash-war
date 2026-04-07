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
            event.preventDefault();

            Swal.fire({
                title: "⚠️ ¿Borrar todos los datos?",
                text: "Esta acción no se puede deshacer.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#e74c3c",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Limpiar",
                cancelButtonText: "Cancelar",
                background: "#fff",
            }).then((result) => {
                if (result.isConfirmed) {
                    formLimpiar.submit();
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
            if (!uploadForm.checkValidity()) {
                return;
            }

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
        });
    }
});
