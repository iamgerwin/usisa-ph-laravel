<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImplementingOffice extends Model
{
    use HasFactory, HasUuid, SoftDeletes;
    
    protected $fillable = [
        'dime_id',
        'name',
        'name_abbreviation',
        'logo_url',
        'description',
        'website',
        'email',
        'phone',
        'address',
        'is_active',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    /**
     * Get the projects for this implementing office
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_implementing_offices')
            ->withPivot(['role', 'is_primary'])
            ->withTimestamps();
    }
}
