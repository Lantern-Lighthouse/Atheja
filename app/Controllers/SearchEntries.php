<?php

namespace Controllers;

use Exception;
use lib\FavFet;
use lib\URLser;

class SearchEntries
{
    public function getSearchEntry(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('entry.read') == false)
            return \lib\Responsivity::respond('Unauthorized', \lib\Responsivity::HTTP_Unauthorized);

        $model = new \Models\Entry();
        $entry = $model->findone(['id=?', $base->get('PARAMS.entry')]);
        if (!$entry)
            return \lib\Responsivity::respond('Entry not found', \lib\Responsivity::HTTP_Not_Found);

        $tags = [];
        foreach ($entry->tags as $tag) {
            $tags[] = [
                'name' => $tag->name,
                'id' => $tag->_id,
            ];
        }

        if ($user != false) {
            $model = new \Models\Vote();
            $userRating = $model->findone(["user=? AND entry=?", $user->id, $base->get('PARAMS.entry')])->vote_type ?? 0;
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
            ...($user != false ? ['user_rating' => $userRating] : []),
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

        \lib\Responsivity::respond($cast);
    }

    public function postSearchEntryCreate(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $author = VerifySessionToken($base);
        $rbac->set_current_user($author);
        if ($rbac->has_permission('entry.create') == false)
            return \lib\Responsivity::respond('Unauthorized', \lib\Responsivity::HTTP_Unauthorized);

        $model = new \Models\Entry();

        // Name setting
        if ($base->get('POST.fetch-name-from-site')) {
            if (!$base->get('POST.page-url'))
                return \lib\Responsivity::respond('URL not found', \lib\Responsivity::HTTP_Not_Found);
            else if ($model->findone(['url=?', $base->get('POST.page-url')]))
                return \lib\Responsivity::respond('URL already found', \lib\Responsivity::HTTP_Bad_Request);

            $pgName = URLser::get_page_name($base->get('POST.page-url'));
            if ($pgName == false && !$base->get('POST.page-name'))
                return \lib\Responsivity::respond("Error getting page title. Please insert the name manually.", \lib\Responsivity::HTTP_Bad_Request);
            else
                $pgName = $base->get('POST.page-name');
        } else {
            if (!$base->get('POST.page-name'))
                return \lib\Responsivity::respond('Page name not found', \lib\Responsivity::HTTP_Not_Found);
            $pgName = $base->get('POST.page-name');
        }
        $model->name = $pgName;

        // Description setting
        $model->description = $base->get('POST.page-desc');

        // URL setting
        if (!$base->get('POST.page-url'))
            return \lib\Responsivity::respond('URL not found', \lib\Responsivity::HTTP_Not_Found);
        else if ($model->findone(['url=?', $base->get('POST.page-url')]))
            return \lib\Responsivity::respond('URL already found', \lib\Responsivity::HTTP_Bad_Request);
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
            \Controllers\SearchTags::CreateTag(trim($tag));
            $tagID = (new \Models\Tag())->findone(["name=?", trim($tag)])['_id'];
            array_push($tagsOut, $tagID);
        }

        foreach ($tagsIn as $tag) {
            if (empty($tag))
                continue;
            \Controllers\SearchTags::CreateTag(trim($tag));
            $tagID = (new \Models\Tag())->findone(['name=?', trim($tag)])['_id'];
            array_push($tagsOut, $tagID);
        }

        foreach (array_map("strtolower", URLser::parse_domain($base->get('POST.page-url'))) as $tag) {
            if (empty($tag))
                continue;
            \Controllers\SearchTags::CreateTag(trim($tag));
            $tagID = (new \Models\Tag())->findone(['name=?', trim($tag)])['_id'];
            array_push($tagsOut, $tagID);
        }

        $model->tags = array_values(array_unique($tagsOut));

        // Saving and feedback
        try {
            $model->save();

            if (!$model->get('is_nsfw'))
                $this->createAuthorUpvote($author, $model);
            \lib\Responsivity::respond('Entry added', \lib\Responsivity::HTTP_Created);
        } catch (Exception $e) {
            return \lib\Responsivity::respond($e->getMessage(), \lib\Responsivity::HTTP_Internal_Error);
        }

    }

    public function postSearchEntryEdit(\Base $base)
    {
        $model = new \Models\Entry();
        $entry = $model->findone(['id=?', $base->get('PARAMS.entry')]);
        if (!$entry)
            return \lib\Responsivity::respond('Entry not found', \lib\Responsivity::HTTP_Not_Found);

        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if (!\lib\RibbitGuard::require_ownership_or_admin($entry->author))
            return \lib\Responsivity::respond('Unauthorized', \lib\Responsivity::HTTP_Unauthorized);

        $entry->name = $base->get('POST.page-name') ?? $entry->name;
        $entry->description = $base->get('POST.page-desc') ?? $entry->description;
        $entry->url = $base->get('POST.page-url') ?? $entry->url;
        $entry->category = $base->get('POST.view-category') ?? $entry->category;
        $entry->favicon = $base->get('POST.favicon') ?? $entry->favicon;

        $entry->updated_at = date('Y-m-d H:i:s');

        // Saving and feedback
        try {
            $entry->save();
            \lib\Responsivity::respond('Entry edited');
        } catch (Exception $e) {
            return \lib\Responsivity::respond($e->getMessage(), \lib\Responsivity::HTTP_Internal_Error);
        }
    }

    public function postSearchEntryDelete(\Base $base)
    {
        $model = new \Models\Entry();
        $entry = $model->findone(['id=?', $base->get('PARAMS.entry')]);
        if (!$entry)
            return \lib\Responsivity::respond('Entry not found', \lib\Responsivity::HTTP_Not_Found);

        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if (!\lib\RibbitGuard::require_ownership_or_admin($entry->author))
            return \lib\Responsivity::respond('Unauthorized', \lib\Responsivity::HTTP_Unauthorized);

        if ($entry->erase())
            \lib\Responsivity::respond("Entry deleted");
    }

    public function postSearchEntryRate(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('entry.rate') == false)
            return \lib\Responsivity::respond('Unauthorized', \lib\Responsivity::HTTP_Unauthorized);

        $entryId = $base->get('PARAMS.entry');
        $voteType = $base->get('POST.vote_type'); // 1 for upvote, -1 for downvote

        if (!in_array($voteType, [1, -1]))
            return \lib\Responsivity::respond('Invalid vote type', \lib\Responsivity::HTTP_Bad_Request);

        $entryModel = new \Models\Entry();
        $entry = $entryModel->findone(['id=?', $entryId]);
        if (!$entry)
            return \lib\Responsivity::respond('Entry not found', \lib\Responsivity::HTTP_Not_Found);

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

            return \lib\Responsivity::respond([
                'message' => $message,
                'upvotes' => $entry->upvotes,
                'downvotes' => $entry->downvotes,
                'karma' => $entry->getKarma(),
                'user_vote' => $existingVote ? ($existingVote->dry() ? null : $existingVote->vote_type) : $voteType
            ]);
        } catch (Exception $e) {
            return \lib\Responsivity::respond($e->getMessage(), \lib\Responsivity::HTTP_Internal_Error);
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
            $user->karma--;
    }

    public function postSearchEntryTagPush(\Base $base)
    {
        $model = new \Models\Entry();
        $entry = $model->findone(['id=?', $base->get('PARAMS.entry')]);
        if (!$entry)
            return \lib\Responsivity::respond('Entry not found', \lib\Responsivity::HTTP_Not_Found);

        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if (!\lib\RibbitGuard::require_ownership_or_admin($entry->author))
            return \lib\Responsivity::respond('Unauthorized', \lib\Responsivity::HTTP_Unauthorized);

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
            \Controllers\SearchTags::CreateTag($tag);
            $tagID = (new \Models\Tag())->findone(['name=?', $tag])['_id'];
            array_push($tagStack, $tagID);
        }

        $tagStack = array_values(array_unique($tagStack));
        $entry->tags = $tagStack;

        try {
            $entry->updated_at = date('Y-m-d H:i:s');
            $entry->save();
            return \lib\Responsivity::respond('Tags pushed');
        } catch (Exception $e) {
            return \lib\Responsivity::respond($e->getMessage(), \lib\Responsivity::HTTP_Internal_Error);
        }
    }

    public function postSearchEntryTagPop(\Base $base)
    {
        $model = new \Models\Entry();
        $entry = $model->findone(['id=?', $base->get('PARAMS.entry')]);
        if (!$entry)
            return \lib\Responsivity::respond('Entry not found', \lib\Responsivity::HTTP_Not_Found);

        unset($model);

        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if (!\lib\RibbitGuard::require_ownership_or_admin($entry->author))
            return \lib\Responsivity::respond('Unauthorized', \lib\Responsivity::HTTP_Unauthorized);

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
            return \lib\Responsivity::respond('Tags popped');
        } catch (Exception $e) {
            return \lib\Responsivity::respond($e->getMessage(), \lib\Responsivity::HTTP_Internal_Error);
        }
    }

    public function getEntryReport (\Base $base) {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if ($rbac->has_permission('user.report') == false)
            return \lib\Responsivity::respond('Unauthorized', \lib\Responsivity::HTTP_Unauthorized);

        $reportModel = new \Models\Report();
        $entryModel = new \Models\Entry();
        
        $reported_entry = $entryModel->findone(['id=?', $base->get('PARAMS.entry')]);
        if(!$reported_entry)
            return \lib\Responsivity::respond("Entry not found", \lib\Responsivity::HTTP_Not_Found);

        $reportModel->reporter = $user;
        $reportModel->entry_reported = $reported_entry;
        $reportModel->reason = $base->get('POST.reason');
        
        try {
            $reportModel->save();
            return \lib\Responsivity::respond('Report created', \lib\Responsivity::HTTP_Created);
        } catch (Exception $e) {
            return \lib\Responsivity::respond('Failed to report', \lib\Responsivity::HTTP_Internal_Error);
        }
    }
}
