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

    public function getDBsetup(\Base $base)
    {
        try {
            \Models\User::setdown();
            \Models\Sessions::setdown();
            \Models\Category::setdown();
            \Models\Entry::setdown();
            \Models\Tag::setdown();
            \Models\Vote::setdown();
        } catch (Exception $e) {
            JSON_response($e->getMessage(), $e->getMessage());
        }

        try {
            \Models\User::setup();
            \Models\Sessions::setup();
            \Models\Category::setup();
            \Models\Entry::setup();
            \Models\Tag::setup();
            \Models\Vote::setup();
        } catch (Exception $e) {
            JSON_response($e->getMessage(), $e->getMessage());
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
