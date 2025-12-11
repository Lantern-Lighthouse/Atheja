<?php

namespace Controllers;

class Report
{
    public function getReport(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if (!$rbac->has_role('moderator') && !$rbac->has_role('admin'))
            return \lib\Responsivity::respond('Unauthorized', \lib\Responsivity::HTTP_Unauthorized);

        $model = new \Models\Report();
        $report = $model->findone(['id=?', $base->get('PARAMS.reportID')]);
        if (!$report)
            return \lib\Responsivity::respond('No report found', \lib\Responsivity::HTTP_No_Content);
        unset($model);

        $response = [
            'reporter' => $report->reporter->username,
            ...(isset($report->user_reported) ? ['reported' => $report->user_reported->username] : []),
            ...(isset($report->entry_reported) ? [
                'reported' =>
                    [
                        'page_name' => $report->entry_reported->name,
                        'page_description' => $report->entry_reported->description,
                        'page_url' => $report->entry_reported->url,
                        'entry_author' => $report->entry_reported->author->username,
                        'is_nsfw' => $report->entry_reported->is_nsfw
                    ],
            ] : []),
            'reason' => $report->reason,
            'reported_at' => $report->created_at,
            'last_update_at' => $report->updated_at,
            'assigned_to' => $report->resolver,
            'is_resolved' => $report->resolved,
        ];

        return \lib\Responsivity::respond($response);
    }

    public function getReports(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if (!$rbac->has_role('moderator') && !$rbac->has_role('admin'))
            return \lib\Responsivity::respond('Unauthorized', \lib\Responsivity::HTTP_Unauthorized);

        $model = new \Models\Report();
        switch ($base->get('GET.filter')) {
            case "open":
            default:
                $filter = ["resolved=0"];
                break;
            case "closed":
                $filter = ["resolved=1"];
                break;
            case "assigned":
                $filter = ["resolved=1"];
                break;
            case "unassigned":
                $filter = ["resolver=?", NULL];
                break;
        }
        $reports = $model->find($filter);
        if (!$reports)
            return \lib\Responsivity::respond('No report found', \lib\Responsivity::HTTP_No_Content);
        unset($model);
        $response = [];

        foreach ($reports as $report) {

            $response[$report->id] = [
                'reporter' => $report->reporter->username,
                ...(isset($report->user_reported) ? ['reported' => $report->user_reported->username] : []),
                ...(isset($report->entry_reported) ? [
                    'reported' =>
                        [
                            'page_name' => $report->entry_reported->name,
                            'page_description' => $report->entry_reported->description,
                            'page_url' => $report->entry_reported->url,
                            'entry_author' => $report->entry_reported->author->username,
                            'is_nsfw' => $report->entry_reported->is_nsfw
                        ],
                ] : []),
                'reason' => $report->reason,
                'reported_at' => $report->created_at,
                'last_update_at' => $report->updated_at,
                'assigned_to' => $report->resolver,
                'is_resolved' => $report->resolved,
            ];
        }

        return \lib\Responsivity::respond($response);
    }
}