<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Guerra de Clanes</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 20px;
            line-height: 1.6;
            background-color: #f4f7f6;
        }

        .container {
            max-width: 800px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .section-title {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
            margin-top: 0;
        }

        .btn {
            padding: 12px 20px;
            cursor: pointer;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            transition: 0.3s;
        }

        .btn:disabled {
            background-color: #ccc !important;
            cursor: not-allowed;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #34495e;
        }

        select,
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-sizing: border-box;
        }

        .season-box {
            background: #eef2f3;
            padding: 20px;
            border-radius: 8px;
            border-left: 5px solid #3498db;
        }
    </style>
</head>

<body>

    <div class="container">
        <h1 class="section-title">⚔️ Lector de Puntos de Guerra</h1>

        <!-- Mensajes de Estado -->
        @if (session('success'))
        <div class="alert alert-success">✅ {{ session('success') }}</div>
        @endif

        @if ($errors->any())
        <div class="alert alert-danger">
            ❌ <strong>¡Atención!</strong>
            <ul style="margin-top: 5px;">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('process') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
            @csrf

            <div class="season-box form-group">
                <label>Temporada del Reporte:</label>
                <div style="display: flex; gap: 10px;">
                    <select name="month" id="month" required style="flex: 2;">
                        @foreach(['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'] as $m)
                        <option value="{{ $m }}" {{ $m == 'Abril' ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>

                    <select name="year" id="year" required style="flex: 1;">
                        <option value="2025">2025</option>
                        <option value="2026" selected>2026</option>
                        <option value="2027">2027</option>
                    </select>
                </div>
                <small style="color: #666; margin-top: 5px; display: block;">Esto dará nombre a tu archivo Excel.</small>
            </div>

            <div class="form-group">
                <label for="week">Semana de Guerra:</label>
                <select name="week" id="week" required>
                    <option value="week_1">Semana 1</option>
                    <option value="week_2">Semana 2</option>
                    <option value="week_3">Semana 3</option>
                    <option value="week_4">Semana 4</option>
                    <option value="week_5">Semana 5</option>
                </select>
            </div>

            <div class="form-group">
                <label for="war_images">Capturas de Pantalla (puedes elegir varias):</label>
                <input type="file" name="war_images[]" id="war_images" accept="image/*" multiple required>
                <p id="file-count" style="font-size: 0.85em; color: #3498db; margin-top: 5px; font-weight: bold;"></p>
            </div>

            <button type="submit" class="btn" id="submitBtn" style="background-color: #3498db; color: white; width: 100%;">
                🚀 Procesar Lote de Imágenes
            </button>
        </form>

        <hr style="margin: 30px 0; border: 0; border-top: 1px solid #eee;">

        <div style="display: flex; gap: 15px; justify-content: center;">
            <form action="{{ route('download') }}" method="GET">
                <button type="submit" class="btn" style="background-color: #27ae60; color: white;">📥 Descargar Excel</button>
            </form>

            <form action="{{ route('clear') }}" method="POST" onsubmit="return confirm('⚠️ ¿Borrar todos los datos?');">
                @csrf
                <button type="submit" class="btn" style="background-color: #e74c3c; color: white;">🗑️ Limpiar BD</button>
            </form>
        </div>
    </div>

    <script>
        const fileInput = document.getElementById('war_images');
        const fileCount = document.getElementById('file-count');
        const uploadForm = document.getElementById('uploadForm');
        const submitBtn = document.getElementById('submitBtn');

        // Mostrar cuántos archivos seleccionó
        fileInput.addEventListener('change', function() {
            const count = this.files.length;
            fileCount.textContent = count > 0 ? `📂 ${count} imágenes seleccionadas` : '';
        });

        // Cambiar estado del botón al enviar
        uploadForm.addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '⏳ Procesando imágenes... por favor espera';
            submitBtn.style.backgroundColor = '#95a5a6';
        });
    </script>

</body>

</html>