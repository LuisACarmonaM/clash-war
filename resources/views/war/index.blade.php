<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Guerra de Clanes</title>
    <link rel="stylesheet" href="{{ asset('css/estilosglobales.css') }}">
</head>

<body>

    <div class="main-layout">
        <!-- Columna izquierda - Contenido principal -->
        <div class="main-content">
            <h1 class="section-title">⚔️ Lector de Puntos de Guerra</h1>

            @if (session('success'))
                <div class="alert alert-success">✅ {{ session('success') }}</div>
            @endif

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
                    <label>Temporada del Reporte:</label>
                    <div style="display: flex; gap: 10px;">
                        <select name="month" id="month" required style="flex: 2;">
                            @foreach (['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'] as $m)
                                <option value="{{ $m }}" {{ $m == 'Abril' ? 'selected' : '' }}>
                                    {{ $m }}</option>
                            @endforeach
                        </select>

                        <select name="year" id="year" required style="flex: 1;">
                            <option value="2025">2025</option>
                            <option value="2026" selected>2026</option>
                            <option value="2027">2027</option>
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
                    🚀 Procesar Lote de Imágenes
                </button>
            </form>

            <hr>

            <div style="display: flex; gap: 15px; justify-content: center;">
                <form action="{{ route('download') }}" method="GET">
                    <button type="submit" class="btn" style="background-color: #27ae60; color: white;">📥 Descargar
                        Excel</button>
                </form>

                <form action="{{ route('clear') }}" method="POST"
                    onsubmit="return confirm('⚠️ ¿Borrar todos los datos?');">
                    @csrf
                    <button type="submit" class="btn" style="background-color: #e74c3c; color: white;">🗑️ Limpiar
                        BD</button>
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
    <script src="{{ asset('js/calculadora.js') }}"></script>
    <script src="{{ asset('js/clan-wars.js') }}"></script>

</body>

</html>
