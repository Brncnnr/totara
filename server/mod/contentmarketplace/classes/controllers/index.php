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
namespace mod_contentmarketplace\controllers;

use container_course\course;
use context;
use context_course;
use core\format;
use core\webapi\formatter\field\string_field_formatter;
use core_container\factory;
use mod_contentmarketplace\event\course_module_instance_list_viewed;
use moodle_url;
use stdClass;
use totara_mvc\controller;
use totara_mvc\tui_view;

class index extends controller {
    /**
     * @var course
     */
    private $course;

    /**
     * index_controller constructor.
     * @param int|null $course_id
     */
    public function __construct(?int $course_id = null) {
        $course_id = $course_id ?? $this->get_required_param('id', PARAM_INT);
        $this->course = factory::from_id($course_id);

        $this->layout = 'incourse';
        $this->url = new moodle_url(
            '/mod/contentmarketplace/index.php',
            ['id' => $course_id]
        );

        parent::__construct();
    }

    /**
     * @return context_course
     */
    protected function setup_context(): context {
        return $this->course->get_context();
    }

    /**
     * @return tui_view
     */
    public function action(): tui_view {
        $records = $this->get_marketplace_records();
        $formatter = new string_field_formatter(format::FORMAT_PLAIN, $this->context);

        $view = new tui_view(
            'mod_contentmarketplace/pages/ContentMarketplaceModules',
            [
                'marketplace-records' => $records,
                'heading' => $formatter->format($this->course->fullname),
            ]
        );

        $view->set_title(get_string('modulenameplural', 'contentmarketplace'));
        return $view;
    }

    /**
     * Returns a list of array for display marketplace records.
     * The data returned should looks like below:
     * @example
     *         return [
     *              [
     *                  'cm_id' => 15,
     *                  'name' => 'Nine Tails',
     *                  'component_name' => 'Naruto Shippuden'
     *              ]
     *         ]
     *
     * @return array[]
     */
    private function get_marketplace_records(): array {
        $course_record = $this->course->to_record();
        $records = get_all_instances_in_course('contentmarketplace', $course_record);
        $string_manager = get_string_manager();
        $component_name = get_string('unknownname', 'moodle');

        return array_map(
            function (stdClass $record) use ($string_manager, $component_name): array {
                // Reassign variable will not affected the original $component_name.
                $marketplace_component = $record->learning_object_marketplace_component;

                if ($string_manager->string_exists('pluginname', $marketplace_component)) {
                    $component_name = $string_manager->get_string('pluginname', $marketplace_component);
                }

                return [
                    'cm_id' => (int) $record->coursemodule,
                    'name' => $record->name,
                    'component_name' => $component_name,
                ];
            },
            $records
        );
    }

    /**
     * @param string $action
     */
    public function process(string $action = '') {
        parent::process($action);

        // Trigger event after view.
        $event = course_module_instance_list_viewed::create([
            'context' => $this->course->get_context(),
        ]);

        $event->add_record_snapshot('course', $this->course->to_record());
        $event->trigger();
    }
}