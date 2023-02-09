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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\models\activity_log;

use core\orm\entity\entity;
use totara_competency\entity\competency_achievement as competency_achievement_entity;
use totara_competency\models\activity_log;
use totara_competency\pathway;

class competency_achieved_via extends activity_log {

    /**
     * Load an instance of this model using data from the entity passed in.
     *
     * @param entity $entity
     * @return activity_log
     */
    public static function load_by_entity(entity $entity): activity_log {
        if (!($entity instanceof competency_achievement_entity)) {
            throw new \coding_exception('Invalid entity', 'Entity must be instance of competency_achievement');
        }

        return (new competency_achieved_via())->set_entity($entity);
    }

    /**
     * Timestamp of the date corresponding to this data.
     *
     * @return int
     */
    public function get_date(): int {
        return $this->get_entity()->time_created;
    }

    /**
     * Gets the human-readable description for an achieved_via type instance.
     *
     * @return string
     */
    public function get_description(): string {
        /** @var competency_achievement_entity $achievement */
        $scale_value = $this->get_entity()->value;

        if (!$scale_value) {
            return get_string('activity_log_rating_value_reset', 'totara_competency');
        }

        return get_string('activity_log_criteria_met', 'totara_competency', [
            'scale_value_name' => $scale_value->name,
            'criteria_met' => $this->join_criteria_met_strings(
                $this->get_unique_criteria_met_strings()
            ),
        ]);
    }

    /**
     * Get a comment string if the achievement details provide it.
     *
     * @return string
     */
    public function get_comment(): string {
        $comment_strings = [];
        // Theoretically, with more than one 'achieved_via', there can also be more than one comment, so account for that.
        foreach ($this->get_entity()->achieved_via as $via) {
            $comment_string = pathway::from_entity($via->pathway)
                ->get_achievement_detail()
                ->set_related_info((array) json_decode($via->related_info))
                ->get_comment();
            if (!empty($comment_string)) {
                $comment_strings[] = $comment_string;
            }
        }

        // Re-use the concatenation for the "criteria met" list.
        return $this->join_criteria_met_strings($comment_strings);
    }

    /**
     * Get the unique set of strings that describe how this competency achievement was reached.
     *
     * @return string[]
     */
    private function get_unique_criteria_met_strings(): array {
        $criteria_met = [];
        foreach ($this->get_entity()->achieved_via as $via) {
            $achievement_detail_strings = pathway::from_entity($via->pathway)
                ->get_achievement_detail()
                ->set_related_info((array) json_decode($via->related_info))
                ->get_achieved_via_strings();

            $criteria_met = array_merge($criteria_met, $achievement_detail_strings);
        }

        // Deliberately remove duplicate strings (since they don't add any extra info) and alphabetically order them.
        $criteria_met = array_unique($criteria_met);
        sort($criteria_met);
        return $criteria_met;
    }

    /**
     * Join multiple criteria met strings together with a separator defined in the language strings file.
     *
     * We need to use this function instead of simply using implode() as other languages can have different syntax
     * rules around how to separate entries in a list. In english we just separate each entry with a semicolon.
     *
     * @param string[] $strings
     * @return string
     */
    private function join_criteria_met_strings(array $strings): string {
        if (count($strings) < 2) {
            return reset($strings);
        }

        $string_one = array_shift($strings);
        $string_two = array_shift($strings);

        $joined_string = get_string('activity_log_criteria_met_multi_divider', 'totara_competency', [
            'critera_met_one' => $string_one,
            'critera_met_two' => $string_two,
        ]);

        if (empty($strings)) {
            return $joined_string;
        }

        // If there are more strings left, we join them with the original string recursively.
        return $this->join_criteria_met_strings(array_merge([$joined_string], $strings));
    }

}
