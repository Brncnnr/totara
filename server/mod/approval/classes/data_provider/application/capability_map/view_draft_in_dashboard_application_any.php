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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\data_provider\application\capability_map;

/**
 * Capability map for the view_draft_in_dashboard_application_any capability.
 */
class view_draft_in_dashboard_application_any extends capability_map_base {

    use draft_capability_map_trait;

    /**
     * @inheritDoc
     */
    public static function get_capability(): string {
        return 'mod/approval:view_draft_in_dashboard_application_any';
    }

    /**
     * @inheritDoc
     */
    public static function get_table(): string {
        return 'approval_dashboard_draft_application_any';
    }

    /**
     * @inheritDoc
     */
    public static function get_table_alias(): string {
        return 'dashboard_draft_any';
    }
}