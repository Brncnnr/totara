<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Michael Ivanov <michael.ivanov@totaralearning.com>
 * @package contentmarketplace
 */
namespace contentmarketplace_linkedin\webapi\resolver\mutation;

use coding_exception;
use contentmarketplace_linkedin\exception\section_not_found;
use core\notification;
use core\webapi\middleware\require_login;
use core_container\section\section;
use core_container\section\section_factory;
use moodle_url;
use container_course\course;
use contentmarketplace_linkedin\dto\add_activity_result;
use contentmarketplace_linkedin\exception\course_not_found;
use context_course;
use core\webapi\execution_context;
use core\webapi\mutation_resolver;
use core_container\factory;
use dml_missing_record_exception;
use totara_contentmarketplace\course\course_builder;
use totara_contentmarketplace\interactor\catalog_import_interactor;
use totara_contentmarketplace\webapi\middleware\require_content_marketplace;

/**
 * Mutation for class set_self_completion
 */
class catalog_import_add_activity extends mutation_resolver {
    /**
     *  @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec): add_activity_result {
        global $USER;

        if (!isset($args['learning_object_id']) || !isset($args['section_id'])) {
            throw new coding_exception('Missing required field');
        }

        $learning_object_id = $args['learning_object_id'];
        $section_id = $args['section_id'];

        try {
            $section = section_factory::from_id((int) $section_id);
        } catch (dml_missing_record_exception $exception) {
            throw new section_not_found((string) $section_id);
        }
        if (!$section instanceof section) {
            throw new section_not_found((string) $section_id);
        }
        $course_id = $section->get_container_id();
        /** @var course $course */
        try {
            $course = factory::from_id($course_id);
        } catch (dml_missing_record_exception $exception) {
            throw new course_not_found((string) $course_id);
        }
        if (!$course instanceof course) {
            throw new course_not_found((string) $course_id);
        }
        $course_context = context_course::instance($course_id);
        if (!$course_context instanceof context_course) {
            throw new course_not_found((string) $course_id);
        }

        $interactor = new catalog_import_interactor($USER->id);
        $interactor->require_add_activity_to_course($course_context);

        $courseBuilder = course_builder::create_with_learning_object_without_category(
            'contentmarketplace_linkedin',
            $learning_object_id,
            $interactor,
        );

        $result = $courseBuilder->add_activity_to_course($course, $section->get_section_number());

        $mutation_result = new add_activity_result();
        if (!$result->is_successful()) {
            $message = get_string('content_creation_failure_add_activity', 'contentmarketplace_linkedin');
            notification::error($message);
        } else {
            $mutation_result->set_successful(true);
            $message = get_string('content_creation_success_add_activity', 'contentmarketplace_linkedin');
            notification::success($message);
        }
        $mutation_result->set_message($message);
        $redirect_url = new moodle_url('/course/view.php', ['id' => $course->id]);
        $mutation_result->set_redirect_url($redirect_url);

        return $mutation_result;
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_content_marketplace('linkedin'),
        ];
    }
}