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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\controllers\activity;

use context;
use context_coursecat;
use core\entity\user;
use mod_perform\controllers\perform_controller;
use mod_perform\util;
use totara_core\relationship\relationship;
use totara_mvc\tui_view;

class user_activities_priority extends perform_controller {

    /**
     * @return int
     */
    protected function get_relationship_id_param(): int {
        return $this->get_required_param('relationship_id', PARAM_INT);
    }

    /**
     * @inheritDoc
     */
    protected function setup_context(): context {
        $category_id = util::get_default_category_id();
        return context_coursecat::instance($category_id);
    }

    /**
     * @return tui_view
     */
    public function action(): tui_view {
        $this->set_url(self::get_url());

        $current_user_id = user::logged_in()->id;

        $props = [
            'current-user-id' => $current_user_id,
            'user-activities-url' => (string)user_activities::get_base_url(),
            'view-activity-url' => (string)view_user_activity::get_url(),
            'relationship-id' => $this->get_relationship_id_param(),
            'relationship-name' => relationship::load_by_id($this->get_relationship_id_param())->name
        ];

        return self::create_tui_view('mod_perform/pages/UserActivitiesPriority', $props)
            ->set_title(get_string('user_activities_priority_page_title', 'mod_perform'));
    }

    /**
     * @inheritDoc
     */
    public static function get_base_url(): string {
        return '/mod/perform/activity/priority.php';
    }
}