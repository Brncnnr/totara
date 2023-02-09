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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage\event;

use core\event\base;
use core_ml\event\interaction_event;
use totara_engage\entity\rating;

/**
 * Event rating_created
 */
final class rating_created extends base implements interaction_event {

    /**
     * @inheritDoc
     */
    protected function init() {
        $this->data['objecttable'] = rating::TABLE;
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['crud'] = 'c';
    }

    /**
     * @param rating $rating
     * @return rating_created
     */
    public static function from_rating(rating $rating): rating_created {
        $context = \context_system::instance();

        $data = [
            'objectid' => $rating->instanceid,
            'context' => $context,
            'userid' => $rating->userid,
            'other' => [
                'component' => $rating->component,
                'area' => $rating->area,
                'rating' => $rating->rating,
            ]
        ];

        /** @var rating_created $event */
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
        return $this->other['area'];
    }

    /**
     * @return string
     */
    public function get_interaction_type(): string {
        return 'rate';
    }

    /**
     * @return int
     */
    public function get_rating(): int {
        return $this->other['rating'];
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
        return $this->objectid;
    }
}