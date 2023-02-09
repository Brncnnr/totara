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

use totara_engage\answer\answer_type;

/**
 * Class for question result.
 */
final class question {
    /**
     * Question's id
     * @var int
     */
    private $id;

    /**
     * Value of the questions.
     * @var string
     */
    private $value;

    /**
     * @var int
     */
    private $votes;

    /**
     * @var option[]
     */
    private $options;

    /**
     * @var bool
     */
    private $update_able;

    /**
     * @var int
     */
    private $type;

    /**
     * @var int;
     */
    private $participants;

    /**
     * question constructor.
     * @param int $id
     * @param string $value
     * @param bool $update_able
     * @param int  $type
     */
    public function __construct(int $id, string $value, bool $update_able, int $type) {
        $this->id = $id;
        $this->value = $value;
        $this->update_able = $update_able;
        $this->type = $type;

        $this->options = [];
        $this->votes = 0;
        $this->participants = 0;
    }

    /**
     * @param array $parameters
     * @return question
     */
    public static function from_parameters(array $parameters): question {
        if (!isset($parameters['id'])) {
            throw new \coding_exception("No 'id' was found in the array of parameters");
        }

        $value = '';
        if (isset($parameters['value'])) {
            $value = $parameters['value'];
        }

        $update_able = false;
        if (isset($parameters['updateable'])) {
            $update_able = (bool) $parameters['updateable'];
        }

        // Default to single choice.
        $type = answer_type::SINGLE_CHOICE;
        if (isset($parameters['answertype'])) {
            if (!answer_type::is_valid_type($parameters['answertype'])) {
                debugging("Invalid answer type '{$parameters['answertype']}'", DEBUG_DEVELOPER);
            } else {
                $type = $parameters['answertype'];
            }
        }

        $question = new static($parameters['id'], $value, $update_able, $type);

        if (isset($parameters['participants'])) {
            $question->set_participants($parameters['participants']);
        }

        if (isset($parameters['votes'])) {
            $question->set_votes((int) $parameters['votes']);
        }

        if (isset($parameters['options']) && is_array($parameters['options'])) {
            foreach ($parameters['options'] as $option_datum) {
                $option = option::from_parameters($option_datum);
                $question->add_option($option);
            }
        }

        return $question;
    }

    /**
     * @param int $value
     * @return void
     */
    public function set_votes(int $value): void {
        $this->votes = $value;
    }

    /**
     * @param option $option
     * @return void
     */
    public function add_option(option $option): void {
        $this->options[] = $option;
    }

    /**
     * Returning the text value
     * @return string
     */
    public function get_value(): string {
        return $this->value;
    }

    /**
     * @return bool
     */
    public function is_update_able(): bool {
        return $this->update_able;
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
    public function get_votes(): int {
        return $this->votes;
    }

    /**
     * @return int
     */
    public function get_type(): int {
        return $this->type;
    }

    /**
     * @return int
     */
    public function get_participants(): int {
        return $this->participants;
    }

    /**
     * @param int $value
     * @return void
     */
    public function set_participants(int $value): void {
        $this->participants = $value;
    }

    /**
     * @return array
     */
    public function to_array(): array {
        $rtn = [
            'id' => $this->id,
            'value' => $this->value,
            'votes' => $this->votes,
            'updateable' => $this->update_able,
            'answertype' => $this->type,
            'options' => [],
            'participants'=> $this->participants
        ];

        foreach ($this->options as $option) {
            $rtn['options'][] = $option->to_array();
        }

        return $rtn;
    }

    /**
     * This function will not expose option's id as key.
     *
     * @return array
     */
    public function get_option_stats(): array {
        return array_values($this->options);
    }
}