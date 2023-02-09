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
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 */

use mod_approval\model\workflow\interaction\transition\transition_base;
use mod_approval\model\application\application_state;
use mod_approval\model\workflow\workflow_stage;

class sample_interaction_transition extends transition_base {

    public function transition_field(): string {
        return 'transition_key';
    }

    public function resolve(application_state $current_state): ?application_state {
        return null;
    }

    public function get_options(workflow_stage $stage): array {
        return [];
    }

    public static function get_sort_order(): int {
        return 87;
    }
}