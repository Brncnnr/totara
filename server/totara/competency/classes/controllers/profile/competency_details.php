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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\controllers\profile;

use pathway_manual\controllers\rate_competencies;
use totara_competency\aggregation_users_table;
use totara_hierarchy\entity\competency;
use totara_mvc\tui_view;

class competency_details extends base {

    public function action() {
        $competency_id = $this->get_required_param('competency_id', PARAM_INT);

        $this->set_url(
            '/totara/competency/profile/details/index.php',
            [
                'user_id' => $this->user->id,
                'competency_id' => $competency_id
            ]
        );

        $competency = competency::repository()->find($competency_id);
        if ($competency) {
            $title = get_string('competencydetails_competencyname', 'totara_hierarchy', format_string($competency->fullname));
        } else {
            $title = get_string('competencydetails', 'totara_hierarchy');
        }

        // Add breadcrumbs.
        $this->add_navigation($title);

        if (!$competency) {
            throw new \moodle_exception('competency_does_not_exist', 'totara_competency', $this->get_profile_url());
        }

        $show_activity_log_by_default = $this->get_optional_param('show_activity_log', null, PARAM_INT) == 1;

        $props = [
            'user-id'                      => $this->user->id,
            'is-mine'                      => $this->is_for_current_user(),
            'competency-id'                => $competency_id,
            'base-url'                     => (string)$this->get_base_url(),
            'go-back-link'                 => (string)$this->get_profile_url(),
            'go-back-text'                 => $this->get_back_to_profile_text(),
            'show-activity-log-by-default' => $show_activity_log_by_default,
            'toast-message'                => rate_competencies::get_toast_message_from_url(),
            'has-pending-aggregation'      => (new aggregation_users_table())->has_pending_aggregation(
                $this->user->id,
                $competency_id
            ),
        ];

        return tui_view::create('totara_competency/pages/CompetencyDetail', $props)
            ->set_title($title);
    }
}
