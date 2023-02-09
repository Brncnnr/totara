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

use totara_engage\resource\input\input_validator;

final class test_validator implements input_validator {
    /**
     * @var bool
     */
    private $run;

    /**
     * @var mixed|null
     */
    private $expected;

    /**
     * test_validator constructor.
     * @param mixed|null $expected
     */
    public function __construct($expected) {
        $this->run = false;
        $this->expected = $expected;
    }

    /**
     * @param mixed|null $value
     * @return bool
     */
    public function is_valid($value): bool {
        $this->run = true;
        return $this->expected == $value;
    }

    /**
     * @return bool
     */
    public function is_run(): bool {
        return $this->run;
    }
}