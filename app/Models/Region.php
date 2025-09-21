<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Region extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    protected $fillable = [
        'code',
        'name',
        'abbreviation',
        'sort_order',
        'is_active',
        'psa_slug',
        'psa_code',
        'psa_name',
        'psa_data',
        'psa_synced_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'psa_data' => 'array',
        'psa_synced_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($region) {
            if (empty($region->abbreviation)) {
                $region->abbreviation = Str::upper(Str::substr($region->name, 0, 10));
            }
        });
    }

    // Relationships
    public function provinces(): HasMany
    {
        return $this->hasMany(Province::class);
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

    // Accessors
    public function getFullNameAttribute(): string
    {
        return $this->abbreviation ? "{$this->name} ({$this->abbreviation})" : $this->name;
    }

    // Helper methods for Filament
    public function getProvincesCountAttribute(): int
    {
        return $this->provinces()->count();
    }

    public function getProjectsCountAttribute(): int
    {
        return $this->projects()->count();
    }
}
