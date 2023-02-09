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
 * @package totara_topic
 */
namespace totara_topic\event;

use core\event\base;
use totara_topic\topic;

final class topic_delete extends base {
    /**
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'tag';
    }

    /**
     * @return string
     */
    public static function get_name(): string {
        return get_string('event:topicdeleted', 'totara_topic');
    }

    /**
     * @param int|null $actorid
     * @param topic $topic
     * @return topic_delete
     */
    public static function from_topic(topic $topic, int $actorid = null): topic_delete {
        global $USER;

        if (null == $actorid) {
            $actorid = $USER->id;
        }

        $data = [
            'context' => \context_system::instance(),
            'objectid' => $topic->get_id(),
            'userid' => $actorid,
            'other' => [
                'value' => $topic->get_raw_name()
            ]
        ];

        /** @var topic_delete $event */
        $event = static::create($data);
        return $event;
    }
}