<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @package    totara_core
 * @subpackage navigation
 * @author     Oleg Demeshev <oleg.demeshev@totaralms.com>
 */
namespace totara_core\totara\menu;

use  totara_core\advanced_feature;

class home extends item {

    protected function get_default_title() {
        return get_string('home', 'totara_core');
    }

    protected function get_default_url() {
        global $PAGE;

        $homepage = get_home_page();

        if (advanced_feature::is_enabled('totaradashboard') && $homepage == HOMEPAGE_TOTARA_DASHBOARD) {
            return '/totara/dashboard/index.php';
        } else if (get_config('core', 'catalogtype') == 'totara' &&
            $homepage == HOMEPAGE_TOTARA_GRID_CATALOG) {
            return '/totara/catalog/index.php';
        } else {
            return '/index.php?redirect=0';
        }
    }

    protected function check_visibility() {
        global $USER, $CFG;

        if (!empty($USER->tenantid) and $CFG->tenantsisolated) {
            // The front page is not accessible when tenant isolation is active.
            return false;
        }

        return true;
    }

    public function get_default_sortorder() {
        return 10000;
    }
}
