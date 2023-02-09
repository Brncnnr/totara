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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_contentmarketplace
 */
defined('MOODLE_INTERNAL') || die();

use mod_contentmarketplace\completion\condition;
use mod_contentmarketplace\local\helper;
use mod_contentmarketplace\model\content_marketplace;
use mod_contentmarketplace\output\content_marketplace_logo;
use totara_contentmarketplace\learning_object\factory;

/**
 * A callback function from course's API to create an instance of content
 * marketplace module.
 *
 * @param stdClass $record
 * @return int
 */
function contentmarketplace_add_instance(stdClass $record): int {
    $completion_condition = null;

    if (isset($record->completion) && isset($record->completion_condition)) {
        // This block of logic is following the logics from seminar, as if the completion is not enabled,
        // it falls back to the default value which is disabled for any criteria.
        if (COMPLETION_TRACKING_AUTOMATIC == $record->completion) {
            $completion_condition = $record->completion_condition;
        }
    }

    $content_marketplace = helper::create_content_marketplace(
        $record->course,
        $record->learning_object_id,
        $record->learning_object_marketplace_component,
        $completion_condition
    );

    return $content_marketplace->id;
}

/**
 * Render the content for content marketplace.
 *
 * @param cm_info $course_module
 * @return void
 */
function contentmarketplace_cm_info_view(cm_info $course_module): void {
    global $OUTPUT;

    $content_marketplace = content_marketplace::load_by_id($course_module->instance);
    $template = content_marketplace_logo::create_from_model($content_marketplace);

    $content = $OUTPUT->render($template);
    $course_module->set_after_link($content);
}

/**
 * Returns TRUE if we support such feature, FALSE for vice versa. Otherwise
 * NULL if it is unknown.
 *
 * @param string|mixed $feature
 * @return bool|null
 */
function contentmarketplace_supports($feature): ?bool {
    switch ($feature) {
        case FEATURE_BACKUP_MOODLE2:
        case FEATURE_COMPLETION_HAS_RULES:
        case FEATURE_MOD_INTRO:
            return true;

        case FEATURE_NO_VIEW_LINK:
            return false;

        default:
            // Everything is unknown for now.
            return null;
    }
}

/**
 * @param int $id
 * @return bool
 */
function contentmarketplace_delete_instance(int $id): bool {
    $content_marketplace = content_marketplace::load_by_id($id);

    return $content_marketplace->delete();
}

/**
 * A callback function from core, to update the database record.
 *
 * @param stdClass $content_marketplace
 *
 * @return bool
 */
function contentmarketplace_update_instance(stdClass $content_marketplace) {
    $data = [
        'name' => $content_marketplace->name,
        'intro' => $content_marketplace->intro,
        'introformat' => $content_marketplace->introformat,
    ];

    // If overall completion is not enabled, fall back to the default value which is disabled for any criteria.
    // This is consistent with other activity modules behaviour.
    if (COMPLETION_TRACKING_AUTOMATIC != $content_marketplace->completion) {
        $data['completion_condition'] = null;
    } else if (isset($content_marketplace->completion_condition)) {
        // If for any other reason completion condition is not supplied, skip updating it.
        $data['completion_condition'] = $content_marketplace->completion_condition;
    }

    // If the record does not exist, entity API will yield error. So no need to check the validity of record.
    helper::update_content_marketplace($content_marketplace->instance, $data);
    return true;
}

/**
 * Obtains the automatic completion state for this content marketplace activity based on any conditions
 * in content marketplace settings.
 *
 * @param stdClass $course Course
 * @param stdClass $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions).
 *                   This ignored for now.
 *
 * @return bool True if completed, false if not. (If no conditions, then return
 *              value depends on comparison type)
 */
function contentmarketplace_get_completion_state($course, $cm, $userid, $type): bool {
    if (empty($userid)) {
        return false;
    }

    $entity = content_marketplace::load_by_id($cm->instance);
    $resolver = factory::get_resolver($entity->learning_object_marketplace_component);

    if ($entity->completion_condition == condition::CONTENT_MARKETPLACE) {
        // On content marketplace condition - therefore check for existing record of database whether user had
        // completed this in LiL side or not.
        return $resolver->has_user_completed_on_marketplace_condition($userid, $entity->learning_object_id);
    }

    return false;
}
