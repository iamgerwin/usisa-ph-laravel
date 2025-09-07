<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SourceOfFund extends Model
{
    use HasFactory, HasUuid, SoftDeletes;
    
    protected $table = 'source_of_funds';
    
    protected $fillable = [
        'dime_id',
        'name',
        'name_abbreviation',
        'logo_url',
        'description',
        'type',
        'fiscal_year',
        'is_active',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    /**
     * Get the projects for this source of fund
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_source_of_funds', 'source_of_fund_uuid', 'project_uuid')
            ->withTimestamps();
    }
}
