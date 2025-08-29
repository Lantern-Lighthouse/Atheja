<?php

namespace Models;

class Category extends \DB\Cortex
{
    protected $db = 'DB', $table = 'categories';

    protected $fieldConf = [
        'name' => [
            'type' => 'VARCHAR128',
            'required' => true,
            'unique' => true,
            'nullable' => false,
            'index' => true
        ],
        'type' => [
            'type' => 'VARCHAR128',
            'required' => true,
            'unique' => false,
            'nullable' => false,
            'index' => false
        ],
        'icon' => [
            'type' => 'VARCHAR128',
            'required' => false,
            'unique' => false,
            'nullable' => false,
            'index' => false
        ]
    ];
}

