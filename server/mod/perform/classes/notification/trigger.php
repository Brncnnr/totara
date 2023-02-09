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

namespace mod_perform\notification;

use invalid_parameter_exception;

/**
 * The trigger types and helper functions.
 */
final class trigger {
    const TYPE_ONCE = 0;
    const TYPE_BEFORE = 1;
    const TYPE_AFTER = 2;

    /** @var string */
    private $class_key;

    /**
     * Constructor.
     *
     * @param string $class_key
     */
    public function __construct(string $class_key) {
        $this->class_key = $class_key;
    }

    /**
     * Wrapper around loader::support_triggers.
     *
     * @return boolean
     */
    public function are_triggers_available(): bool {
        $loader = factory::create_loader();
        return $loader->support_triggers($this->class_key);
    }

    /**
     * Convert from the internal trigger structure.
     *
     * @param array $values
     * @return array
     */
    public function translate_outgoing(array $values): array {
        if (!$this->are_triggers_available()) {
            return [];
        }
        $values = array_map(function ($value) {
            return (int)floor((int)$value / DAYSECS);
        }, $values);
        return $values;
    }

    /**
     * Convert to the internal trigger structure.
     *
     * @param array $values
     * @return array
     */
    public function translate_incoming(array $values): array {
        if (empty($values)) {
            return [];
        }
        if (!$this->are_triggers_available()) {
            throw new invalid_parameter_exception('triggers not available');
        }
        $values = array_map(function ($value) {
            $value = (int)$value;
            if ($value <= 0 || $value > 365) {
                throw new invalid_parameter_exception('invalid value(s)');
            }
            return $value * DAYSECS;
        }, $values);
        sort($values, SORT_REGULAR);
        $values_unique = array_unique($values, SORT_REGULAR);
        if (count($values_unique) != count($values)) {
            throw new invalid_parameter_exception('duplicated value(s)');
        }
        return $values;
    }
}
