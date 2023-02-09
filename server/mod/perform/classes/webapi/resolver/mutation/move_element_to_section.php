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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\mutation;

use coding_exception;
use core\orm\query\exceptions\record_not_found_exception;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\mutation_resolver;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\section;
use mod_perform\models\activity\section_element;
use mod_perform\entity\activity\section_element as section_element_entity;
use mod_perform\webapi\middleware\require_activity;
use mod_perform\webapi\middleware\require_manage_capability;

class move_element_to_section extends mutation_resolver {

    /**
     * This moves an element to a different section
     *
     * Currently, the target section must belong to the same activity as the source section.
     * In future, this criteria could be relaxed, but we'd need to add require_manage_capability and
     * is_active checks to the target activity.
     *
     * {@inheritdoc}
     */
    public static function resolve(array $args, execution_context $ec) {
        global $DB;

        // The require_activity middleware loads the activity and passes it along via the args
        /** @var activity $activity */
        $activity = $args['activity'];

        // Do not eager-load section_elements.
        /** @var section_element_entity $source_section_element_entity */
        $source_section_element_entity = section_element_entity::repository()
            ->where('element_id', $args['input']['element_id'])
            ->where('section_id', $args['input']['source_section_id'])
            ->with('section')
            ->with('element')
            ->one();

        if (empty($source_section_element_entity)) {
            throw new coding_exception('Element does not exist or does not belong to source section');
        }

        $source_section_element = section_element::load_by_entity($source_section_element_entity);
        $source_section = section::load_by_entity($source_section_element_entity->section);

        try {
            $target_section = section::load_by_id($args['input']['target_section_id']);
        } catch (record_not_found_exception $exception) {
            throw new coding_exception('Target section does not exist');
        }

        if ($target_section->activity_id != $source_section->activity_id) {
            throw new coding_exception('Element cannot be moved to a section belonging to a different activity');
        }

        if ($target_section->id == $source_section->id) {
            throw new coding_exception('Element must be moved to a section other than its current section');
        }

        if ($activity->is_active()) {
            throw new coding_exception('Element cannot be moved if activity is active');
        }

        $source_section_element->move_to_section($target_section);

        return ['source_section_elements' => $source_section->section_elements];
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('performance_activities'),
            require_activity::by_section_id('input.source_section_id', true),
            require_manage_capability::class
        ];
    }
}