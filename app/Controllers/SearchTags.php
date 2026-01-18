<?php

namespace Controllers;

use Exception;

class SearchTags
{
    public function getSearchTags(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('tag.read') == false)
            return \lib\Responsivity::respond('Unauthorized', \lib\Responsivity::HTTP_Unauthorized);

        $model = new \Models\Tag();
        $entries = $model->find();
        if (!$entries)
            return \lib\Responsivity::respond('Tag not found', \lib\Responsivity::HTTP_Not_Found);

        $cast = array();
        foreach ($entries as $entry) {
            array_push($cast, $entry->name);
        }
        if (sizeof($cast) != 0)
            \lib\Responsivity::respond($cast);
        else
            \lib\Responsivity::respond("Tags not found", 404);
    }

    public function getSearchTag(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('tag.read') == false)
            return \lib\Responsivity::respond('Unauthorized', \lib\Responsivity::HTTP_Unauthorized);

        $model = new \Models\Tag();
        $entry = $model->findone(['name=?', $base->get('PARAMS.tag')]);
        if (!$entry) {
            return \lib\Responsivity::respond('Tag not found', \lib\Responsivity::HTTP_Not_Found);
        }
        unset($model);
        $model = new \Models\Entry();
        $entries = $model->afind(['tags=?', $base->get('PARAMS.tag')]);

        $cast = [
            'name' => $base->get('PARAMS.tag'),
            'count' => $entries ? count($entries) : 0
        ];

        \lib\Responsivity::respond($cast);
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
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('tag.create') == false)
            return \lib\Responsivity::respond('Unauthorized', \lib\Responsivity::HTTP_Unauthorized);

        if (self::CreateTag($base->get('POST.tagname')))
            return \lib\Responsivity::respond('Tag added', \lib\Responsivity::HTTP_Created);
        else
            return \lib\Responsivity::respond('Tag already exists', \lib\Responsivity::HTTP_Bad_Request);
    }

    public function postSearchTagEdit(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('tag.update') == false)
            return \lib\Responsivity::respond('Unauthorized', \lib\Responsivity::HTTP_Unauthorized);

        $model = new \Models\Tag();
        $entry = $model->findone(['name=?', $base->get('PARAMS.tag')]);
        if (!$entry)
            return \lib\Responsivity::respond('Tag not found', \lib\Responsivity::HTTP_Not_Found);

        $entry->name = $base->get('POST.tagname') ?? $entry->name;

        try {
            $entry->save();
        } catch (Exception $e) {
            \lib\Responsivity::respond('Changes not saved', \lib\Responsivity::HTTP_Internal_Error);
            return;
        }
        \lib\Responsivity::respond("Tag edited");
    }

    public function postSearchTagDelete(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('tag.delete') == false)
            return \lib\Responsivity::respond('Unauthorized', \lib\Responsivity::HTTP_Unauthorized);

        $model = new \Models\Tag();
        $entry = $model->findone(['name=?', $base->get('PARAMS.tag')]);
        if (!$entry)
            return \lib\Responsivity::respond('Tag not found', \lib\Responsivity::HTTP_Not_Found);

        try {
            $entry->erase();
        } catch (Exception $e) {
            \lib\Responsivity::respond('Tag not deleted', \lib\Responsivity::HTTP_Internal_Error);
            return;
        }
        \lib\Responsivity::respond("Tag deleted");
    }
}
