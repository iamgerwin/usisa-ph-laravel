<?php

namespace App\Models;

use App\Traits\HasUuid;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectProgress extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    protected $table = 'project_progresses';

    protected $fillable = [
        'project_id',
        'progress_date',
        'progress_percentage',
        'physical_progress',
        'financial_progress',
        'description',
        'remarks',
        'status',
        'reported_by',
        'metadata',
    ];

    protected $casts = [
        'progress_date' => 'date',
        'progress_percentage' => 'decimal:2',
        'physical_progress' => 'decimal:2',
        'financial_progress' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
