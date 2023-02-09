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

use container_workspace\totara_notification\placeholder\workspace as workspace_placeholder_group;
use container_workspace\workspace;
use core_phpunit\testcase;
use totara_notification\placeholder\option;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_notification
 * @group container_workspace
 * @group totara_engage
 */
class container_workspace_totara_notification_workspace_placeholder_testcase extends testcase {
    /**
     * @return void
     */
    public function test_workspace_placeholder_instances_are_cached(): void {
        global $DB;

        self::setAdminUser();
        $workspace1 = $this->create_workspace();
        $workspace2 = $this->create_workspace();

        $query_count = $DB->perf_get_reads();
        workspace_placeholder_group::from_id($workspace1->id);
        self::assertEquals($query_count + 1, $DB->perf_get_reads());

        workspace_placeholder_group::from_id($workspace1->id);
        self::assertEquals($query_count + 1, $DB->perf_get_reads());

        workspace_placeholder_group::from_id($workspace2->id);
        self::assertEquals($query_count + 2, $DB->perf_get_reads());

        workspace_placeholder_group::from_id($workspace1->id);
        workspace_placeholder_group::from_id($workspace2->id);
        self::assertEquals($query_count + 2, $DB->perf_get_reads());
    }

    /**
     * @return void
     */
    public function test_workspace_placeholders(): void {
        global $CFG;

        // Make devs aware they should extend this test when adding placeholders.
        $option_keys = array_map(static function (option $option) {
            return $option->get_key();
        }, workspace_placeholder_group::get_options());
        self::assertEqualsCanonicalizing(
            ['full_name', 'full_name_link'],
            $option_keys,
            'Please add missing placeholders to test coverage.'
        );

        self::setAdminUser();

        $workspace = $this->create_workspace('A test workspace 1');

        $placeholder_group = workspace_placeholder_group::from_id($workspace->id);
        self::assertEquals('A test workspace 1', $placeholder_group->do_get('full_name'));
        self::assertEquals(
            '<a href="' . $CFG->wwwroot . '/container/type/workspace/workspace.php?id='
            . $workspace->id . '">A test workspace 1</a>',
            $placeholder_group->do_get('full_name_link')
        );
    }

    public function test_workspace_placeholders_invalid_workspace(): void {
        self::setAdminUser();
        $placeholder_group = workspace_placeholder_group::from_id(123);
        self::expectException(coding_exception::class);
        self::expectExceptionMessage('The workspace record is empty');
        $placeholder_group->do_get('full_name');
    }

    public function test_workspace_placeholders_not_available(): void {
        self::setAdminUser();
        $workspace = $this->create_workspace('Another test workspace');
        $placeholder_group = workspace_placeholder_group::from_id($workspace->id);

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
        /** @var \container_workspace\testing\generator $gen */
        $gen = self::getDataGenerator()
            ->get_plugin_generator('container_workspace');
        return $gen->create_workspace(...$args);
    }
}
