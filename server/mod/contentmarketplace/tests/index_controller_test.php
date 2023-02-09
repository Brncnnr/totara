<?php
/**
 * This file is part of Totara Core
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
 * @package mod_contentmarketplace
 */
use core_phpunit\testcase;
use mod_contentmarketplace\controllers\index;
use mod_contentmarketplace\event\course_module_instance_list_viewed;
use totara_mvc\tui_view;
use mod_contentmarketplace\testing\generator;

/**
 * @group totara_contentmarketplace
 */
class mod_contentmarketplace_index_controller_testcase extends testcase {
    /**
     * @return void
     */
    public function test_view_listing_within_course_without_instance(): void {
        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();

        self::setAdminUser();

        $controller = new index($course_record->id);
        $event_sink = self::redirectEvents();

        self::assertEquals(0, $event_sink->count());
        self::assertEmpty($event_sink->get_events());

        ob_start();
        $controller->process();
        $content = ob_get_contents();
        ob_end_clean();

        $events = $event_sink->get_events();
        self::assertCount(1, $events);
        [$event] = $events;

        self::assertInstanceOf(course_module_instance_list_viewed::class, $event);
        $tui_view = new tui_view(
            'mod_contentmarketplace/pages/ContentMarketplaceModules',
            [
                'marketplace-records' => [],
                'heading' => $course_record->fullname,
            ]
        );

        $tui_view->set_title(get_string('modulenameplural', 'contentmarketplace'));
        self::assertEquals($tui_view->render(), $content);
    }

    /**
     * @return void
     */
    public function test_view_listing_within_course_with_instance(): void {
        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();

        $mod_generator = generator::instance();
        $module = $mod_generator->create_instance([
            'course' => $course_record->id,
            'learning_object_marketplace_component' => 'contentmarketplace_linkedin'
        ]);

        self::setAdminUser();
        $controller = new index($course_record->id);

        // Prevent the event to be fired.
        self::redirectEvents();

        ob_start();
        $controller->process();
        $content = ob_get_contents();
        ob_end_clean();

        $tui_view = new tui_view(
            'mod_contentmarketplace/pages/ContentMarketplaceModules',
            [
                'marketplace-records' => [
                    [
                        'cm_id' => $module->cmid,
                        'name' => $module->name,
                        'component_name' => get_string('pluginname', 'contentmarketplace_linkedin'),
                    ]
                ],
                'heading' => $course_record->fullname,
            ]
        );

        $tui_view->set_title(get_string('modulenameplural', 'contentmarketplace'));
        self::assertEquals($tui_view->render(), $content);
    }

    /**
     * @return void
     */
    public function test_view_listing_within_course_with_encoded_heading(): void {
        $generator = self::getDataGenerator();
        $course_record = $generator->create_course([
            'fullname' => 'Darth & Vader'
        ]);

        self::setAdminUser();
        $controller = new index($course_record->id);
        // Prevent the event to be fired.
        self::redirectEvents();

        ob_start();
        $controller->process();
        $content = ob_get_contents();
        ob_end_clean();

        $tui_view = new tui_view(
            'mod_contentmarketplace/pages/ContentMarketplaceModules',
            [
                'marketplace-records' => [],
                'heading' => 'Darth & Vader',
            ]
        );

        $tui_view->set_title(get_string('modulenameplural', 'contentmarketplace'));
        self::assertEquals($tui_view->render(), $content);
    }
}