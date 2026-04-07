<?php

namespace App\Exports;

use App\Models\Player;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PlayersExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        // En PostgreSQL usamos COALESCE en lugar de IFNULL
        return Player::orderByRaw('(COALESCE(week_1, 0) + COALESCE(week_2, 0) + COALESCE(week_3, 0) + COALESCE(week_4, 0) + COALESCE(week_5, 0)) DESC')->get();
    }

    public function headings(): array
    {
        return [
            'Nombre',
            'Semana 1',
            'Semana 2',
            'Semana 3',
            'Semana 4',
            'Semana 5',
            'Total'
        ];
    }

    public function map($player): array
    {
        // Calculamos el total de la fila
        $total = $player->week_1 + $player->week_2 + $player->week_3 +
            $player->week_4 + $player->week_5;

        return [
            $player->name,
            $player->week_1,
            $player->week_2,
            $player->week_3,
            $player->week_4,
            $player->week_5,
            $total // La suma final que pediste
        ];
    }
}
