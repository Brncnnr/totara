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
 * @package totara_engage
 */
namespace totara_engage\event;

use core\event\base;
use totara_engage\answer\answer;

/**
 * Base event for most of the events related to the CRUD operations on answer.
 */
abstract class base_answer extends base {
    /**
     * @return void
     */
    protected function init(): void {
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * @param answer    $answer
     * @param \context   $context
     * @param int|null  $userid
     *
     * @return base_answer
     */
    public static function from_answer(answer $answer, \context $context, ?int $userid = null): base_answer {
        $data = [
            'userid' => $userid,
            'context' => $context,
            'relateduserid' => $answer->get_userid(),
            'other' => static::extract_from_answer($answer)
        ];

        /** @var base_answer $event */
        $event = static::create($data);
        return $event;
    }

    /**
     * @param answer $answer
     * @return array
     */
    protected static function extract_from_answer(answer $answer): array {
        return [
            'questionid' => $answer->get_question()->get_id()
        ];
    }
}