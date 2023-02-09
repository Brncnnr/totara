<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\userdata;

use core\orm\entity\repository;
use core\orm\query\builder;
use totara_competency\entity\competency_achievement;
use totara_competency\entity\pathway_achievement;
use totara_criteria\entity\criteria_item_record;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

class achievement extends item {

    /**
     * Returns sort order.
     *
     * @return int
     */
    public static function get_sortorder() {
        return 3; // 4th item of 6 in the 'Competencies' list.
    }

    /**
     * Can user data of this item data be purged from system?
     *
     * @param int $userstatus target_user::STATUS_ACTIVE, target_user::STATUS_DELETED or target_user::STATUS_SUSPENDED
     * @return bool
     */
    public static function is_purgeable(int $userstatus) {
        return true;
    }

    /**
     * Purge user data for this item.
     *
     * @param target_user $user
     * @param \context $context restriction for purging e.g., system context for everything, course context for purging one course
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, \context $context) {
        return builder::get_db()->transaction(function () use ($user) {
            foreach (self::get_tables() as $table) {
                $table->where('user_id', $user->id)->delete();
            }

            return self::RESULT_STATUS_SUCCESS;
        });
    }

    /**
     * Can user data of this item data be exported from the system?
     *
     * @return bool
     */
    public static function is_exportable() {
        return true;
    }

    /**
     * Export user data from this item.
     *
     * @param target_user $user
     * @param \context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return export result object
     */
    protected static function export(target_user $user, \context $context) {
        $export = new export();
        $export->data = [
            'criteria_items'       => static::export_criteria_records($user->id),
            'achievements'         => static::export_competency_achievements($user->id),
            'pathway_achievements' => static::export_pathway_achievements($user->id),
        ];
        return $export;
    }

    /**
     * Can user data of this item be somehow counted?
     *
     * @return bool
     */
    public static function is_countable() {
        return true;
    }

    /**
     * Count user data for this item.
     *
     * @param target_user $user
     * @param \context $context restriction for counting i.e., system context for everything and course context for course data
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, \context $context) {
        $count = 0;
        foreach (self::get_tables() as $table) {
            $count += $table->where('user_id', $user->id)->count();
        }
        return $count;
    }

    /**
     * @param int $user_id
     * @return array
     */
    private static function export_criteria_records(int $user_id): array {
        $criteria_records = criteria_item_record::repository()
            ->order_by('id')
            ->with('item.criterion')
            ->where('user_id', $user_id);

        $data = [];
        foreach ($criteria_records->get() as $record) {
            /** @var criteria_item_record $record */
            $data[] = [
                'id'                    => (int) $record->id,
                'criterion_item_id'     => (int) $record->criterion_item_id,
                'criterion_met'         => (bool) $record->criterion_met,
                'time_evaluated'        => (int) $record->timeevaluated,
                'time_achieved'         => (int) $record->timeachieved,
                'item_type'             => \core_text::entities_to_utf8(format_string($record->item->item_type)),
                'item_id'               => (int) $record->item->id,
                'criterion_id'          => (int) $record->item->criterion->id,
                'criterion_plugin_type' => \core_text::entities_to_utf8(format_string($record->item->criterion->plugin_type)),
            ];
        }
        return $data;
    }

    /**
     * @param int $user_id
     * @return array
     */
    private static function export_competency_achievements(int $user_id): array {
        $competency_achievements = competency_achievement::repository()
            ->order_by('id')
            ->with(['competency', 'value'])
            ->where('user_id', $user_id);

        $data = [];
        foreach ($competency_achievements->get() as $achievement) {
            /** @var competency_achievement $achievement */
            $data[] = [
                'id'               => (int) $achievement->id,
                'competency_id'    => (int) $achievement->competency_id,
                'competency_name'  => \core_text::entities_to_utf8(format_string($achievement->competency->fullname)),
                'assignment_id'    => (int) $achievement->assignment_id,
                'scale_value_id'   => (int) $achievement->value->id,
                'scale_value_name' => \core_text::entities_to_utf8(format_string($achievement->value->name)),
                'proficient'       => (bool) $achievement->proficient,
                'status'           => (int) $achievement->status,
                'time_created'     => (int) $achievement->time_created,
                'time_status'      => (int) $achievement->time_status,
                'time_proficient'  => (int) $achievement->time_proficient,
                'time_scale_value' => (int) $achievement->time_scale_value,
            ];
        }
        return $data;
    }

    /**
     * @param int $user_id
     * @return array
     */
    private static function export_pathway_achievements(int $user_id): array {
        $pathway_achievements = pathway_achievement::repository()
            ->order_by('id')
            ->with(['scale_value', 'pathway.competency'])
            ->where('user_id', $user_id);

        $data = [];
        foreach ($pathway_achievements->get() as $achievement) {
            /** @var pathway_achievement $achievement */
            $data[] = [
                'id'               => (int) $achievement->id,
                'pathway_id'       => (int) $achievement->pathway_id,
                'pathway_type'     => \core_text::entities_to_utf8(format_string($achievement->pathway->path_type)),
                'competency_id'    => (int) $achievement->pathway->competency->id,
                'competency_name'  => \core_text::entities_to_utf8(format_string($achievement->pathway->competency->fullname)),
                'scale_value_id'   => (int) $achievement->scale_value->id,
                'scale_value_name' => \core_text::entities_to_utf8(format_string($achievement->scale_value->name)),
                'last_aggregated'  => (int) $achievement->last_aggregated,
                'status'           => (int) $achievement->status,
            ];
        }
        return $data;
    }

    /**
     * @return repository[]
     */
    private static function get_tables(): array {
        return [
            criteria_item_record::repository(),
            competency_achievement::repository(),
            pathway_achievement::repository(),
        ];
    }

}
