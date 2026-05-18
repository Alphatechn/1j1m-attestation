<?php

namespace App\Exports;

use App\Models\Participant;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ParticipantsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $periodeId;

    public function __construct($periodeId = null)
    {
        $this->periodeId = $periodeId;
    }

    public function collection()
    {
        $query = Participant::with(['periode', 'attestations']);

        if ($this->periodeId) {
            $query->where('periode_id', $this->periodeId);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Période',
            'Noms',
            'Email',
            'Téléphone',
            'Ville',
            'WhatsApp',
            'Groupe de formation',
            'Statut',
            'Validation',
            'Nombre d\'attestations',
            'Date de création',
            'Date de modification'
        ];
    }

    public function map($participant): array
    {
        return [
            $participant->id,
            $participant->periode->libelle ?? 'N/A',
            $participant->name,
            $participant->email ?? 'Non renseigné',
            $participant->phone ?? 'Non renseigné',
            $participant->city ?? 'Non renseigné',
            $participant->whatsapp ?? 'Non renseigné',
            $participant->training_group ?? 'Non renseigné',
            $participant->is_active ? 'Actif' : 'Inactif',
            $participant->validation_status === 'validated' ? 'Validé' : ($participant->validation_status === 'rejected' ? 'Rejeté' : 'En attente'),
            $participant->attestations_count ?? $participant->attestations->count(),
            $participant->created_at->format('d/m/Y H:i'),
            $participant->updated_at->format('d/m/Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style pour l'en-tête
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '3498DB']]
            ],
            // Style pour les lignes
            'A2:M1000' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => 'thin',
                        'color' => ['rgb' => 'DDDDDD'],
                    ],
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Participants';
    }
}
