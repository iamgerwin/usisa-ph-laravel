<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contractor extends Model
{
    use HasFactory, HasUuid, SoftDeletes;
    
    protected $fillable = [
        'dime_id',
        'name',
        'name_abbreviation',
        'logo_url',
        'description',
        'contractor_type',
        'license_number',
        'license_expiry',
        'website',
        'email',
        'phone',
        'address',
        'is_active',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'license_expiry' => 'date',
    ];
    
    /**
     * Get the projects for this contractor
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_contractors')
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
}
