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
namespace mod_contentmarketplace\testing;

use coding_exception;
use container_course\course;
use container_course\module\course_module;
use core\orm\query\builder;
use core\testing\mod_generator;
use core_container\factory;
use stdClass;
use totara_contentmarketplace\learning_object\abstraction\metadata\model;
use totara_contentmarketplace\model\course_module_source;
use totara_contentmarketplace\testing\generator as totara_contentmarketplace_generator;

class generator extends mod_generator {
    /**
     * @param stdClass|array $record
     * @param array|null     $options
     *
     * @return stdClass
     */
    public function create_instance($record = null, array $options = null): stdClass {
        if (is_array($record)) {
            $record = (object) $record;
        }

        // Clear the pass by references.
        $record = clone $record;
        $options = $options ?? [];

        if (!property_exists($record, 'modulename')) {
            $record->modulename = 'contentmarketplace';
        }

        if (!property_exists($record, 'section')) {
            $record->section = 0;
        }

        if (!property_exists($record, 'introformat')) {
            $record->introformat = FORMAT_HTML;
        }

        if (!property_exists($record, 'learning_object_marketplace_component')) {
            // Default to linkedin learning, for test environment, if it is not provided.
            $record->learning_object_marketplace_component = 'contentmarketplace_linkedin';
        }

        if (!property_exists($record, 'learning_object_id')) {
            $marketplace_generator = totara_contentmarketplace_generator::instance();
            $marketplace_component = $record->learning_object_marketplace_component;

            $learning_object = $marketplace_generator->create_learning_object($marketplace_component, $record->name ?? null);
            $record->learning_object_id = $learning_object->get_id();
        }

        return parent::create_instance($record, $options);
    }

    /**
     * Functions to generate a content marketplace instance from the behat system.
     * What it does is to generate a learning object record and then map with the
     * generated content marketplace activity record.
     *
     * @param array $data
     * @return stdClass
     */
    public function create_content_marketplace_instance(array $data = []): stdClass {
        if (!isset($data['course'])) {
            throw new coding_exception(
                "Missing either of the required fields: ['course', 'marketplace_component']"
            );
        }

        if (isset($data['learning_object']) && $data['learning_object'] instanceof model) {
            $learning_object = $data['learning_object'];
        } else if (isset($data['name'], $data['marketplace_component'])) {
            $learning_object = totara_contentmarketplace_generator::instance()->create_learning_object(
                $data['marketplace_component'],
                $data['name']
            );
        } else {
            throw new coding_exception(
                "Must specify either ['learning_object'] model instance, or ['marketplace_component', 'name']"
            );
        }

        $db = builder::get_db();

        $course = $data['course'];
        if (is_object($course)) {
            $course = factory::from_record($course);
        } else if (is_numeric($course)) {
            $course = factory::from_id($course);
        } else {
            $course_record = $db->get_record('course', ['shortname' => $data['course']], '*', MUST_EXIST);
            $course = factory::from_record($course_record);
        }

        /** @var course $course */
        $module = $this->create_instance([
            'course' => $course->id,
            'section' => 0,
            'learning_object_id' => $learning_object->get_id(),
            'learning_object_marketplace_component' => $learning_object::get_marketplace_component(),
            'intro' => $data['intro'] ?? null,
            'introformat' => $data['introformat'] ?? FORMAT_HTML,
        ]);

        if ('singleactivity' === $course->format) {
            $course_format = course_get_format($course->to_record());
            $course_format->update_course_format_options(['activitytype' => 'contentmarketplace']);

            $course->rebuild_cache();
        }

        // Create course module source.
        course_module_source::create(course_module::from_id($module->cmid), $learning_object);

        return $module;
    }
}