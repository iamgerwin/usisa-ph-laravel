<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Province extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    protected $fillable = [
        'region_id',
        'code',
        'name',
        'abbreviation',
        'island_group_code',
        'old_name',
        'district_code',
        'sort_order',
        'is_active',
        'psa_slug',
        'psa_code',
        'psa_name',
        'income_class',
        'psa_data',
        'psa_synced_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'region_id' => 'integer',
        'psa_data' => 'array',
        'psa_synced_at' => 'datetime',
    ];

    // Relationships
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
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

    // Route model binding
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeByRegion($query, $regionId)
    {
        return $query->where('region_id', $regionId);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        $regionName = $this->region?->name ?? '';
        return $regionName ? "{$this->name}, {$regionName}" : $this->name;
    }

    // Helper methods for Filament
    public function getCitiesCountAttribute(): int
    {
        return $this->cities()->count();
    }

    public function getProjectsCountAttribute(): int
    {
        return $this->projects()->count();
    }
}
