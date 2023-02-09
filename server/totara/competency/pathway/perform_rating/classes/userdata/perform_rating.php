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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package pathway_perform_rating
 */

namespace pathway_perform_rating\userdata;

use core_text;
use core\orm\entity\repository;

use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Base class to do GDPR related processing for competency ratings entered in
 * perform activities.
 */
abstract class perform_rating extends item {
    /**
     * {@inheritdoc}
     */
    public static function get_main_component() {
        return 'totara_competency';
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
        $query = static::rating_query($user->id)
            ->order_by('id')
            ->with(['competency', 'scale_value']);

        $data = [];
        foreach ($query->get() as $rating) {
            $scale_name = $rating->scale_value_id
                ? core_text::entities_to_utf8(format_string($rating->scale_value->name))
                : null;

            $export =  [
                'id' => (int) $rating->id,
                'user_id' => (int)$rating->user_id,
                'competency_name' => core_text::entities_to_utf8(format_string($rating->competency->fullname)),
                'scale_value_name' => $scale_name,
                'rater_user_id' => (int)$rating->rater_user_id,
                'created_at' => (int)$rating->created_at
            ];

            $activity = $rating->activity;
            if ($activity) {
                $export['activity_name'] = core_text::entities_to_utf8(format_string($activity->name));
            }

            $rater_relationship = $rating->rater_relationship;
            if ($rater_relationship) {
                $export['rater_relationship'] = $rater_relationship->idnumber;
            }

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
        return static::rating_query($user->id)->count();
    }

    /**
     * Formulates the base perform competency rating repository get all the
     * data needed for processing.
     *
     * @param int $user_id the user whose perform activity ratings are to be
     *        returned.
     *
     * @return repository the primed repository.
     */
    abstract protected static function rating_query(int $user_id): repository;
}