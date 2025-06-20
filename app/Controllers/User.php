<?php

namespace Controllers;

use Exception;

class User
{
    public function postUserCreate(\Base $base)
    {
        $model = new \Models\User();

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

    public function postUserEdit(\Base $base)
    {
        $model = new \Models\User();

        $entry = $model->findone(['username=?', $base->get('POST.user_identification')]);
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
        $entry->is_admin = $base->get('POST.permissions') ?? $entry->is_admin;

        try {
            $entry->save();
        } catch (Exception $e) {
            JSON_response($e->getMessage(), intval($e->getCode()));
            return;
        }
        JSON_response($base->get('POST.username'), 200);
    }
}
