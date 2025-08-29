<?php

namespace Models;

class Entry extends \DB\Cortex
{
    protected $db = 'DB', $table = 'entries';

    protected $fieldConf = [
        'name' => [
            'type' => 'VARCHAR256',
            'required' => true,
            'unique' => false,
            'nullable' => false,
            'index' => true
        ],
        'description' => [
            'type' => 'TEXT',
            'required' => false,
            'unique' => false,
            'nullable' => false,
            'index' => false
        ],
        'url' => [
            'type' => 'VARCHAR256',
            'required' => true,
            'unique' => true,
            'nullable' => false,
            'index' => true
        ],
        'category' => [
            'belongs-to-one' => 'Models\Category',
            'required' => true,
            'unique' => false,
            'nullable' => false,
            'index' => false,
            'default' => 1
        ],
        'favicon' => [
            'type' => 'LONGTEXT',
            'required' => false,
            'unique' => false,
            'nullable' => false,
            'index' => false
        ],
        'upvotes' => [
            'type' => 'INT4',
            'required' => true,
            'unique' => false,
            'nullable' => false,
            'index' => false,
            'default' => 1
        ],
        'downvotes' => [
            'type' => 'INT4',
            'required' => true,
            'unique' => false,
            'nullable' => false,
            'index' => false,
            'default' => 0
        ],
        'author' => [
            'belongs-to-one' => 'Models\User',
            'required' => true,
            'index' => true
        ],
        'tags' => [
            'belongs-to-many' => 'Models\Tag',
            'required' => true,
            'unique' => false,
            'index' => true
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

    public function getKarma()
    {
        return $this->upvotes - $this->downvotes;
    }

    public function getVoteRatio()
    {
        $total = $this->upvotes + $this->downvotes;
        return $total > 0 ? ($this->upvotes / $total) * 100 : 0;
    }
}

