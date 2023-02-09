<?php

use container_workspace\workspace;

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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */

class container_workspace_update_hidden_workspace_with_audience_visibility_testcsae extends advanced_testcase {
    /**
     * @return void
     */
    public function test_update_old_hidden_workspace_with_audience_visibility(): void {
        global $DB, $USER, $CFG;
        $this->setAdminUser();

        if (!defined('COHORT_VISIBLE_ALL')) {
            require_once("{$CFG->dirroot}/totara/core/totara.php");
        }

        // Create a public workspace.
        $public_course_record = new stdClass();
        $public_course_record->category = workspace::get_default_category_id();
        $public_course_record->fullname = "Public workspace";
        $public_course_record->shortname = "public_workspace";
        $public_course_record->summary = "";
        $public_course_record->summaryformat = FORMAT_JSON_EDITOR;
        $public_course_record->visible = 1;
        $public_course_record->visibleold = 1;
        $public_course_record->audiencevisible = COHORT_VISIBLE_ALL;
        $public_course_record->containertype = 'container_workspace';
        $public_course_record->id = $DB->insert_record('course', $public_course_record);

        // Insert table workspace for public record.
        $public_w_record = new stdClass();
        $public_w_record->course_id = $public_course_record->id;
        $public_w_record->user_id = $USER->id;
        $public_w_record->timestamp = time();
        $public_w_record->private = 0;
        $public_w_record->id = $DB->insert_record('workspace', $public_w_record);

        // Create a private workspace.
        $private_course_record = new stdClass();
        $private_course_record->category = workspace::get_default_category_id();
        $private_course_record->fullname = "Private workspace";
        $private_course_record->shortname = "private_workspace";
        $private_course_record->summary = "";
        $private_course_record->summaryformat = FORMAT_JSON_EDITOR;
        $private_course_record->visible = 1;
        $private_course_record->visibleold = 1;
        $private_course_record->audiencevisible = COHORT_VISIBLE_ALL;
        $private_course_record->containertype = 'container_workspace';
        $private_course_record->id = $DB->insert_record('course', $private_course_record);

        // Insert table workspace for private record.
        $private_w_record = new stdClass();
        $private_w_record->course_id = $private_course_record->id;
        $private_w_record->user_id = $USER->id;
        $private_w_record->timestamp = time();
        $private_w_record->private = 1;
        $private_w_record->id = $DB->insert_record('workspace', $private_w_record);


        // Create a hidden workspace.
        $hidden_course_record = new stdClass();
        $hidden_course_record->category = workspace::get_default_category_id();
        $hidden_course_record->fullname = "Private workspace";
        $hidden_course_record->shortname = "private_workspace";
        $hidden_course_record->summary = "";
        $hidden_course_record->summaryformat = FORMAT_JSON_EDITOR;
        $hidden_course_record->visible = 0;
        $hidden_course_record->visibleold = 0;
        $hidden_course_record->audiencevisible = COHORT_VISIBLE_ALL;
        $hidden_course_record->containertype = 'container_workspace';
        $hidden_course_record->id = $DB->insert_record('course', $hidden_course_record);

        // Insert table workspace for hidden record.
        $hidden_w_record = new stdClass();
        $hidden_w_record->course_id = $hidden_course_record->id;
        $hidden_w_record->user_id = $USER->id;
        $hidden_w_record->timestamp = time();
        $hidden_w_record->private = 1;
        $hidden_w_record->id = $DB->insert_record('workspace', $hidden_w_record);

        require_once("{$CFG->dirroot}/container/type/workspace/db/upgradelib.php");
        // Before we run the upgrade step, we need to fetch all the current records.
        $before_public_workspace = $DB->get_record('course', ['id' => $public_course_record->id]);
        $before_private_workspace = $DB->get_record('course', ['id' => $private_course_record->id]);
        $before_hidden_workspace = $DB->get_record('course', ['id' => $hidden_course_record->id]);

        container_workspace_update_hidden_workspace_with_audience_visibility();

        // After the upgrade step, we will fetch the records and check that the public and private records
        // should not be changed much.
        $after_public_workspace = $DB->get_record('course', ['id' => $public_course_record->id]);
        $after_private_workspace = $DB->get_record('course', ['id' => $private_course_record->id]);
        $after_hidden_workspace = $DB->get_record('course', ['id' => $hidden_course_record->id]);

        // Check that audience visible after upgrade does not change for public and private workspace.
        self::assertNotEquals(COHORT_VISIBLE_ENROLLED, $after_public_workspace->audiencevisible);
        self::assertNotEquals(COHORT_VISIBLE_ENROLLED, $after_private_workspace->audiencevisible);

        self::assertEquals(COHORT_VISIBLE_ALL, $after_public_workspace->audiencevisible);
        self::assertEquals(COHORT_VISIBLE_ALL, $after_private_workspace->audiencevisible);

        self::assertEquals($before_public_workspace, $after_public_workspace);
        self::assertEquals($before_private_workspace, $after_private_workspace);

        self::assertNotEquals($before_hidden_workspace, $after_hidden_workspace);
        self::assertNotEquals(COHORT_VISIBLE_ENROLLED, $before_hidden_workspace->audiencevisible);
        self::assertEquals(COHORT_VISIBLE_ALL, $before_hidden_workspace->audiencevisible);

        self::assertNotEquals(COHORT_VISIBLE_ALL, $after_hidden_workspace->audiencevisible);
        self::assertEquals(COHORT_VISIBLE_ENROLLED, $after_hidden_workspace->audiencevisible);
    }
}