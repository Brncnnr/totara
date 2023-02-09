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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_comment
 */
namespace totara_comment\event;

use core\event\base;
use core_ml\event\interaction_event;
use totara_comment\comment;
use totara_comment\entity\comment as entity;

/**
 * Class comment_created
 * @package totara_comment\event
 */
final class comment_created extends base implements interaction_event {
    /**
     * @param comment   $comment
     * @param \context  $context
     * @param int|null  $user_id    The user who is responsible for triggering this event.
     *
     * @return comment_created
     */
    public static function from_comment(comment $comment, \context $context, ?int $user_id = null): comment_created {
        if (!$comment->exists()) {
            throw new \coding_exception("Cannot create an event from a comment that does not exist in the system");
        } else if ($comment->is_reply()) {
            throw new \coding_exception("Cannot create a comment event from a reply");
        }

        if (empty($user_id)) {
            // We fallback to the author of the comment, as there is no way that someone is creating
            // comment for somebody else.
            $user_id = $comment->get_userid();
        }

        $component = $comment->get_component();
        $area = $comment->get_area();

        $data = [
            'objectid' => $comment->get_id(),
            'userid' => $user_id,
            'context' => $context,
            'other' => [
                'area' => $area,
                'component' => $component,
                'instanceid' => $comment->get_instanceid()
            ]
        ];

        if (CONTEXT_COURSE == $context->contextlevel) {
            $data['courseid'] = $context->instanceid;
        }

        /** @var comment_created $event */
        $event = self::create($data);
        return $event;
    }

    /**
     * @return void
     */
    protected function init(): void {
        $this->data['objecttable'] = entity::TABLE;
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['crud'] = 'c';
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
        return 'comment';
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

    /**
     * @return array
     */
    public function get_notification_event_data(): array {
        return [
            'comment_id' => $this->objectid
        ];
    }
}