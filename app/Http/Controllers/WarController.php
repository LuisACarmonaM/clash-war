<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Player;
use App\Exports\PlayersExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Http;
use App\Services\DolarApiService;
use Illuminate\Support\Facades\Log;

class WarController extends Controller
{
    protected $dolarApi;

    public function __construct(DolarApiService $dolarApi)
    {
        $this->dolarApi = $dolarApi;
    }

    public function index()
    {
        $tasasDolar = $this->dolarApi->getTasas();
        $estadoApi = $this->dolarApi->getEstado();
        //dd($tasasDolar);
        return view('war.index', [
            'tasasDolar' => $tasasDolar,
            'estadoApi' => $estadoApi
        ]);
    }

    public function processImage(Request $request)
    {
        set_time_limit(300);
        $request->validate([
            'war_images'   => 'required|array',
            'war_images.*' => 'required|image|mimes:jpeg,png,jpg',
            'week'         => 'required|in:week_1,week_2,week_3,week_4,week_5',
            'month'        => 'required|string',
            'year'         => 'required|string'
        ]);

        session(['selected_month' => $request->month, 'selected_year' => $request->year]);
        $weekColumn = $request->input('week');
        $apiKey = env('API_KEY_EXTERNAL');
        $totalProcessed = 0;
        $jugadoresProcesados = [];

        foreach ($request->file('war_images') as $image) {

            // 1. SISTEMA DE REINTENTOS MÁS AGRESIVO
            $maxIntentos = 4;
            $intentoActual = 0;
            $ocrText = null;

            while ($intentoActual < $maxIntentos) {
                $intentoActual++;
                try {
                    $response = Http::timeout(180)->connectTimeout(30)
                        ->attach('file', file_get_contents($image->getPathname()), 'captura.jpg')
                        ->post('https://api.ocr.space/parse/image', [
                            'apikey' => $apiKey,
                            'language' => 'eng',
                            'OCREngine' => '3'
                        ]);

                    $result = $response->json();

                    if ($response->failed() || !isset($result['ParsedResults']) || (isset($result['IsErroredOnProcessing']) && $result['IsErroredOnProcessing'] === true)) {
                        sleep(5);
                        continue;
                    }

                    if (isset($result['ParsedResults'][0]['ParsedText'])) {
                        $ocrText = $result['ParsedResults'][0]['ParsedText'];
                        break;
                    }
                } catch (\Exception $e) {
                    $nombreArchivo = $image->getClientOriginalName();
                    Log::warning("Intento {$intentoActual} fallido para la imagen '{$nombreArchivo}'. Motivo: " . $e->getMessage());
                    sleep(5);
                }
            }

            if (!$ocrText) {
                $nombreArchivo = $image->getClientOriginalName();
                Log::error("La imagen '{$nombreArchivo}' fue rechazada por la API después de {$maxIntentos} intentos y se omitió.");
                continue;
            }

            // 2. EXTRACCIÓN POR COLUMNAS
            $jugadoresEnEstaImagen = [];
            $prohibitedWords = ['---', 'rank', 'user', 'name', 'points', 'vale', 'detalles', 'guerra', 'batalla', 'id'];

            // ==========================================
            // 🧠 MOTOR CENTRAL DE LIMPIEZA Y DICCIONARIO
            // (Se define una sola vez y se usa en los 3 planes)
            // ==========================================
            $correccionesOcr = [
                'atxena'           => 'athena',
                'tangel†'          => '†ANGEL†',
                'taid'             => '0964$aiD0964',
                '0964'             => '0964$aiD0964',
                'said 0964'        => '0964$aiD0964',
                '0964 said 0964'   => '0964$aiD0964',
                'cer-x'            => 'Ger-X',
                'josefero4'        => 'JosefeR04',
                'bl4gkfy4h'        => 'BL4CKFY4H',
                'josé kieber'      => 'José kleber',
                'hama_yt'          => 'Hàma_YT',
                'hàmà_yt'          => 'Hàma_YT',
                'hamayt'           => 'Hàma_YT',
                'hàmáyt'           => 'Hàma_YT',
                'gr-raj'           => 'CR-RAJ',
                'john wick'        => 'john wick',
                'byarman27xx'      => 'ByARman27Xx',
                'drago'            => 'dRago',
                'biskuji'          => 'Biskuii',
                '23 biskuji'          => 'Biskuii',
                '-1 ihident i-'    => '-| iHidenT |-',
                '-1 ihiddent -'    => '-| iHidenT |-',
            ];

            // Esta función recibe el nombre sucio y devuelve el nombre perfecto (o "false" si es basura)
            $limpiarYFiltrar = function ($nombreCrudo) use ($correccionesOcr, $prohibitedWords) {
                // Limpieza inicial
                $nombre = str_replace(['*', '_', '~'], '', $nombreCrudo);
                $nombre = trim($nombre);
                $nombre = preg_replace('/^ID\s*/i', '', $nombre);
                $nombre = trim($nombre);

                // Diccionario
                $nombreMinuscula = mb_strtolower($nombre, 'UTF-8');
                if (array_key_exists($nombreMinuscula, $correccionesOcr)) {
                    $nombre = $correccionesOcr[$nombreMinuscula];
                } else {
                    $nombre = preg_replace('/^\d+\s*/', '', $nombre);
                    $nombre = trim($nombre);
                }

                // Filtros finales (Balas de Plata)
                if (preg_match('/h\s*\d+\s*min/i', $nombre)) return false;
                if (stripos($nombre, 'entrenamiento') !== false) return false;
                if (stripos($nombre, 'dia de') !== false) return false;
                if (stripos($nombre, 'día de') !== false) return false;
                if (in_array(strtolower($nombre), $prohibitedWords)) return false;
                if (is_numeric($nombre) || strlen($nombre) < 2) return false;

                return $nombre; // Si sobrevivió a todo, devolvemos el nombre limpio
            };
            // ==========================================


            // ==========================================
            // PLAN A: Formato de Tabla (Busca barras '|')
            // ==========================================
            $lineas = explode("\n", $ocrText);
            foreach ($lineas as $linea) {
                if (strpos($linea, '|') === false) continue;

                $columnasCrudas = explode('|', $linea);
                $columnas = [];
                foreach ($columnasCrudas as $col) {
                    $limpio = trim($col);
                    if ($limpio !== '' && !preg_match('/^[-:\s]+$/', $limpio)) {
                        $columnas[] = $limpio;
                    }
                }

                $cantidadColumnas = count($columnas);
                if ($cantidadColumnas == 0) continue;

                $nombreSucio = '';
                $puntos = 0;

                $ultimaColumna = strtolower($columnas[$cantidadColumnas - 1]);
                if ($ultimaColumna === 'o') $ultimaColumna = '0';

                if (is_numeric(preg_replace('/[^0-9]/', '', $ultimaColumna))) {
                    $puntos = (int) preg_replace('/[^0-9]/', '', $ultimaColumna);
                    if ($cantidadColumnas >= 2) $nombreSucio = $columnas[$cantidadColumnas - 2];
                } else {
                    $nombreSucio = $columnas[$cantidadColumnas - 1];
                }

                // Mandamos el nombre al Motor Central
                $nombrePerfecto = $limpiarYFiltrar($nombreSucio);
                if ($nombrePerfecto !== false) {
                    $jugadoresEnEstaImagen[$nombrePerfecto] = $puntos;
                }
            }

            // ==========================================
            // 🆘 PLAN B: Formato de Lista (Texto arriba, número abajo)
            // ==========================================
            if (empty($jugadoresEnEstaImagen)) {
                preg_match_all('/^(.+)\r?\n\s*(\d{1,4})\s*$/mi', $ocrText, $matchesPlanB, PREG_SET_ORDER);
                foreach ($matchesPlanB as $match) {
                    $nombreSucio = trim($match[1]);
                    $puntos = (int) $match[2];

                    // Mandamos el nombre al Motor Central
                    $nombrePerfecto = $limpiarYFiltrar($nombreSucio);
                    if ($nombrePerfecto !== false) {
                        $jugadoresEnEstaImagen[$nombrePerfecto] = $puntos;
                    }
                }
            }

            // ==========================================
            // 🚨 PLAN C: Formato de Línea Única (Ranking ID Nombre Puntos)
            // ==========================================
            if (empty($jugadoresEnEstaImagen)) {
                preg_match_all('/^(?:\d+\s+)?(?:ID\s+)?(.+?)\s+(\d{1,4})$/mi', $ocrText, $matchesPlanC, PREG_SET_ORDER);
                foreach ($matchesPlanC as $match) {
                    $nombreSucio = trim($match[1]);
                    $puntos = (int) $match[2];

                    // Mandamos el nombre al Motor Central
                    $nombrePerfecto = $limpiarYFiltrar($nombreSucio);
                    if ($nombrePerfecto !== false) {
                        $jugadoresEnEstaImagen[$nombrePerfecto] = $puntos;
                    }
                }
            }
            // ==========================================

            // 3. GUARDADO (Actualiza si existe, crea si no existe)
            $count = 0;
            foreach ($jugadoresEnEstaImagen as $nombre => $puntos) {
                if ($count >= 9) break;

                $player = Player::firstOrNew(['name' => $nombre]);
                $player->{$weekColumn} = $puntos;
                $player->save();

                $jugadoresProcesados[] = ['nombre' => $nombre, 'puntos' => $puntos];
                $totalProcessed++;
                $count++;
            }

            sleep(4);
        }

        //dd($result, $jugadoresProcesados);
        if ($totalProcessed === 0) {
            return back()->withErrors(['war_images' => 'No se detectaron datos. API saturada.']);
        }

        return back()->with('success', "¡Éxito! Se actualizaron $totalProcessed registros.")
            ->with('jugadores', $jugadoresProcesados);
    }
    public function downloadExcel()
    {
        $month = session('selected_month', 'General');
        $year = session('selected_year', date('Y'));
        $fileName = "Guerra_{$month}_{$year}.xlsx";

        return Excel::download(new PlayersExport, $fileName);
    }

    public function clearDatabase()
    {
        Player::truncate();
        return back()->with('success', '¡Datos borrados! Listo para una nueva temporada.');
    }
}
