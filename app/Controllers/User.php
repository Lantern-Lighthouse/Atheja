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
        $model->password = password_hash($base->get('POST.email'), PASSWORD_DEFAULT);
        $model->email = $base->get('POST.email');

        $model->is_admin = $model->count() ? 0 : 1;

        try {
            $model->save();
        } catch (Exception $e) {
            JSON_response($e->getMessage(), intval($e->getCode()));
            return;
        }
        JSON_response(true, 201);
    }
}
