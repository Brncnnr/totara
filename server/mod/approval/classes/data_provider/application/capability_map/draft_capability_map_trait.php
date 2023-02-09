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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\data_provider\application\capability_map;

use core\orm\query\builder;
use mod_approval\model\application\application_state;

/**
 * A trait for application data_provider capability map implementations where the application must be draft.
 */
trait draft_capability_map_trait {
    /**
     * @inheritDoc
     */
    public function get_application_condition(builder $builder): void {
        $builder->where(
            'is_draft',
            '=',
            1
        );
    }
}