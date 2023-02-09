<?php
/*
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @category totara_catalog
 */

use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_catalog
 */
class totara_catalog_settings_hook_testcase extends advanced_testcase {
    public function test_turn_off_grid_catalog_observer() {
        global $DB, $CFG;
        require_once($CFG->libdir . '/adminlib.php');

        $this->setAdminUser();
        admin_get_root(true, true); // Fix random errors depending on test order.

        set_config('catalogtype', 'totara');
        // create a course
        $DB->delete_records('catalog');
        $this->getDataGenerator()->create_course();

        // create a program
        $program_generator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $program_generator->create_program();

        $this->assertSame(2, $DB->count_records('catalog'));

        // turn off grid catalog
        admin_write_settings(['s__catalogtype' => 'enhanced']);

        // check the result after event triggered
        $this->assertSame(0, $DB->count_records('catalog'));
    }

    public function test_tags_settings_changes() {
        global $DB, $CFG;
        require_once($CFG->libdir . '/adminlib.php');

        $this->setAdminUser();
        admin_get_root(true, true); // Fix random errors depending on test order.

        $DB->delete_records('catalog');
        $DB->delete_records('task_adhoc');

        // create a course
        $this->assertSame('1', $CFG->usetags);
        $DB->delete_records('task_adhoc');
        $course = $this->getDataGenerator()->create_course();

        core_tag_tag::add_item_tag(
            'core',
            'course',
            $course->id,
            context_system::instance(),
            'newtagname'
        );

        // trigger course update event to update catalog data
        $course_update_event = \core\event\course_updated::create(
            [
                'objectid' => $course->id,
                'context'  => context_system::instance(),
                'other'    => ['fulname' => 'newfullname'],
            ]
        );

        $course_update_event->trigger();
        $data = $DB->get_record('catalog', ['objecttype' => 'course']);
        $this->assertStringContainsString('newtagname', $data->ftsmedium);

        // turn off tags
        admin_write_settings(['s__usetags' => '0']);
        $this->assertSame('0', $CFG->usetags);

        $this->assertTrue($DB->record_exists('task_adhoc', ['classname' => '\totara_catalog\task\refresh_catalog_adhoc']));
        totara_catalog\cache_handler::reset_all_caches();
        ob_start();
        $this->executeAdhocTasks();
        ob_end_clean();

        // check the result after adhoc task completed
        $this->assertSame(1, $DB->count_records('catalog'));
        $data = $DB->get_record('catalog', ['objecttype' => 'course']);
        $this->assertStringNotContainsString('newtagname', $data->ftsmedium);
    }

    public function test_turn_off_certification_observer() {
        global $DB, $CFG;
        require_once($CFG->libdir . '/adminlib.php');

        $this->setAdminUser();
        admin_get_root(true, true); // Fix random errors depending on test order.

        $DB->delete_records('catalog');
        $DB->delete_records('task_adhoc');

        // create one course , one program and one certification
        set_config('enablecertifications', advanced_feature::ENABLED);
        $this->create_catalog_objects();

        $this->assertSame(1, $DB->count_records('catalog', ['objecttype' => 'certification']));

        // turn off certification
        admin_write_settings(['s__enablecertifications' => advanced_feature::DISABLED]);

        // check the result after event triggered
        $this->assertSame(0, $DB->count_records('catalog', ['objecttype' => 'certification']));
        $this->assertSame(2, $DB->count_records('catalog'));
    }

    public function test_turn_on_certification_observer() {
        global $DB, $CFG;
        require_once($CFG->libdir . '/adminlib.php');

        $this->setAdminUser();
        admin_get_root(true, true); // Fix random errors depending on test order.

        $DB->delete_records('catalog');
        $DB->delete_records('task_adhoc');

        // create one course , one program and one certification
        set_config('enablecertifications', advanced_feature::DISABLED);
        $this->create_catalog_objects();

        $this->assertSame(0, $DB->count_records('catalog', ['objecttype' => 'certification']));
        $this->assertSame(2, $DB->count_records('catalog'));

        // turn on certification
        admin_write_settings(['s__enablecertifications' => advanced_feature::ENABLED]);

        $this->assertTrue($DB->record_exists('task_adhoc', ['classname' => '\totara_catalog\task\provider_active_task']));
        totara_catalog\cache_handler::reset_all_caches();
        ob_start();
        $this->executeAdhocTasks();
        ob_end_clean();

        // check the result after adhoc task completed
        // check the result after adhoc task completed
        $this->assertSame(1, $DB->count_records('catalog', ['objecttype' => 'certification']));
        $this->assertSame(3, $DB->count_records('catalog'));
    }

    public function test_turn_off_program_observer() {
        global $DB, $CFG;
        require_once($CFG->libdir . '/adminlib.php');

        $this->setAdminUser();
        admin_get_root(true, true); // Fix random errors depending on test order.

        $DB->delete_records('catalog');
        $DB->delete_records('task_adhoc');

        // create one course , one program and one certification
        set_config('enableprograms', advanced_feature::ENABLED);
        $this->create_catalog_objects();

        $this->assertSame(1, $DB->count_records('catalog', ['objecttype' => 'program']));
        $this->assertSame(3, $DB->count_records('catalog'));

        // turn off programs
        admin_write_settings(['s__enableprograms' => advanced_feature::DISABLED]);

        // check the result after event triggered
        $this->assertSame(0, $DB->count_records('catalog', ['objecttype' => 'program']));
        $this->assertSame(2, $DB->count_records('catalog'));
    }

    public function test_turn_on_program_observer() {
        global $DB, $CFG;
        require_once($CFG->libdir . '/adminlib.php');

        $this->setAdminUser();
        admin_get_root(true, true); // Fix random errors depending on test order.

        $DB->delete_records('catalog');
        $DB->delete_records('task_adhoc');

        // create one course , one program and one certification
        set_config('enableprograms', advanced_feature::DISABLED);
        $this->create_catalog_objects();

        $this->assertSame(0, $DB->count_records('catalog', ['objecttype' => 'program']));
        $this->assertSame(2, $DB->count_records('catalog'));

        // turn on programs
        admin_write_settings(['s__enableprograms' => advanced_feature::ENABLED]);

        $this->assertTrue($DB->record_exists('task_adhoc', ['classname' => '\totara_catalog\task\provider_active_task']));
        totara_catalog\cache_handler::reset_all_caches();
        ob_start();
        $this->executeAdhocTasks();
        ob_end_clean();

        // check the result after adhoc task completed
        $this->assertSame(1, $DB->count_records('catalog', ['objecttype' => 'program']));
        $this->assertSame(3, $DB->count_records('catalog'));
    }

    private function create_catalog_objects() {

        // create a program
        $program_generator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $program_generator->create_program();

        // create a course
        $this->getDataGenerator()->create_course();

        // create a certification
        $program_generator->create_certification();
    }
}
