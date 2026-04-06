// ==================================================
// ========== GUERRA DE CLANES - FORMULARIO ========
// ==================================================

document.addEventListener("DOMContentLoaded", function () {
    const fileInput = document.getElementById("war_images");
    const fileCount = document.getElementById("file-count");
    const uploadForm = document.getElementById("uploadForm");
    const submitBtn = document.getElementById("submitBtn");

    if (fileInput) {
        fileInput.addEventListener("change", function () {
            const count = this.files.length;
            fileCount.textContent =
                count > 0 ? `📂 ${count} imágenes seleccionadas` : "";
        });
    }

    if (uploadForm) {
        uploadForm.addEventListener("submit", function () {
            submitBtn.disabled = true;
            submitBtn.innerHTML = "⏳ Procesando imágenes... por favor espera";
            submitBtn.style.backgroundColor = "#95a5a6";
        });
    }
});
