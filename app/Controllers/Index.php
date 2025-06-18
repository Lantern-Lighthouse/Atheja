<?php

namespace Controllers;

class Index
{
    public function getStatus(\Base $base)
    {
        JSON_response(['message' => 'API is running', 'status' => 'ok']);
    }
}
