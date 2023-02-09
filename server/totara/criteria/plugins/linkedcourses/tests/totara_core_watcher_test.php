<?php
/*
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package criteria_linkedcourses
 */

use core\hook\admin_setting_changed;
use totara_competency\linked_courses;
use totara_core\advanced_feature;
use totara_criteria\entity\criterion as criterion_entity;

/**
 * @group totara_competency
 */
class criteria_linkedcourses_totara_core_watcher_testcase extends advanced_testcase {

    public function test_admin_settings_changed() {
        set_config('enablecompletion', 1);

        for ($course_idx = 1; $course_idx <= 3; $course_idx++) {
            $courses[$course_idx] = $this->getDataGenerator()->create_course(['enablecompletion' => true]);
        }

        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $competency = [
            'A' => $competency_generator->create_competency('Comp A'),
            'B' => $competency_generator->create_competency('Comp B'),
        ];

        linked_courses::set_linked_courses($competency['A']->id, [
            ['id' => $courses[1]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
            ['id' => $courses[2]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
        ]);

        linked_courses::set_linked_courses($competency['B']->id, [
            ['id' => $courses[1]->id, 'linktype' => linked_courses::LINKTYPE_OPTIONAL],
            ['id' => $courses[3]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
        ]);

        /** @var \totara_criteria\testing\generator $criteria_generator */
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');
        $criteria = [
            'A' => $criteria_generator->create_linkedcourses(['competency' => $competency['A']->id]),
            'B' => $criteria_generator->create_linkedcourses(['competency' => $competency['B']->id]),
        ];

        $on_disk = criterion_entity::repository()
            ->where('valid', 1)
            ->count();
        $this->assertSame(2, $on_disk);

        // First disable something else
        (new admin_setting_changed('whatever', advanced_feature::DISABLED, advanced_feature::ENABLED))->execute();

        $on_disk = criterion_entity::repository()
            ->where('valid', 1)
            ->count();
        $this->assertSame(2, $on_disk);

        // Now disable completion
        // We need to disable the setting as well as generate the event to simulate what actually happens
        set_config('enablecompletion', 0);
        (new admin_setting_changed('enablecompletion', advanced_feature::ENABLED, advanced_feature::DISABLED))->execute();

        $on_disk = criterion_entity::repository()
            ->where('valid', 1)
            ->count();
        $this->assertSame(0, $on_disk);

        $on_disk = criterion_entity::repository()
            ->where('valid', 0)
            ->count();
        $this->assertSame(2, $on_disk);


        // And enable it again
        set_config('enablecompletion', 1);
        (new admin_setting_changed('enablecompletion', advanced_feature::DISABLED, advanced_feature::ENABLED))->execute();

        $on_disk = criterion_entity::repository()
            ->where('valid', 1)
            ->count();
        $this->assertSame(2, $on_disk);

        $on_disk = criterion_entity::repository()
            ->where('valid', 0)
            ->count();
        $this->assertSame(0, $on_disk);


        // Check nothing happens if the setting is set to the same value
        (new admin_setting_changed('enablecompletion', advanced_feature::DISABLED, advanced_feature::ENABLED))->execute();

        $on_disk = criterion_entity::repository()
            ->where('valid', 1)
            ->count();
        $this->assertSame(2, $on_disk);

        $on_disk = criterion_entity::repository()
            ->where('valid', 0)
            ->count();
        $this->assertSame(0, $on_disk);
    }

}
