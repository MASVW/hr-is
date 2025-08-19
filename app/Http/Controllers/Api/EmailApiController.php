<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Emailer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class EmailApiController extends Controller
{
    /**
     * POST /api/email/notify
     * Body:
     * {
     *   "to": "a@b.com" | ["a@b.com","c@d.com"],            // opsional jika pakai to_user_ids
     *   "to_user_ids": [1,2,3],                             // opsional jika pakai to
     *   "subject": "string",
     *   "message": "string",
     *   "actionText": "string|null",
     *   "actionUrl": "https://...|null"
     * }
     */
    public function notify(Request $request)
    {
        $data = $request->validate([
            'to'           => ['sometimes'],
            'to_user_ids'  => ['sometimes','array'],
            'to_user_ids.*'=> ['integer','exists:users,id'],
            'subject'      => ['required','string','max:200'],
            'message'      => ['required','string'],
            'actionText'   => ['nullable','string','max:100'],
            'actionUrl'    => ['nullable','url'],
        ]);

        $recipients = $this->resolveRecipients($data, 'to', 'to_user_ids');
        if (empty($recipients)) {
            return response()->json(['ok' => false, 'error' => 'No valid recipients'], 422);
        }

        $queued = 0;
        foreach ($recipients as $to) {
            try {
                Emailer::notify(
                    to: $to,
                    subject: $data['subject'],
                    message: $data['message'],
                    actionText: $data['actionText'] ?? null,
                    actionUrl: $data['actionUrl'] ?? null,
                );
                $queued++;
            } catch (TransportExceptionInterface $e) {
                Log::warning("Mail queue failed to {$this->maskRecipient($to)}: {$e->getMessage()}");
            }
        }

        return response()->json(['ok' => true, 'queued' => $queued]);
    }

    /**
     * POST /api/email/approval
     * Body:
     * {
     *   "approver": "a@b.com" | ["a@b.com"]                  // opsional jika pakai approver_user_ids
     *   "approver_user_ids": [1,2],                          // opsional jika pakai approver
     *   "type": "recruitment",                               // tag/kategori internal kamu
     *   "approvable_type": "App\\Models\\RecruitmentRequest",// FQCN model yang disetujui
     *   "approvable_id": 123,                                // id dari model tsb
     *   "subject": "string",
     *   "message": "string",
     *   "context": { ... },                                  // bebas; dipakai di view ApprovalMail
     *   "recruitmentId": "REQ-2025-0001",                    // untuk signed URL
     *   "userId": "42",                                      // untuk signed URL
     *   "expiresIn": 60                                      // menit; opsional
     * }
     */
    public function approval(Request $request)
    {
        $data = $request->validate([
            'approver'           => ['sometimes'],
            'approver_user_ids'  => ['sometimes','array'],
            'approver_user_ids.*'=> ['integer','exists:users,id'],

            'type'               => ['required','string','max:100'],

            'approvable_type'    => ['required','string','max:255'],
            'approvable_id'      => ['required'],

            'subject'            => ['required','string','max:200'],
            'message'            => ['required','string'],

            'context'            => ['sometimes','array'],

            'recruitmentId'      => ['required','string','max:100'],
            'userId'             => ['required','string','max:100'],

            'expiresIn'          => ['nullable','integer','min:1','max:1440'],
        ]);

        $approvers = $this->resolveRecipients($data, 'approver', 'approver_user_ids');
        if (empty($approvers)) {
            return response()->json(['ok' => false, 'error' => 'No approver specified'], 422);
        }

        $approvable = $this->resolveApprovable($data['approvable_type'], $data['approvable_id']);

        $queued = 0;
        foreach ($approvers as $to) {
            try {
                Emailer::approval(
                    approver:    $to,
                    type:        $data['type'],
                    approvable:  $approvable,
                    subject:     $data['subject'],
                    message:     $data['message'],
                    context:     $data['context'] ?? [],
                    recruitmentId: $data['recruitmentId'],
                    userId:        $data['userId'],
                    expiresIn:     $data['expiresIn'] ?? null,
                );
                $queued++;
            } catch (TransportExceptionInterface $e) {
                Log::warning("Approval mail failed to {$this->maskRecipient($to)}: {$e->getMessage()}");
            }
        }

        return response()->json(['ok' => true, 'queued' => $queued]);
    }

    /** ----------------- helpers ----------------- */

    /**
     * @return array<int, string|\App\Models\User>
     */
    private function resolveRecipients(array $data, string $emailKey, string $userIdsKey): array
    {
        $emails = [];
        if (array_key_exists($emailKey, $data)) {
            $emails = Arr::wrap($data[$emailKey]);
            $emails = array_values(array_filter($emails, fn ($e) => is_string($e) && filter_var($e, FILTER_VALIDATE_EMAIL)));
        }

        $users = [];
        if (array_key_exists($userIdsKey, $data) && is_array($data[$userIdsKey])) {
            $users = User::query()->whereIn('id', $data[$userIdsKey])->get()->all(); // kirim sebagai koleksi User
        }

        return array_merge($emails, $users);
    }

    private function maskRecipient(string|User $r): string
    {
        if ($r instanceof User) return "user#{$r->id}";
        $parts = explode('@', $r);
        return $parts[0] . '@***';
    }

    private function resolveApprovable(string $fqcn, $id): Model
    {
        // Keamanan: whitelist model yang boleh
        $allowed = [
            \App\Models\RecruitmentRequest::class,
            // tambahkan model lain yang valid di sini...
        ];

        if (! in_array($fqcn, $allowed, true)) {
            abort(422, 'Invalid approvable_type');
        }

        /** @var Model|null $model */
        $model = $fqcn::query()->find($id);
        abort_if(! $model, 404, 'Approvable not found');

        return $model;
    }
}
