<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RecruitmentRequest;
use App\Models\User;
use App\Support\Emailer;
use Illuminate\Http\Request;

class EmailApiController extends Controller
{
    public function notify(Request $r)
    {
        $data = $r->validate([
            'to_user_id'  => 'nullable|uuid|exists:users,id',
            'to_email'    => 'nullable|email',
            'subject'     => 'required|string|max:200',
            'message'     => 'required|string',
            'action_text' => 'nullable|string|max:50',
            'action_url'  => 'nullable|url',
        ]);

        $to = $data['to_email'] ?? User::find($data['to_user_id'] ?? null);
        if (! $to) {
            return response()->json(['error' => 'Recipient not found'], 422);
        }

        Emailer::notify(
            to: $to,
            subject: $data['subject'],
            message: $data['message'],
            actionText: $data['action_text'] ?? null,
            actionUrl:  $data['action_url']  ?? null,
        );

        return response()->json(['queued' => true], 202);
    }

    public function approval(Request $r)
    {
        $data = $r->validate([
            'approver_user_id' => 'nullable|uuid|exists:users,id',
            'approver_email'   => 'nullable|email',
            'type'             => 'required|string',
            'recruitment_id'   => 'required|uuid|exists:recruitment_requests,id',
            'subject'          => 'required|string|max:200',
            'message'          => 'required|string',
            'context'          => 'array',
            'expires_in'       => 'nullable|integer|min:1',
        ]);

        $approver = $data['approver_email'] ?? User::find($data['approver_user_id'] ?? null);
        if (! $approver) {
            return response()->json(['error' => 'Approver not found'], 422);
        }

        $record = RecruitmentRequest::findOrFail($data['recruitment_id']);

        Emailer::approval(
            approver:     $approver,
            type:         $data['type'],
            approvable:   $record,
            subject:      $data['subject'],
            message:      $data['message'],
            context:      $data['context'] ?? [],
            recruitmentId: (string) $record->getKey(),
            userId:        is_string($approver) ? $approver : (string) (is_object($approver) ? $approver->getKey() : $approver),
            expiresIn:     $data['expires_in'] ?? null,
        );

        return response()->json(['queued' => true], 202);
    }
}
