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
    //endregion

    //region Tags
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
    //endregion

    //region Entries
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
            // $user->karma = max(0, $user->karma - 1);
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
            self::CreateTag($tag);
            $tagID = (new \Models\Tag())->findone(['name=?', $tag])['_id'];
            array_push($tagStack, $tagID);
        }

        $tagStack = array_unique($tagStack);
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

    public function getSearchEntries(\Base $base)
    {
        $query = $base->get('GET.q') ?? $base->get('GET.s');
        if (!$query)
            return \lib\Responsivity::respond('Query parameter required', \lib\Responsivity::HTTP_Bad_Request);

        $keywords = array_map('trim', array_map('strtolower', preg_split('/[\s,;]+/', $query)));
        $keywords = array_filter($keywords);
        if (empty($keywords))
            return \lib\Responsivity::respond('No valid keywords provided', \lib\Responsivity::HTTP_Bad_Request);

        $nsfwFilter = intval($base->get('GET.safe') ?? 0); // Safe search

        $limit = intval($base->get('GET.limit') ?? 20);
        $limit = min($limit, 100);

        // Get scoring weights from config or use defaults
        $connectionWeight = floatval($base->get('GET.connection_weight') ?? 2.0);
        $karmaWeight = floatval($base->get('GET.karma_weight') ?? 0.1);
        $nameWeight = floatval($base->get('GET.name_weight') ?? 0.5);

        $results = $this->searchEntriesByKeywords($keywords, $limit, $connectionWeight, $karmaWeight, $nameWeight);
        $filteredResults = array_filter($results, function ($entry) use ($nsfwFilter) {
            if ($nsfwFilter == 1 && $entry['nsfw'] == $nsfwFilter)
                return false;

            return true;
        });


        \lib\Responsivity::respond([
            'query' => $query,
            'keywords' => $keywords,
            'total_results' => count($filteredResults),
            'results' => $filteredResults
        ]);
    }

    private function searchEntriesByKeywords($keywords, $limit, $connectionWeight, $karmaWeight, $nameWeight)
    {
        $tagModel = new \Models\Tag();
        $entryModel = new \Models\Entry();

        // Find all tag IDs that match our keywords
        $matchingTagIDs = [];
        $tagKeywordMap = [];

        foreach ($keywords as $keyword) {
            $tags = $tagModel->find(['LOWER(name) LIKE ?', "%$keyword%"]);
            if ($tags) {
                foreach ($tags as $tag) {
                    $tagID = $tag->_id;
                    $matchingTagIDs[] = $tagID;
                    if (!isset($tagKeywordMap[$tagID]))
                        $tagKeywordMap[$tagID] = [];
                    $tagKeywordMap[$tagID][] = $keyword;
                }
            }
        }

        $matchingTagIDs = array_unique($matchingTagIDs);
        if (empty($matchingTagIDs))
            return [];

        // Find all entries that have at least one matching tag
        $entries = $entryModel->find();
        if (!$entries)
            return [];

        $scoredResults = [];
        foreach ($entries as $entry) {
            $entryTags = $entry->tags;
            if (!$entryTags)
                continue;

            // Count connections (how many matching tags this entry has)
            $connectionCount = 0;
            $matchedKeywords = [];

            foreach ($entryTags as $entryTag) {
                $tagID = $entryTag->_id;
                if (in_array($tagID, $matchingTagIDs)) {
                    $connectionCount++;
                    // Track wich keywords were matched
                    if (isset($tagKeywordMap[$tagID]))
                        $matchedKeywords = array_merge($matchedKeywords, $tagKeywordMap[$tagID]);
                }
            }

            if ($connectionCount == 0) // Skip entries with no matching tags
                continue;

            $matchedKeywords = array_unique($matchedKeywords);

            $karma = $entry->getKarma(); // Calculate karma
            $score = ($connectionCount * $connectionWeight) + ($karma * $karmaWeight); // Calculate score

            // Additional name matching bonus
            $nameLower = strtolower($entry->name);
            $nameMatchCount = 0;
            foreach ($keywords as $keyword)
                if (strpos($nameLower, $keyword) !== false)
                    $nameMatchCount++;
            if ($nameMatchCount > 0)
                $score += $nameMatchCount * $nameWeight;

            // Prepare entry data
            $tags = [];
            foreach ($entry->tags as $tag)
                $tags[] = [
                    'name' => $tag->name,
                    'id' => $tag->_id,
                ];

            $base = \Base::instance();
            $user = VerifySessionToken($base);
            if ($user != false) {
                $model = new \Models\Vote();
                $userRating = $model->findone(["user=? AND entry=?", $user->id, $entry->_id])->vote_type ?? 0;
            }

            $scoredResults[] = [
                'id' => $entry->_id,
                'name' => $entry->name,
                'description' => $entry->description,
                'url' => $entry->url,
                'category' => [
                    'name' => $entry->category->name,
                    'id' => $entry->category->_id,
                ],
                'karma' => $karma,
                'karma-upvotes' => $entry->upvotes,
                'karma-downvotes' => $entry->downvotes,
                ...($user != false ? ['user_rating' => $userRating] : []),
                'author' => [
                    'username' => $entry->author->username,
                    'displayname' => $entry->author->displayname,
                ],
                'tags' => $tags,
                'nsfw' => $entry->is_nsfw,
                'created_at' => $entry->created_at,
                'score' => round($score, 2),
                'connection_count' => $connectionCount,
                'matched_keywords' => $matchedKeywords,
                'name_matches' => $nameMatchCount,
            ];
        }

        // Sort by score descending
        usort($scoredResults, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return array_slice($scoredResults, 0, $limit); // Limit results
    }

    public function postSearchEntriesAdvanced(\Base $base)
    {
        $query = $base->get('POST.query');
        if (!$query)
            return \lib\Responsivity::respond('Query required', \lib\Responsivity::HTTP_Bad_Request);

        $keywords = array_map('trim', array_map('strtolower', preg_split('/[\s,;]+/', $query)));
        $keywords = array_filter($keywords);
        if (empty($keywords))
            return \lib\Responsivity::respond('No valid keywords provided', \lib\Responsivity::HTTP_Bad_Request);

        // Optional filters
        $categoryFilter = strtolower($base->get('POST.category'));
        $nsfwFilter = $base->get('POST.nsfw'); // null => all; 0 => SFW only; 1 => NSFW only
        $minKarma = intval($base->get('POST.min_karma') ?? 0);
        $limit = intval($base->get('POST.limit') ?? 20);
        $limit = min($limit, 100);

        // Scoring weights
        $connectionWeight = floatval($base->get('POST.connection_weight') ?? 2.0);
        $karmaWeight = floatval($base->get('POST.karma_weight') ?? 0.1);
        $nameWeight = floatval($base->get('POST.name_weight') ?? 0.5);

        $results = $this->searchEntriesByKeywords($keywords, $limit * 3, $connectionWeight, $karmaWeight, $nameWeight);

        // Apply filters
        $filteredResults = array_filter($results, function ($entry) use ($categoryFilter, $nsfwFilter, $minKarma) {
            if ($categoryFilter && strtolower($entry['category']['name']) !== $categoryFilter)
                return false;

            if ($nsfwFilter !== null && $entry['nsfw'] != $nsfwFilter)
                return false;

            if ($entry['karma'] < $minKarma)
                return false;

            return true;
        });

        $filteredResults = array_values($filteredResults);
        $filteredResults = array_slice($filteredResults, 0, $limit);

        \lib\Responsivity::respond([
            'query' => $query,
            'keywords' => $keywords,
            'filters' => [
                'category' => $categoryFilter,
                'nsfw' => $nsfwFilter,
                'min_karma' => $minKarma,
            ],
            'total_results' => count($filteredResults),
            'results' => $filteredResults,
        ]);
    }
    //endregion
}
