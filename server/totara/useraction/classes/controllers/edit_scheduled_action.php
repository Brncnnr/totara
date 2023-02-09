<?php
/**
 * This file is part of Totara Talent Experience Platform
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Simon Chester <simon.chester@totara.com>
 * @package totara_useraction
 */

namespace totara_useraction\controllers;

use moodle_url;
use totara_mvc\tui_view;

/**
 * Controller action for editing a scheduled rule.
 */
class edit_scheduled_action extends base_scheduled_action {
    /**
     * @return tui_view
     */
    public function action(): tui_view {
        $id = $this->get_required_param('id', PARAM_INT);

        $this->set_url(new moodle_url('/totara/useraction/edit_scheduled_action.php', ['id' => $id]));
        $this->require_capability('totara/useraction:manage_actions', $this->get_context());

        $title = get_string('edit_scheduled_action', 'totara_useraction');
        $this->get_page()->navbar->add($title);

        // Confirm the rule exists
        $this->get_rule('id');

        $actions = $this->get_user_actions();

        return tui_view::create('totara_useraction/pages/EditScheduledAction', compact('id', 'actions'));
    }
}
