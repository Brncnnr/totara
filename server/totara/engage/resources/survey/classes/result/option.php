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
 * @package engage_survey
 */
namespace engage_survey\result;

/**
 * Class for option's result
 */
final class option {
    /**
     * Option's id
     * @var int
     */
    private $id;

    /**
     * Question's id which this option is belonging to.
     * @var int
     */
    private $question_id;

    /**
     * Total votes for this option.
     * @var int
     */
    private $votes;

    /**
     * @var string
     */
    private $value;

    /**
     * option constructor.
     * @param int $id
     * @param int $question_id
     * @param string $value
     */
    public function __construct(int $id, int $question_id, string $value) {
        $this->id = $id;
        $this->question_id = $question_id;
        $this->value = $value;

        $this->votes = 0;
    }

    /**
     * @param array $parameters
     * @return option
     */
    public static function from_parameters(array $parameters): option {
        if (!isset($parameters['id']) || !isset($parameters['questionid'])) {
            throw new \coding_exception("No 'id' or 'questionid' in the array of parameters");
        }

        $option_id = $parameters['id'];
        $question_id = $parameters['questionid'];

        $value = '';
        if (isset($parameters['value'])) {
            $value = $parameters['value'];
        }

        $option = new static($option_id, $question_id, $value);
        if (isset($parameters['votes'])) {
            $option->votes = (int) $parameters['votes'];
        }

        return $option;
    }

    /**
     * @return string
     */
    public function get_value(): string {
        return $this->value;
    }

    /**
     * @return int
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * @return int
     */
    public function get_question_id(): int {
        return $this->question_id;
    }

    /**
     * @return int
     */
    public function get_votes(): int {
        return $this->votes;
    }

    /**
     * @param int $value
     * @return void
     */
    public function set_votes(int $value): void {
        $this->votes = $value;
    }

    /**
     * @return array
     */
    public function to_array(): array {
        return [
            'id' => $this->id,
            'questionid' => $this->question_id,
            'value' => $this->value,
            'votes' => $this->votes
        ];
    }
}