<?php

namespace Models;

class Tag extends \DB\Cortex
{
    protected $db = 'DB', $table = 'tags';

    protected $fieldConf = [
        'name' => [
            'type' => 'VARCHAR256',
            'required' => true,
            'unique' => false,
            'nullable' => false,
            'index' => true
        ]
    ];
}

