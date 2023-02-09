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

use totara_engage\answer\answer;
use totara_engage\answer\single_choice;

/**
 * Event for single choice answer deleted.
 */
final class single_choice_answer_deleted extends base_answer {
    /**
     * @return void
     */
    protected function init(): void {
        parent::init();
        $this->data['crud'] = 'd';
        $this->data['objecttable'] = 'engage_answer_choice';
    }

    /**
     * @return string
     */
    public static function get_name(): string {
        return get_string('singlechoiceanswerdeleted', 'totara_engage');
    }

    /**
     * @return string
     */
    public function get_description(): string {
        $other = $this->other;
        $questionid = "unknown";

        if (array_key_exists('questionid', $other)) {
            $questionid = $other['questionid'];
        }

        return "The choice answer with id '{$this->objectid}' had been deleted for the question '{$questionid}'";
    }

    /**
     * @param answer   $answer
     * @param \context $context
     * @param int|null $userid
     *
     * @return base_answer
     */
    public static function from_answer(answer $answer, \context $context, ?int $userid = null): base_answer {
        if (!($answer instanceof single_choice)) {
            throw new \coding_exception(
                "Cannot create an event from an answer that is not an instance of " . single_choice::class
            );
        }

        $event = parent::from_answer($answer, $context, $userid);
        $event->data['objectid'] = $answer->get_choice()->id;

        return $event;
    }
}