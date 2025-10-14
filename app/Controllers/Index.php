<?php

namespace Controllers;

use Exception;

class Index
{
    public function getStatus(\Base $base)
    {
        JSON_response([
            'message' => $base->get('ATH.GREETING_MESSAGE'),
            'version' => [
                'API version' => $base->get('ATH.vAPI'),
                'Atheja version' => $base->get('ATH.VERSION'),
                'Atheja codename' => $base->get('ATH.CODENAME')
            ],
            'stats' => [
                'categories' => (new \Models\Category())->count(),
                'entries' => (new \Models\Entry())->count(),
                'sessions' => (new \Models\Sessions())->count(),
                'tags' => (new \Models\Tag())->count(),
                'users' => (new \Models\User())->count(),
                'votes' => (new \Models\Vote())->count(),
            ]
        ]);
    }

    public function getDBInit(\Base $base)
    {
        if ($base->get('ATH.SETUP_FINISHED') == 1) {
            $rbac = \lib\RibbitCore::get_instance($base);
            $user = VerifySessionToken($base);
            $rbac->set_current_user($user);
            if ($rbac->has_permission('system.admin') == false && (new \Models\User())->count() != 0)
                return JSON_response('Unauthorized', 401);
        }

        try {
            \Models\User::setdown();
            \Models\Sessions::setdown();
            \Models\Category::setdown();
            \Models\Entry::setdown();
            \Models\Tag::setdown();
            \Models\Vote::setdown();
        } catch (Exception $e) {
            JSON_response($e->getMessage(), 500);
        }

        try {
            \Models\User::setup();
            \Models\Sessions::setup();
            \Models\Category::setup();
            \Models\Entry::setup();
            \Models\Tag::setup();
            \Models\Vote::setup();
        } catch (Exception $e) {
            JSON_response($e->getMessage(), 500);
        }

        try {
            // Setup dummy values - Categories: Web Links
            $model = new \Models\Category();
            $model->name = "Pages";
            $model->type = "articles";
            $model->icon = "language";
            $model->save();
            unset($model);

            // Setup dummy values - Categories: Images
            $model = new \Models\Category();
            $model->name = "Images";
            $model->type = "gallery";
            $model->icon = "photo";
            $model->save();
            unset($model);
        } catch (Exception $e) {
            JSON_response($e->getMessage(), 500);
        }

        try {
            \Models\RbacRole::setdown();
            \Models\RbacPermission::setdown();
        } catch (Exception $€) {
            JSON_response("Error dropping RBAC tables: " . $€->getMessage(), 500);
        }

        try {
            \Models\RbacRole::setup();
            \Models\RbacPermission::setup();

            $manager = new \lib\RibbitManager($base);
            $manager->setup_default_roles_and_permissions();

            $userModel = new \Models\User();
            $users = $userModel->find();
            if ($users) {
                $rbacCore = \lib\RibbitCore::get_instance($base);
                foreach ($users as $user)
                    if (!$user->is_admin)
                        $rbacCore->asign_role_to_user($user->id, 'user');
            }
        } catch (Exception $e) {
            JSON_response("Error setting up Ribbit: " . $e->getMessage(), 500);
        }

        updateConfigValue($base, 'ATH.SETUP_FINISHED', 1);
        JSON_response(true);
    }
    
    public function getDBSetup(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('system.admin') == false)
            return JSON_response('Unauthorized', 401);

        try {
            \Models\User::setup();
            \Models\Sessions::setup();
            \Models\Category::setup();
            \Models\Entry::setup();
            \Models\Tag::setup();
            \Models\Vote::setup();
        } catch (Exception $e) {
            JSON_response($e->getMessage(), 500);
        }

        try {
            \Models\RbacRole::setup();
            \Models\RbacPermission::setup();

            $manager = new \lib\RibbitManager($base);
            $manager->setup_default_roles_and_permissions();
        } catch (Exception $e) {
            JSON_response("Error setting up Ribbit: " . $e->getMessage(), 500);
        }

        JSON_response(true);
    }

    public function getDBSetdown(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('system.admin') == false)
            return JSON_response('Unauthorized', 401);

        try {
            \Models\User::setdown();
            \Models\Sessions::setdown();
            \Models\Category::setdown();
            \Models\Entry::setdown();
            \Models\Tag::setdown();
            \Models\Vote::setdown();
        } catch (Exception $e) {
            JSON_response($e->getMessage(), 500);
        }

        try {
            \Models\RbacRole::setdown();
            \Models\RbacPermission::setdown();
        } catch (Exception $€) {
            JSON_response("Error dropping RBAC tables: " . $€->getMessage(), 500);
        }

        JSON_response(true);
    }

    public function getDBCleanSessions(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('system.admin') == false)
            return JSON_response('Unauthorized', 401);

        $model = new \Models\Sessions();
        $expiredSessions = $model->find(['expires_at < ?', date('Y-m-d H:i:s')]);
        if (!$expiredSessions)
            return JSON_response('No expired sessions found', 404);
        foreach ($expiredSessions as $session) {
            $session->erase();
        }
    }
}
