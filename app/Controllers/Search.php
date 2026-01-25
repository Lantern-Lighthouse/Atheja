<?php

namespace Controllers;

use Responsivity\Responsivity;

class Search
{
    public function getSearchEntries(\Base $base)
    {
        $query = $base->get('GET.q') ?? $base->get('GET.s');
        if (!$query)
            return Responsivity::respond('Query parameter required', Responsivity::HTTP_Bad_Request);

        $keywords = array_map('trim', array_map('strtolower', preg_split('/[\s,;]+/', $query)));
        $keywords = array_filter($keywords);
        if (empty($keywords))
            return Responsivity::respond('No valid keywords provided', Responsivity::HTTP_Bad_Request);

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


        Responsivity::respond([
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
                    ...(isset($entry->author->username) ? ['username' => $entry->author->username] : ['username' => null]),
                    ...(isset($entry->author->displayname) ? ['displayname' => $entry->author->displayname] : ['displayname' => "Deleted User"]),
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
            return Responsivity::respond('Query required', Responsivity::HTTP_Bad_Request);

        $keywords = array_map('trim', array_map('strtolower', preg_split('/[\s,;]+/', $query)));
        $keywords = array_filter($keywords);
        if (empty($keywords))
            return Responsivity::respond('No valid keywords provided', Responsivity::HTTP_Bad_Request);

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

        Responsivity::respond([
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
}
