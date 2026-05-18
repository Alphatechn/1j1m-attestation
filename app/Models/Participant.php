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
        'civility',
        'name',
        'email',
        'city',
        'phone',
        'whatsapp',
        'training_group',
        'classmates_contacts',
        'course_delivery_explanation',
        'technical_skills',
        'public_speaking_description',
        'ecommerce_steps',
        'knowledge_use_plan',
        'coaches',
        'homework_screenshot_paths',
        'is_active',
        'validation_status',
        'submitted_at',
        'validated_at',
        'validated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'submitted_at' => 'datetime',
        'validated_at' => 'datetime',
        'homework_screenshot_paths' => 'array',
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

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValidated($query)
    {
        return $query->where('validation_status', 'validated');
    }

    public function scopePendingValidation($query)
    {
        return $query->where('validation_status', 'pending');
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
