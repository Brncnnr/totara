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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

use core\entity\user;
use core\orm\collection;
use core\orm\query\builder;
use mod_approval\entity\workflow\workflow_version as workflow_version_entity;
use mod_approval\form_schema\form_schema_field;
use mod_approval\model\application\application;
use mod_approval\model\application\application_submission;
use mod_approval\model\application\application_state;
use mod_approval\model\assignment\assignment;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\form\form_data;
use mod_approval\model\form\form_contents;
use mod_approval\model\form\merger\form_data_merger_edit;
use mod_approval\model\form\merger\form_data_merger_preview;
use mod_approval\model\form\merger\form_data_merger_view;
use mod_approval\model\form\merger\form_schema_merger_edit;
use mod_approval\model\form\merger\form_schema_merger_preview;
use mod_approval\model\form\merger\form_schema_merger_view;
use mod_approval\model\status;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\stage_type\finished;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_formview;
use mod_approval\model\workflow\workflow_version;
use mod_approval\testing\generator;
use mod_approval\testing\workflow_generator_object;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 */
class mod_approval_form_contents_testcase extends mod_approval_testcase {
    /**
     * @covers mod_approval\model\form\form_contents::create_mergers
     * @covers mod_approval\model\form\merger\form_data_merger::__construct
     * @covers mod_approval\model\form\merger\form_schema_merger::__construct
     */
    public function test_create_mergers(): void {
        $method = new ReflectionMethod(form_contents::class, 'create_mergers');
        $method->setAccessible(true);
        $this->setAdminUser();
        $application = $this->create_application_for_user();
        // VIEW
        [$schema_merger, $data_merger] = $method->invoke(null, $application, user::logged_in(), form_contents::VIEW);
        $this->assertInstanceOf(form_schema_merger_view::class, $schema_merger);
        $this->assertInstanceOf(form_data_merger_view::class, $data_merger);
        // EDIT
        [$schema_merger, $data_merger] = $method->invoke(null, $application, user::logged_in(), form_contents::EDIT);
        $this->assertInstanceOf(form_schema_merger_edit::class, $schema_merger);
        $this->assertInstanceOf(form_data_merger_edit::class, $data_merger);
        // PREVIEW
        [$schema_merger, $data_merger] = $method->invoke(null, $application, user::logged_in(), form_contents::PREVIEW);
        $this->assertInstanceOf(form_schema_merger_preview::class, $schema_merger);
        $this->assertInstanceOf(form_data_merger_preview::class, $data_merger);
        // 0 and 1 are permanently invalid to prevent passing a boolean value to $purpose
        try {
            $method->invoke(null, $application, user::logged_in(), false);
            $this->fail('coding_exception expected');
        } catch (coding_exception $e) {
            $this->assertStringContainsString('Unknown purpose: 0', $e->getMessage());
        }
        try {
            $method->invoke(null, $application, user::logged_in(), true);
            $this->fail('coding_exception expected');
        } catch (coding_exception $e) {
            $this->assertStringContainsString('Unknown purpose: 1', $e->getMessage());
        }
    }

    /**
     * @covers mod_approval\model\form\form_contents::get_working_stages
     */
    public function test_get_working_stages(): void {
        $invoker = function (application $application, ?int $current_stage_id): array {
            $this->application_update_stage_and_level_silently($application, $current_stage_id, null);
            $application->refresh(true);
            $method = new ReflectionMethod(form_contents::class, 'get_working_stages');
            $method->setAccessible(true);
            /** @var collection|workflow_stage[] $stages */
            $stages = $method->invoke(null, $application);
            return $stages->map(function (workflow_stage $stage) {
                return $stage->ordinal_number;
            })->all(false);
        };

        $this->setAdminUser();
        $application = $this->create_application_for_user(null, [$this, 'setup_get_working_stages']);

        /** @var workflow_stage $stage1 */
        $stage1 = $application->workflow_version->stages->first();
        $stage2 = $application->workflow_version->get_next_stage($stage1->id);
        $stage3 = $application->workflow_version->get_next_stage($stage2->id);
        $stage4 = $application->workflow_version->get_next_stage($stage3->id);

        static::assertEquals([1], $invoker($application, $stage1->id));
        static::assertEquals([1, 2], $invoker($application, $stage2->id));
        static::assertEquals([1, 2, 3], $invoker($application, $stage3->id));
        static::assertEquals([1, 2, 3, 4], $invoker($application, $stage4->id));
    }

    public function setup_get_working_stages(workflow_version $workflow_version) {
        workflow_stage::create($workflow_version, 'stage 1', form_submission::get_enum());
        workflow_stage::create($workflow_version, 'stage 2', form_submission::get_enum());
        workflow_stage::create($workflow_version, 'stage 3', form_submission::get_enum());
        workflow_stage::create($workflow_version, 'stage 4', form_submission::get_enum());
        workflow_stage::create($workflow_version, 'stage 4', finished::get_enum());
    }

    /**
     * @return array
     */
    public function data_generate_from_application_empty(): array {
        $stage1_keys = ['gender', 'food', 'drink'];
        $stage2_view_keys = ['gender', 'food', 'drink', 'genre', 'shirt'];
        $stage2_edit_keys = ['food', 'drink', 'genre', 'shirt'];
        $all_keys = ['agency_code', 'gender', 'food', 'drink', 'genre', 'tomato', 'shirt'];
        // View: Empty form fields
        $stage1_view = ['gender' => null, 'food' => null, 'drink' => null];
        $stage2_view = ['gender' => null, 'food' => null, 'drink' => null, 'genre' => null, 'shirt' => null];
        // Edit: Empty form fields
        $stage1_edit = ['gender' => null, 'food' => null, 'drink' => null];
        $stage2_edit = ['food' => null, 'drink' => null, 'genre' => null, 'shirt' => null];
        // Preview: All form fields with default values provided by formview and schema.
        $stage1_preview = [
            'agency_code' => null,
            'gender' => '?',
            'food' => null,
            'drink' => 'Latte',
            'genre' => null,
            'tomato' => null,
            'shirt' => null
        ];
        $stage2_preview = [
            'agency_code' => null,
            'gender' => '?',
            'food' => 'Poison',
            'drink' => 'Tea',
            'genre' => 'Horror',
            'tomato' => null,
            'shirt' => 'Tee'
        ];
        // NOTES:
        // - default gender is carried over
        // - missing values are filled with default values in preview
        // - formview overrides the default 'Latte' with 'Tea' at stage 2 in preview
        return [
            'VIEW' => [form_contents::VIEW, [$stage1_keys, $stage2_view_keys], [$stage1_view, $stage2_view]],
            'EDIT' => [form_contents::EDIT, [$stage1_keys, $stage2_edit_keys], [$stage1_edit, $stage2_edit]],
            'PREVIEW' => [form_contents::PREVIEW, [$all_keys, $all_keys], [$stage1_preview, $stage2_preview]],
        ];
    }

    /**
     * @covers       mod_approval\model\form\form_contents::generate_from_application
     * @param integer $purpose
     * @param array $stage_keys
     * @param array $stage_forms
     * @dataProvider data_generate_from_application_empty
     */
    public function test_generate_from_application_empty(int $purpose, array $stage_keys, array $stage_forms): void {
        $user = new user($this->getDataGenerator()->create_user());
        $this->setUser($user);
        $application = $this->create_application_for_user('test2');
        workflow_version_entity::repository()->where('id', $application->workflow_version_id)
            ->update([
                'status' => status::DRAFT
            ]);
        $application->workflow_version->refresh();
        /** @var workflow_stage $stage1 */
        $stage1 = $application->workflow_version->stages->first();
        $stage2 = workflow_stage::create($application->workflow_version, 'stage 2', form_submission::get_enum());
        $stage3 = workflow_stage::create($application->workflow_version, 'stage 3', form_submission::get_enum());

        $this->erase_formviews_from_stages($application->workflow_version);

        workflow_stage_formview::create($stage1, 'gender', false, false, '?');
        workflow_stage_formview::create($stage1, 'food', false, false, null);
        workflow_stage_formview::create($stage1, 'drink', false, false, null);
        workflow_stage_formview::create($stage2, 'food', false, false, 'Poison');
        workflow_stage_formview::create($stage2, 'genre', false, false, 'Horror');
        workflow_stage_formview::create($stage2, 'drink', false, true, 'Tea');
        workflow_stage_formview::create($stage2, 'shirt', false, true, 'Tee');
        $stage1->refresh(true);
        $stage2->refresh(true);
        $application->workflow_version->refresh();

        $deal = function () use ($purpose, $application) {
            $application->refresh(true);
            $result = form_contents::generate_from_application($application, user::logged_in(), $purpose);
            return [
                array_map(function (form_schema_field $field) {
                    return $field->default;
                }, $result->get_form_schema()->get_fields()),
                (array) $result->get_form_data()->jsonSerialize(),
            ];
        };

        $this->create_submission($application, $user, $stage1, []);
        $this->application_update_stage_and_level_silently($application, $stage1->id, null);
        [$fields, $data] = $deal();
        $this->assertEqualsCanonicalizing($stage_keys[0], array_keys($fields));
        $this->assertEquals($stage_forms[0], $data);

        $this->create_submission($application, $user, $stage2, []);
        $this->application_update_stage_and_level_silently($application, $stage2->id, null);
        [$fields, $data] = $deal();
        $this->assertEqualsCanonicalizing($stage_keys[1], array_keys($fields));
        $this->assertEquals($stage_forms[1], $data);

        if ($purpose !== form_contents::EDIT) {
            $this->application_update_stage_and_level_silently($application, $stage3->id, null);
            [$fields, $data] = $deal();
            $this->assertEqualsCanonicalizing($stage_keys[1], array_keys($fields));
            $this->assertEquals($stage_forms[1], $data);
        }
    }

    /**
     * @param application $application
     * @param user $user
     * @param workflow_stage $stage
     * @param array $form_data
     * @return application_submission
     */
    private function create_submission(
        application $application, user $user,
        workflow_stage $stage,
        array $form_data
    ): application_submission {
        if (empty($form_data)) {
            $form_data = form_data::create_empty();
        } else {
            $form_data = form_data::from_json(json_encode($form_data));
        }
        $entity = generator::instance()->create_application_submission($application->id, $user->id, $stage->id, $form_data);
        // supersede all other submissions
        builder::table('approval_application_submission')
            ->where('application_id', $application->id)
            ->where('workflow_stage_id', $stage->id)
            ->where('id', '!=', $entity->id)
            ->update(['superseded' => 1]);
        return application_submission::load_by_entity($entity);
    }

    public function test_regression(): void {
        $this->setAdminUser();
        $form_version = generator::instance()
            ->create_form_and_version('simple', 'test form', __DIR__ . '/fixtures/schema/test1.json');
        $version_entity = generator::instance()->create_workflow_and_version(
            new workflow_generator_object(
                generator::instance()->create_workflow_type('test workflow type')->id,
                $form_version->form_id,
                $form_version->id
            )
        );
        $version_entity->status = status::DRAFT;
        $version_entity->update();
        $workflow_version = workflow_version::load_by_entity($version_entity);

        $stage1 = workflow_stage::create($workflow_version, 'First stage', form_submission::get_enum());
        $stage2 = workflow_stage::create($workflow_version, 'Second stage', approvals::get_enum());
        $stage3 = workflow_stage::create($workflow_version, 'Third stage', approvals::get_enum());
        $stage4 = workflow_stage::create($workflow_version, 'Fourth stage', approvals::get_enum());
        $this->erase_formviews_from_stages($workflow_version);

        workflow_stage_formview::create($stage1, 'gender', true, false, null);
        workflow_stage_formview::create($stage1, 'food', true, false, null);
        workflow_stage_formview::create($stage1, 'drink', true, false, null);

        $level1 = $stage2->add_approval_level('First level');
        $level2 = $stage2->add_approval_level('Second level');
        $level3 = $stage2->add_approval_level('Third level');

        workflow_stage_formview::create($stage2, 'gender', true, false, null);
        workflow_stage_formview::create($stage2, 'food', true, false, null);
        workflow_stage_formview::create($stage2, 'drink', true, false, null);

        $level4 = $stage2->add_approval_level('Fourth level');
        $level5 = $stage2->add_approval_level('Fifth level');

        workflow_stage_formview::create($stage3, 'gender', true, false, null);
        workflow_stage_formview::create($stage3, 'tomato', false, false, null);
        workflow_stage_formview::create($stage3, 'shirt', true, false, null);

        $level6 = $stage3->add_approval_level('Final level');

        workflow_stage_formview::create($stage4, 'agency_code', false, false, null);
        workflow_stage_formview::create($stage4, 'drink', false, false, null);
        workflow_stage_formview::create($stage4, 'genre', false, false, null);

        $final_stage = workflow_stage::create($workflow_version, 'Final stage', finished::get_enum());
        $workflow_version->activate();

        $user = new user($this->getDataGenerator()->create_user());
        $approver = new user($this->getDataGenerator()->create_user());
        $assignment = assignment::create(
            $workflow_version->workflow->course_id,
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id,
            true
        )->activate();
        $application = application::create($workflow_version, $assignment, $user->id);

        $this->create_submission($application, $user, $stage1, ['gender' => 'M', 'food' => 'Pancake', 'drink' => 'Водка']);
        $this->create_submission($application, $approver, $stage3, ['gender' => '?', 'shirt' => 'S']);
        // this one will be
        $this->create_submission($application, $approver, $stage4, ['agency_code' => 'A93C0D']);
        // superseded by this one
        $this->create_submission($application, $user, $stage4, ['drink' => 'Oil', 'genre' => 'Horror']);

        // Set to finished.
        $application->set_current_state($final_stage->state_manager->get_initial_state());

        $stage1->refresh(true);
        $stage3->refresh(true);
        $stage4->refresh(true);
        $application->refresh(true);

        $call = function (application $application, array $stages): array {
            $field_prop = new ReflectionProperty(form_schema_field::class, 'index');
            $field_prop->setAccessible(true);
            $test_method = new ReflectionMethod(form_contents::class, 'process_merger');
            $test_method->setAccessible(true);
            $data_merger = new form_data_merger_view($application, user::logged_in());
            $schema_merger = new form_schema_merger_view($application, user::logged_in(), $data_merger);
            $test_method->invoke(null, $schema_merger, (new collection($stages))->sort('ordinal_number'));
            // grab only key/value pairs
            $return = [];
            foreach ($schema_merger->get_result()->get_fields() as $key => $field) {
                $return["{$key}:{$field_prop->getValue($field)}"] = $data_merger->get_result()->get_value($key);
            }
            return $return;
        };

        $this->assertEmpty($call($application, []));
        $expected = ['gender:top/gender' => 'M', 'food:0/food' => 'Pancake', 'drink:0/drink' => 'Водка'];
        $this->assertEquals($expected, $call($application, [$stage1]));
        $expected = ['gender:top/gender' => '?', 'tomato:0/tomato' => null, 'shirt:0/shirt' => 'S'];
        $this->assertEquals($expected, $call($application, [$stage3]));
        $expected = ['agency_code:top/agency_code' => null, 'drink:0/drink' => 'Oil', 'genre:1/genre' => 'Horror'];
        $this->assertEquals($expected, $call($application, [$stage4]));
        $expected = [
            'agency_code:top/agency_code' => null,
            'gender:top/gender' => '?',
            'food:0/food' => 'Pancake',
            'drink:0/drink' => 'Oil',
            'genre:1/genre' => 'Horror',
            'tomato:1/tomato' => null,
            'shirt:1/shirt' => 'S'
        ];
        $this->assertEquals($expected, $call($application, [$stage1, $stage3, $stage4]));
    }
}
