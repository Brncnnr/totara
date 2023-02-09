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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\mutation;

use coding_exception;
use core\orm\query\builder;
use core\orm\query\exceptions\record_not_found_exception;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\mutation_resolver;
use mod_perform\hook\dto\pre_deleted_dto;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\section;
use mod_perform\section_relationship_deletion_exception;
use mod_perform\webapi\middleware\require_activity;
use mod_perform\webapi\middleware\require_manage_capability;

class update_section_settings extends mutation_resolver {
    /**
     * This updates the list of relationships for a specified section.
     *
     * {@inheritdoc}
     */
    public static function resolve(array $args, execution_context $ec) {
        // The require_activity middleware loads the activity and passes it along via the args
        /** @var activity $activity */
        $activity = $args['activity'];
        $input = $args['input'];

        try {
            $section = section::load_by_id($input['section_id']);
        } catch (record_not_found_exception $e) {
            throw new coding_exception('Specified section id does not exist');
        }

        if ($activity->is_active()) {
            throw new coding_exception('Can\'t update section settings on an active activity.');
        }

        // We want the title be updated even if the relationship could not be updated
        if (isset($input['title'])) {
            $section->update_title($input['title']);
        }

        $transaction = builder::get_db()->start_delegated_transaction();
        try {
            $result = ['section' => $section->update_relationships($input['relationships'])];

            $transaction->allow_commit();
        } catch (section_relationship_deletion_exception $exception) {
            // We don't want the changes to be saved and return a proper validation info result
            $transaction->rollback();

            $result = self::handle_validation_error($section, $exception);
        }

        return $result;
    }

    /**
     * Creates result based on validation error
     *
     * @param section $section
     * @param section_relationship_deletion_exception $exception
     * @return array
     */
    private static function handle_validation_error(section $section, section_relationship_deletion_exception $exception): array {
        $description = '';
        $data = null;
        if ($exception->get_additional_data() instanceof pre_deleted_dto) {
            $description = $exception->get_additional_data()->get_description();
            $data = $exception->get_additional_data()->get_data();
        }

        $validation_result = [
            'title' => get_string('modal_can_not_delete_relationship_title', 'mod_perform'),
            'can_delete' => false,
            'reason' => [
                "description" => $description,
                "data" => $data
            ],
        ];

        return [
            'section' => section::load_by_id($section->id),
            'validation_info' => $validation_result
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('performance_activities'),
            require_activity::by_section_id('input.section_id', true),
            require_manage_capability::class
        ];
    }
}