<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

use mod_approval\entity\application\application_activity;
use mod_approval\exception\model_exception;
use mod_approval\model\application\activity\level_ended;
use mod_approval\model\application\activity\level_started;
use mod_approval\model\application\activity\stage_ended;
use mod_approval\model\application\activity\stage_started;
use mod_approval\model\application\application;
use mod_approval\model\application\application_state;
use mod_approval\model\workflow\stage_type\approvals as approvals_stage_type;
use mod_approval\model\workflow\stage_type\finished;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\stage_type\state_manager\approvals;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_formview;
use mod_approval\model\workflow\workflow_version;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\workflow\stage_type\state_manager\approvals
 */
class mod_approval_workflow_stage_type_state_manager_approvals_testcase extends mod_approval_testcase {

    private $application;

    private $user;

    public function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
        $this->user = $this->create_user();
        $this->setUser($this->user);
        $this->application = $this->create_application_for_user(null, function (workflow_version $workflow_version) {
            $form_stage = workflow_stage::create($workflow_version, 'stage 1', form_submission::get_enum());

            workflow_stage_formview::create($form_stage, 'kia', true, false, 'KIA');
            workflow_stage_formview::create($form_stage, 'ora', false, false, 'ORA');

            $approval_stage = workflow_stage::create($workflow_version, 'stage 2', approvals_stage_type::get_enum());
            $approval_stage->add_approval_level('level 2');

            workflow_stage_formview::create($approval_stage, 'kia', true, false, 'KIA');
            workflow_stage_formview::create($approval_stage, 'ora', false, false, 'ORA');

            workflow_stage::create($workflow_version, 'stage 4', finished::get_enum());
        });
        application_activity::repository()->where('application_id', $this->application->id)->delete();
    }

    protected function tearDown(): void {
        $this->application = null;
        $this->user = null;
        parent::tearDown();
    }

    public function test_get_next_state_on_last_approval_level() {
        $workflow_version = $this->application->workflow_version;

        /** @var workflow_stage $approvals_stage */
        $approvals_stage = $workflow_version->stages->find('type', approvals_stage_type::class);
        $state_manager = new approvals($approvals_stage);
        $next_stage = $workflow_version->get_next_stage($approvals_stage->id);

        $state = new application_state(
            $approvals_stage->id,
            false,
            $approvals_stage->approval_levels->last()->id
        );
        $next_state = $state_manager->get_next_state($state);
        $this->assertEquals($next_stage->id, $next_state->get_stage_id());
    }

    public function test_get_next_state_on_first_approval_level() {
        $workflow_version = $this->application->workflow_version;

        /** @var workflow_stage $approvals_stage */
        $approvals_stage = $workflow_version->stages->find('type', approvals_stage_type::class);
        $state_manager = new approvals($approvals_stage);
        $first_approval_level_id = $approvals_stage->approval_levels->first()->id;

        $state = new application_state(
            $approvals_stage->id,
            false,
            $first_approval_level_id
        );
        $next_approval_level = $approvals_stage->feature_manager->approval_levels->get_next($first_approval_level_id);
        $next_state = $state_manager->get_next_state($state);
        $this->assertEquals($approvals_stage->id, $next_state->get_stage_id());
        $this->assertTrue($next_state->is_stage_type(approvals_stage_type::get_code()));
        $this->assertEquals($next_approval_level->id, $next_state->get_approval_level_id());
    }

    public function test_get_previous_state() {
        $workflow_version = $this->application->workflow_version;

        /** @var workflow_stage $approvals_stage */
        $approvals_stage = $workflow_version->stages->find('type', approvals_stage_type::class);
        $state_manager = new approvals($approvals_stage);
        $previous_stage = $workflow_version->get_previous_stage($approvals_stage->id);

        $state = new application_state(
            $approvals_stage->id,
            false,
            $approvals_stage->approval_levels->last()->id
        );
        $previous_state = $state_manager->get_previous_state($state);
        $this->assertEquals($previous_stage->id, $previous_state->get_stage_id());
    }

    public function test_get_initial_state() {
        /** @var workflow_stage $approvals_stage */
        $approvals_stage = $this->application->workflow_version->stages->find('type', approvals_stage_type::class);
        $state_manager = new approvals($approvals_stage);

        $state = $state_manager->get_initial_state();
        $this->assertEquals($approvals_stage->id, $state->get_stage_id());
        $this->assertTrue($state->is_stage_type(approvals_stage_type::get_code()));
        $this->assertEquals($approvals_stage->approval_levels->first()->id, $state->get_approval_level_id());
    }

    public function test_get_start_state() {
        $approvals_stage = $this->application->workflow_version->stages->find(function (workflow_stage $stage) {
            return $stage->type === approvals_stage_type::class;
        });
        $state_manager = new approvals($approvals_stage);

        $this->expectException(model_exception::class);
        $this->expectExceptionMessage('An application can not start in an approval stage');
        $state_manager->get_creation_state();
    }

    public function test_on_application_start() {
        $approvals_stage = $this->application->workflow_version->stages->find(function (workflow_stage $stage) {
            return $stage->type === approvals_stage_type::class;
        });
        $state_manager = new approvals($approvals_stage);

        $this->expectException(model_exception::class);
        $this->expectExceptionMessage('An application can not start in an approval stage');
        $state_manager->on_application_start($this->createMock(application::class), $this->user->id);
    }

    public function test_on_state_entry() {
        $scenarios = $this->get_state_entry_scenarios();

        foreach ($scenarios as $scenario => $data) {
            $state = $data['state'];
            $activities_recorded = $data['activities_recorded'];
            $this->application->set_current_state($state['to']);
            $state_manager = new approvals($this->application->current_stage);
            application_activity::repository()->where('application_id', $this->application->id)->delete();

            $state_manager->on_state_entry($this->application, $state['from'], null);

            $this->application->refresh(true);

            $activities = $this->application->activities->map(function ($activity) {
                return $activity->activity_type;
            })->all();
            $this->assertEquals($activities_recorded, $activities, "Testing on state entry scenario: $scenario failed");
        }
    }

    private function get_state_entry_scenarios(): array {
        $workflow_version = $this->application->workflow_version;
        /** @var workflow_stage $first_stage */
        $first_stage = $workflow_version->stages->first();
        $start_state = $first_stage->state_manager->get_initial_state();

        /** @var workflow_stage $approval_stage */
        $approval_stage = $workflow_version->stages->find('type', approvals_stage_type::class);
        $first_approval_state = $approval_stage->state_manager->get_initial_state();

        $second_approval_state = $approval_stage->state_manager->get_next_state($first_approval_state);

        return [
            'From Another stage' => [
                'state' => [
                    'from' => $start_state,
                    'to' => $first_approval_state,
                ],
                'activities_recorded' => [
                    stage_started::get_type(),
                    level_started::get_type(),
                ]
            ],
            'Within the same stage' => [
                'state' => [
                    'from' => $first_approval_state,
                    'to' => $second_approval_state,
                ],
                'activities_recorded' => [
                    level_started::get_type(),
                ],
            ]
        ];
    }

    public function test_on_state_exit() {
        $scenarios = $this->get_state_exit_scenarios();

        foreach ($scenarios as $scenario => $data) {
            $state = $data['state'];
            $activities_recorded = $data['activities_recorded'];
            $this->application->set_current_state($state['from']);
            $state_manager = new approvals($this->application->current_stage);
            application_activity::repository()->where('application_id', $this->application->id)->delete();

            $state_manager->on_state_exit($this->application, $state['to'], null);
            $this->application->refresh(true);

            $activities = $this->application->activities->map(function ($activity) {
                return $activity->activity_type;
            })->all();
            $this->assertEquals($activities_recorded, $activities, "Testing on state exit scenario: $scenario failed");
        }
    }

    private function get_state_exit_scenarios(): array {
        $workflow_version = $this->application->workflow_version;
        /** @var workflow_stage $last_stage */
        $last_stage = $workflow_version->stages->last();
        $last_state = $last_stage->state_manager->get_initial_state();

        /** @var workflow_stage $approval_stage */
        $approval_stage = $workflow_version->stages->find('type', approvals_stage_type::class);
        $first_approval_state = $approval_stage->state_manager->get_initial_state();

        $second_approval_state = $approval_stage->state_manager->get_next_state($first_approval_state);

        return [
            'To Another stage' => [
                'state' => [
                    'from' => $second_approval_state,
                    'to' => $last_state,
                ],
                'activities_recorded' => [
                    level_ended::get_type(),
                    stage_ended::get_type(),
                ]
            ],
            'Within the same stage' => [
                'state' => [
                    'from' => $first_approval_state,
                    'to' => $second_approval_state,
                ],
                'activities_recorded' => [
                    level_ended::get_type(),
                ],
            ]
        ];
    }

    public function test_instantiating_state_manager() {
        // With valid stage_type.
        $approvals_stage = $this->application->workflow_version->stages->find(function (workflow_stage $stage) {
            return $stage->type === approvals_stage_type::class;
        });
        $state_manager = new approvals($approvals_stage);
        $this->assertInstanceOf(approvals::class, $state_manager);

        // With invalid stage_type.
        $non_approvals_stage = $this->application->workflow_version->stages->find(function (workflow_stage $stage) {
            return $stage->type !== approvals_stage_type::class;
        });

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Application stage is not of type " . approvals_stage_type::class);
        new approvals($non_approvals_stage);
    }
}
