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
 * @package pathway_manual
 */

namespace pathway_manual\userdata;

use core\orm\entity\repository;
use pathway_manual\entity\rating;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

abstract class manual_rating extends item {

    /**
     * Get main Frankenstyle component name (core subsystem or plugin).
     * This is used for UI purposes to group items into components.
     */
    public static function get_main_component() {
        return 'totara_competency';
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
        $query = static::rating_query($user->id)
            ->order_by('id')
            ->with(['competency', 'scale_value']);

        $data = [];
        foreach ($query->get() as $rating) {
            /** @var rating $rating */
            $data[] = [
                'id'               => (int) $rating->id,
                'competency_id'    => (int) $rating->competency->id,
                'competency_name'  => \core_text::entities_to_utf8(format_string($rating->competency->fullname)),
                'scale_value_id'   => (int) $rating->scale_value_id,
                'scale_value_name' => \core_text::entities_to_utf8(format_string($rating->scale_value->name)),
                'date_assigned'    => (int) $rating->date_assigned,
                'assigned_by'      => (int) $rating->assigned_by,
                'assigned_by_role' => \core_text::entities_to_utf8(format_string($rating->assigned_by_role)),
                'comment'          => \core_text::entities_to_utf8(format_string($rating->comment)),
            ];
        }

        $export = new export();
        $export->data = [static::get_name() => $data];
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
        return static::rating_query($user->id)->count();
    }

    /**
     * Manual rating repository query for what data set to perform actions on
     *
     * @param int $user_id
     * @return repository
     */
    abstract protected static function rating_query(int $user_id): repository;

}
