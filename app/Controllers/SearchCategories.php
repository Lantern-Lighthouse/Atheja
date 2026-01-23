<?php

namespace Controllers;

use Exception;
use Responsivity\Responsivity;

class SearchCategories
{
    public function getSearchCategory(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('category.read') == false)
            return Responsivity::respond('Unauthorized', Responsivity::HTTP_Unauthorized);

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
        Responsivity::respond($cast);
    }

    public function postSearchCategoryCreate(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('category.create') == false)
            return Responsivity::respond('Unauthorized', Responsivity::HTTP_Unauthorized);

        $model = new \Models\Category();
        $model->name = $base->get('POST.name');
        $model->type = $base->get('POST.type');
        $model->icon = $base->get('POST.icon');

        try {
            $model->save();
        } catch (Exception $e) {
            return Responsivity::respond($e->getMessage(), Responsivity::HTTP_Internal_Error);
        }

        Responsivity::respond("Category created", Responsivity::HTTP_Created);
    }

    public function postSearchCategoryEdit(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('category.update') == false)
            return Responsivity::respond('Unauthorized', Responsivity::HTTP_Unauthorized);

        $model = new \Models\Category();
        $entry = $model->findone(['name=?', $base->get('PARAMS.category')]);
        if (!$entry)
            return Responsivity::respond('Category not found', Responsivity::HTTP_Not_Found);

        $entry->name = $base->get('POST.name') ?? $entry->name;
        $entry->type = $base->get('POST.type') ?? $entry->type;
        $entry->icon = $base->get('POST.icon') ?? $entry->icon;

        try {
            $entry->save();
        } catch (Exception $e) {
            return Responsivity::respond($e->getMessage(), Responsivity::HTTP_Internal_Error);
        }

        Responsivity::respond("Category edited");
    }

    public function postSearchCategoryDelete(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('category.delete') == false)
            return Responsivity::respond('Unauthorized', Responsivity::HTTP_Unauthorized);

        $model = new \Models\Category();
        $entry = $model->findone(['name=?', $base->get('PARAMS.category')]);
        if (!$entry)
            return Responsivity::respond('Category not found', Responsivity::HTTP_Not_Found);

        try {
            $entry->erase();
        } catch (Exception $e) {
            return Responsivity::respond('Unable to delete category', Responsivity::HTTP_Internal_Error);
        }

        Responsivity::respond("Category deleted", Responsivity::HTTP_OK);
    }
}
