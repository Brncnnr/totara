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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package core
 */
namespace core\hook;

use coding_exception;
use totara_core\hook\base;

/**
 * Hook for resetting phpunit.
 */
class phpunit_reset extends base {

    /**
     * @return phpunit_reset
     */
    public function execute(): phpunit_reset {
        if ((defined('PHPUNIT_TEST') && PHPUNIT_TEST)) {
            return parent::execute();
        }

        throw new coding_exception('Hook phpunit_reset only supported with phpunit');
    }
}