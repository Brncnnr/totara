<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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

namespace totara_competency\controllers\assignment;

use core\output\notification;
use hierarchy_organisation\services\organisation;
use hierarchy_position\services\position;
use moodle_url;
use totara_competency\baskets\competency_basket;
use totara_competency\services\cohort;
use totara_competency\services\user;
use totara_competency\views;
use totara_core\basket\session_basket;
use totara_core\output\select_tree;

class save extends base {

    protected $admin_external_page_name = 'competency_assignment_create';

    protected $basket_key = 'totara_competency_create_assignment';

    protected $basket_keys = [
        'basket_users' => 'totara_competency_select_users',
        'basket_audiences' => 'totara_competency_select_audiences',
        'basket_positions' => 'totara_competency_select_positions',
        'basket_organisations' => 'totara_competency_select_organisations',
    ];

    protected $services = [
        'service_users' => 'totara_competency_user_index',
        'service_audiences' => 'totara_competency_cohort_index',
        'service_positions' => 'hierarchy_position_index',
        'service_organisations' => 'hierarchy_organisation_index',
        'service_create_assignments' => 'totara_competency_assignment_create_from_baskets',
        'service_update_basket' => 'totara_core_basket_update',
    ];

    public function action() {
        // Get competencies count
        $basket = new competency_basket($this->basket_key);
        $items = $basket->load();

        $view = new views\save('totara_competency/save', []);

        if (empty($items)) {
            $message = get_string('basket_empty_basket_can_not_proceed_creating_assignment', 'totara_competency');
            redirect(new moodle_url('/totara/competency/assignments/create.php'), $message, null, notification::NOTIFY_ERROR);
        }

        $basket_diff = $basket->sync();
        if (!empty($basket_diff)) {
            $message = get_string('error_competencies_out_of_sync', 'totara_competency', count($basket_diff));
            redirect(new moodle_url('/totara/competency/assignments/create.php'), $message, null, notification::NOTIFY_WARNING);
        }

        $total = 0;

        // Count all items in all user group baskets
        foreach ($this->basket_keys as $key => $basket_key) {
            $basket = new session_basket($basket_key);

            $count = count($basket->load());

            $total += $count;
            $this->basket_keys["{$key}_count"] = $count;
        }

        $template_data = array_merge(
            [
                'basket_competencies' => $this->basket_key,
                'count_string' => get_string('competencies_selected', 'totara_competency', count($items)),
                'count' => count($items),
                'user_groups_count' => $total,
                'user_groups' => $this->create_dropdown(),
                'user_group_types' => $this->preload_selected_users(),
            ],
            $this->basket_keys,
            $this->services
        );

        $view->set_data($template_data);

        return $view;
    }

    /**
     * Preload selected user groups for the template to avoid multiple web-service requests from JavaScript
     *
     * @return array
     */
    protected function preload_selected_users(): array {
        // We are going to preload items for selected user group types:

        return [
            [
                'type' => 'positions',
                'title' => get_string('positionplural', 'totara_hierarchy'),
                'items' => position::index(['basket' => $this->basket_keys['basket_positions']], 0, '', 'asc')['items'],
            ],
            [
                'type' => 'organisations',
                'title' => get_string('organisationplural', 'totara_hierarchy'),
                'items' => organisation::index(['basket' => $this->basket_keys['basket_organisations']], 0, '', 'asc')['items'],
            ],
            [
                'type' => 'users',
                'title' => get_string('individualplural', 'totara_core'),
                'items' => user::index(['basket' => $this->basket_keys['basket_users']], 0, '', 'asc')['items'],
            ],
            [
                'type' => 'audiences',
                'title' => get_string('cohorts', 'totara_cohort'),
                'items' => cohort::index(['basket' => $this->basket_keys['basket_audiences']], 0, '', 'asc')['items'],
            ]
        ];
    }

    /**
     * Create user group dropdown widget
     *
     * @return select_tree
     */
    protected function create_dropdown() {
        return select_tree::create(
            'user_groups',
            '',
            true,
            [
                (object)[
                    'name' => get_string('cohorts', 'totara_cohort'),
                    'key' => 'audiences',
                ],
                (object)[
                    'name' => get_string('positionplural', 'totara_hierarchy'),
                    'key' => 'positions',
                ],
                (object)[
                    'name' => get_string('organisationplural', 'totara_hierarchy'),
                    'key' => 'organisations',
                ],
                (object)[
                    'name' => get_string('individualplural', 'totara_core'),
                    'key' => 'users'
                ],
            ],
            null,
            true,
            false,
            get_string('action_add_user_groups', 'totara_competency'),
            true,
            true
        );
    }
}
