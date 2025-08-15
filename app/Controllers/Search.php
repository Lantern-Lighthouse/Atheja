<?php

namespace Controllers;

use Exception;
use Lib\URLser;

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

    //region Entries
    public function getSearchEntry(\Base $base)
    {
        $model = new \Models\Entry();
        $entry = $model->findone(['id=?', $base->get('PARAMS.entry')]);
        if (!$entry)
            return JSON_response('Entry not found', 404);

        JSON_response($entry->cast());
    }

    public function postSearchEntryCreate(\Base $base)
    {
        if (!VerifySessionToken($base))
            return JSON_response('Unauthorized', 401);

        $model = new \Models\Entry();

        // Name setting
        if ($base->get('POST.fetch-name-from-site')) {
            // TODO: Name fetching
        } else {
            if (!$base->get('POST.page-name'))
                return JSON_response('Page name not found', 404);
            $model->name = $base->get('POST.page-name');
        }

        // Description setting
        $model->description = $base->get('POST.page-desc');

        // URL setting
        if (!$base->get('POST.page-url'))
            return JSON_response('URL not found', 404);
        else if ($model->findone(['url=?', $base->get('POST.page-url')]))
            return JSON_response('URL already found', 409);
        $model->url = $base->get('POST.page-url');

        // Category setting
        $model->category = $base->get('POST.view-category') ?? 1;

        // Favicon setting
        // TODO: Favicon fetching

        // Karma setting
        $model->karma = 1;

        // Author setting
        $model->author = (new \Models\User())->findone(['username=? OR email=?', $base->get('POST.author-username'), $base->get('POST.author-email')]);

        // Tags setting
        $tagsIn = explode(';', $base->get('POST.tags'));
        $tagsOut = array();
        foreach ($tagsIn as $tag) {
            self::CreateTag($tag);
            $tagID = (new \Models\Tag())->findone(['name=?', $tag])['_id'];
            array_push($tagsOut, $tagID);
        }
        array_push($tagsOut, URLser::parse_domain($base->get('POST.page-url')));
        $model->tags = $tagsOut;

        $model->save();
        JSON_response('Entry added');
    }

    public function postSearchEntryEdit(\Base $base)
    {
        if (!VerifySessionToken($base))
            return JSON_response('Unauthorized', 401);

        $model = new \Models\Entry();
        $entry = $model->findone('id=?', $base->get('PARAMS.entry'));
        if (!$entry)
            return JSON_response('Entry not found', 404);

        $entry->name = $base->get('POST.site-name') ?? $entry->name;
        $entry->description = $base->get('POST.site-desc') ?? $entry->description;
        $entry->url = $base->get('POST.site-url') ?? $entry->url;
        $entry->category = $base->get('POST.view-category') ?? $entry->category;
        $entry->favicon = $base->get('POST.site-favicon') ?? $entry->favicon;
    }
    //endregion
}
