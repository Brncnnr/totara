<?php
/**
 * This file is part of Totara Learn
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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

namespace contentmarketplace_linkedin\task;

use contentmarketplace_linkedin\config;
use contentmarketplace_linkedin\event\import_course_full_failure;
use contentmarketplace_linkedin\event\import_course_partial_failure;
use totara_contentmarketplace\interactor\catalog_import_interactor;
use core\entity\user;
use core\task\adhoc_task;
use core\task\manager as task_manager;
use null_progress_trace;
use progress_trace;
use text_progress_trace;
use totara_contentmarketplace\course\course_builder;
use totara_contentmarketplace\exception\cannot_resolve_default_course_category;

final class create_course_delay_task extends adhoc_task {

    /**
     * @var string
     */
    protected const COURSE_INPUT_ARRAY_KEY = 'course_input_array';

    /**
     * @var progress_trace
     */
    protected $trace;

    /**
     * create_course_delay_task constructor.
     * @param progress_trace|null $trace
     */
    public function __construct(?progress_trace $trace = null) {
        $this->set_component('contentmarketplace_linkedin');

        if (is_null($trace)) {
            // This is pretty bad, because there is no way we can set the progress_trace instance to
            // the adhoc task, without relying on the global constant PHPUNIT_TEST.
            if (!defined('PHPUNIT_TEST') || !PHPUNIT_TEST) {
                $this->trace = new text_progress_trace();
            } else {
                $this->trace = new null_progress_trace();
            }
        }
    }

    /**
     * @param array $input_params
     * $input_params = [
     *      [
     *          'learning_object_id' => 1,
     *          'category_id'        => 1
     *      ]
     * ]
     *
     * @return static
     */
    public static function enqueue(array $input_params): self {
        $task = new self();

        $task->set_userid(user::logged_in()->id);
        $task->set_custom_data([
            self::COURSE_INPUT_ARRAY_KEY => $input_params,
        ]);
        task_manager::queue_adhoc_task($task);
        return $task;
    }

    /**
     * @return void
     */
    public function execute() {
        $this->valid_custom_data_key();
        $course_input_array = $this->get_course_input_array();

        if (count($course_input_array) == 0) {
            $this->trace->output('No courses were queue up');
            return;
        }

        $result = $this->create_bulk_courses($course_input_array);

        if ($result) {
            $this->trace->output('Course creation completed');
            return;
        }

        $this->trace->output('Some courses could not be created');
    }

    /**
     * @return array
     */
    private function get_course_input_array(): array {
        $data = $this->get_custom_data();
        $course_input_array = $data->course_input_array;

        return array_map(function ($course_input) {
            if (is_array($course_input)) {
                return $course_input;
            }
            return (array) $course_input;
        }, $course_input_array);
    }

    /**
     * @param array $course_input_array
     * @return bool
     */
    private function create_bulk_courses(array $course_input_array): bool {
        $actor_id = $this->get_userid();

        // Flagging the number of course to be import before the array is being modified.
        $total_to_import = count($course_input_array);

        // A list of learning objects that were failed to be imported into course.
        // Note that this list will be a 2 dimensional array before it becomes a flat list.
        $failed_learning_objects = [];

        // Note that we are using count($course_input_array) instead of using $total_to_import, because
        // $course_input_array would be reduced from the array_splice hence the while loop will reach
        // to the point of exiting by itself.
        while (count($course_input_array) > 0) {
            $batch_array = array_splice($course_input_array, 0, config::get_max_selected_items_number());
            $partial_failed_learning_objects = $this->do_create_bulk_courses($batch_array);

            $failed_learning_objects[] = $partial_failed_learning_objects;
        }

        // Flatten the list.
        $failed_learning_objects = array_merge(...$failed_learning_objects);

        if ($total_to_import === count($failed_learning_objects)) {
            // All items are failed to import. Triggers event full failure.
            $event = import_course_full_failure::from_actor_id($actor_id);
            $event->trigger();
        } else if ($total_to_import > count($failed_learning_objects) && !empty($failed_learning_objects)) {
            // Partial of items are failed to import. Triggers event for partial failure.
            $event = import_course_partial_failure::from_list_of_learning_object_ids($failed_learning_objects, $actor_id);
            $event->trigger();
        }

        return empty($failed_learning_objects);
    }

    /**
     * Bulk creating the courses out of learning objects. Then returns the list of learning objects
     * that are failed to be created. If the list is empty then all the courses were created successfully.
     *
     * @param array $patch_array
     * @return int[]
     */
    private function do_create_bulk_courses(array $patch_array): array {
        $interactor = new catalog_import_interactor($this->get_userid());

        // The list of failed learning object's id. If this list is empty the whole process is completed
        // without any errors.
        $failed_learning_objects = [];

        foreach ($patch_array as $course_input) {
            $learning_object_id = $course_input['learning_object_id'];
            $category_id = $course_input['category_id'] ?? null;

            try {
                $course = course_builder::create_with_learning_object(
                    $this->get_component(),
                    $learning_object_id,
                    $interactor,
                    $category_id
                );

                $result = $course->create_course_in_transaction();
                if (!$result->is_successful()) {
                    $failed_learning_objects[] = $learning_object_id;
                }
            } catch (cannot_resolve_default_course_category $e) {
                $this->trace->output('There is a course that cannot be added with given category id.');
                $failed_learning_objects[] = $learning_object_id;
            }
        }

        return $failed_learning_objects;
    }

    /**
     * @return void
     */
    private function valid_custom_data_key(): void {
        $data = $this->get_custom_data();
        $keys = [self::COURSE_INPUT_ARRAY_KEY];

        foreach ($keys as $key) {
            if (!property_exists($data, $key)) {
                debugging("The custom data for the task does not have key '{$key}'");
                return;
            }
        }
    }
}