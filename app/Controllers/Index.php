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
            \Models\Sessions::setdown();
        } catch (Exception $e) {
            JSON_response($e->getMessage(), $e->getMessage());
        }
        try {
            \Models\User::setup();
            \Models\Sessions::setup();
        } catch (Exception $e) {
            JSON_response($e->getMessage(), $e->getMessage());
        }
        JSON_response(true);
    }

    public function getDBCleanSessions(\Base $base)
    {
        if (!VerifySessionToken($base))
            return JSON_response('Unauthorized', 401);

        $model = new \Models\Sessions();

        $expiredSessions = $model->find(['expires_at < ?', date('Y-m-d H:i:s')]);
        foreach ($expiredSessions as $session) {
            $session->erase();
        }
    }
}
