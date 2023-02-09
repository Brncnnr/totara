<?php
/**
* This file is part of Totara Learn
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
* @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
* @package mod_contentmarketplace
*/

namespace mod_contentmarketplace\workflow_manager;

use totara_contentmarketplace\local;
use totara_workflow\workflow_manager\base;

/**
 * Workflow manager singleton class for managing content marketplace workflow instances.
 */
class create_marketplace_activity extends base {

    /**
     * @inheritDoc
     */
    public function get_name(): string {
        return get_string('workflow:type', 'mod_contentmarketplace');
    }

    /**
     * @inheridDoc
     */
    protected function can_access(): bool {
        return during_initial_install()
            || local::is_enabled();
    }

    /**
     * @inheridDoc
     */
    public function get_workflow_manager_data(): array {
        $section_id = required_param('section_id', PARAM_INT);

        return [
            'section_id' => $section_id,
        ];
    }

}