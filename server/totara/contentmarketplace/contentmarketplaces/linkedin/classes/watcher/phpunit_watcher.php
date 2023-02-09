<?php
/**
 * This file is part of Totara Core
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package contentmarketplace_linkedin
 */
namespace contentmarketplace_linkedin\watcher;

use contentmarketplace_linkedin\testing\generator;
use core\hook\phpunit_reset;

class phpunit_watcher {
    /**
     * @param phpunit_reset $hook
     * @return void
     */
    public static function watch_phpunit_reset(phpunit_reset $hook): void {
        // Reset the locales.
        $generator = generator::instance();
        $generator->reset_locales_for_locales_provider();
    }
}