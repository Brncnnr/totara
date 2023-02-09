<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package container_workspace
 * @category totara_notification
 */

use container_workspace\totara_notification\placeholder\enrolment as enrolment_placeholder_group;
use container_workspace\workspace;
use core\orm\query\builder;
use core_phpunit\testcase;
use totara_notification\placeholder\option;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_notification
 * @group container_workspace
 * @group totara_engage
 */
class container_workspace_totara_notification_enrolment_placeholder_testcase extends testcase {

    /**
     * @return void
     */
    public function test_placeholder_instances_are_cached(): void {
        global $DB;

        self::setAdminUser();
        [$workspace1, $user1] = $this->create_workspace_assign_member();
        [$workspace2,] = $this->create_workspace_assign_member($user1);

        $query_count = $DB->perf_get_reads();
        enrolment_placeholder_group::from_workspace_id_and_user_id($workspace1->id, $user1->id);
        self::assertEquals($query_count + 1, $DB->perf_get_reads());

        enrolment_placeholder_group::from_workspace_id_and_user_id($workspace1->id, $user1->id);
        self::assertEquals($query_count + 1, $DB->perf_get_reads());

        enrolment_placeholder_group::from_workspace_id_and_user_id($workspace2->id, $user1->id);
        self::assertEquals($query_count + 2, $DB->perf_get_reads());

        enrolment_placeholder_group::from_workspace_id_and_user_id($workspace1->id, $user1->id);
        enrolment_placeholder_group::from_workspace_id_and_user_id($workspace2->id, $user1->id);
        self::assertEquals($query_count + 2, $DB->perf_get_reads());
    }

    /**
     * @return void
     */
    public function test_placeholders(): void {
        // Make devs aware they should extend this test when adding placeholders.
        $option_keys = array_map(static function (option $option) {
            return $option->get_key();
        }, enrolment_placeholder_group::get_options());
        self::assertEqualsCanonicalizing(
            ['join_date'],
            $option_keys,
            'Please add missing placeholders to test coverage.'
        );

        self::setAdminUser();

        $workspace = $this->create_workspace('A test workspace 1');
        $user1 = $this->getDataGenerator()->create_user();
        $this->workspace_generator()->add_member($workspace, $user1->id);

        // It's time-based, so pull the time out of the DB for comparison
        $user1_enrol = builder::table('user_enrolments', 'ue')
            ->select_raw(
                "CASE WHEN ue.timestart IS NULL OR ue.timestart = 0 THEN ue.timecreated
                         ELSE ue.timestart
                     END AS time_joined")
            ->join(['enrol', 'e'], 'ue.enrolid', 'e.id')
            ->where('ue.userid', $user1->id)
            ->where('e.courseid', $workspace->id)
            ->order_by('ue.id')
            ->results_as_arrays(true)
            ->first();

        $this->setUser($user1);
        $joined = userdate($user1_enrol['time_joined']);

        $placeholder_group = enrolment_placeholder_group::from_workspace_id_and_user_id($workspace->id, $user1->id);
        self::assertEquals($joined, $placeholder_group->do_get('join_date'));
    }

    /**
     * @return void
     */
    public function test_placeholders_invalid_workspace(): void {
        self::setAdminUser();
        $placeholder_group = enrolment_placeholder_group::from_workspace_id_and_user_id(123, 123456);
        self::expectException(coding_exception::class);
        self::expectExceptionMessage('The workspace enrolment record is empty');
        $placeholder_group->do_get('join_date');
    }

    /**
     * @return void
     */
    public function test_workspace_placeholders_not_available(): void {
        self::setAdminUser();
        [$workspace, $user] = $this->create_workspace_assign_member();
        $placeholder_group = enrolment_placeholder_group::from_workspace_id_and_user_id($workspace->id, $user->id);

        self::expectException(coding_exception::class);
        self::expectExceptionMessage("Invalid key 'whatever'");
        $placeholder_group->do_get('whatever');
    }

    /**
     * Create a workspace
     *
     * @param ...$args mixed Params to pass to create_workspace
     * @return workspace
     */
    protected function create_workspace(...$args): workspace {
        return $this->workspace_generator()->create_workspace(...$args);
    }

    /**
     * @param stdClass|null $user
     * @return array
     */
    protected function create_workspace_assign_member(stdClass $user = null): array {
        $workspace = $this->create_workspace();
        $user = $user ?? $this->getDataGenerator()->create_user();
        $this->workspace_generator()->add_member($workspace, $user->id);

        return [$workspace, $user];
    }

    /**
     * @return \container_workspace\testing\generator
     */
    protected function workspace_generator(): \container_workspace\testing\generator {
        /** @var \container_workspace\testing\generator $gen */
        $gen = self::getDataGenerator()
            ->get_plugin_generator('container_workspace');
        return $gen;
    }
}
