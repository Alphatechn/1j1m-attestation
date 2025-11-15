<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Participant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'periode_id',
        'name',
        'email',
        'phone',
        'matricule',
        'organisation',
        'fonction',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relations
    public function periode()
    {
        return $this->belongsTo(Periode::class);
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

    public function scopeForPeriode($query, $periodeId)
    {
        return $query->where('periode_id', $periodeId);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return "{$this->name}";
    }

    // Methods
    public function hasAttestation()
    {
        return $this->attestations()->exists();
    }

    public function getLatestAttestation()
    {
        return $this->attestations()->latest()->first();
    }
}
