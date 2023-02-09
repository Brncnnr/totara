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

use core\event\base;
use core_ml\event\interaction_event;
use totara_reaction\reaction;

/**
 * Base class for the event
 */
abstract class base_reaction_event extends base implements interaction_event {
    /**
     * @return void
     */
    protected function init(): void {
        $this->data['objecttable'] = "reaction";
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * @param reaction $reaction
     *
     * @return base_reaction_event
     */
    public static function instance(reaction $reaction): base_reaction_event {
        $data = [
            'userid' => $reaction->get_userid(),
            'objectid' => $reaction->get_id(),
            'contextid' => $reaction->get_contextid(),
            'other' => [
                'component' => $reaction->get_component(),
                'area' => $reaction->get_area(),
                'instanceid' => $reaction->get_instanceid(),
            ],
        ];

        /** @var base_reaction_event $event */
        $event = static::create($data);
        return $event;
    }

    /**
     * @return string
     */
    public function get_component(): string {
        return $this->other['component'];
    }

    /**
     * @return string|null
     */
    public function get_area(): ?string {
        return $this->other['area'] ?? null;
    }

    /**
     * @return int
     */
    public function get_rating(): int {
        return 1;
    }

    /**
     * @return int
     */
    public function get_user_id(): int {
        return $this->userid;
    }

    /**
     * @return int
     */
    public function get_item_id(): int {
        return $this->other['instanceid'];
    }
}