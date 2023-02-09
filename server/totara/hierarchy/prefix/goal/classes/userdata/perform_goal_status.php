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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package hierarchy_goal
 */

namespace hierarchy_goal\userdata;

use core_text;
use core\orm\entity\repository;
use hierarchy_goal\models\company_goal_perform_status as company_goal_perform_status_model;
use hierarchy_goal\models\personal_goal_perform_status as personal_goal_perform_status_model;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Base class to do GDPR related processing for goal statuses changed through
 * perform activities.
 */
abstract class perform_goal_status extends item {
    /**
     * {@inheritdoc}
     */
    public static function get_main_component() {
        return 'hierarchy_goal';
    }

    /**
     * {@inheritdoc}
     */
    public static function is_purgeable(int $userstatus) {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public static function is_exportable() {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected static function export(target_user $user, \context $context) {
        $query = static::goal_status_query($user->id)
            ->order_by('id');

        $data = [];
        foreach ($query->get() as $goal_status_change) {
            if ($goal_status_change->goal_id !== null) {
                /** @var company_goal_perform_status_model $perform_status_model */
                $perform_status_model = company_goal_perform_status_model::load_by_entity($goal_status_change);
                $company_goal_name = core_text::entities_to_utf8(format_string($goal_status_change->company_goal->shortname));
                $personal_goal_name = null;
            } else {
                /** @var personal_goal_perform_status_model $perform_status_model */
                $perform_status_model = personal_goal_perform_status_model::load_by_entity($goal_status_change);
                $company_goal_name = null;
                $personal_goal_name = core_text::entities_to_utf8(format_string($goal_status_change->personal_goal->name));
            }

            $scale_name = $goal_status_change->scale_value_id
                ? core_text::entities_to_utf8(format_string($perform_status_model->scale_value->name))
                : null;

            $export =  [
                'id' => (int) $goal_status_change->id,
                'user_id' => (int)$goal_status_change->user_id,
                'activity_name' => core_text::entities_to_utf8(format_string($perform_status_model->activity->name)),
                'company_goal_name' => $company_goal_name,
                'personal_goal_name' => $personal_goal_name,
                'scale_value_name' => $scale_name,
                'status_changer_id' => (int)$perform_status_model->status_changer_user_id,
                'status_changer_relationship' => $perform_status_model->status_changer_role,
                'created_at' => (int)$goal_status_change->created_at,
            ];

            $data[] = $export;
        }

        $export = new export();
        $export->data = [static::get_name() => $data];
        return $export;
    }

    /**
     * {@inheritdoc}
     */
    public static function is_countable() {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected static function count(target_user $user, \context $context) {
        return static::goal_status_query($user->id)->count();
    }

    /**
     * Formulates the base perform goal status change repository to get all the
     * data needed for processing.
     *
     * @param int $user_id the user whose perform activity ratings are to be
     *        returned.
     *
     * @return repository the primed repository.
     */
    abstract protected static function goal_status_query(int $user_id): repository;
}