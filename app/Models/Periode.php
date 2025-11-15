<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Periode extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'libelle',
        'mois_debut',
        'annee_debut',
        'mois_fin',
        'annee_fin',
        'is_active',
        'date_debut',
        'date_fin',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    // Relations
    public function participants()
    {
        return $this->hasMany(Participant::class);
    }

    public function attestations()
    {
        return $this->hasMany(Attestation::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrent($query)
    {
        $now = Carbon::now();
        return $query->where('date_debut', '<=', $now)
                     ->where('date_fin', '>=', $now);
    }

    // Accessors
    public function getFullLibelleAttribute()
    {
        return $this->libelle ?: "{$this->mois_debut}/{$this->annee_debut} - {$this->mois_fin}/{$this->annee_fin}";
    }

    // Mutators
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($periode) {
            if (!$periode->date_debut) {
                $periode->date_debut = Carbon::createFromDate($periode->annee_debut, $periode->mois_debut, 1);
            }
            if (!$periode->date_fin) {
                $periode->date_fin = Carbon::createFromDate($periode->annee_fin, $periode->mois_fin, 1)->endOfMonth();
            }
        });
    }
}
