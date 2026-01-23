<?php

namespace Controllers;

use Exception;
use Responsivity\Responsivity;

class SearchTags
{
    public function getSearchTags(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('tag.read') == false)
            return Responsivity::respond('Unauthorized', Responsivity::HTTP_Unauthorized);

        $model = new \Models\Tag();
        $entries = $model->find();
        if (!$entries)
            return Responsivity::respond('Tag not found', Responsivity::HTTP_Not_Found);

        $cast = array();
        foreach ($entries as $entry) {
            array_push($cast, $entry->name);
        }
        if (sizeof($cast) != 0)
            Responsivity::respond($cast);
        else
            Responsivity::respond("Tags not found", 404);
    }

    public function getSearchTag(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('tag.read') == false)
            return Responsivity::respond('Unauthorized', Responsivity::HTTP_Unauthorized);

        $model = new \Models\Tag();
        $entry = $model->findone(['name=?', $base->get('PARAMS.tag')]);
        if (!$entry) {
            return Responsivity::respond('Tag not found', Responsivity::HTTP_Not_Found);
        }
        unset($model);
        $model = new \Models\Entry();
        $entries = $model->afind(['tags=?', $base->get('PARAMS.tag')]);

        $cast = [
            'name' => $base->get('PARAMS.tag'),
            'count' => $entries ? count($entries) : 0
        ];

        Responsivity::respond($cast);
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
            return Responsivity::respond('Unauthorized', Responsivity::HTTP_Unauthorized);

        if (self::CreateTag($base->get('POST.tagname')))
            return Responsivity::respond('Tag added', Responsivity::HTTP_Created);
        else
            return Responsivity::respond('Tag already exists', Responsivity::HTTP_Bad_Request);
    }

    public function postSearchTagEdit(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('tag.update') == false)
            return Responsivity::respond('Unauthorized', Responsivity::HTTP_Unauthorized);

        $model = new \Models\Tag();
        $entry = $model->findone(['name=?', $base->get('PARAMS.tag')]);
        if (!$entry)
            return Responsivity::respond('Tag not found', Responsivity::HTTP_Not_Found);

        $entry->name = $base->get('POST.tagname') ?? $entry->name;

        try {
            $entry->save();
        } catch (Exception $e) {
            Responsivity::respond('Changes not saved', Responsivity::HTTP_Internal_Error);
            return;
        }
        Responsivity::respond("Tag edited");
    }

    public function postSearchTagDelete(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('tag.delete') == false)
            return Responsivity::respond('Unauthorized', Responsivity::HTTP_Unauthorized);

        $model = new \Models\Tag();
        $entry = $model->findone(['name=?', $base->get('PARAMS.tag')]);
        if (!$entry)
            return Responsivity::respond('Tag not found', Responsivity::HTTP_Not_Found);

        try {
            $entry->erase();
        } catch (Exception $e) {
            Responsivity::respond('Tag not deleted', Responsivity::HTTP_Internal_Error);
            return;
        }
        Responsivity::respond("Tag deleted");
    }
}
