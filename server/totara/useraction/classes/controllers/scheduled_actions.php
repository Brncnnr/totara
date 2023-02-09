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
 * Base action for listing the scheduled actions.
 */
class scheduled_actions extends base_scheduled_action {
    /**
     * @return tui_view
     */
    public function action(): tui_view {
        $this->set_url(new moodle_url('/totara/useraction/scheduled_actions.php'));
        $this->require_capability('totara/useraction:manage_actions', $this->get_context());

        return tui_view::create('totara_useraction/pages/ScheduledActions');
    }
}
