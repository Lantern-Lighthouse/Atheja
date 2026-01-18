<?php

namespace Controllers;

use Exception;

class SearchCategories
{
    public function getSearchCategory(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('category.read') == false)
            return \lib\Responsivity::respond('Unauthorized', \lib\Responsivity::HTTP_Unauthorized);

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
        \lib\Responsivity::respond($cast);
    }

    public function postSearchCategoryCreate(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('category.create') == false)
            return \lib\Responsivity::respond('Unauthorized', \lib\Responsivity::HTTP_Unauthorized);

        $model = new \Models\Category();
        $model->name = $base->get('POST.name');
        $model->type = $base->get('POST.type');
        $model->icon = $base->get('POST.icon');

        try {
            $model->save();
        } catch (Exception $e) {
            return \lib\Responsivity::respond($e->getMessage(), \lib\Responsivity::HTTP_Internal_Error);
        }

        \lib\Responsivity::respond("Category created", \lib\Responsivity::HTTP_Created);
    }

    public function postSearchCategoryEdit(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('category.update') == false)
            return \lib\Responsivity::respond('Unauthorized', \lib\Responsivity::HTTP_Unauthorized);

        $model = new \Models\Category();
        $entry = $model->findone(['name=?', $base->get('PARAMS.category')]);
        if (!$entry)
            return \lib\Responsivity::respond('Category not found', \lib\Responsivity::HTTP_Not_Found);

        $entry->name = $base->get('POST.name') ?? $entry->name;
        $entry->type = $base->get('POST.type') ?? $entry->type;
        $entry->icon = $base->get('POST.icon') ?? $entry->icon;

        try {
            $entry->save();
        } catch (Exception $e) {
            return \lib\Responsivity::respond($e->getMessage(), \lib\Responsivity::HTTP_Internal_Error);
        }

        \lib\Responsivity::respond("Category edited");
    }

    public function postSearchCategoryDelete(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('category.delete') == false)
            return \lib\Responsivity::respond('Unauthorized', \lib\Responsivity::HTTP_Unauthorized);

        $model = new \Models\Category();
        $entry = $model->findone(['name=?', $base->get('PARAMS.category')]);
        if (!$entry)
            return \lib\Responsivity::respond('Category not found', \lib\Responsivity::HTTP_Not_Found);

        try {
            $entry->erase();
        } catch (Exception $e) {
            return \lib\Responsivity::respond('Unable to delete category', \lib\Responsivity::HTTP_Internal_Error);
        }

        \lib\Responsivity::respond("Category deleted", \lib\Responsivity::HTTP_OK);
    }
}
