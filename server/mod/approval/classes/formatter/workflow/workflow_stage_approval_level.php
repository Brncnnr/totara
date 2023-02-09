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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\formatter\workflow;

use core\orm\formatter\entity_model_formatter;
use core\webapi\formatter\field\date_field_formatter;
use core\webapi\formatter\field\string_field_formatter;

/**
 * Class workflow stage approval level
 *
 * @package mod_approval\formatter\workflow
 */
class workflow_stage_approval_level extends entity_model_formatter {

    protected function get_map(): array {
        return [
            'id' => null,
            'workflow_stage' => null,
            'name' => string_field_formatter::class,
            'ordinal_number' => null,
            'active' => null,
            'approvers' => null,
            'created' => date_field_formatter::class,
            'updated' => date_field_formatter::class,
        ];
    }
}