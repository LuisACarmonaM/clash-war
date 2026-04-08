// ==================================================
// ========== GUERRA DE CLANES - FORMULARIO ========
// ==================================================

document.addEventListener("DOMContentLoaded", function () {
    const fileInput = document.getElementById("war_images");
    const fileCount = document.getElementById("file-count");
    const uploadForm = document.getElementById("uploadForm");
    const formLimpiar = document.getElementById("form-limpiar-bd");

    // Lógica para el botón de borrar datos
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

    // Actualiza el texto de cuántos archivos se seleccionaron
    if (fileInput) {
        fileInput.addEventListener("change", function () {
            const count = this.files.length;
            fileCount.textContent =
                count > 0 ? `📂 ${count} imágenes seleccionadas` : "";
        });
    }

    // Estilos para la lista de archivos
    const style = document.createElement("style");
    style.innerHTML = `
        .file-list-container {
            margin-top: 15px;
            max-height: 150px;
            overflow-y: auto;
            text-align: left;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 10px;
        }
        .file-item {
            font-size: 13px;
            color: #2c3e50;
            padding: 4px 0;
            border-bottom: 1px dashed #ccc;
        }
        .file-item:last-child {
            border-bottom: none;
        }
    `;
    document.head.appendChild(style);

    // Lógica de carga
    if (uploadForm) {
        uploadForm.addEventListener("submit", function (event) {
            if (!uploadForm.checkValidity()) {
                return;
            }

            // Creamos el contenedor HTML que irá dentro del SweetAlert
            let htmlContent = `
                <div style="font-size: 15px; margin-bottom: 10px;">
                    Por favor no cierres esta ventana.<br>
                    El sistema está leyendo los puntos de:
                </div>

                <div id="swal-file-list" class="file-list-container"></div>

                <div style="font-size: 12px; color: #e67e22; font-weight: bold; margin-top: 15px;">
                    Esto puede tardar unos minutos.
                </div>
            `;

            // Lanzamos el SweetAlert
            Swal.fire({
                title: "⏳ Procesando...",
                html: htmlContent,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                width: "500px",
                didOpen: () => {
                    Swal.showLoading();

                    // Llenamos la cajita con los nombres de los archivos
                    const container = document.getElementById("swal-file-list");

                    if (fileInput.files && fileInput.files.length > 0) {
                        Array.from(fileInput.files).forEach((file) => {
                            const item = document.createElement("div");
                            item.className = "file-item";
                            item.innerHTML = `📄 <strong>${file.name}</strong>`;
                            container.appendChild(item);
                        });
                    } else {
                        container.style.display = "none"; // Se oculta si por algún motivo no hay archivos
                    }
                },
            });
        });
    }
});
