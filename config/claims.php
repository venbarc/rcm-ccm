<?php

return [
    'import' => [
        'chunk_size' => (int) env('CLAIM_IMPORT_CHUNK_SIZE', 500),
        'memory_limit' => (int) env('CLAIM_IMPORT_MEMORY_LIMIT', 512),
    ],
    'export' => [
        'chunk_size' => (int) env('CLAIM_EXPORT_CHUNK_SIZE', 1000),
    ],
];
