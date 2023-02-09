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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_approval
 */

use core_phpunit\testcase;
use mod_approval\entity\workflow\workflow_stage;
use mod_approval\model\workflow\workflow;
use mod_approval\testing\approval_workflow_test_setup;
use mod_approval\totara_notification\placeholder\workflow_stage as workflow_stage_placeholder_group;
use totara_notification\placeholder\option;

defined('MOODLE_INTERNAL') || die();

/**
 * @group approval_workflow
 * @group totara_notification
 */
class mod_approval_totara_notification_placeholder_workflow_stage_testcase extends testcase {

    use approval_workflow_test_setup;

    public function test_get_placeholders(): void {
        // Make devs aware they should extend this test when adding placeholders.
        $option_keys = array_map(static function (option $option) {
            return $option->get_key();
        }, workflow_stage_placeholder_group::get_options());
        self::assertEqualsCanonicalizing(
            [
                'name',
            ],
            $option_keys,
            'Please add missing placeholders to test coverage.'
        );

        // Create an application stage.
        $workflow_entity = $this->generator()->create_simple_request_workflow();
        $workflow = workflow::load_by_entity($workflow_entity);
        /** @var workflow_stage $stage */
        $stage = $workflow->latest_version->stages->first();

        $placeholder_group = workflow_stage_placeholder_group::from_id($stage->id);

        // Check each placeholder.
        self::assertEquals($stage->name, $placeholder_group->do_get('name'));
    }

    public function test_not_available(): void {
        $placeholder_group = new workflow_stage_placeholder_group(null);
        self::assertEquals('', $placeholder_group->get('name'));

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('The workflow stage model is empty');
        $placeholder_group->do_get('name');
    }

    public function test_instances_are_cached(): void {
        global $DB;

        // Create two application stages.
        $this->generator()->create_simple_request_workflow();
        $this->generator()->create_simple_request_workflow();

        $stage1 = workflow_stage::repository()->order_by('id', 'ASC')->first();
        $stage2 = workflow_stage::repository()->order_by('id', 'DESC')->first();
        self::assertNotEquals($stage1->id, $stage2->id);

        $query_count = $DB->perf_get_reads();
        workflow_stage_placeholder_group::from_id($stage1->id);
        self::assertEquals($query_count + 1, $DB->perf_get_reads());

        workflow_stage_placeholder_group::from_id($stage1->id);
        self::assertEquals($query_count + 1, $DB->perf_get_reads());

        workflow_stage_placeholder_group::from_id($stage2->id);
        self::assertEquals($query_count + 2, $DB->perf_get_reads());

        workflow_stage_placeholder_group::from_id($stage1->id);
        workflow_stage_placeholder_group::from_id($stage2->id);
        self::assertEquals($query_count + 2, $DB->perf_get_reads());
    }
}
