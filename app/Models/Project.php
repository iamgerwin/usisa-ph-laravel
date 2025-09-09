<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    protected $fillable = [
        'external_id',
        'external_source',
        'project_name',
        'project_code',
        'description',
        'project_image_url',
        'slug',
        'program_id',
        'region_id',
        'region_code',
        'region_name',
        'province_id',
        'province_code',
        'province_name',
        'city_id',
        'city_code',
        'city_name',
        'barangay_id',
        'barangay_code',
        'barangay_name',
        'street_address',
        'zip_code',
        'country',
        'state',
        'latitude',
        'longitude',
        'status',
        'publication_status',
        'cost',
        'utilized_amount',
        'last_updated_project_cost',
        'date_started',
        'actual_date_started',
        'contract_completion_date',
        'actual_contract_completion_date',
        'as_of_date',
        'updates_count',
        'is_active',
        'is_featured',
        'metadata',
        'data_source',
        'last_synced_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'cost' => 'decimal:2',
        'utilized_amount' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'last_updated_project_cost' => 'date',
        'date_started' => 'date',
        'actual_date_started' => 'date',
        'contract_completion_date' => 'date',
        'actual_contract_completion_date' => 'date',
        'as_of_date' => 'date',
        'updates_count' => 'integer',
        'metadata' => 'array',
        'last_synced_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            if (empty($project->slug)) {
                $project->slug = Str::slug($project->project_name);
            }
        });

        static::updating(function ($project) {
            if ($project->isDirty('project_name') && empty($project->slug)) {
                $project->slug = Str::slug($project->project_name);
            }
        });
    }

    // Relationships
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function scraperSource(): BelongsTo
    {
        return $this->belongsTo(ScraperSource::class, 'external_source', 'code');
    }

    // Many-to-many relationships
    public function implementingOffices(): BelongsToMany
    {
        return $this->belongsToMany(ImplementingOffice::class, 'project_implementing_offices', 'project_uuid', 'implementing_office_uuid', 'uuid', 'uuid')
            ->withPivot(['role', 'is_primary'])
            ->withTimestamps();
    }

    public function sourceOfFunds(): BelongsToMany
    {
        return $this->belongsToMany(SourceOfFund::class, 'project_source_of_funds', 'project_uuid', 'source_of_fund_uuid', 'uuid', 'uuid')
            ->withPivot(['allocated_amount', 'utilized_amount', 'allocation_type', 'is_primary'])
            ->withTimestamps();
    }

    public function contractors(): BelongsToMany
    {
        return $this->belongsToMany(Contractor::class, 'project_contractors', 'project_uuid', 'contractor_uuid', 'uuid', 'uuid')
            ->withPivot([
                'contractor_type',
                'contract_amount',
                'contract_start_date',
                'contract_end_date',
                'contract_number',
                'status'
            ])
            ->withTimestamps();
    }

    // One-to-many relationships
    public function progresses(): HasMany
    {
        return $this->hasMany(ProjectProgress::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(ProjectResource::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublished($query)
    {
        return $query->where('publication_status', 'Published');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByProgram($query, $programId)
    {
        return $query->where('program_id', $programId);
    }

    public function scopeByLocation($query, $regionId = null, $provinceId = null, $cityId = null)
    {
        if ($regionId) {
            $query->where('region_id', $regionId);
        }
        if ($provinceId) {
            $query->where('province_id', $provinceId);
        }
        if ($cityId) {
            $query->where('city_id', $cityId);
        }
        return $query;
    }

    public function scopeWithinBudgetRange($query, $min = null, $max = null)
    {
        if ($min !== null) {
            $query->where('cost', '>=', $min);
        }
        if ($max !== null) {
            $query->where('cost', '<=', $max);
        }
        return $query;
    }

    // Accessors
    public function getFullLocationAttribute(): string
    {
        $parts = array_filter([
            $this->barangay?->name,
            $this->city?->name,
            $this->province?->name,
            $this->region?->name
        ]);
        return implode(', ', $parts);
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->cost <= 0) {
            return 0;
        }
        return round(($this->utilized_amount / $this->cost) * 100, 2);
    }

    public function getRemainingBudgetAttribute(): float
    {
        return $this->cost - $this->utilized_amount;
    }

    public function getPrimaryImplementingOfficeAttribute()
    {
        return $this->implementingOffices()->wherePivot('is_primary', true)->first();
    }

    public function getPrimaryFundingSourceAttribute()
    {
        return $this->sourceOfFunds()->wherePivot('is_primary', true)->first();
    }

    public function getLatestProgressAttribute()
    {
        return $this->progresses()->latest('progress_date')->first();
    }

    // Helper methods
    public function isOverBudget(): bool
    {
        return $this->utilized_amount > $this->cost;
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, ['Completed', 'Finished']);
    }

    public function isOngoing(): bool
    {
        return in_array($this->status, ['In Progress', 'Ongoing']);
    }

    public function getTotalAllocatedAmount(): float
    {
        return $this->sourceOfFunds()->sum('project_source_of_funds.allocated_amount') ?? 0;
    }

    public function incrementUpdatesCount(): void
    {
        $this->increment('updates_count');
    }
}
