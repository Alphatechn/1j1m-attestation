<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Attestation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'participant_id',
        'periode_id',
        'generated_by',
        'attestation_number',
        'qr_token',
        'issue_date',
        'content_text',
        'pdf_path',
        'status',
        'sent_at',
        'verification_count',
        'last_verified_at',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'sent_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'verification_count' => 'integer',
    ];

    // Relations
    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public function periode()
    {
        return $this->belongsTo(Periode::class);
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    // Scopes
    public function scopeGenerated($query)
    {
        return $query->where('status', 'generated');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    // Boot method pour générer automatiquement les codes
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($attestation) {
            if (!$attestation->attestation_number) {
                $attestation->attestation_number = self::generateAttestationNumber();
            }
            if (!$attestation->qr_token) {
                $attestation->qr_token = self::generateQrToken();
            }
        });
    }

    // Methods
    public static function generateAttestationNumber()
    {
        do {
            $number = 'ATT-' . date('Y') . '-' . strtoupper(Str::random(8));
        } while (self::where('attestation_number', $number)->exists());

        return $number;
    }

    public static function generateQrToken()
    {
        do {
            $token = Str::random(32);
        } while (self::where('qr_token', $token)->exists());

        return $token;
    }

    public function incrementVerification()
    {
        $this->increment('verification_count');
        $this->update(['last_verified_at' => now()]);
    }

    public function getVerificationUrl()
    {
        return route('attestations.verify', ['token' => $this->qr_token]);
    }
}
