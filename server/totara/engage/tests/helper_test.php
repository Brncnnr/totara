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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_engage
 */

use totara_engage\share\recipient\helper;
use totara_engage\share\recipient\recipient;

class totara_engage_helper_testcase extends advanced_testcase {

    public function test_get_recipient_class() {
        $class = helper::get_recipient_class('totara_engage', 'LIBRARY');
        $this->assertTrue(class_exists($class));
        $this->assertTrue(is_subclass_of($class, recipient::class));
        $instance = new $class();
        $this->assertInstanceOf(recipient::class, $instance);

        $class = helper::get_recipient_class('totara_engage', 'library');
        $this->assertTrue(class_exists($class));
        $this->assertTrue(is_subclass_of($class, recipient::class));
        $instance = new $class();
        $this->assertInstanceOf(recipient::class, $instance);

        $class = helper::get_recipient_class('core_user', 'USER');
        $this->assertTrue(class_exists($class));
        $this->assertTrue(is_subclass_of($class, recipient::class));
        $instance = new $class();
        $this->assertInstanceOf(recipient::class, $instance);

        $class = helper::get_recipient_class('core_user', 'user');
        $this->assertTrue(class_exists($class));
        $this->assertTrue(is_subclass_of($class, recipient::class));
        $instance = new $class();
        $this->assertInstanceOf(recipient::class, $instance);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('No recipient handler found for \'idontexist\'');

        helper::get_recipient_class('totara_engage', 'idontexist');
    }

}