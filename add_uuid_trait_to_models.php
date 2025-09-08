<?php

$models = glob('app/Models/*.php');

foreach ($models as $file) {
    $content = file_get_contents($file);
    
    // Skip if already has HasUuid
    if (strpos($content, 'use App\Traits\HasUuid;') !== false || 
        strpos($content, 'use HasUuid') !== false) {
        echo "Already has UUID trait: $file\n";
        continue;
    }
    
    // Add the use statement after namespace
    $content = preg_replace(
        '/(namespace App\\\\Models;)/',
        "$1\n\nuse App\\Traits\\HasUuid;",
        $content,
        1
    );
    
    // Add HasUuid to the traits list
    // Handle different cases
    if (preg_match('/use\s+HasFactory\s*,\s*SoftDeletes\s*;/', $content)) {
        $content = preg_replace(
            '/use\s+HasFactory\s*,\s*SoftDeletes\s*;/',
            'use HasFactory, SoftDeletes, HasUuid;',
            $content
        );
    } elseif (preg_match('/use\s+HasFactory\s*;/', $content)) {
        $content = preg_replace(
            '/use\s+HasFactory\s*;/',
            'use HasFactory, HasUuid;',
            $content
        );
    }
    
    file_put_contents($file, $content);
    echo "Updated: $file\n";
}

echo "\nAll models now have UUID trait!\n";