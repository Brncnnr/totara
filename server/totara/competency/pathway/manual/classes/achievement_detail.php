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
 * @package pathway_manual
 */

namespace pathway_manual;

use pathway_manual\entity\rating;
use totara_competency\base_achievement_detail;

class achievement_detail extends base_achievement_detail {

    /**
     * @inheritDoc
     */
    public function get_achieved_via_strings(): array {
        if (empty($this->related_info)) {
            return [];
        }

        $rating = new rating($this->related_info['rating_id']);
        $rater = $rating->assigned_by_user;
        if ($rater) {
            $string = get_string('activity_log_rating_by', 'pathway_manual', [
                'name' => fullname((object) $rater->to_array()),
                'role' => $rating->get_role()::get_display_name(),
            ]);
        } else {
            // The user data for the rater has been purged or user has been deleted.
            $role = get_string("role_{$rating->assigned_by_role}_prefix", 'pathway_manual');
            $string = get_string('activity_log_rating_by_removed', 'pathway_manual', $role);
        }

        return [$string];
    }

    /**
     * Return the manual rating's comment if it exists.
     *
     * @return string
     */
    public function get_comment(): string {
        if (empty($this->related_info)) {
            return '';
        }

        $comment = (new rating($this->related_info['rating_id']))->comment;
        return $comment ?? '';
    }

    /**
     * If a manual pathway value has been achieved, the corresponding rating record should be added here.
     * This will store the appropriate data to be used when processing the information on how a value was achieved.
     *
     * @param rating|null $rating Entity representation of this rating.
     */
    public function add_rating($rating) {
        if (!is_null($rating)) {
            $this->related_info['rating_id'] = $rating->id;
            $this->set_scale_value_id($rating->scale_value_id);
            $this->set_achieved_at($rating->date_assigned);
        }
    }
}