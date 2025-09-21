<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class City extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    protected $fillable = [
        'province_id',
        'code',
        'name',
        'type',
        'zip_code',
        'sort_order',
        'is_active',
        'psa_slug',
        'psa_code',
        'psa_name',
        'city_class',
        'income_class',
        'is_capital',
        'psa_data',
        'psa_synced_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'province_id' => 'integer',
        'is_capital' => 'boolean',
        'psa_data' => 'array',
        'psa_synced_at' => 'datetime',
    ];

    // Relationships
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function barangays(): HasMany
    {
        return $this->hasMany(Barangay::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeByProvince($query, $provinceId)
    {
        return $query->where('province_id', $provinceId);
    }

    public function scopeCities($query)
    {
        return $query->where('type', 'city');
    }

    public function scopeMunicipalities($query)
    {
        return $query->where('type', 'municipality');
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        $provinceName = $this->province?->name ?? '';
        return $provinceName ? "{$this->name}, {$provinceName}" : $this->name;
    }

    public function getDisplayNameAttribute(): string
    {
        $typeDisplay = ucfirst($this->type);
        return "{$typeDisplay} of {$this->name}";
    }
}
