<?php
return [
    // fallback type mapping (used if a field has no explicit type in form())
    'db_to_input' => [
        'string'   => 'text',
        'text'     => 'textarea',
        'integer'  => 'number',
        'bigint'   => 'number',
        'smallint' => 'number',
        'decimal'  => 'number',
        'float'    => 'number',
        'boolean'  => 'checkbox',
        'date'     => 'date',
        'datetime' => 'datetime-local',
        'json'     => 'textarea',
    ],
    // names to auto-hide
    'skip' => ['id','created_at','updated_at','deleted_at'],
];
