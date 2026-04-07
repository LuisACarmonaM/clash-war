<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Player;
use App\Exports\PlayersExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Http;
use App\Services\DolarApiService; // Importamos el servicio


class WarController extends Controller
{
    protected $dolarApi;

    // Inyectamos el servicio en el constructor
    public function __construct(DolarApiService $dolarApi)
    {
        $this->dolarApi = $dolarApi;
    }

    public function index()
    {
        // Obtener datos de la API del dólar
        $tasasDolar = $this->dolarApi->getTasas();
        $estadoApi = $this->dolarApi->getEstado();
        //dd($tasasDolar);
        // Pasar los datos a la vista
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
        $apiKey = 'K86510533188957'; // Asegúrate de usar tu clave correcta
        $totalProcessed = 0;
        $jugadoresProcesados = [];

        foreach ($request->file('war_images') as $image) {

            // 1. SISTEMA DE REINTENTOS MÁS AGRESIVO
            $maxIntentos = 4; // Subimos a 4 intentos por imagen
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

                    // Si la API falla, nos bloquea, o no devuelve resultados, esperamos 5 segundos y reintentamos
                    if ($response->failed() || !isset($result['ParsedResults']) || (isset($result['IsErroredOnProcessing']) && $result['IsErroredOnProcessing'] === true)) {
                        sleep(5);
                        continue;
                    }

                    if (isset($result['ParsedResults'][0]['ParsedText'])) {
                        $ocrText = $result['ParsedResults'][0]['ParsedText'];
                        break;
                    }
                } catch (\Exception $e) {
                    sleep(5);
                }
            }

            if (!$ocrText) {
                \Log::error("Una imagen fue rechazada por la API después de 4 intentos.");
                continue;
            }

            // 2. EXTRACCIÓN POR COLUMNAS
            $jugadoresEnEstaImagen = [];
            $prohibitedWords = ['---', 'rank', 'user', 'name', 'points', 'vale', 'detalles', 'guerra', 'batalla', 'id'];

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

                $nombre = '';
                $puntos = 0;

                $ultimaColumna = strtolower($columnas[$cantidadColumnas - 1]);
                if ($ultimaColumna === 'o') $ultimaColumna = '0';

                if (is_numeric(preg_replace('/[^0-9]/', '', $ultimaColumna))) {
                    $puntos = (int) preg_replace('/[^0-9]/', '', $ultimaColumna);
                    if ($cantidadColumnas >= 2) {
                        $nombre = $columnas[$cantidadColumnas - 2];
                    }
                } else {
                    $nombre = $columnas[$cantidadColumnas - 1];
                    $puntos = 0;
                }

                // 👇 AQUI ESTÁ LA MAGIA DE LA LIMPIEZA 👇
                $nombre = str_replace(['*', '_', '~'], '', $nombre); // Destruimos basura de Markdown (asteriscos)
                $nombre = trim($nombre);
                $nombre = preg_replace('/^ID\s*/i', '', $nombre); // Quitamos el "ID"
                $nombre = preg_replace('/^\d+\s*/', '', $nombre); // Quitamos número de ranking suelto

                // Filtros
                if (preg_match('/h\s*\d+\s*min/i', $nombre)) continue;
                if (in_array(strtolower($nombre), $prohibitedWords)) continue;
                if (is_numeric($nombre) || strlen($nombre) < 2) continue;

                // Lo metemos al arreglo
                $jugadoresEnEstaImagen[$nombre] = $puntos;
            }

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

            // Pausa obligatoria de 4 segundos entre imágenes exitosas para no enojar a la API
            sleep(4);
        }

        // Puedes dejar esto para ver la magia de la extracción perfecta

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
        return back()->with('success', '¡Base de datos reiniciada! Lista para una nueva temporada.');
    }
}
