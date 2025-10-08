<?php

namespace Controllers;

use Exception;
use lib\FavFet;
use lib\URLser;

class Search
{
    //region Categories
    public function getSearchCategory(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('category.read') == false)
            return JSON_response('Unauthorized', 401);

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
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('category.create') == false)
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
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('category.update') == false)
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
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('category.delete') == false)
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
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('tag.read') == false)
            return JSON_response('Unauthorized', 401);

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
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('tag.read') == false)
            return JSON_response('Unauthorized', 401);

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
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('tag.create') == false)
            return JSON_response('Unauthorized', 401);

        if (self::CreateTag($base->get('POST.tagname')))
            return JSON_response('Tag added', 201);
        else
            return JSON_response('Tag already exists', 409);
    }

    public function postSearchTagEdit(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('tag.update') == false)
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
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('tag.delete') == false)
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
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('entry.read') == false)
            return JSON_response('Unauthorized', 401);

        $model = new \Models\Entry();
        $entry = $model->findone(['id=?', $base->get('PARAMS.entry')]);
        if (!$entry)
            return JSON_response('Entry not found', 404);

        $tags = [];
        foreach ($entry->tags as $tag) {
            $tags[] = [
                'name' => $tag->name,
                'id' => $tag->_id,
            ];
        }

        $cast = [
            'name' => $entry->name,
            'description' => $entry->description,
            'url' => $entry->url,
            'category' => [
                'name' => $entry->category->name,
                'id' => $entry->category->_id,
            ],
            ...($base->get('GET.show_favicon') ? ['favicon' => $entry->favicon] : []),
            'karma' => $entry->getKarma(),
            'author' => [
                'username' => $entry->author->username,
                'displayname' => $entry->author->displayname,
                'karma' => $entry->author->karma,
                'account_created' => $entry->author->account_created,
            ],
            'nsfw' => $entry->is_nsfw,
            'tags' => $tags,
            'post_created' => $entry->created_at,
            'post_updated' => $entry->updated_at,
            'id' => $entry->_id,
        ];

        JSON_response($cast);
    }

    public function postSearchEntryCreate(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $author = VerifySessionToken($base);
        $rbac->set_current_user($author);
        if ($rbac->has_permission('entry.create') == false)
            return JSON_response('Unauthorized', 401);

        $model = new \Models\Entry();

        // Name setting
        if ($base->get('POST.fetch-name-from-site')) {
            if (!$base->get('POST.page-url'))
                return JSON_response('URL not found', 404);
            else if ($model->findone(['url=?', $base->get('POST.page-url')]))
                return JSON_response('URL already found', 409);

            $pgName = URLser::get_page_name($base->get('POST.page-url'));
            if ($pgName == false && !$base->get('POST.page-name'))
                return JSON_response("Error getting page title. Please insert the name manually.", 500);
            else
                $pgName = $base->get('POST.page-name');
        } else {
            if (!$base->get('POST.page-name'))
                return JSON_response('Page name not found', 404);
            $pgName = $base->get('POST.page-name');
        }
        $model->name = $pgName;

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
        $model->favicon = FavFet::get_favicon_as_base64($base->get('POST.page-url'));

        // Rating setting
        $model->is_nsfw = $base->get('POST.is-nsfw') ?? $base->get('ATH.ASSUME_UNKNOWN_AS_NSFW');

        // Karma setting
        $model->upvotes = intval(!$model->get('is_nsfw'));
        $model->downvotes = 0;

        // Author setting
        $model->author = $author;

        // Tags setting
        $tagsIn = explode(';', strtolower($base->get('POST.tags')));
        $tagsOut = array();

        foreach (array_map("strtolower", URLser::extractTextPartsUnique($pgName)) as $tag) {
            if (empty($tag))
                continue;
            self::CreateTag(trim($tag));
            $tagID = (new \Models\Tag())->findone(["name=?", trim($tag)])['_id'];
            array_push($tagsOut, $tagID);
        }

        foreach ($tagsIn as $tag) {
            if (empty($tag))
                continue;
            self::CreateTag(trim($tag));
            $tagID = (new \Models\Tag())->findone(['name=?', trim($tag)])['_id'];
            array_push($tagsOut, $tagID);
        }

        foreach (array_map("strtolower", URLser::parse_domain($base->get('POST.page-url'))) as $tag) {
            if (empty($tag))
                continue;
            self::CreateTag(trim($tag));
            $tagID = (new \Models\Tag())->findone(['name=?', trim($tag)])['_id'];
            array_push($tagsOut, $tagID);
        }

        $model->tags = array_unique($tagsOut);

        // Saving and feedback
        try {
            $model->save();

            if (!$model->get('is_nsfw'))
                $this->createAuthorUpvote($author, $model);
            JSON_response('Entry added', 201);
        } catch (Exception $e) {
            return JSON_response($e->getMessage(), 500);
        }

    }

    public function postSearchEntryEdit(\Base $base)
    {
        $model = new \Models\Entry();
        $entry = $model->findone(['id=?', $base->get('PARAMS.entry')]);
        if (!$entry)
            return JSON_response('Entry not found', 404);

        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if (!\lib\RibbitGuard::require_ownership_or_admin($entry->author))
            return JSON_response('Unauthorized', 401);

        $entry->name = $base->get('POST.page-name') ?? $entry->name;
        $entry->description = $base->get('POST.page-desc') ?? $entry->description;
        $entry->url = $base->get('POST.page-url') ?? $entry->url;
        $entry->category = $base->get('POST.view-category') ?? $entry->category;
        $entry->favicon = $base->get('POST.favicon') ?? $entry->favicon;

        $entry->updated_at = date('Y-m-d H:i:s');

        // Saving and feedback
        try {
            $entry->save();
            JSON_response('Entry edited');
        } catch (Exception $e) {
            return JSON_response($e->getMessage(), 500);
        }
    }

    public function postSearchEntryDelete(\Base $base)
    {
        $model = new \Models\Entry();
        $entry = $model->findone(['id=?', $base->get('PARAMS.entry')]);
        if (!$entry)
            return JSON_response('Entry not found', 404);

        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if (!\lib\RibbitGuard::require_ownership_or_admin($entry->author))
            return JSON_response('Unauthorized', 401);

        if ($entry->erase())
            JSON_response(null, 204);
    }

    public function postSearchEntryRate(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('entry.rate') == false)
            return JSON_response('Unauthorized', 401);

        $entryId = $base->get('PARAMS.entry');
        $voteType = $base->get('POST.vote_type'); // 1 for upvote, -1 for downvote

        if (!in_array($voteType, [1, -1]))
            return JSON_response('Invalid vote type', 400);

        $entryModel = new \Models\Entry();
        $entry = $entryModel->findone(['id=?', $entryId]);
        if (!$entry)
            return JSON_response('Entry not found', 404);

        $entryAuthor = $entry->author;

        $voteModel = new \Models\Vote();
        $existingVote = $voteModel->findone([
            'user=? AND entry=?',
            $user->id,
            $entryId
        ]);

        try {
            if ($existingVote) {
                if ($existingVote->vote_type == $voteType) { // Same vote -> remove vote
                    $this->updateEntryVoteCounts($entry, $existingVote->vote_type, 0);
                    $this->updateUserKarma($entryAuthor, $existingVote->vote_type, 0);
                    $existingVote->erase();
                    $message = 'Vote removed';
                } else { // Different vote -> change vote
                    $this->updateEntryVoteCounts($entry, $existingVote->vote_type, $voteType);
                    $this->updateUserKarma($entryAuthor, $existingVote->vote_type, $voteType);
                    $existingVote->vote_type = $voteType;
                    $existingVote->updated_at = date('Y-m-d H:i:s');
                    $existingVote->save();
                    $message = 'Vote updated';
                }
            } else { // New vote
                $voteModel->user = $user;
                $voteModel->entry = $entry;
                $voteModel->vote_type = $voteType;
                $voteModel->save();

                $this->updateEntryVoteCounts($entry, 0, $voteType);
                $this->updateUserKarma($entryAuthor, 0, $voteType);
                $message = 'Vote added';
            }

            $entry->save();
            $entryAuthor->save();

            return JSON_response([
                'message' => $message,
                'upvotes' => $entry->upvotes,
                'downvotes' => $entry->downvotes,
                'karma' => $entry->getKarma(),
                'user_vote' => $existingVote ? ($existingVote->dry() ? null : $existingVote->vote_type) : $voteType
            ]);
        } catch (Exception $e) {
            return JSON_response($e->getMessage(), 500);
        }
    }

    private function updateEntryVoteCounts($entry, $oldVote, $newVote)
    {
        // Remove old value count
        if ($oldVote == 1)
            $entry->upvotes = max(0, $entry->upvotes - 1);
        elseif ($oldVote == -1)
            $entry->downvotes = max(0, $entry->downvotes - 1);

        if ($newVote == 1)
            $entry->upvotes++;
        elseif ($newVote == -1)
            $entry->downvotes++;
    }

    private function createAuthorUpvote($user, $entry)
    {
        try {
            $model = new \Models\Vote();
            $model->user = $user;
            $model->entry = $entry;
            $model->vote_type = 1;
            $model->save();

            $user->karma += 1;
            $user->save();

            return true;
        } catch (Exception $e) {
            error_log("Failed to create author upvote: " . $e->getMessage());
            return false;
        }
    }

    private function updateUserKarma($user, $oldVote, $newVote)
    {
        if ($oldVote == 1)
            $user->karma = max(0, $user->karma - 1);
        elseif ($oldVote == -1)
            $user->karma++;

        if ($newVote == 1)
            $user->karma++;
        elseif ($newVote == -1)
            // $user->karma = max(0, $user->karma - 1);
            $user->karma--;
    }

    public function postSearchEntryTagPush(\Base $base)
    {
        $model = new \Models\Entry();
        $entry = $model->findone(['id=?', $base->get('PARAMS.entry')]);
        if (!$entry)
            return JSON_response('Entry not found', 404);

        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if (!\lib\RibbitGuard::require_ownership_or_admin($entry->author))
            return JSON_response('Unauthorized', 401);

        // loading current tags to a stack
        $tagStack = [];
        foreach ($entry->tags as $tag)
            $tagStack[] = $tag->_id;

        // Pushing new tags
        $tagsToPush = explode(';', strtolower($base->get('POST.tags')));
        foreach ($tagsToPush as $tag) {
            if (empty($tag))
                continue;
            $tag = trim($tag);
            self::CreateTag($tag);
            $tagID = (new \Models\Tag())->findone(['name=?', $tag])['_id'];
            array_push($tagStack, $tagID);
        }

        $tagStack = array_unique($tagStack);
        $entry->tags = $tagStack;

        try {
            $entry->updated_at = date('Y-m-d H:i:s');
            $entry->save();
            return JSON_response('Tags pushed');
        } catch (Exception $e) {
            return JSON_response($e->getMessage(), 500);
        }
    }

    public function postSearchEntryTagPop(\Base $base)
    {
        $model = new \Models\Entry();
        $entry = $model->findone(['id=?', $base->get('PARAMS.entry')]);
        if (!$entry)
            return JSON_response('Entry not found', 404);

        unset($model);

        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if (!\lib\RibbitGuard::require_ownership_or_admin($entry->author))
            return JSON_response('Unauthorized', 401);

        // loading current tags to a stack
        $tagStack = [];
        foreach ($entry->tags as $tag)
            $tagStack[] = $tag->_id;

        // Popping selected tags
        $tagsToPop = explode(';', strtolower($base->get('POST.tags')));
        foreach ($tagsToPop as $tag) {
            if (empty($tag))
                continue;
            $tag = trim($tag);
            $model = (new \Models\Tag())->findone(['name=?', $tag]);
            if ($model)
                $tagID = $model['_id'];
            else
                continue;
            $searchResult = array_search($tagID, $tagStack);
            array_splice($tagStack, $searchResult, 1);
        }

        $tagStack = array_unique($tagStack);
        $entry->tags = $tagStack;

        try {
            $entry->updated_at = date('Y-m-d H:i:s');
            $entry->save();
            return JSON_response('Tags popped');
        } catch (Exception $e) {
            return JSON_response($e->getMessage(), 500);
        }
    }
    //endregion
}
