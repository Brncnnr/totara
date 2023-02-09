<?php
/*
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\controllers\activity;

use mod_perform\controllers\perform_controller;
use mod_perform\models\activity\activity as activity_model;
use mod_perform\util;
use totara_mvc\admin_controller;
use totara_mvc\tui_view;

class manage_activities extends admin_controller {

    public const URL =  '/mod/perform/manage/activity/index.php';

    protected $admin_external_page_name = 'mod_perform_manage_activities';

    protected $layout = 'noblocks';

    /**
     * @inheritDoc
     */
    protected function setup_context(): \context {
        $category_id = util::get_default_category_id();
        return \context_coursecat::instance($category_id);
    }

    /**
     * @return tui_view
     */
    public function action(): tui_view {
        $props = [
            'edit-url' => (string) edit_activity::get_url(),
            'can-add' => activity_model::can_create(),
        ];

        return perform_controller::create_tui_view('mod_perform/pages/Activities', $props)
            ->set_title(get_string('perform:manage_activity', 'mod_perform'));
    }

}