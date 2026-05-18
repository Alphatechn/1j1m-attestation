<?php

namespace App\Imports;

use App\Models\Participant;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Validation\Rule;

class ParticipantsImport implements ToModel, WithHeadingRow, WithValidation
{
    protected $periodeId;
    protected $importedCount = 0;
    protected $failedCount = 0;
    protected $errors = [];

    public function __construct($periodeId)
    {
        $this->periodeId = $periodeId;
    }

    public function model(array $row)
    {
        $mappedData = [
            'name' => $row['nom'] ?? $row['name'] ?? null,
            'email' => $row['email'] ?? null,
            'phone' => $this->convertToString($row['telephone'] ?? $row['phone'] ?? null),
            'city' => $row['ville'] ?? $row['city'] ?? null,
            'whatsapp' => $this->convertToString($row['whatsapp'] ?? null),
            'training_group' => $row['groupe_de_formation'] ?? $row['training_group'] ?? null,
            'periode_id' => $this->periodeId,
            'is_active' => true,
            'validation_status' => 'validated',
            'validated_at' => now(),
        ];

        $this->importedCount++;
        return new Participant($mappedData);
    }

    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:255',
            'email' => 'nullable|email|unique:participants,email,NULL,id,periode_id,' . $this->periodeId,
            'telephone' => 'nullable|max:20',
            'ville' => 'nullable|string|max:255',
            'whatsapp' => 'nullable|max:30',
            'groupe_de_formation' => 'nullable|string|max:255',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'nom.required' => 'Le nom est requis',
            'email.unique' => 'Cet email existe déjà pour cette période',
        ];
    }

    // Méthodes pour le reporting
    public function getImportedCount() { return $this->importedCount; }
    public function getFailedCount() { return $this->failedCount; }
    public function getErrors() { return $this->errors; }

    /**
     * Convertit toute valeur en string
     */
    private function convertToString($value)
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        if (is_string($value)) {
            return $value;
        }

        return (string) $value;
    }
}
