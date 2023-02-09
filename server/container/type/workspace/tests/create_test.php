<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use container_workspace\exception\workspace_exception;
use container_workspace\member\member;
use core\webapi\execution_context;
use core_phpunit\testcase;
use totara_webapi\graphql;
use container_workspace\local\workspace_helper;
use container_workspace\workspace;

/**
 * @group container_workspace
 * @group totara_engage
 */
class container_workspace_create_testcase extends testcase {
    /**
     * @return void
     */
    public function test_create_workspace(): void {
        global $DB, $USER;
        $this->setAdminUser();

        $workspace = workspace_helper::create_workspace('Workspace 1010', $USER->id);

        $sql = '
            SELECT 1 FROM "ttr_course" c
            INNER JOIN "ttr_workspace" wo ON wo.course_id = c.id
            WHERE c.id = :course_id
        ';

        $this->assertTrue($DB->record_exists_sql($sql, ['course_id' => $workspace->id]));
    }

    /**
     * @return void
     */
    public function test_create_workspace_with_image(): void {
        global $CFG, $USER;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        // Log in as admin and start creating a workspace.
        $this->setAdminUser();

        $draft_id = file_get_unused_draft_itemid();

        $file_record = new stdClass();
        $file_record->component = 'user';
        $file_record->filearea = 'draft';
        $file_record->itemid = $draft_id;
        $file_record->filename = 'file_style.png';
        $file_record->filepath = '/';
        $file_record->contextid = context_user::instance($USER->id)->id;

        $fs = get_file_storage();
        $fs->create_file_from_string($file_record, "This is the file content");

        $workspace = workspace_helper::create_workspace(
            'workspce_101',
            $USER->id,
            null,
            null,
            null,
            $draft_id
        );

        // There should have no debugging called.
        $debug_messages = $this->getDebuggingMessages();
        $this->assertCount(0, $debug_messages);

        // The files should be moved to the right area.
        $this->assertTrue(
            $fs->file_exists(
                $workspace->get_context()->id,
                workspace::get_type(),
                workspace::IMAGE_AREA,
                0,
                '/',
                $file_record->filename
            )
        );
    }

    /**
     * @return void
     */
    public function test_create_workspace_without_image_but_has_draft_id(): void {
        global $CFG, $USER, $DB;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        $this->setAdminUser();
        $draft_file_id = file_get_unused_draft_itemid();

        $workspace = workspace_helper::create_workspace(
            'This is workspace',
            $USER->id,
            null,
            'This is summary',
            FORMAT_PLAIN,
            $draft_file_id
        );

        $debug_messages = $this->getDebuggingMessages();
        $this->assertEmpty($debug_messages);

        $this->assertDebuggingNotCalled();

        $this->assertTrue(
            $DB->record_exists('course', ['id' => $workspace->get_id()])
        );
    }

    /**
     * @return void
     */
    public function test_create_workspace_via_graphql(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $ec = execution_context::create('ajax', 'container_workspace_create_workspace');
        $result = graphql::execute_operation(
            $ec,
            [
                'name' => 'Hello world ?',
                'summary' => 'This is summary',
                'summary_format' => FORMAT_PLAIN,
                'hidden' => false,
                'private' => false,
                'theme' => 'ventura',
            ]
        );

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);
    }

    /**
     * @return void
     */
    public function test_create_private_workspace_without_helper(): void {
        $record = new stdClass();
        $record->fullname = "This is workspace";
        $record->workspace_private = true;

        // Log in as admin - so that we can have an owner for the workspace.
        $this->setAdminUser();

        /** @var workspace $workspace */
        $workspace = workspace::create($record);
        $this->assertFalse($workspace->is_public());
        $this->assertTrue($workspace->is_private());
        $this->assertFalse($workspace->is_hidden());
    }

    /**
     * @return void
     */
    public function test_normalise_on_create(): void {
        $ref_class = new \ReflectionClass(workspace::class);

        $this->assertTrue($ref_class->hasMethod('normalise_data_on_create'));
        $ref_method = $ref_class->getMethod('normalise_data_on_create');

        $this->assertTrue($ref_method->isStatic());
        $this->assertTrue($ref_method->isProtected());

        // Make the method accessible so that we can execute it.
        $ref_method->setAccessible(true);

        // Now start assertion on the behaviour of the function.
        $data = new stdClass();
        $data->fullname = 'This is workspace';

        // Execute the function.
        $result_data = $ref_method->invoke(null, $data);

        // Make sure that the function does not modify the original data.
        $this->assertNotSame($data, $result_data);

        // Assert all the data that has been injected to the data.
        $this->assertObjectHasAttribute('shortname', $result_data);
        $this->assertObjectHasAttribute('timecreated', $result_data);
        $this->assertObjectHasAttribute('category', $result_data);
        $this->assertObjectHasAttribute('enablecompletion', $result_data);
        $this->assertObjectHasAttribute('completionstartonenrol', $result_data);
        $this->assertObjectHasAttribute('completionnotify', $result_data);
        $this->assertObjectHasAttribute('visible', $result_data);
        $this->assertObjectHasAttribute('visibleold', $result_data);
        $this->assertObjectHasAttribute('summary', $result_data);
        $this->assertObjectHasAttribute('summaryformat', $result_data);
        $this->assertObjectHasAttribute('timemodified', $result_data);

        // Check on course format - it MUST always be 'none'.
        $this->assertObjectHasAttribute('format', $result_data);
        $this->assertEquals('none', $result_data->format);

        // Check on container type
        $this->assertObjectHasAttribute('containertype', $result_data);
        $this->assertEquals(workspace::get_type(), $result_data->containertype);

        // Check on the default value(s) that we injected to the data.
        $this->assertEquals(0, $result_data->enablecompletion);
        $this->assertEquals(0, $result_data->completionstartonenrol);
        $this->assertEquals(0, $result_data->completionnotify);

        // By default summary is null and summary format is set to FORMAT_PLAIN
        $this->assertNull($result_data->summary);
        $this->assertEquals(FORMAT_PLAIN, $result_data->summaryformat);

        // Check on the shortname. The shortname should be a strtolower version
        // of course's record full name.
        $this->assertEquals(strtolower($data->fullname), $result_data->shortname);

        // Check on the visible - by default it should always be public.
        $this->assertEquals(1, $result_data->visible);
        $this->assertEquals(1, $result_data->visibleold);
    }

    /**
     * @return void
     */
    public function test_create_miss_match_settings(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        $record = new stdClass();
        $record->fullname = 'data -x';
        $record->visible = 0;
        $record->workspace_private = false;

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The settings for workspace's access and visibility are miss matched");
        workspace::create($record);
    }

    /**
     * @return void
     */
    public function test_create_workspace_capabilities(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $cases = [
            ['cap' => 'create', 'private' => false, 'hidden' => false],
            ['cap' => 'createprivate', 'private' => true, 'hidden' => false],
            ['cap' => 'createhidden', 'private' => true, 'hidden' => true],
        ];

        $context = \context_coursecat::instance(workspace::get_default_category_id());

        // We're going to make a role to work with
        $role_id = $this->getDataGenerator()->create_role();
        $this->getDataGenerator()->role_assign($role_id, $user->id, $context->id);

        foreach ($cases as $case) {
            // Run it with the permission enabled
            $capability = 'container/workspace:' . $case['cap'];
            assign_capability($capability, CAP_ALLOW, $role_id, $context);
            $workspace = workspace_helper::create_workspace(
                'Test' . $case['cap'],
                $user->id
            );

            $this->assertSame('Test' . $case['cap'], $workspace->get_name());

            // Now deny it
            assign_capability($capability, CAP_PROHIBIT, $role_id, $context, true);

            $this->expectException(workspace_exception::class);
            $workspace = workspace_helper::create_workspace(
                'Test' . $case['cap'],
                $user->id
            );
        }
    }

    /**
     * @return void
     */
    public function test_create_workspace_with_self_enrol_disabled(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        set_config('enrol_plugins_enabled', 'manual');

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('self enrolment is not available');

        workspace_helper::create_workspace(
            'Workspace 101',
            $user_one->id
        );
    }

    /**
     * @return void
     */
    public function test_create_workspace_with_manual_enrol_disabled(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        set_config('enrol_plugins_enabled', 'self');

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('manual enrolment is not available');

        workspace_helper::create_workspace(
            'Workspace without manual enrol',
            $user_one->id
        );
    }

    /**
     * @return void
     */
    public function test_create_workspace_with_hashtag(): void {
        global $CFG, $DB;
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // This is a summary with a #testme hashtag.
        $summary = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"This is a summary with a "},{"type":"hashtag","attrs":{"text":"testme"}},{"type":"text","text":" hashtag."}]}]}';

        $ec = execution_context::create('ajax', 'container_workspace_create_workspace');
        $result = graphql::execute_operation(
            $ec,
            [
                'name' => 'Hello world ?',
                'description' => $summary,
                'summary_format' => FORMAT_JSON_EDITOR,
                'hidden' => false,
                'private' => false,
                'theme' => 'ventura',
            ]
        );

        // Ensure we have a workspace.
        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);

        // Check that hashtag was identified and stored.
        $where = "tagcollid = " . $CFG->hashtag_collection_id;
        $sql_result = $DB->get_field_select('tag', 'name', $where, null, MUST_EXIST);
        $this->assertEquals('testme', $sql_result);
    }

    /**
     * @return void
     */
    public function test_create_workspace_with_invalid_length(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $name = 'TfIKQ8IXoycfkcbGaav6B1XVVibwtIYTlyGIOiJukJ4xVOVd4dlbDBnVioSmM5LwdJ7lEv7MCNax';
        $this->assertEquals(76, strlen($name));

        $this->expectException(workspace_exception::class);
        $this->expectExceptionMessage('Cannot create a workspace');
        workspace_helper::create_workspace(
            $name,
            $user->id
        );
    }

    /**
     * @return void
     */
    public function test_create_workspace_with_correct_visibility_capabilities(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $workspace = workspace_helper::create_workspace(
            'Is Hidden',
            $user->id,
            null,
            null,
            null,
            null,
            true,
            true
        );
        $user_role = member::get_role_for_members();
        $context = $workspace->get_context();

        // Confirm the capability is assigned
        $caps = role_context_capabilities($user_role->id, $context, 'moodle/course:viewhiddencourses');
        self::assertArrayHasKey('moodle/course:viewhiddencourses', $caps);
        self::assertNotEmpty($caps['moodle/course:viewhiddencourses']);

        // Make the workspace private (not hidden)
        $record = new stdClass();
        $record->fullname = $workspace->fullname;
        $record->summaryformat = $workspace->summaryformat;
        $record->visibleold = $workspace->visibleold;
        $record->visible = true; // Not hidden
        $record->workspace_private = true;
        $workspace->update($record);

        // Confirm the capability is not assigned
        $caps = role_context_capabilities($user_role->id, $context, 'moodle/course:viewhiddencourses');
        self::assertArrayNotHasKey('moodle/course:viewhiddencourses', $caps);
    }
}