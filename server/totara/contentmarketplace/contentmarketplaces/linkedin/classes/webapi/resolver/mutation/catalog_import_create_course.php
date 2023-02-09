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
 * @author  Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

namespace contentmarketplace_linkedin\webapi\resolver\mutation;

use contentmarketplace_linkedin\config;
use contentmarketplace_linkedin\dto\course_creation_result;
use contentmarketplace_linkedin\entity\learning_object;
use totara_contentmarketplace\interactor\catalog_import_interactor;
use contentmarketplace_linkedin\task\create_course_delay_task;
use core\notification;
use core\webapi\execution_context;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use moodle_url;
use totara_contentmarketplace\course\course_builder;
use totara_contentmarketplace\exception\cannot_resolve_default_course_category;
use totara_contentmarketplace\webapi\middleware\require_content_marketplace;

class catalog_import_create_course extends mutation_resolver {
    /**
     * Note: the resolver functionality will queue up to $SESSION for the notification of the process.
     *       This can cause the race conditions problem with a low chances, it is not ideally, but at
     *       the moment, there is no common practice around this notification for navigated page.
     *
     * {@inheritdoc}
     */
    public static function resolve(array $args, execution_context $ec): course_creation_result {
        global $USER;

        $interactor = new catalog_import_interactor($USER->id);
        $interactor->require_add_course();

        $input_params = $args['input'];

        if (count($input_params) <= config::get_max_selected_items_number()) {
            // Creation on runtime here.
            return self::create_course_immediate($input_params, $interactor);
        }

        return self::queue_adhoc_task($input_params);
    }

    /**
     * The data structure of $input_params would be looking like:
     * $input_params = [
     *  [
     *      'learning_object_id' => 42,
     *      'category_id' => 496
     *  ]
     * ]
     *
     * @param array $input_params
     * @return course_creation_result
     */
    private static function queue_adhoc_task(array $input_params): course_creation_result {
        // Create course delay with adhoc task.
        create_course_delay_task::enqueue($input_params);

        notification::info(get_string('course_content_delay_creation', 'contentmarketplace_linkedin'));
        $result = new course_creation_result(true);
        $result->set_redirect_url(new moodle_url('/totara/catalog/index.php'));

        return $result;
    }

    /**
     * Passing a list of hashmap, which the hashmaps contains the learning_object_id and the category_id.
     * Which we would want to create the course out of learning_object and under the category.
     *
     * If category's id is not provided, then fallback to the default category's id that user is able to create.
     * The data structure of $input_params would be looking like:
     * $input_params = [
     *  [
     *      'learning_object_id' => 42,
     *      'category_id' => 496
     *  ]
     * ]
     *
     * @param array                     $input_params
     * @param catalog_import_interactor $interactor
     * @return course_creation_result
     */
    private static function create_course_immediate(array $input_params, catalog_import_interactor $interactor): course_creation_result {
        $redirect_url = new moodle_url('/totara/catalog/index.php', ['orderbykey' => 'time']);
        $mutation_result = new course_creation_result();

        // The list of learning objects that are failed to create a course out of.
        // If the list is empty, the process is success. Otherwise, there are either some or all failing.
        $failed_learning_objects = [];

        foreach ($input_params as $input_param) {
            $learning_object_id = $input_param['learning_object_id'];
            $category_id = $input_param['category_id'] ?? null;

            try {
                $course = course_builder::create_with_learning_object(
                    'contentmarketplace_linkedin',
                    $learning_object_id,
                    $interactor,
                    $category_id
                );

                $creation_result = $course->create_course_in_transaction();
                if ($creation_result->is_error()) {
                    // One course failed to be created.
                    $failed_learning_objects[] = $learning_object_id;
                }
            } catch (cannot_resolve_default_course_category $e) {
                // Cannot resolve the category id. However, we do not want to stop the process
                // here, as there are several other courses that can be added with the given category id(s).
                debugging($e->getMessage(), DEBUG_ALL);
                $failed_learning_objects[] = $learning_object_id;
            }
        }

        if (!empty($failed_learning_objects)) {
            // The process does not get to complete when create 50 or less courses.
            if (count($input_params) > count($failed_learning_objects)) {
                // Only a partial of 50 or less courses were completed within unsuccessful process.
                // Hence we yield error notification banner for the navigated page, with a list of learning object's
                // names that are failed to be created.
                $repository = learning_object::repository();
                $names = $repository->get_titles_of($failed_learning_objects);
                $names = implode(',', $names);

                notification::error(
                    get_string('content_creation_failure', 'contentmarketplace_linkedin', $names)
                );

                $mutation_result->set_redirect_url($redirect_url);
            } else {
                $mutation_result->set_message(
                    get_string('content_creation_failure_no_course', 'contentmarketplace_linkedin')
                );
            }
        } else {
            // Successful creation
            notification::success(get_string('course_content_immediate_creation', 'contentmarketplace_linkedin'));
            $mutation_result->set_successful(true);
            $mutation_result->set_redirect_url($redirect_url);
        }

        return $mutation_result;
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_content_marketplace('linkedin'),
        ];
    }
}