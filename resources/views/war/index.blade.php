<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Guerra de Clanes</title>
    <link rel="stylesheet" href="{{ asset('css/estilosglobales.css') }}">
</head>

<body>

    <div class="main-layout">
        <!-- Columna izquierda - Contenido principal -->
        <div class="main-content">
            <h1 class="section-title">⚔️ Puntos de Guerra</h1>

            @if ($errors->any())
                <div class="alert alert-danger">
                    ❌ <strong>¡Atención!</strong>
                    <ul style="margin-top: 5px; margin-bottom: 0;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('process') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                @csrf

                <div class="season-box form-group">
                    <label>Reporte de Temporada:</label>
                    <div style="display: flex; gap: 10px;">

                        <!-- MES AUTOMÁTICO (Bono extra) -->
                        <select name="month" id="month" required style="flex: 2;">
                            @php
                                $meses = [
                                    'Enero',
                                    'Febrero',
                                    'Marzo',
                                    'Abril',
                                    'Mayo',
                                    'Junio',
                                    'Julio',
                                    'Agosto',
                                    'Septiembre',
                                    'Octubre',
                                    'Noviembre',
                                    'Diciembre',
                                ];
                                $mesActual = $meses[date('n') - 1]; // Obtiene el mes actual en español
                            @endphp

                            @foreach ($meses as $m)
                                <option value="{{ $m }}" {{ $m == $mesActual ? 'selected' : '' }}>
                                    {{ $m }}
                                </option>
                            @endforeach
                        </select>

                        <!-- AÑO AUTOMÁTICO -->
                        <select name="year" id="year" required style="flex: 1;">
                            @php
                                $currentYear = date('Y');
                            @endphp

                            <!-- Año actual (seleccionado por defecto) -->
                            <option value="{{ $currentYear }}" selected>{{ $currentYear }}</option>

                            <!-- Un año más en el futuro -->
                            <option value="{{ $currentYear + 1 }}">{{ $currentYear + 1 }}</option>
                        </select>

                    </div>
                    <small style="color: #666; margin-top: 5px; display: block;">Esto dará nombre a tu archivo
                        Excel.</small>
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
                    <p id="file-count" style="font-size: 0.85em; color: #3498db; margin-top: 5px; font-weight: bold;">
                    </p>
                </div>

                <button type="submit" class="btn" id="submitBtn"
                    style="background-color: #3498db; color: white; width: 100%;">
                    🚀 Procesar Imágenes
                </button>
            </form>

            <hr>

            <div style="display: flex; gap: 15px; justify-content: center;">
                <form action="{{ route('download') }}" method="GET">
                    <button type="submit" class="btn" style="background-color: #27ae60; color: white;">📥 Descargar
                        Excel</button>
                </form>

                <form action="{{ route('clear') }}" method="POST" id="form-limpiar-bd">
                    @csrf
                    <button type="submit" class="btn" style="background-color: #e74c3c; color: white;">🗑️ Borrar
                        Datos</button>
                </form>
            </div>
        </div>

        <!-- Columna derecha - Calculadora -->
        <div class="sidebar">
            <!-- ✅ La tasa se pasa como data attribute -->
            <div class="calculator-mini" data-tasa-dolar="{{ $tasasDolar['promedio'] ?? 0 }}">
                <div class="calculator-title">
                    <h3 style="margin: 0;">💱 Conversor de Moneda</h3>
                </div>

                <!-- Conversor USD → Bs -->
                <div class="calc-converter">
                    <label>💰 USD (Dólares)</label>
                    <input type="text" id="usdAmount" placeholder="0,00" value="1">
                    <div class="calc-result">
                        <div class="calc-result-label">🇻🇪 Equivalente en Bolívares</div>
                        <div class="calc-result-value" id="resultBs">
                            Bs. {{ number_format($tasasDolar['promedio'] ?? 0, 2, ',', '.') }}
                        </div>
                    </div>
                </div>

                <!-- Conversor Bs → USD -->
                <div class="calc-converter">
                    <label>🇻🇪 Bs. (Bolívares)</label>
                    <input type="text" id="bsAmount" placeholder="0,00" value="">
                    <div class="calc-result">
                        <div class="calc-result-label">💰 Equivalente en Dólares</div>
                        <div class="calc-result-value" id="resultUsd">
                            $ 0,00
                        </div>
                    </div>
                </div>

                <div class="calc-buttons">
                    <button class="btn-calc btn-clear" onclick="limpiarTodo()">🗑️ Limpiar Todo</button>
                </div>

                <div class="calc-info">
                    1 USD = Bs. {{ number_format($tasasDolar['promedio'] ?? 0, 2, ',', '.') }}
                    <br>
                    <span>📅 {{ \Carbon\Carbon::parse($tasasDolar['fechaActualizacion'])->format('d/m/Y') }}</span>
                </div>
            </div>
        </div>
    </div>
    <!-- Solo los scripts, sin código inline -->
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('js/calculadora.js') }}"></script>
    <script src="{{ asset('js/clan-wars.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Si hay un mensaje de éxito desde el backend
            @if (session('success'))
                Swal.fire({
                    title: '¡Completado!',
                    text: "{{ session('success') }}",
                    icon: 'success',
                    confirmButtonText: 'Genial',
                    confirmButtonColor: '#27ae60'
                }).then((result) => {
                    // 👇 ESTO OCURRE AL DARLE CLIC A "GENIAL" 👇
                    if (result.isConfirmed) {
                        // Limpiamos el formulario para que quede en blanco
                        document.getElementById('uploadForm').reset();

                        // Y limpiamos el textito azul de "X imágenes seleccionadas"
                        const fileCount = document.getElementById('file-count');
                        if (fileCount) fileCount.textContent = '';
                    }
                });
            @endif

            // Si hay errores desde el backend (ej. falló la API, no subió fotos)
            @if ($errors->any())
                let errorMessages = '';
                @foreach ($errors->all() as $error)
                    errorMessages += '{{ $error }}\n';
                @endforeach

                Swal.fire({
                    title: 'Hubo un problema',
                    text: errorMessages,
                    icon: 'error',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#e74c3c'
                });
            @endif
        });
    </script>
</body>

</html>
