<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_reaction
 */
namespace totara_reaction\event;

final class reaction_created extends base_reaction_event {
    protected function init(): void {
        parent::init();
        $this->data['crud'] = 'c';
    }

    /**
     * @return string
     */
    public static function get_name(): string {
        return get_string('reactioncreated', 'totara_reaction');
    }

    /**
     * @return string|void
     */
    public function get_description() {
        $other = $this->other;

        return
            "User with id '{$this->userid}' had liked an instance '{$other['instanceid']}' " .
            "of component '{$other['component']}' with area '{$other['area']}'";
    }

    /**
     * @return string
     */
    public function get_interaction_type(): string {
        return 'like';
    }
}