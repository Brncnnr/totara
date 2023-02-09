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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\controllers\activity;

use context;
use context_coursecat;
use core\entity\user;
use mod_perform\controllers\perform_controller;
use mod_perform\totara\menu\my_activities;
use mod_perform\util;
use totara_mvc\tui_view;

/**
 * This page lists activities that the user must select the users who will participate in.
 */
class user_activities_select_participants extends perform_controller {

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
        $this->get_page()->set_totara_menu_selected(my_activities::class);

        $props = [
            'current-user-id' => user::logged_in()->id,
            'user-activities-url' => user_activities::get_base_url(),
        ];

        return self::create_tui_view('mod_perform/pages/UserActivitiesSelectParticipants', $props)
            ->set_title(get_string('user_activities_select_participants_page_title', 'mod_perform'));
    }

    /**
     * @return string
     */
    public static function get_base_url(): string {
        return '/mod/perform/activity/select-participants.php';
    }

}
