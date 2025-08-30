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
                        'status' => 'progress',
                        'note' => '',
                        'updatedAt' => '2025-07-16T09:00:00Z',
                        'reviseNotes' => []
                    ], //0
                    [
                        'name' => 'Approval by Stakeholder',
                        'status' => 'pending',
                        'isApproved' => true,
                        'updatedAt' => null,
                        'note' => '',
                        'reviseNotes' => []
                    ], //1
                    [
                        'name' => 'CV Collection',
                        'status' => 'pending',
                        'totalCV' => 0,
                        'updatedAt' => null,
                        'note' => '',
                        "reviseNotes" => []
                    ], //2
                    [
                        'name' => 'CV Screening',
                        'status' => 'pending',
                        'approvedCV' => 0,
                        'updatedAt' => null,
                        'note' => '',
                        'reviseNotes' => []
                    ], //3
                    [
                        'name' => 'Check Background (SLIK)',
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
                        'finished' => 0,
                        'passed' => 0,
                        'updatedAt' => null,
                        'note' => '',
                        'reviseNotes' => []
                    ], //5
                    [
                        'name' => 'Interview with HRD',
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
                        'name' => 'Interview with TOP Management',
                        'status' => 'pending',
                        'interviewed' => 0,
                        'candidate' => 0,
                        'passed' => 0,
                        'updatedAt' => null,
                        'note' => '',
                        'reviseNotes' => []
                    ],
                    [
                        'name' => 'Negosiasi THP',
                        'status' => 'pending',
                        'candidate' => 0,
                        'agreed' => 0,
                        'updatedAt' => null,
                        'note' => '',
                        'reviseNotes' => []
                    ],
                    [
                        'name' => 'Medical Check Up (MCU)',
                        'status' => 'pending',
                        'hasChecked' => 0,
                        'candidate' => 0,
                        'passed' => 0,
                        'updatedAt' => null,
                        'note' => '',
                        'reviseNotes' => []
                    ],
                    [
                        'name' => 'Onboarding (TTD Kontrak)',
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
