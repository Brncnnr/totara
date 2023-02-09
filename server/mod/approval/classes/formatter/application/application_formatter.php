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

namespace mod_approval\formatter\application;

use core\orm\formatter\entity_model_formatter;
use core\webapi\formatter\field\date_field_formatter;
use core\webapi\formatter\field\string_field_formatter;

/**
 * Class application_formatter
 *
 * @package mod_approval\formatter\application
 */
class application_formatter extends entity_model_formatter {

    protected function get_map(): array {
        return [
            'id' => null,
            'title' => string_field_formatter::class,
            'id_number' => string_field_formatter::class,
            'workflow_type' => string_field_formatter::class,
            'created' => date_field_formatter::class,
            'submitted' => date_field_formatter::class,
            'completed' => date_field_formatter::class,
            'overall_progress' => null, // ENUM
            'overall_progress_label' => null,
            'your_progress' => null, // ENUM
            'your_progress_label' => null,
            'current_state' => null,
            'last_action' => null,
            'last_published_submission' => null,
            'user' => null,
            'creator' => null,
            'owner' => null,
            'submitter' => null,
            'interactor' => null,
            'approver_users' => null,
            'page_urls' => null,
        ];
    }
}
