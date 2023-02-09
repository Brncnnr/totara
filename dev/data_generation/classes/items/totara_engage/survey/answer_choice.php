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
 */

namespace degeneration\items\totara_engage\survey;

use degeneration\items\item;
use totara_engage\entity\answer_choice as answer_choice_entity;

final class answer_choice extends item {
    /**
     * @var int
     */
    private $question_id;

    /**
     * @var int
     */
    private $option_id;

    /**
     * @var int
     */
    private $user_id;

    /**
     * answer_choice constructor.
     *
     * @param int $question_id
     * @param int $option_id
     * @param int $user_id
     */
    public function __construct(int $question_id, int $option_id, int $user_id) {
        $this->question_id = $question_id;
        $this->option_id = $option_id;
        $this->user_id = $user_id;
    }

    /**
     * @return array
     */
    public function get_properties(): array {
        return [
            'questionid' => $this->question_id,
            'optionid' => $this->option_id,
            'userid' => $this->user_id,
        ];
    }

    /**
     * @return string|null
     */
    public function get_entity_class(): ?string {
        return answer_choice_entity::class;
    }
}