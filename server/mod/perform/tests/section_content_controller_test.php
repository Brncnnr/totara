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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\controllers\activity\section_content;
use mod_perform\models\activity\activity;
use mod_perform\state\activity\active;

/**
 * @group perform
 */
class section_content_controller_testcase extends advanced_testcase {

    public function test_missing_capability(): void {
        $user = self::getDataGenerator()->create_user();

        $_POST['section_id'] = $this->create_activity()->get_sections()->first()->id;

        self::setUser($user);
        $this->expectException(required_capability_exception::class);
        (new section_content())->process();
    }

    public function test_bad_section_id(): void {
        self::setAdminUser();

        $_POST['section_id'] = - 1;
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Invalid section');
        (new section_content())->process();
    }

    public function test_context(): void {
        $activity = $this->create_activity();
        $_POST['section_id'] = $activity->get_sections()->first()->id;

        self::assertSame($activity->get_context(), (new section_content())->get_context());
    }

    public function test_props_single_section(): void {
        $activity = $this->create_activity();
        $section_id = $activity->get_sections()->first()->id;
        $_POST['section_id'] = $section_id;

        $view = (new section_content())->action();
        self::assertEquals('Content elements: ' . $activity->name, $view->get_title());
        self::assertEquals(self::get_expected_props($section_id, $activity, $activity->name), $view->get_data());
    }

    public function test_props_multi_section(): void {
        $activity = $this->create_activity(2);
        $sections = $activity->get_sections()->all();
        self::assertCount(2, $sections);

        foreach ($sections as $section) {
            $_POST['section_id'] = $section->id;
            $view = (new section_content())->action();
            self::assertEquals('Content elements: ' . $section->get_display_title(), $view->get_title());
            $props = $view->get_data();
            self::assertEquals(self::get_expected_props($section->id, $activity, $section->get_display_title()), $props);
        }
    }

    private static function get_expected_props(int $section_id, activity $activity, string $expected_title): array {
        return [
            'section-id' => $section_id,
            'activity-id' => $activity->id,
            'is-multi-section-active' => $activity->get_multisection_setting(),
            'title' => $expected_title,
            'activity-state' => [
                'code' => active::get_code(),
                'name' => active::get_name(),
                'display_name' => active::get_display_name(),
            ],
            'go-back-link' => [
                'url' => "https://www.example.com/moodle/mod/perform/manage/activity/edit.php?activity_id={$activity->id}",
                'text' => "Content ({$activity->name})",
            ],
            'activity-context-id' => $activity->context_id,
        ];
    }

    /**
     * @param int $num_sections
     * @return activity
     * @throws coding_exception
     */
    private function create_activity(int $num_sections = 1): activity {
        self::setAdminUser();

        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();
        $config = \mod_perform\testing\activity_generator_configuration::new()
            ->set_number_of_activities(1)
            ->set_number_of_sections_per_activity($num_sections);
        return $generator->create_full_activities($config)->first();
    }
}
