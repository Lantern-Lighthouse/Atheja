<?php

namespace Controllers;

use Exception;
use lib\Identicon;

class User
{
    public function postUserCreate(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($base->get('ATH.PUBLIC_USER_CREATION') == 0 || $rbac->has_permission('user.create') == false)
            return \lib\Responsivity::respond("User creation is disabled or unauthorized", \lib\Responsivity::HTTP_Bad_Request);

        $model = new \Models\User();
        if ($model->findone(['username=? OR email=?', $base->get('POST.username'), $base->get('POST.email')])) {
            \lib\Responsivity::respond("User already exists", \lib\Responsivity::HTTP_Bad_Request);
            return;
        }

        $model->username = $base->get('POST.username');
        $model->displayname = $base->get('POST.displayname');
        $model->email = $base->get('POST.email');
        $model->password = password_hash($base->get('POST.password'), PASSWORD_DEFAULT);
        $model->is_admin = $model->count() ? 0 : 1;

        try {
            $model->save();
            $model->count() <= 1 ? (\lib\RibbitCore::get_instance($base))->asign_role_to_user($model->get("id"), 'admin') : (\lib\RibbitCore::get_instance($base))->asign_role_to_user($model->get("id"), 'user');
        } catch (Exception $e) {
            \lib\Responsivity::respond($e->getMessage(), \lib\Responsivity::HTTP_Internal_Error);
            return;
        }
        \lib\Responsivity::respond("User created", \lib\Responsivity::HTTP_Created);
    }

    public function postUserLogin(\Base $base)
    {
        $userModel = new \Models\User();
        $user = $userModel->findone(['username=? OR email=?', $base->get('POST.username') ?? $base->get('POST.email')]);

        if (!$user || !password_verify($base->get('POST.password'), $user->password))
            return \lib\Responsivity::respond('User not found or unauthorized', \lib\Responsivity::HTTP_Unauthorized);

        $sessionToken = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime($base->get('ATH.SESSION_DURATION')));

        $sessionModel = new \Models\Sessions();
        $sessionModel->user = $user;
        $sessionModel->key = password_hash($sessionToken, PASSWORD_DEFAULT);
        $sessionModel->expires_at = $expiry;
        $sessionModel->save();

        \lib\Responsivity::respond(['session_token' => $sessionToken, 'expires_at' => $expiry]);
    }

    public function getUser(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('user.read') == false)
            return \lib\Responsivity::respond('Unauthorized', \lib\Responsivity::HTTP_Unauthorized);

        $model = new \Models\User();

        $entry = $model->findone(['username=? OR email=?', $base->get('PARAMS.user') ?? $base->get('POST.username'), $base->get('POST.email')]);
        if (!$entry) {
            return \lib\Responsivity::respond('User not found', \lib\Responsivity::HTTP_Not_Found);
        }

        $cast = [
            'id' => $entry->id,
            'username' => $entry->username,
            'displayname' => $entry->displayname,
            'email' => $entry->email,
            'karma' => $entry->karma,
            'account_created_at' => $entry->account_created,
        ];

        \lib\Responsivity::respond($cast);
    }

    public function postUserEdit(\Base $base)
    {

        $model = new \Models\User();
        $entry = $model->findone(['username=?', $base->get('PARAMS.user')]);
        if (!$entry) {
            \lib\Responsivity::respond("User not found", \lib\Responsivity::HTTP_Not_Found);
            return;
        }

        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if (!\lib\RibbitGuard::require_ownership_or_admin($entry->_id))
            return \lib\Responsivity::respond('Unauthorized', \lib\Responsivity::HTTP_Unauthorized);

        if ($model->findone(['username=? OR email=?', $base->get('POST.username'), $base->get('POST.email')])) {
            \lib\Responsivity::respond("User already exists", \lib\Responsivity::HTTP_Bad_Request);
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
            \lib\Responsivity::respond($e->getMessage(), \lib\Responsivity::HTTP_Internal_Error);
            return;
        }
        \lib\Responsivity::respond("User edited");
    }

    public function postUserDelete(\Base $base)
    {
        $model = new \Models\User();
        $entry = $model->findone(['username=? OR email=?', $base->get('PARAMS.user') ?? $base->get('POST.username'), $base->get('POST.email')]);
        if (!$entry)
            return \lib\Responsivity::respond('User not found', \lib\Responsivity::HTTP_Not_Found);

        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if (!\lib\RibbitGuard::require_ownership_or_admin($entry->_id))
            return \lib\Responsivity::respond('Unauthorized', \lib\Responsivity::HTTP_Unauthorized);

        try {
            $entry->erase();
        } catch (Exception $e) {
            return \lib\Responsivity::respond('Unable to delete user', \lib\Responsivity::HTTP_Internal_Error);
        }
    }

    public function getUserAvatar(\Base $base)
    {
        $model = new \Models\User();
        $user = $model->findone(['username=? OR email=?', $base->get('PARAMS.user') ?? $base->get('POST.username'), $base->get('POST.email')]);
        if (!$user)
            return \lib\Responsivity::respond("User not found", \lib\Responsivity::HTTP_Not_Found);

        try {
            Identicon::output_image($user->username);
        } catch (Exception $e) {
            return \lib\Responsivity::respond("Unable to display avatar: " . $e->getMessage(), \lib\Responsivity::HTTP_Internal_Error);
        }
    }

    public function getUserReport(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('user.report') == false)
            return \lib\Responsivity::respond('Unauthorized', \lib\Responsivity::HTTP_Unauthorized);

        $reportModel = new \Models\Report();
        $userModel = new \Models\User();
        
        $reported_user = $userModel->findone(['username=?', $base->get('PARAMS.user')]);
        if(!$reported_user)
            return \lib\Responsivity::respond("User not found", \lib\Responsivity::HTTP_Not_Found);

        $reportModel->reporter = $user;
        $reportModel->user_reported = $reported_user;
        $reportModel->reason = $base->get('POST.reason');
        
        try {
            $reportModel->save();
            return \lib\Responsivity::respond('Report created', \lib\Responsivity::HTTP_Created);
        } catch (Exception $e) {
            return \lib\Responsivity::respond('Failed to report', \lib\Responsivity::HTTP_Internal_Error);
        }
    }
}
