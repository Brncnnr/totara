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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

use totara_reportbuilder\template_helper;
use totara_core\advanced_feature;

class totara_reportbuilder_template_helper_testcase extends advanced_testcase {

    public function test_templates() {
        // Certifications enabled (by default).
        template_helper::reset_caches();
        $templates = template_helper::get_templates();

        $found = false;
        foreach ($templates as $classname) {
            if ($classname === 'totara_certification\rb\template\learner_certification_completion') {
                $found = true;
                break;
            }
        }
        self::assertTrue($found);

        // Certifications disabled.
        advanced_feature::disable('certifications');
        template_helper::reset_caches();
        $templates = template_helper::get_templates();

        $found = false;
        foreach ($templates as $classname) {
            if ($classname === 'totara_certification\rb\template\learner_certification_completion') {
                $found = true;
                break;
            }
        }
        self::assertTrue($found); // Still found! See get_templates for details.
    }

    public function test_template_groups() {
        // Certifications enabled (by default).
        template_helper::reset_caches();
        $template_groups = template_helper::get_template_groups();

        $found = false;
        foreach ($template_groups as $group_label => $templates) {
            if (isset($templates['totara_certification\rb\template\learner_certification_completion'])) {
                $found = true;
                break;
            }
        }
        self::assertTrue($found);

        // Certifications disabled.
        advanced_feature::disable('certifications');
        template_helper::reset_caches();
        $template_groups = template_helper::get_template_groups();

        $found = false;
        foreach ($template_groups as $group_label => $templates) {
            if (isset($templates['totara_certification\rb\template\learner_certification_completion'])) {
                $found = true;
                break;
            }
        }
        self::assertFalse($found);
    }
}