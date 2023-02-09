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

use core\orm\entity\entity;
use totara_engage\answer\answer_type;

/**
 * @property int        $id
 * @property string     $value
 * @property int        $timecreated
 * @property int|null   $timemodified
 * @property int        $userid
 * @property int        $answertype
 * @property string     $component
 */
class question extends entity {
    /**
     * @var string
     */
    public const TABLE = 'engage_question';

    /**
     * @var string
     */
    public const CREATED_TIMESTAMP = 'timecreated';

    /**
     * @var string
     */
    public const UPDATED_TIMESTAMP = 'timemodified';

    /**
     * question constructor.
     *
     * @param null      $id
     * @param bool      $validate
     * @param bool|null $exists
     */
    final public function __construct($id = null, bool $validate = true, ?bool $exists = null) {
        parent::__construct($id, $validate, $exists);
    }

    /**
     * @param string|int $value
     * @return void
     */
    protected function set_answertype_attribute($value): void {
        if (!answer_type::is_valid_type($value)) {
            throw new \coding_exception("Invalid value '{$value}' for answertype");
        }

        $this->set_attribute_raw('answertype', $value);
    }
}