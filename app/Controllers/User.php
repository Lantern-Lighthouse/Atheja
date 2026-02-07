<?php

namespace Controllers;

use Exception;
use Responsivity\Responsivity;
use Identicon\Identicon;

class User
{
    public function postUserCreate(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($base->get('ATH.PUBLIC_USER_CREATION') == 0 || $rbac->has_permission('user.create') == false)
            return Responsivity::respond("User creation is disabled or unauthorized", Responsivity::HTTP_Bad_Request);

        $model = new \Models\User();
        if ($model->findone(['username=? OR email=?', $base->get('POST.username'), $base->get('POST.email')])) {
            Responsivity::respond("User already exists", Responsivity::HTTP_Bad_Request);
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
            Responsivity::respond($e->getMessage(), Responsivity::HTTP_Internal_Error);
            return;
        }
        Responsivity::respond("User created", Responsivity::HTTP_Created);
    }

    public function postUserLogin(\Base $base)
    {
        $userModel = new \Models\User();
        $user = $userModel->findone(['username=? OR email=?', $base->get('POST.username') ?? $base->get('POST.email')]);

        if (!$user || !password_verify($base->get('POST.password'), $user->password))
            return Responsivity::respond('User not found or unauthorized', Responsivity::HTTP_Unauthorized);

        $sessionToken = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime($base->get('ATH.SESSION_DURATION')));

        $sessionModel = new \Models\Sessions();
        $sessionModel->user = $user;
        $sessionModel->key = password_hash($sessionToken, PASSWORD_DEFAULT);
        $sessionModel->expires_at = $expiry;
        $sessionModel->save();

        if ($user->hasRole('guest')) {
            $role = 'muted';
        } else if ($user->hasRole('user')) {
            $role = 'user';
        } else if ($user->hasRole('moderator') || $user->hasRole('admin')) {
            $role = 'moderator';
        }

        Responsivity::respond([
            'session_token' => $sessionToken,
            'expires_at' => $expiry,
            'username' => $user->username,
            'displayname' => $user->displayname,
            ...(isset($role) ? ['role' => $role] : [])
        ]);
    }

    public function getUser(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('user.read') == false)
            return Responsivity::respond('Unauthorized', Responsivity::HTTP_Unauthorized);

        $model = new \Models\User();

        $entry = $model->findone(['username=? OR email=?', $base->get('PARAMS.user') ?? $base->get('POST.username'), $base->get('POST.email')]);
        if (!$entry) {
            return Responsivity::respond('User not found', Responsivity::HTTP_Not_Found);
        }

        if ($entry->hasRole('guest')) {
            $role = 'muted';
        } else if ($entry->hasRole('user')) {
            $role = 'user';
        } else if ($entry->hasRole('moderator') || $entry->hasRole('admin')) {
            $role = 'moderator';
        }

        $cast = [
            'id' => $entry->id,
            'username' => $entry->username,
            'displayname' => $entry->displayname,
            'email' => $entry->email,
            'karma' => $entry->karma,
            'account_created_at' => $entry->account_created,
            ...(isset($role) ? ['role' => $role] : [])
        ];

        Responsivity::respond($cast);
    }

    public function postUserEdit(\Base $base)
    {

        $model = new \Models\User();
        $entry = $model->findone(['username=?', $base->get('PARAMS.user')]);
        if (!$entry) {
            Responsivity::respond("User not found", Responsivity::HTTP_Not_Found);
            return;
        }

        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if (!\lib\RibbitGuard::require_ownership_or_admin($entry->_id))
            return Responsivity::respond('Unauthorized', Responsivity::HTTP_Unauthorized);

        if ($model->findone(['username=? OR email=?', $base->get('POST.username'), $base->get('POST.email')])) {
            Responsivity::respond("User already exists", Responsivity::HTTP_Bad_Request);
            return;
        }

        $entry->username = $base->get('POST.username') ?? $entry->username;
        $entry->displayname = $base->get('POST.displayname') ?? $entry->displayname;
        $entry->email = $base->get('POST.email') ?? $entry->email;
        $entry->password = $base->get('POST.password') ? password_hash($base->get('POST.password'), PASSWORD_DEFAULT) : $entry->password;

        try {
            $entry->save();
        } catch (Exception $e) {
            Responsivity::respond($e->getMessage(), Responsivity::HTTP_Internal_Error);
            return;
        }
        Responsivity::respond("User edited");
    }

    public function postUserDelete(\Base $base)
    {
        $model = new \Models\User();
        $entry = $model->findone(['username=? OR email=?', $base->get('PARAMS.user') ?? $base->get('POST.username'), $base->get('POST.email')]);
        if (!$entry)
            return Responsivity::respond('User not found', Responsivity::HTTP_Not_Found);

        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if (!\lib\RibbitGuard::require_ownership_or_admin($entry->_id))
            return Responsivity::respond('Unauthorized', Responsivity::HTTP_Unauthorized);

        try {
            $entry->erase();
        } catch (Exception $e) {
            return Responsivity::respond('Unable to delete user', Responsivity::HTTP_Internal_Error);
        }
    }

    public function getUserAvatar(\Base $base)
    {
        $model = new \Models\User();
        $user = $model->findone(['username=? OR email=?', $base->get('PARAMS.user') ?? $base->get('POST.username'), $base->get('POST.email')]);
        if (!$user)
            return Responsivity::respond("User not found", Responsivity::HTTP_Not_Found);

        try {
            Identicon::outputImage($user->username);
        } catch (Exception $e) {
            return Responsivity::respond("Unable to display avatar: " . $e->getMessage(), Responsivity::HTTP_Internal_Error);
        }
    }

    public function getUserReport(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('user.report') == false)
            return Responsivity::respond('Unauthorized', Responsivity::HTTP_Unauthorized);

        $reportModel = new \Models\Report();
        $userModel = new \Models\User();

        $reported_user = $userModel->findone(['username=?', $base->get('PARAMS.user')]);
        if (!$reported_user)
            return Responsivity::respond("User not found", Responsivity::HTTP_Not_Found);

        $reportModel->reporter = $user;
        $reportModel->user_reported = $reported_user;
        $reportModel->reason = $base->get('POST.reason');

        try {
            $reportModel->save();
            return Responsivity::respond('Report created', Responsivity::HTTP_Created);
        } catch (Exception $e) {
            return Responsivity::respond('Failed to report', Responsivity::HTTP_Internal_Error);
        }
    }
}
