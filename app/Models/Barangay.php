<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Barangay extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    protected $fillable = [
        'city_id',
        'code',
        'name',
        'sort_order',
        'is_active',
        'psa_code',
        'psa_name',
        'urban_rural',
        'psa_data',
        'psa_synced_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'city_id' => 'integer',
        'psa_data' => 'array',
        'psa_synced_at' => 'datetime',
    ];

    // Relationships
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
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

    public function scopeByCity($query, $cityId)
    {
        return $query->where('city_id', $cityId);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        $cityName = $this->city?->name ?? '';
        return $cityName ? "{$this->name}, {$cityName}" : $this->name;
    }

    public function getFullLocationAttribute(): string
    {
        $city = $this->city;
        $provinceName = $city?->province?->name ?? '';
        $cityName = $city?->name ?? '';
        
        $parts = array_filter([$this->name, $cityName, $provinceName]);
        return implode(', ', $parts);
    }
}
