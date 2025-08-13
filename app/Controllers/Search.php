<?php

namespace Controllers;

use Exception;

class Search
{
    //region Categories
    public function getSearchCategory(\Base $base)
    {
        $model = new \Models\Category();
        $entries = $model->afind();
        $cast = array();
        foreach ($entries as $entry) {
            $cast[$entry['name']] = array(
                'id' => $entry['_id'],
                'type' => $entry['type'],
                'icon' => $entry['icon'],
            );
        }
        JSON_response($cast);
    }

    public function postSearchCategoryCreate(\Base $base)
    {
        if (!VerifySessionToken($base))
            return JSON_response('Unauthorized', 401);

        $model = new \Models\Category();
        $model->name = $base->get('POST.name');
        $model->type = $base->get('POST.type');
        $model->icon = $base->get('POST.icon');

        try {
            $model->save();
        } catch (Exception $e) {
            return JSON_response($e->getMessage(), 500);
        }

        JSON_response(true);
    }

    public function postSearchCategoryEdit(\Base $base)
    {
        if (!VerifySessionToken($base))
            return JSON_response('Unauthorized', 401);

        $model = new \Models\Category();
        $entry = $model->findone(['name=?', $base->get('PARAMS.category')]);
        if (!$entry)
            return JSON_response('Category not found', 404);

        $entry->name = $base->get('POST.name') ?? $entry->name;
        $entry->type = $base->get('POST.type') ?? $entry->type;
        $entry->icon = $base->get('POST.icon') ?? $entry->icon;

        try {
            $entry->save();
        } catch (Exception $e) {
            return JSON_response($e->getMessage(), 500);
        }

        JSON_response(true);
    }

    public function postSearchCategoryDelete(\Base $base)
    {
        if (!VerifySessionToken($base))
            return JSON_response('Unauthorized', 401);

        $model = new \Models\Category();
        $entry = $model->findone(['name=?', $base->get('PARAMS.category')]);
        if (!$entry)
            return JSON_response('Category not found', 404);

        try {
            $entry->erase();
        } catch (Exception $e) {
            return JSON_response('Unable to delete category', 500);
        }

        JSON_response(true);
    }
    //endregion

    //region Tags
    public function getSearchTags(\Base $base)
    {
        $model = new \Models\Tag();
        $entries = $model->find();
        if (!$entries)
            return JSON_response('Tag not found', 404);

        $cast = array();
        foreach ($entries as $entry) {
            array_push($cast, $entry->name);
        }
        if (sizeof($cast) != 0)
            JSON_response($cast);
        else
            JSON_response(false, 404);
    }

    public function getSearchTag(\Base $base)
    {
        $model = new \Models\Tag();
        $entry = $model->findone(['name=?', $base->get('PARAMS.tag')]);
        if (!$entry) {
            return JSON_response('Tag not found', 404);
        }
        unset($model);
        $model = new \Models\Entry();
        $entries = $model->afind(['tags=?', $base->get('PARAMS.tag')]);

        $cast = [
            'name' => $base->get('PARAMS.tag'),
            'count' => $entries ? count($entries) : 0
        ];

        JSON_response($cast);
    }

    public static function CreateTag(string $tagName)
    {
        $model = new \Models\Tag();
        if (!$model->findone(['name=?', $tagName])) {
            $model->name = $tagName;
            $model->save();
            return true;
        }
        return false;
    }

    public function postSearchTagAdd(\Base $base)
    {
        if (!VerifySessionToken($base))
            return JSON_response('Unauthorized', 401);

        if (self::CreateTag($base->get('POST.tagname')))
            return JSON_response('Tag added', 201);
        else
            return JSON_response('Tag already exists', 409);
    }

    public function postSearchTagEdit(\Base $base)
    {
        if (!VerifySessionToken($base))
            return JSON_response('Unauthorized', 401);

        $model = new \Models\Tag();
        $entry = $model->findone(['name=?', $base->get('PARAMS.tag')]);
        if (!$entry)
            return JSON_response('Tag not found', 404);

        $entry->name = $base->get('POST.tagname') ?? $entry->name;

        try {
            $entry->save();
        } catch (Exception $e) {
            JSON_response('Changes not saved', 500);
            return;
        }
        JSON_response(true);
    }

    public function postSearchTagDelete(\Base $base)
    {
        if (!VerifySessionToken($base))
            return JSON_response('Unauthorized', 401);

        $model = new \Models\Tag();
        $entry = $model->findone(['name=?', $base->get('PARAMS.tag')]);
        if (!$entry)
            return JSON_response('Tag not found', 404);

        try {
            $entry->erase();
        } catch (Exception $e) {
            JSON_response('Tag not deleted', 500);
            return;
        }
        JSON_response(null, 204);
    }
    //endregion
}
