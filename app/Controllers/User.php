<?php

namespace Controllers;

use Exception;

class User
{
    public function postUserCreate(\Base $base)
    {
        $model = new \Models\User();
        $authHeader = $base->get('HEADERS.Authorization');

        if ($base->get('ATH.PUBLIC_USER_CREATION') == 0 && !VerifySessionToken($base)) {
            JSON_response("User creation is disabled", 503);
            return;
        }

        if ($model->findone(['username=? OR email=?', $base->get('POST.username'), $base->get('POST.email')])) {
            JSON_response("User already exists", 409);
            return;
        }

        $model->username = $base->get('POST.username');
        $model->displayname = $base->get('POST.displayname');
        $model->email = $base->get('POST.email');
        $model->password = password_hash($base->get('POST.password'), PASSWORD_DEFAULT);
        $model->is_admin = $model->count() ? 0 : 1;

        try {
            $model->save();
        } catch (Exception $e) {
            JSON_response($e->getMessage(), intval($e->getCode()));
            return;
        }
        JSON_response(true, 201);
    }

    public function postUserLogin(\Base $base)
    {
        $userModel = new \Models\User();
        $user = $userModel->findone(['username=? OR email=?', $base->get('POST.username') ?? $base->get('POST.email')]);

        if (!$user || !password_verify($base->get('POST.password'), $user->password))
            return JSON_response('User not found or unauthorized', 401);

        $sessionToken = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime($base->get('ATH.SESSION_DURATION')));

        $sessionModel = new \Models\Sessions();
        $sessionModel->user = $user;
        $sessionModel->key = password_hash($sessionToken, PASSWORD_DEFAULT);
        $sessionModel->expires_at = $expiry;
        $sessionModel->save();

        JSON_response(['session_token' => $sessionToken, 'expires_at' => $expiry]);
    }

    public function getUser(\Base $base)
    {
        $model = new \Models\User();

        $entry = $model->findone(['username=? OR email=?', $base->get('PARAMS.user') ?? $base->get('POST.username'), $base->get('POST.email')]);
        if (!$entry) {
            return JSON_response('User not found', 404);
        }

        $cast = [
            'id' => $entry->id,
            'username' => $entry->username,
            'displayname' => $entry->displayname,
            'email' => $entry->email,
            'karma' => $entry->karma,
            'account_created_at' => $entry->account_created,
        ];

        JSON_response($cast);
    }

    public function postUserEdit(\Base $base)
    {
        $model = new \Models\User();

        $entry = $model->findone(['username=?', $base->get('PARAMS.user')]);
        if (!$entry) {
            JSON_response("User not found", 404);
            return;
        }

        if ($model->findone(['username=? OR email=?', $base->get('POST.username'), $base->get('POST.email')])) {
            JSON_response("User already exists", 409);
            return;
        }

        $entry->username = $base->get('POST.username') ?? $entry->username;
        $entry->displayname = $base->get('POST.displayname') ?? $entry->displayname;
        $entry->email = $base->get('POST.email') ?? $entry->email;
        $entry->password = $base->get('POST.password') ? password_hash($base->get('POST.password'), PASSWORD_DEFAULT) : $entry->password;
        // $entry->is_admin = $base->get('POST.permissions') ?? $entry->is_admin;

        try {
            $entry->save();
        } catch (Exception $e) {
            JSON_response($e->getMessage(), intval($e->getCode()));
            return;
        }
        JSON_response(true, 200);
    }

    public function postUserDelete(\Base $base)
    {
        if (!VerifySessionToken($base))
            return JSON_response('Unauthorized', 401);

        $model = new \Models\User();

        $user = $model->findone(['username=? OR email=?', $base->get('PARAMS.user') ?? $base->get('POST.username'), $base->get('POST.email')]);
        if (!$user)
            return JSON_response('User not found', 404);

        try {
            $user->erase();
        } catch (Exception $e) {
            return JSON_response('Unable to delete user', 500);
        }
    }
}
