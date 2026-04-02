<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Player;
use App\Exports\PlayersExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Http;

class WarController extends Controller
{
    public function index()
    {
        return view('war.index');
    }

    public function processImage(Request $request)
    {
        set_time_limit(300);
        // 1. CORRECCIÓN DE VALIDACIÓN: Validamos el campo 'war_images' Y sus archivos hijos '.*'
        $request->validate([
            'war_images'   => 'required|array',
            'war_images.*' => 'required|image|mimes:jpeg,png,jpg',
            'week'         => 'required|in:week_1,week_2,week_3,week_4,week_5',
            'month'        => 'required|string',
            'year'         => 'required|string'
        ]);

        session(['selected_month' => $request->month, 'selected_year' => $request->year]);

        $weekColumn = $request->input('week');
        $apiKey = 'K86510533188957'; // 👈 Asegúrate de poner tu clave
        $totalProcessed = 0;
        foreach ($request->file('war_images') as $image) {
            try {
                // Aumentamos el timeout a 90 por si el lote es pesado
                $response = Http::timeout(180)          // Tiempo total de la operación
                    ->connectTimeout(30)                // 👈 Tiempo para encontrar la dirección IP (DNS)
                    ->attach(
                        'file',
                        file_get_contents($image->getPathname()),
                        'captura.jpg'
                    )->post('https://api.ocr.space/parse/image', [
                        'apikey' => $apiKey,
                        'language' => 'eng',
                        'OCREngine' => '1'
                    ]);

                $result = $response->json();
                $ocrText = $result['ParsedResults'][0]['ParsedText'] ?? null;
                dd($result['ParsedResults'][0]['ParsedText']);
                if ($ocrText) {
                    // --- PASO 1: Intentamos capturar con ambos métodos ---

                    // Tu método original (Formato tabla con barras y número de posición)
                    preg_match_all('/\|\s*\d+\s*\|(?:[^|]*\|)?\s*([^|]+?)\s*\|(?:[^|]*\|)?\s*(\d{1,4})\s*\|/', $ocrText, $matchesOriginal, PREG_SET_ORDER);

                    // Mi método nuevo (Formato Markdown con celdas vacías al inicio)
                    preg_match_all('/\|\s*\|\s*([^|]+?)\s*\|\s*(\d{1,4})\s*\|/', $ocrText, $matchesMarkdown, PREG_SET_ORDER);

                    // Combinamos ambos resultados para procesarlos igual
                    $allMatches = array_merge($matchesOriginal, $matchesMarkdown);

                    foreach ($allMatches as $match) {
                        $playerName = trim($match[1]);
                        $score = (int) $match[2];

                        // --- PASO 2: Filtros de limpieza para evitar líneas como "a de bata" ---

                        // 1. Quitamos el "ID " si existe
                        $playerName = preg_replace('/^ID\s+/i', '', $playerName);

                        // 2. Definimos palabras prohibidas que el OCR suele confundir
                        $prohibitedWords = ['---', 'Vale', 'bata', 'Detalles', 'guerra', 'dia de', 'batalla'];
                        $isProhibited = false;
                        foreach ($prohibitedWords as $word) {
                            if (stripos($playerName, $word) !== false) {
                                $isProhibited = true;
                                break;
                            }
                        }

                        // --- PASO 3: Guardado final ---
                        // Solo guardamos si: no es prohibido, no es un número puro, y tiene longitud razonable
                        if (
                            !$isProhibited &&
                            !is_numeric($playerName) &&
                            strlen($playerName) > 2
                        ) {
                            $player = Player::firstOrNew(['name' => $playerName]);
                            $player->{$weekColumn} = $score; // Usamos la columna seleccionada
                            $player->save();
                            $totalProcessed++;
                        }
                    }
                }

                // Pausa de cortesía para la API gratuita
                sleep(3);
            } catch (\Exception $e) {
                // En lugar de 'continue', vamos a registrar el error para saber qué pasó
                \Log::error("Error procesando imagen: " . $e->getMessage());
                return back()->withErrors(['war_images' => "Error en una de las imágenes: " . $e->getMessage()]);
            }
        }

        if ($totalProcessed === 0) {
            return back()->withErrors(['war_images' => 'No se detectaron datos. Asegúrate de que las capturas sean claras y se vea la columna de puntos.']);
        }

        return back()->with('success', "¡Éxito! Se procesaron las imágenes y se actualizaron $totalProcessed registros.");
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
