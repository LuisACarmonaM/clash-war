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
        $request->validate([
            'war_images'   => 'required|array',
            'war_images.*' => 'required|image|mimes:jpeg,png,jpg',
            'week'         => 'required|in:week_1,week_2,week_3,week_4,week_5',
            'month'        => 'required|string',
            'year'         => 'required|string'
        ]);

        session(['selected_month' => $request->month, 'selected_year' => $request->year]);
        $weekColumn = $request->input('week');
        $apiKey = 'K86510533188957';
        $totalProcessed = 0;
        $jugadoresProcesados = [];

        foreach ($request->file('war_images') as $image) {

            // 1. SISTEMA DE REINTENTOS PARA LA API
            $maxIntentos = 3;
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

                    if (isset($result['IsErroredOnProcessing']) && $result['IsErroredOnProcessing'] === true) {
                        sleep(3);
                        continue;
                    }

                    if (isset($result['ParsedResults'][0]['ParsedText'])) {
                        $ocrText = $result['ParsedResults'][0]['ParsedText'];
                        break;
                    }
                } catch (\Exception $e) {
                    sleep(3);
                }
            }

            if (!$ocrText) continue;

            // 2. NUEVA LÓGICA DE EXTRACCIÓN POR COLUMNAS (A prueba de balas)
            $jugadoresEnEstaImagen = [];
            $prohibitedWords = ['---', 'rank', 'user', 'name', 'points', 'vale', 'detalles', 'guerra', 'batalla', 'id'];

            // Cortamos el texto gigante en líneas individuales
            $lineas = explode("\n", $ocrText);

            foreach ($lineas as $linea) {
                // Si la línea no tiene una barra "|", no es de la tabla
                if (strpos($linea, '|') === false) continue;

                // Cortamos la línea por las barras
                $columnasCrudas = explode('|', $linea);
                $columnas = [];

                // Limpiamos espacios vacíos y columnas inútiles
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

                // Analizamos de derecha a izquierda:
                $ultimaColumna = strtolower($columnas[$cantidadColumnas - 1]);

                // Si el OCR confundió un 0 con la letra 'O'
                if ($ultimaColumna === 'o') $ultimaColumna = '0';

                // Si la última columna es un número, son los puntos.
                if (is_numeric(preg_replace('/[^0-9]/', '', $ultimaColumna))) {
                    $puntos = (int) preg_replace('/[^0-9]/', '', $ultimaColumna);
                    if ($cantidadColumnas >= 2) {
                        $nombre = $columnas[$cantidadColumnas - 2]; // El nombre es el anterior
                    }
                } else {
                    // Si no hay número al final, significa que el OCR se saltó los puntos
                    $nombre = $columnas[$cantidadColumnas - 1]; // La última columna es el nombre
                    $puntos = 0;
                }

                // --- LIMPIEZA FINAL DEL NOMBRE ---
                $nombre = trim($nombre);
                $nombre = preg_replace('/^ID\s+/i', '', $nombre); // Quitamos el "ID "
                $nombre = preg_replace('/^\d+\s+/', '', $nombre); // Quitamos si se coló un número de ranking

                // --- FILTROS ---
                // Ignoramos la línea de "19 h 24 min"
                if (preg_match('/h\s*\d+\s*min/i', $nombre)) continue;

                if (in_array(strtolower($nombre), $prohibitedWords)) continue;
                if (is_numeric($nombre) || strlen($nombre) < 2) continue;

                // ¡Aprobado! Lo metemos al arreglo
                $jugadoresEnEstaImagen[$nombre] = $puntos;
            }

            // 3. GUARDADO EN BASE DE DATOS
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

            sleep(2); // Pausa de cortesía entre imágenes
        }

        // Puedes dejar esto para ver la magia de la extracción perfecta
        //dd($result, $jugadoresProcesados);

        if ($totalProcessed === 0) {
            return back()->withErrors(['war_images' => 'No se detectaron datos. Asegúrate de que las capturas sean claras.']);
        }

        return back()->with('success', "¡Éxito! Se procesaron las imágenes y se actualizaron $totalProcessed registros.")
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
