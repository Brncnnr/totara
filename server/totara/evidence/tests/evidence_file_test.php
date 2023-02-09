<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 * @category test
 */

use core\orm\query\builder;
use totara_evidence\customfield_area;
use totara_evidence\entity\evidence_item;

global $CFG;
require_once($CFG->dirroot . '/totara/evidence/tests/evidence_testcase.php');

/**
 * @group totara_evidence
 */
class totara_evidence_file_testcase extends totara_evidence_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->generator()->set_create_files(true);
        self::setAdminUser();
    }

    /**
     * Test that files uploaded into a filemanager in an evidence item is removed on deletion
     */
    public function test_filearea_files_deleted_on_item_deletion(): void {
        $this->assertEquals(0, $this->get_filearea_count(customfield_area\evidence::get_fileareas()[1]));

        $this->generator()->create_evidence_type(['field_types' => ['file']]);
        $item = $this->generator()->create_evidence_item();
        $this->assertEquals(1, $this->get_filearea_count(customfield_area\evidence::get_fileareas()[1]));

        $item->delete();
        $this->assertEquals(0, $this->get_filearea_count(customfield_area\evidence::get_fileareas()[1]));
    }

    /**
     * Test that files uploaded into a textarea in an evidence item is removed on deletion
     */
    public function test_textarea_files_deleted_on_item_deletion(): void {
        $this->assertEquals(0, $this->get_filearea_count(customfield_area\evidence::get_fileareas()[0]));

        $this->generator()->create_evidence_type(['field_types' => ['textarea']]);
        $item = $this->generator()->create_evidence_item();
        $this->assertEquals(1, $this->get_filearea_count(customfield_area\evidence::get_fileareas()[0]));

        $item->delete();
        $this->assertEquals(0, $this->get_filearea_count(customfield_area\evidence::get_fileareas()[0]));
    }

    /**
     * Test that files uploaded into the default value for an evidence type is removed on deletion
     */
    public function test_textarea_files_deleted_on_type_deletion(): void {
        $this->assertEquals(0, $this->get_filearea_count('textarea'));

        $type = $this->generator()->create_evidence_type(['field_types' => ['textarea']]);
        $this->assertEquals(1, $this->get_filearea_count('textarea'));

        $type->delete();
        $this->assertEquals(0, $this->get_filearea_count('textarea'));
    }

    /**
     * Test that all evidence for a user is deleted when the user is deleted
     */
    public function test_observer_user_deleted(): void {
        global $DB;
        $this->generator()->create_evidence_type(['field_types' => ['file', 'textarea']]);
        $user_one = $DB->get_record('user', ['id' => $this->generator()->create_evidence_user()->id]);
        $user_two = $DB->get_record('user', ['id' => $this->generator()->create_evidence_user()->id]);

        /**
         * @var evidence_item[] $user_one_evidence
         * @var evidence_item[] $user_two_evidence
         */
        $user_one_evidence = [];
        $user_two_evidence = [];
        $evidence_count = 3;
        for ($i = 0; $i < $evidence_count; $i++) {
            $user_one_evidence[] = $this->generator()->create_evidence_item_entity(['user_id' => $user_one->id]);
            $user_two_evidence[] = $this->generator()->create_evidence_item_entity(['user_id' => $user_two->id]);
        }

        for ($i = 0; $i < $evidence_count; $i++) {
            $this->assertTrue($user_one_evidence[$i]->exists());
            $this->assertTrue($user_two_evidence[$i]->exists());
        }
        $this->assertEquals($evidence_count, evidence_item::repository()->where('user_id', $user_one->id)->count());
        $this->assertEquals($evidence_count, evidence_item::repository()->where('user_id', $user_two->id)->count());
        $this->assertEquals(6, $this->get_filearea_count(customfield_area\evidence::get_fileareas()[0]));
        $this->assertEquals(6, $this->get_filearea_count(customfield_area\evidence::get_fileareas()[1]));

        delete_user($user_one);

        $this->assertEquals(0, evidence_item::repository()->where('user_id', $user_one->id)->count());
        $this->assertEquals($evidence_count, evidence_item::repository()->where('user_id', $user_two->id)->count());
        $this->assertEquals(3, $this->get_filearea_count(customfield_area\evidence::get_fileareas()[0]));
        $this->assertEquals(3, $this->get_filearea_count(customfield_area\evidence::get_fileareas()[1]));

        delete_user($user_two);

        $this->assertEquals(0, evidence_item::repository()->where('user_id', $user_one->id)->count());
        $this->assertEquals(0, evidence_item::repository()->where('user_id', $user_two->id)->count());
        $this->assertEquals(0, $this->get_filearea_count(customfield_area\evidence::get_fileareas()[0]));
        $this->assertEquals(0, $this->get_filearea_count(customfield_area\evidence::get_fileareas()[1]));
    }

    protected function get_filearea_count(string $filearea): int {
        return builder::table('files')
            ->where('filename', '<>', '.')
            ->where('filearea', $filearea)
            ->count();
    }

}
