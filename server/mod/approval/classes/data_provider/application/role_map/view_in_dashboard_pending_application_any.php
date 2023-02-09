<?php
/**
 * This file is part of Totara LMS
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\data_provider\application\role_map;

defined('MOODLE_INTERNAL') || die();

/**
 * Capability map for view_in_dashboard_pending_application_any.
 */
final class view_in_dashboard_pending_application_any extends role_map_base {

    /**
     * Returns the view hidden capability for the items within this map.
     *
     * @return string
     */
    public function get_view_hidden_capability(): string {
        return 'mod/approval:view_in_dashboard_pending_application_any';
    }
}