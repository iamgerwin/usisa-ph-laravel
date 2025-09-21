<?php

namespace App\Models;

use App\Traits\HasUuid;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectResource extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    protected $table = 'project_resources';

    protected $fillable = [
        'project_id',
        'resource_type',
        'file_name',
        'file_path',
        'file_url',
        'file_size',
        'mime_type',
        'title',
        'description',
        'uploaded_by',
        'is_public',
        'download_count',
        'metadata',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'is_public' => 'boolean',
        'download_count' => 'integer',
        'metadata' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
