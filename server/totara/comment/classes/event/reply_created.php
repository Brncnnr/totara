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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_comment
 */
namespace totara_comment\event;

use core\event\base;
use totara_comment\comment;
use totara_comment\entity\comment as entity;

/**
 * Event for reply creation.
 */
final class reply_created extends base {
    /**
     * @return void
     */
    protected function init(): void {
        $this->data['objecttable'] = entity::TABLE;
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['crud'] = 'c';
    }

    /**
     * @param comment   $reply
     * @param int       $context_id
     * @param int|null  $user_id        The actor who is responsible for triggering this event.
     *
     * @return reply_created
     */
    public static function from_reply(comment $reply, int $context_id, ?int $user_id = null): reply_created {
        if (!$reply->exists()) {
            throw new \coding_exception(
                "Cannot create an event from a reply that is not existing in the system"
            );
        } else if (!$reply->is_reply()) {
            throw new \coding_exception(
                "Cannot create an event from a comment that is not a reply"
            );
        }

        if (empty($user_id)) {
            $user_id = $reply->get_userid();
        }

        $data = [
            'objectid' => $reply->get_id(),
            'userid' => $user_id,
            'contextid' => $context_id,
            'relateduserid' => $reply->get_userid(),
            'other' => [
                'component' => $reply->get_component(),
                'instance_id' => $reply->get_instanceid(),
                'area' => $reply->get_area()
            ],
        ];

        $context = \context::instance_by_id($context_id);
        if (CONTEXT_COURSE == $context->contextlevel) {
            $data['courseid'] = $context->instanceid;
        }

        /** @var reply_created $event */
        $event = static::create($data);
        return $event;
    }
}