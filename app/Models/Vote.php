<?php

namespace Models;

class Vote extends \DB\Cortex
{
    protected $db = 'DB', $table = 'votes';

    protected $fieldConf = [
        'user' => [
            'belongs-to-one' => 'Models\User',
            'required' => true,
            'index' => true
        ],
        'entry' => [
            'belongs-to-one' => 'Models\Entry',
            'required' => true,
            'index' => true
        ],
        'vote_type' => [
            'type' => 'INT1', // 1 for upvote, -1 for downvote,
            'required' => true,
            'index' => false
        ],
        'created_at' => [
            'type' => 'DATETIME',
            'default' => \DB\SQL\Schema::DF_CURRENT_TIMESTAMP
        ],
        'updated_at' => [
            'type' => 'DATETIME',
            'default' => \DB\SQL\Schema::DF_CURRENT_TIMESTAMP
        ]
    ];
}