<?php
/*
 * This file is part of Totara LMS
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_mobile
 */

namespace totara_mobile\watcher;

use core\hook\page_initialize_body_classes as page_hook;
use totara_mobile\local\util as mobile_util;

defined('MOODLE_INTERNAL') || die();

/**
 * A hook watcher for core renderer hooks.
 */
final class page_initialization_watcher {

    /**
     * A hook watcher to inject the mobile classes into the page during initialization
     */
    public static function inject_mobile_classes(page_hook $hook): void {
        $page = $hook->get_page();

        if (mobile_util::is_mobile_webview()) {
            $page->add_body_class('ua-webview');
        }
    }
}
