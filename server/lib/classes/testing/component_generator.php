<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package core
 */

namespace core\testing;

/**
 * Data generator class for PHPUnit, behat and other tools that need to create fake test sites.
 */
abstract class component_generator {
    /** @var static */
    protected static $instances = [];
    /** @var generator */
    protected $datagenerator;

    /**
     * Constructor, do not change the signature when overriding!
     */
    protected function __construct() {
    }

    /**
     * Returns instance of core data generator.
     * @return static
     */
    final public static function instance() {
        if (!isset(self::$instances[static::class])) {
            self::$instances[static::class] = new static();
            self::$instances[static::class]->datagenerator = generator::instance();
        }
        return self::$instances[static::class];
    }

    /**
     * To be called from data reset code only, do not use in tests.
     *
     * Override if necessary.
     *
     * @return void
     */
    public function reset() {
    }
}
