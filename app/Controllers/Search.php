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
        
        $cast = [
            'name' => $base->get('PARAMS.tag'),
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
    //endregion
}
