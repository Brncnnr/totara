<?php
/*
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
 * @author  Petr Skoda <petr.skoda@totaralearning.com>
 * @package core
 */

namespace core\hook;

/**
 * Hook for monitoring of admin setting changes done via admin configuration interface,
 * this is a more dynamic alternative to 'updatedcallback' property in settings.
 *
 * NOTE: this is NOT triggered after general set_config() calls.
 */
class admin_setting_changed extends \totara_core\hook\base {
    /** @var string admin setting name, '/' is used as a separator for plugin setting names */
    public $name;
    /** @var mixed old value */
    public $oldvalue;
    /** @var mixed new value */
    public $newvalue;

    public function __construct(string $name, $oldvalue, $newvalue) {
        $this->name = $name;
        $this->oldvalue = $oldvalue;
        $this->newvalue = $newvalue;
    }
}
