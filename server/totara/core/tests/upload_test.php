<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2010-2019 Totara Learning Solutions Ltd
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package totara_core
 */

use totara_core\upload\upload;

/**
 * Test upload class
 */

defined('MOODLE_INTERNAL') || die();

class totara_core_upload_testcase extends advanced_testcase {
    /**
     * Test that get_draft_id returns non-null sequence that does not exists in database
     */
    public function test_get_draft_id() {
        global $DB;
        $draftitemid = $this->generate_draft();
        $draftitemidnew = upload::get_draft_id();

        $this->assertNotEmpty($draftitemidnew);
        $this->assertNotEmpty($draftitemid);
        $this->assertNotEquals($draftitemidnew, $draftitemid);
        $this->assertEmpty($DB->get_records('files', ['itemid' => $draftitemidnew]));
        $this->assertNotEmpty($DB->get_records('files', ['itemid' => $draftitemid]));
    }

    /**
     * Save file(s) uploaded using given draftid
     */
    public function test_save_draft() {
        global $DB;
        $draftitemid = $this->generate_draft();
        upload::save_draft($draftitemid, context_system::instance()->id, 'totara_core', 'test', 42);
        $records = $DB->get_records('files', ['itemid' => 42]);
        $previousnames = [];
        $this->assertNotEmpty($records);
        $this->assertFalse($DB->record_exists('files', ['itemid' => $draftitemid]));
        foreach($records as $record) {
            $this->assertContains($record->filename, ['pokus.txt', '.']);
            $this->assertEquals('totara_core', $record->component);
            $this->assertEquals('test', $record->filearea);
            $this->assertNotContainsEquals($record->filename, $previousnames);
            $previousnames[] = $record->filename;
        }
    }

    /**
     * Confirm that saving drafts will not overwrite files that already stored in the space
     */
    public function test_save_draft_not_overwrite_existing() {
        global $DB;
        $draftitemid = $this->generate_draft();
        upload::save_draft($draftitemid, context_system::instance()->id, 'totara_core', 'test', 42);
        $draftitemidnew = $this->generate_draft('hocus.txt');
        $this->markTestSkipped('totara_core_courselib_testcase or totara_core_file_storage_testcase break this test');
        return;
        upload::save_draft($draftitemidnew, context_system::instance()->id, 'totara_core', 'test', 42);
        $records = $DB->get_records('files', ['itemid' => 42]);
        $this->assertCount(3, $records);
        $previousnames = [];
        $this->assertNotEmpty($records);
        foreach($records as $record) {
            $this->assertEquals('totara_core', $record->component);
            $this->assertEquals('test', $record->filearea);
            $this->assertContains($record->filename, ['hocus.txt', 'pokus.txt', '.']);
            $this->assertNotContainsEquals($record->filename, $previousnames);
            $previousnames[] = $record->filename;
        }
    }

    /**
     * Test that delete draft file deletes only
     */
    public function test_delete_draft_file() {
        global $DB, $USER;
        $draftitemidother = $this->generate_draft();
        $usercontext = \context_user::instance($USER->id);
        // Generate second file in the same draft area.
        $draftitemid = $this->generate_draft();
        $fs = get_file_storage();
        $record = array(
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => 'hocus.txt',
        );
        $fs->create_file_from_string($record, 'lalala');
        upload::delete_draft_file($draftitemid, 'pokus.txt');
        // Confirm that other draft with the same name is not removed.
        $others = $DB->get_records('files', ['itemid' => $draftitemidother]);
        $this->assertNotEmpty($others);
        $previousnames = [];
        foreach ($others as $other) {
            $this->assertEquals('user', $other->component);
            $this->assertEquals('draft', $other->filearea);
            $this->assertContainsEquals($other->filename, ['pokus.txt', '.']);
            $this->assertNotContainsEquals($other->filename, $previousnames);
            $previousnames[] = $other->filename;
        }
        // Confirm that only requested file in draft area removed.
        $this->assertEquals(2, $DB->count_records('files',['itemid' => $draftitemid]));
        $records = $DB->get_records('files', ['itemid' => $draftitemid]);
        $previousnames = [];
        $this->assertNotEmpty($records);
        foreach($records as $record) {
            $this->assertEquals('user', $record->component);
            $this->assertEquals('draft', $record->filearea);
            $this->assertContainsEquals($record->filename, ['hocus.txt', '.']);
            $this->assertNotContainsEquals($record->filename, $previousnames);
            $previousnames[] = $record->filename;
        }
    }

    /**
     * Get required params for vue upload component
     */
    public function test_get_vue_params() {
        $this->setAdminUser();
        $params = upload::get_vue_params();
        $this->assertStringContainsString('repository/repository_ajax.php?action=upload', $params['href']);
        $this->assertNotEmpty($params['item-id']);
        $this->assertNotEmpty($params['repository-id']);
    }

    /**
     * Create draft file and return its itemid
     * @return int
     */
    private function generate_draft(string $name = "pokus.txt") : int {
        global $USER;
        $this->setAdminUser();
        $usercontext = \context_user::instance($USER->id);
        $draftitemid = file_get_unused_draft_itemid();
        $fs = get_file_storage();
        $record = array(
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => $name,
        );
        $fs->create_file_from_string($record, 'lalala');
        return $draftitemid;
    }
}