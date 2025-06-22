<?php

namespace Controllers;

use Exception;

class User
{
    public function postUserCreate(\Base $base)
    {
        $model = new \Models\User();
        $authHeader = $base->get('HEADERS.Authorization');

        if ($base->get('ATH.PUBLIC_USER_CREATION') == 0 && !VerifyAuth($base)) {
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
        $key = bin2hex(random_bytes(16));
        $model->key = password_hash($key, PASSWORD_DEFAULT);

        try {
            $model->save();
        } catch (Exception $e) {
            JSON_response($e->getMessage(), intval($e->getCode()));
            return;
        }
        JSON_response($key, 201);
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
}
