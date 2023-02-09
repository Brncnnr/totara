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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\notification\exceptions;

use invalid_parameter_exception;

/**
 * An exception thrown when the specified class key does not exist in the system.
 */
class class_key_not_available extends invalid_parameter_exception {
    /** @var string */
    public $class_key;

    /**
     * Constructor.
     *
     * @param string $class_key
     * @codeCoverageIgnore
     */
    public function __construct(string $class_key) {
        $this->class_key = $class_key;
        parent::__construct("notification {$class_key} is not registered");
    }
}
