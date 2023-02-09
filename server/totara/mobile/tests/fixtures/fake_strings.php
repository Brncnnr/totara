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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package totara_mobile
 */

use totara_mobile\language\source;

/**
 * Get testing strings
 */
class fake_strings extends source {

    public function __construct() {
        parent::__construct('totara_mobile', 'en');
    }

    public function get_all_strings(): array {
        $string = [];
        $string['someotherstring'] = 'Str';
        $string['app:my-learning:action_primary'] = 'A';
        $string['app:my-learning:primary_info:zero'] = 'B';
        $string['application:strings'] = 'Not needed';
        $string['app:my-learning:primary_info:one'] = 'C {{count}} D';
        $string['app:my-learning:primary_info:other'] = 'E';
        $string['app:my-learning:no_learning_message'] = 'F';
        $string['some:app:string'] = 'str';
        return $string;
    }
}