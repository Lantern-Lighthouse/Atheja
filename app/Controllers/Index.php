<?php

namespace Controllers;

use Exception;

class Index
{
    public function getStatus(\Base $base)
    {
        JSON_response(['message' => 'API is running', 'status' => 'ok']);
    }

    public function getDBsetup(\Base $base)
    {
        try {
            \Models\User::setdown();
        } catch (Exception $e) {
            JSON_response($e->getMessage(), $e->getMessage());
        }
        try {
            \Models\User::setup();
        } catch (Exception $e) {
            JSON_response($e->getMessage(), $e->getMessage());
        }
        JSON_response(true);
    }
}
