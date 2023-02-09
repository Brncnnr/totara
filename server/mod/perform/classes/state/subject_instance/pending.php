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

namespace mod_perform\state\subject_instance;

use mod_perform\state\subject_instance\condition\all_manual_relationship_selections_complete;
use mod_perform\state\transition;

/**
 * This class represents the "pending" manual status of a subject instance.
 *
 * @package mod_perform
 */
class pending extends subject_instance_manual_status {

    /**
     * @inheritDoc
     */
    public static function get_name(): string {
        return 'PENDING';
    }

    /**
     * @inheritDoc
     */
    public static function get_display_name(): string {
        return get_string('subject_instance_status_pending', 'mod_perform');
    }

    /**
     * @inheritDoc
     */
    public static function get_code(): int {
        return 2;
    }

    /**
     * @inheritDoc
     */
    public function get_transitions(): array {
        return [
            transition::to(new active($this->object))->with_conditions([
                all_manual_relationship_selections_complete::class,
            ]),
        ];
    }

}
