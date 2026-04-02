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
        return Player::all();
    }

    public function headings(): array
    {
        return [
            'Nombre', 'Semana 1', 'Semana 2', 'Semana 3', 'Semana 4', 'Semana 5', 'Total'
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
