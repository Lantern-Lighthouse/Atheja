<?php

namespace Controllers;

use Exception;

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
            'assigned_to' => $report->resolver ? $report->resolver->username : null,
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
                $filter = ["resolver>0"];
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
                'assigned_to' => $report->resolver ? $report->resolver->username : null,
                'is_resolved' => $report->resolved,
            ];
        }

        return \lib\Responsivity::respond($response);
    }

    public function postReportAssign(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if (!$rbac->has_role('moderator') && !$rbac->has_role('admin'))
            return \lib\Responsivity::respond('Unauthorized', \lib\Responsivity::HTTP_Unauthorized);

        $model = new \Models\Report();
        $report = $model->findone(['id=?', $base->get('PARAMS.reportID')]);
        if (!$report)
            return \lib\Responsivity::respond('No report found', \lib\Responsivity::HTTP_Not_Found);
        unset($model);

        $model = new \Models\User();
        $resolver = $model->findone(["username=?", $base->get('POST.username')]);
        if (!$resolver)
            return \lib\Responsivity::respond('User not found', \lib\Responsivity::HTTP_Not_Found);
        unset($model);

        unset($rbac);
        $rbac = \lib\RibbitCore::get_instance($base);
        $rbac->set_current_user($resolver);
        if ($rbac->has_role('moderator') || $rbac->has_role('admin'))
            $report->resolver = $resolver;
        else
            return \lib\Responsivity::respond('Cannot assign this user', \lib\Responsivity::HTTP_Unauthorized);

        try {
            $report->updated_at = date('Y-m-d H:i:s');
            $report->save();
            return \lib\Responsivity::respond($resolver->username . " assigned to case " . $report->id);
        } catch (Exception $e) {
            return \lib\Responsivity::respond("Failed to assign resolver", \lib\Responsivity::HTTP_Internal_Error);
        }
    }

    public function postReportResolve(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);

        $model = new \Models\Report();
        $report = $model->findone(['id=?', $base->get('PARAMS.reportID')]);
        if (!$report)
            return \lib\Responsivity::respond('No report found', \lib\Responsivity::HTTP_Not_Found);
        unset($model);

        if ((!$report->resolver || $report->resolver->id !== $user->id) && !$rbac->has_role('admin'))
            return \lib\Responsivity::respond('Unauthorized', \lib\Responsivity::HTTP_Unauthorized);

        $resolved = boolval($base->get('POST.state')) ?? 0;
        $report->resolved = $resolved;
        $report->resolution = $base->get('POST.resolution');

        try {
            $report->updated_at = date('Y-m-d H:i:s');
            $report->save();
            return \lib\Responsivity::respond($report->id . " changed to state " . $resolved);
        } catch (Exception $e) {
            return \lib\Responsivity::respond("Failed to change state", \lib\Responsivity::HTTP_Internal_Error);
        }
    }
}