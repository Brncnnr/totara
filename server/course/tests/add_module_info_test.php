<?php
/*
 * This file is part of Totara LMS
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core_course
 */
defined('MOODLE_INTERNAL') || die();

use mod_facetoface\seminar;

class core_course_add_module_info_testcase extends advanced_testcase {
    /**
     * Test suit of a whole process creating course module of facetoface.
     * @return void
     */
    public function test_add_module_factoface(): void {
        global $CFG, $DB;
        require_once("{$CFG->dirroot}/course/modlib.php");

        $this->setAdminUser();

        $gen = $this->getDataGenerator();
        $course = $gen->create_course(['enablecompletion' => 1]);

        $moduleinfo = new \stdClass();
        $moduleinfo->modulename = "facetoface";
        $moduleinfo->module = $this->get_module_id('facetoface');
        $moduleinfo->visible = 1;
        $moduleinfo->section = 0;

        // Completion
        $moduleinfo->completion = COMPLETION_ENABLED;
        $moduleinfo->completionexpected = time() + (7 * 24 * 3600);
        $moduleinfo->completionview = COMPLETION_VIEW_REQUIRED;
        $moduleinfo->completiongradeitemnumber = 0; // Either zero or null
        $moduleinfo->completionunlocked = 1;
        $moduleinfo->showdescription = 1;
        $moduleinfo->cmidnumber = "harder-than-you-think";

        // Intro
        file_prepare_draft_area($drafitemid, null, null, null, null);
        $moduleinfo->introeditor = [
            'text' => "Facetoface 101 description is here",
            'format' => FORMAT_PLAIN,
            'itemid' => $drafitemid
        ];

        // Factoface
        $moduleinfo->name = "Facetoface 101";
        $moduleinfo->display = 0;
        $moduleinfo->timecreated = time();
        $moduleinfo->timemodified = time();
        $moduleinfo->approvaltype = seminar::APPROVAL_NONE;

        $cloned = clone $moduleinfo;
        $result = add_moduleinfo($cloned, $course);

        // Making sure that only $cloned object was changed, but not the source
        $this->assertNotEquals($cloned, $moduleinfo);

        // We are expecting nothing has changed at all for the $moduleinfo, plus that these are the fields that
        // are remain after adding a module info, and also some of them are updated after add.
        $this->assertEquals($moduleinfo->modulename, $result->modulename);
        $this->assertEquals($moduleinfo->module, $result->module);
        $this->assertEquals($moduleinfo->visible, $result->visible);
        $this->assertEquals($moduleinfo->section, $result->section);
        $this->assertEquals($moduleinfo->completion, $result->completion);
        $this->assertEquals($moduleinfo->completionexpected, $result->completionexpected);
        $this->assertEquals($moduleinfo->completionview, $result->completionview);
        $this->assertEquals($moduleinfo->completiongradeitemnumber, $result->completiongradeitemnumber);
        $this->assertEquals($moduleinfo->completionunlocked, $result->completionunlocked);
        $this->assertEquals($moduleinfo->showdescription, $result->showdescription);
        $this->assertEquals($moduleinfo->introeditor['text'], $result->intro);
        $this->assertEquals($moduleinfo->introeditor['format'], $result->introformat);
        $this->assertEquals($moduleinfo->name, $result->name);
        $this->assertEquals($moduleinfo->display, $result->display);
        $this->assertEquals($moduleinfo->timecreated, $result->timecreated);
        $this->assertEquals($moduleinfo->timemodified, $result->timemodified);
        $this->assertEquals($moduleinfo->approvaltype, $result->approvaltype);
        $this->assertEquals($moduleinfo->cmidnumber, $result->cmidnumber);
        $this->assertObjectHasAttribute('instance', $result);
        $this->assertObjectHasAttribute('coursemodule', $result);

        // Start asserting against the database
        $cm = $DB->get_record('course_modules', ['id' => $result->coursemodule], '*', MUST_EXIST);

        $this->assertEquals($course->id,$cm->course);
        $this->assertNull($cm->availability);
        $this->assertEquals(0, $cm->deletioninprogress);
        $this->assertEquals($moduleinfo->showdescription, $cm->showdescription);
        $this->assertEquals($moduleinfo->completionview, $cm->completionview);
        $this->assertEquals($moduleinfo->completionexpected, $cm->completionexpected);
        $this->assertEquals($moduleinfo->completiongradeitemnumber, $cm->completiongradeitemnumber);
        $this->assertEquals($moduleinfo->completion, $cm->completion);
        $this->assertEquals($course->groupmode, $cm->groupmode);
        $this->assertEquals($moduleinfo->visible, $cm->visible);
        $this->assertEquals($moduleinfo->visible, $cm->visibleold);
        $this->assertEquals(1, $cm->visibleoncoursepage);
        $this->assertEmpty($cm->groupingid);
        $this->assertEquals($moduleinfo->cmidnumber, $cm->idnumber);
        $this->assertEmpty($cm->indent);
        $this->assertTrue($DB->record_exists('facetoface', ['id' => $cm->instance]));
        $this->assertTrue($DB->record_exists('course_sections', ['id' => $cm->section]));
    }

    /**
     * Test suite for adding course_module record
     * @return void
     */
    public function test_add_course_module(): void {
        global $CFG, $DB;
        require_once("{$CFG->dirroot}/course/modlib.php");

        $course = $this->getDataGenerator()->create_course();
        $sectionid = $DB->get_field('course_sections', 'id', ['section' => 0, 'course' => $course->id]);

        $newcm = new \stdClass();
        $newcm->id = time();
        $newcm->course = $course->id;
        $newcm->instance = 0;
        $newcm->section = $sectionid;
        $newcm->idnumber = "all-i-do-is-win";
        $newcm->visible = 1;
        $newcm->visibleold = 1;

        $cloned = clone $newcm;
        $id = add_course_module($newcm);

        // Start asserting the behaviour of the function
        $this->assertObjectNotHasAttribute('id', $newcm);
        $this->assertObjectHasAttribute('added', $newcm);

        // Start asserting against the database
        $cm = $DB->get_record('course_modules', ['id' => $id], '*', MUST_EXIST);
        $this->assertEquals($cloned->course, $cm->course);
        $this->assertEquals($cloned->instance, $cm->instance);
        $this->assertEquals($cloned->instance, $cm->instance);
        $this->assertEquals($cloned->section, $cm->section);
        $this->assertEquals($cloned->idnumber, $cm->idnumber);
        $this->assertEquals($cloned->visible, $cm->visible);
        $this->assertEquals($cloned->visibleold, $cm->visibleold);
    }

    /**
     * Get the module id
     * @param string $modulename
     * @return int
     */
    private function get_module_id(string $modulename): int {
        global $DB;
        return $DB->get_field('modules', 'id', ['name' => $modulename], MUST_EXIST);
    }
}