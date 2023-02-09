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
 * @package totara_engage
 */
namespace totara_engage\entity;

use coding_exception;
use totara_engage\repository\answer_choice_repository;

/**
 * @property int $id
 * @property int $optionid
 * @property int $questionid
 * @property int $userid
 * @property int $timecreated
 */
final class answer_choice extends answer {
    /**
     * @var string
     */
    public const TABLE = 'engage_answer_choice';

    /**
     * @return answer_option
     */
    public function get_answer_option(): answer_option {
        if (!$this->exists()) {
            throw new coding_exception(
                "Cannot get the picked option record of a choice that is not existing in the system"
            );
        }

        $belong = $this->belongs_to(answer_option::class, 'optionid');

        /** @var answer_option $option */
        $option = $belong->one(true);
        return $option;
    }

    /**
     * @return string
     */
    public static function repository_class_name(): string {
        return answer_choice_repository::class;
    }
}