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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package performelement_static_element
 */

defined('MOODLE_INTERNAL') || die();

use container_perform\perform;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\element;
use mod_perform\models\activity\section_element;
use performelement_static_content\static_content;
use totara_webapi\phpunit\webapi_phpunit_helper;

require_once(__DIR__ . '/static_content_testcase.php');

/**
 * @group perform
 * @group perform_element
 */
class performelement_static_content_clone_testcase extends performelement_static_content_testcase {

    use webapi_phpunit_helper;

    public function test_clone(): void {
        $this->setAdminUser();

        // Generate some data.
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();
        $activity = $perform_generator->create_activity_in_container();
        $section = $perform_generator->create_section($activity);

        // Get element data.
        $data = $this->create_element_data();

        // Create element.
        $default_context = context_coursecat::instance(perform::get_default_category_id());
        $element = element::create(
            $default_context,
            'static_content',
            'test element 1 title',
            'test identifier',
            $data,
            true
        );

        // Link element to section.
        section_element::create($section, $element, 123);

        // Update the element, saving the files and so on.
        /** @var static_content $plugin */

        // Clone the activity.
        $new_activity = activity::load_by_id($activity->id)->clone();
        $sections = $new_activity->get_sections();

        // Confirm that all static content elements cloned correctly.
        $count = 0;
        foreach ($sections as $section) {
            $section_elements = $section->get_section_elements();
            foreach ($section_elements as $section_element) {
                $element = $section_element->get_element();
                if ($element->plugin_name === 'static_content') {
                    ++$count;
                    $data = json_decode($element->data, true);

                    // Confirm that the file URL has been rewritten.
                    $this->assertStringContainsString('@@PLUGINFILE@@/test_file.png', $data['wekaDoc']);

                    // Confirm that the draft area contains the image added to element content.
                    $fs = get_file_storage();
                    $file_exist = $fs->file_exists($new_activity->get_context_id(),
                        'performelement_static_content',
                        'content',
                        $element->id,
                        '/',
                        'test_file.png'
                    );
                    $this->assertEquals(true, $file_exist);
                }
            }
        }

        // We need to have found exactly 1 element.
        $this->assertEquals(1, $count, 'Incorrect amount of elements cloned');
    }

}