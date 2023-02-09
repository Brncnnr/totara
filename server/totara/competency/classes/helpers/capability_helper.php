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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\helpers;

use context;
use context_user;
use core\entity\user;
use moodle_exception;
use pathway_manual\models\roles\appraiser;
use totara_competency\models\assignment;
use totara_competency\entity\assignment as assignment_entity;

defined('MOODLE_INTERNAL') || die();

/**
 * This class abstracts some logic for checking competency related capabilities, mainly to deal with the special
 * treatment that the appraiser role requires.
 *
 * @package totara_competency\helpers
 */
class capability_helper {

    /**
     * Make sure the currently logged in user has permission to view the competency profile for the given user.
     *
     * @param int $for_user_id
     * @param context|null $context
     * @throws moodle_exception
     */
    public static function require_can_view_profile(int $for_user_id, ?context $context = null) {
        if (!static::can_view_profile($for_user_id, $context)) {
            $exception_info = ($for_user_id == user::logged_in()->id)
                ? 'competency:view_own_profile'
                : 'competency:view_other_profile';
            throw new moodle_exception('nopermissions', '', '', get_string($exception_info, 'totara_competency'));
        }
    }

    /**
     * Find out if the currently logged in user has permission to view the competency profile for the given user.
     *
     * @param mixed $for_user_id
     * @param context|null $context
     * @return bool
     */
    public static function can_view_profile($for_user_id, ?context $context = null) {
        if (!$for_user_id) {
            return false;
        }
        $for_user_id = intval($for_user_id);
        if (is_null($context)) {
            // Take into consideration that subject user may be deleted.
            $context = context_user::instance($for_user_id, IGNORE_MISSING);
            if (!$context) {
                return false;
            }
        }
        if ($for_user_id == user::logged_in()->id) {
            return has_capability('totara/competency:view_own_profile', $context);
        } else {
            return has_capability('totara/competency:view_other_profile', $context)
                || appraiser::has_for_user($for_user_id);
        }
    }

    /**
     * Make sure the currently logged in user has permission to assign competencies for the given user.
     *
     * @param int $for_user_id
     * @param context|null $context
     * @throws moodle_exception
     */
    public static function require_can_assign(int $for_user_id, ?context $context = null) {
        if (!static::can_assign($for_user_id, $context)) {
            $exception_info = ($for_user_id == user::logged_in()->id)
                ? 'competency:assign_self'
                : 'competency:assign_other';
            throw new moodle_exception('nopermissions', '', '', get_string($exception_info, 'totara_competency'));
        }
    }

    /**
     * Find out if the currently logged in user has permission to assign competencies for the given user.
     *
     * @param mixed $for_user_id
     * @param context|null $context
     * @return bool
     */
    public static function can_assign($for_user_id, ?context $context = null) {
        if (!$for_user_id) {
            return false;
        }
        $for_user_id = intval($for_user_id);
        if (is_null($context)) {
            $context = context_user::instance($for_user_id);
        }
        if ($for_user_id == user::logged_in()->id) {
            return has_capability('totara/competency:assign_self', $context);
        } else {
            return has_capability('totara/competency:assign_other', $context)
                || appraiser::has_for_user($for_user_id);
        }
    }

    /**
     * Checks if user can archive a competency assignment.
     *
     * @param int $user_id
     * @param int|assignment $assignment
     *
     * @return bool
     */
    public static function can_user_archive_assignment(int $user_id, $assignment): bool {
        if (is_int($assignment)) {
            $assignment = assignment::load_by_id($assignment);
        }

        if (!$assignment instanceof assignment) {
            throw new \coding_exception('Accepting only assignment models.');
        }

        if (has_capability('totara/competency:manage_assignments', \context_system::instance())) {
            return true;
        }

        if (in_array($assignment->get_type(), [assignment_entity::TYPE_SELF, assignment_entity::TYPE_OTHER], true)) {
            $assigned_by_manager = $assignment->get_type() === assignment_entity::TYPE_OTHER;
            $is_assigned_user = (int)$assignment->user_group_id === $user_id;

            if ($is_assigned_user && $assigned_by_manager) {
                return false;
            }
            return self::can_assign($assignment->user_group_id);
        }

        return false;
    }
}
