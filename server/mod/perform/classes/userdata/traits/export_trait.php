<?php
/*
 * This file is part of Totara Perform
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package mod_perform
 *
 */

namespace mod_perform\userdata\traits;

use coding_exception;
use core\orm\query\builder;
use mod_perform\entity\activity\element_response;
use mod_perform\models\activity\element_plugin;
use mod_perform\models\activity\participant_source;
use mod_perform\models\activity\respondable_element_plugin;
use mod_perform\userdata\helpers\userdata_file_helper;

/**
 * Trait export_trait
 * @package mod_perform\userdata\traits
 */
trait export_trait {
    /**
     * Can user data of this item data be purged from system?
     * @param int $userstatus target_user::STATUS_ACTIVE, target_user::STATUS_DELETED or target_user::STATUS_SUSPENDED
     * @return bool
     */
    public static function is_purgeable(int $userstatus): bool {
        return false;
    }

    /**
     * Can user data of this item be exported from the system?
     * @return bool
     */
    public static function is_exportable(): bool {
        return true;
    }

    /**
     * Can user data of this item be somehow counted?
     * How much data is there?
     * @return bool
     */
    public static function is_countable(): bool {
        return true;
    }

    /**
     * Is the given context level compatible with this item?
     * @return array
     */
    public static function get_compatible_context_levels(): array {
        return [
            CONTEXT_SYSTEM,
            CONTEXT_COURSECAT,
            CONTEXT_COURSE
        ];
    }

    /**
     * Process a single element_response into the correct export format.
     *
     * This method formats the response as required by the element and applies anonymous restrictions.
     *
     * @param element_response $response_record
     * @param int $user_id
     * @return array
     * @throws \coding_exception
     */
    protected static function process_response_record(element_response $response_record, int $user_id) {
        $record = $response_record->to_array();

        // Convert response data into actual answer.
        /** @var respondable_element_plugin $element_plugin */
        try {
            $element_plugin = element_plugin::load_by_plugin($record['element_type']);
        } catch (coding_exception $e) {
            // This means the plugin was removed or does not exist. So no response
            // can be returned.
            return [];
        }

        if ($element_plugin->get_plugin_name() == 'linked_review') {
            // Linked review elements are processed in their own custom handlers.
            return [];
        }

        if ($element_plugin->get_is_respondable()) {
            $record['response_data'] = $element_plugin->decode_response($record['response_data'], $record['element_data']);
        }

        // Anonymise responses if the activity is anonymous and response isn't from the current user.
        if ($record['anonymous_responses'] == 1 &&
            $record['participant_source'] == participant_source::INTERNAL &&
            $record['participant_id'] != $user_id) {
            unset($record['participant_id']);
        }

        unset($record['element_data']);
        unset($record['element_type']);
        return $record;
    }

    /**
     * Append any files that are part of the responses to the export object.
     *
     * @param int[] $response_ids
     * @return \stored_file[] Array of stored_files, keyed by the file ID
     */
    protected static function get_response_files(array $response_ids): array {
        $fs = get_file_storage();
        return builder::table('files')
            ->when(true, function (builder $builder) {
                userdata_file_helper::apply_respondable_element_file_restrictions($builder);
            })
            ->where_in('itemid', $response_ids)
            ->where('filename', '!=', '.') // Exclude directories
            ->get()
            ->map(function (object $file) use ($fs) {
                return $fs->get_file_instance($file);
            })
            ->all(true);
    }

}
