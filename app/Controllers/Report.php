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
                $filterParameter = null;
                $filter = ["resolver=?", $filterParameter];
                break;
            case "assigned_to":
                $filterParameter = (new \Models\User())->findone(['username=?', $base->get('GET.filter_parameter')]);
                if ($filterParameter)
                    $filter = ["resolver=?", $filterParameter->id];
                else
                    $filter = ["resolver=?", null];
                break;
            case "reported_by":
                $filterParameter = (new \Models\User())->findone(['username=?', $base->get('GET.filter_parameter')]);
                if ($filterParameter)
                    $filter = ["reporter=?", $filterParameter->id];
                else
                    $filter = ["reporter=?", null];
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
            if ($report->user_reported != null)
                $this->sendMail([$report->reporter->email, $report->user_reported->email], "Moderator was assigned to case #" . $report->id, "Moderator " . $resolver->username . " was assigned to case.");
            else if ($report->entry_reported != null)
                $this->sendMail([$report->reporter->email, $report->entry_reported->author->email], "Moderator was assigned to case #" . $report->id, "Moderator " . $resolver->username . " was assigned to case.");
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

        if ((!$report->resolver || $report->resolver->id !== $user) && !$rbac->has_role('admin'))
            return \lib\Responsivity::respond('Unauthorized', \lib\Responsivity::HTTP_Unauthorized);

        $resolved = boolval($base->get('POST.state')) ?? 0;
        $report->resolved = $resolved;

        $resolution = $base->get('POST.resolution');
        if (!$resolution)
            return \lib\Responsivity::respond('Missing resolution text', \lib\Responsivity::HTTP_Not_Acceptable);
        $report->resolution = $resolution;

        try {
            $report->updated_at = date('Y-m-d H:i:s');
            $report->save();
            return \lib\Responsivity::respond($report->id . " changed to state " . $resolved);
        } catch (Exception $e) {
            return \lib\Responsivity::respond("Failed to change state", \lib\Responsivity::HTTP_Internal_Error);
        }
    }

    public function postReportUnassign(\Base $base)
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

        if (!$report->resolver)
            return \lib\Responsivity::respond('Can\'t unassign unassignable', \lib\Responsivity::HTTP_Bad_Request);
        $oldResolverUsername = $report->resolver->username;

        $report->resolver = null;

        try {
            $report->updated_at = date('Y-m-d H:i:s');
            $report->save();
            return \lib\Responsivity::respond($oldResolverUsername . " unassigned from case " . $report->id);
        } catch (Exception $e) {
            return \lib\Responsivity::respond("Failed to assign resolver", \lib\Responsivity::HTTP_Internal_Error);
        }
    }

    public function postReportBulkAssign(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);
        if (!$rbac->has_role('moderator') && !$rbac->has_role('admin'))
            return \lib\Responsivity::respond('Unauthorized', \lib\Responsivity::HTTP_Unauthorized);

        $model = new \Models\User();
        $resolver = $model->findone(["username=?", $base->get('POST.username')]);
        if (!$resolver)
            return \lib\Responsivity::respond('User not found', \lib\Responsivity::HTTP_Not_Found);
        unset($model);

        $reportIDs = array_values(array_unique(array_filter(explode(';', $base->get('POST.reports')))));

        foreach ($reportIDs as $reportID) {
            $model = new \Models\Report();
            $report = $model->findone(['id=?', intval($reportID)]);
            if (!$report)
                return \lib\Responsivity::respond('No report ' . $reportID . ' found', \lib\Responsivity::HTTP_Not_Found);
            $rbac = \lib\RibbitCore::get_instance($base);
            $rbac->set_current_user($resolver);
            if ($rbac->has_role('moderator') || $rbac->has_role('admin'))
                $report->resolver = $resolver;
            else
                return \lib\Responsivity::respond('Cannot assign this user', \lib\Responsivity::HTTP_Unauthorized);
            try {
                $report->updated_at = date('Y-m-d H:i:s');
                $report->save();
            } catch (Exception $e) {
                return \lib\Responsivity::respond("Failed to assign resolver on case" . $reportID, \lib\Responsivity::HTTP_Internal_Error);
            }
        }

        return \lib\Responsivity::respond($resolver->username . " assigned to cases " . implode(';', $reportIDs));
    }

    public function postReportBulkResolve(\Base $base)
    {
        $rbac = \lib\RibbitCore::get_instance($base);
        $user = VerifySessionToken($base);
        $rbac->set_current_user($user);

        $reportIDs = array_values(array_unique(array_filter(explode(';', $base->get('POST.reports')))));
        $resolved = boolval($base->get('POST.state')) ?? 0;
        $resolution = $base->get('POST.resolution');
        if (!$resolution)
            return \lib\Responsivity::respond('Missing resolution text', \lib\Responsivity::HTTP_Not_Acceptable);

        foreach ($reportIDs as $reportID) {
            $model = new \Models\Report();
            $report = $model->findone(['id=?', $reportID]);
            if (!$report)
                return \lib\Responsivity::respond('No report found', \lib\Responsivity::HTTP_Not_Found);
            unset($model);

            if ((!$report->resolver || $report->resolver->id !== $user->id) && !$rbac->has_role('admin'))
                return \lib\Responsivity::respond('Unauthorized', \lib\Responsivity::HTTP_Unauthorized);

            $report->resolved = $resolved;
            $report->resolution = $resolution;

            try {
                $report->updated_at = date('Y-m-d H:i:s');
                $report->save();
            } catch (Exception $e) {
                return \lib\Responsivity::respond("Failed to change state on case" . $reportID, \lib\Responsivity::HTTP_Internal_Error);
            }
        }
        return \lib\Responsivity::respond(implode(';', $reportIDs) . " changed to state " . $resolved);
    }

    public function postReportEdit(\Base $base)
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

        if ((!$report->resolver || $report->resolver->id !== $user) && !$rbac->has_role('admin'))
            return \lib\Responsivity::respond('Unauthorized', \lib\Responsivity::HTTP_Unauthorized);

        $report->reason = $base->get('POST.reason') ?? $report->reason;
        $report->resolution = $base->get('POST.resolution') ?? $report->resolution;

        try {
            $report->updated_at = date('Y-m-d H:i:s');
            $report->save();
            return \lib\Responsivity::respond("Edited case " . $report->id);
        } catch (Exception $e) {
            return \lib\Responsivity::respond("Failed to edit case", \lib\Responsivity::HTTP_Internal_Error);
        }
    }

    private function sendMail(array $mailTo, string $subject, string $content)
    {
        $mailTo = array_filter($mailTo, function ($email) {
            return !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL);
        });

        if (empty($mailTo))
            throw new Exception("No valid email address provided");

        $mail = new \Mailer();

        $primaryEmail = array_shift($mailTo);

        $mail->addTo($primaryEmail);
        foreach ($mailTo as $mailAddr) {
            $mail->addCc($mailAddr);
        }

        $mail->setHTML($content);
        $mail->send($subject);
    }
}