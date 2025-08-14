<?php

namespace Database\Factories;

use App\Models\RecruitmentPhase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecruitmentPhase>
 */
class RecruitmentPhaseFactory extends Factory
{
    protected $model = RecruitmentPhase::class;

    public function definition(): array
    {
        return [
            'request_id' => \App\Models\RecruitmentRequest::factory(),
            'status' => 'progress',
            'started_at' => now()->subDays(7),
            'finish_at' => null,
            'form_data' => [
                'phases' => [
                    [
                        'name' => 'Requesting',
                        'status' => 'finish',
                        'note' => '',
                        'updatedAt' => '2025-07-16T09:00:00Z',
                        'reviseNotes' => []
                    ], //0
                    [
                        'name' => 'Approval by Stakeholder',
                        'status' => 'finish',
                        'isApproved' => true,
                        'useId' => 1,
                        'updatedAt' => null,
                        'note' => '',
                        'reviseNotes' => []
                    ], //1
                    [
                        'name' => 'CV Collection',
                        'status' => 'progress',
                        'totalCV' => 0,
                        'updatedAt' => null,
                        'note' => '',
                        "reviseNotes" => []
                    ], //2
                    [
                        'name' => 'CV Screening',
                        'status' => 'pending',
                        'approvedCV' => 10,
                        'updatedAt' => null,
                        'note' => '',
                        'reviseNotes' => []
                    ], //3
                    [
                        'name' => 'Check Background (SLIK, Medic CU)',
                        'status' => 'pending',
                        'candidate' => 0,
                        'checked' => 0,
                        'passed' => 0,
                        'updatedAt' => null,
                        'note' => '',
                        'reviseNotes' => []
                    ], //4
                    [
                        'name' => 'Psychology Assessment',
                        'status' => 'pending',
                        'candidate' => 0,
                        'finisihed' => 0,
                        'passed' => 0,
                        'updatedAt' => null,
                        'note' => '',
                        'reviseNotes' => []
                    ], //5
                    [
                        'name' => 'HRD Interview',
                        'status' => 'pending',
                        'interviewed' => 0,
                        'candidate' => 0,
                        'passed' => 0,
                        'updatedAt' => null,
                        'note' => '',
                        'reviseNotes' => [],
                    ],
                    [
                        'name' => 'Interview with User',
                        'status' => 'pending',
                        'interviewed' => 0,
                        'candidate' => 0,
                        'passed' => 0,
                        'updatedAt' => null,
                        'note' => '',
                        'reviseNotes' => []
                    ],
                    [
                        'name' => 'Offering',
                        'status' => 'pending',
                        'candidate' => 0,
                        'offered' => 0,
                        'agreed' => 0,
                        'updatedAt' => null,
                        'note' => '',
                        'reviseNotes' => []
                    ],
                    [
                        'name' => 'Onboarding',
                        'status' => 'pending',
                        'candidate' => [
                            [
                                'name' => '',
                                'position' => '',
                                'onBoardingDate' => '',
                            ]
                        ],
                        'onboarded' => 0,
                        'updatedAt' => null,
                        'note' => '',
                        'reviseNotes' => []
                    ],
                    [
                        'name' => 'Closed',
                        'status' => 'pending',
                        'closedReason' => '',
                        'updatedAt' => null,
                        'note' => '',
                        'reviseNotes' => []
                    ]
                ]
            ]
        ];
    }
}
