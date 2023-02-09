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
 * @package mod_perform
 */

namespace mod_perform\views;

use moodle_page;
use totara_mvc\view;
use totara_mvc\view_override;

class override_nav_breadcrumbs implements view_override {

    /**
     * @inheritDoc
     */
    public function apply(view $view): void {
        self::remove_nav_breadcrumbs($view->get_page());
    }

    /**
     * Remove course navigation and settings blocks from the page.
     *
     * @param moodle_page $page
     */
    public static function remove_nav_breadcrumbs(moodle_page $page): void {
        $settings = $page->settingsnav->children;
        // Remove course-related settings blocks.
        $settings->remove('categorysettings');
        $settings->remove('modulesettings');
        $settings->remove('courseadmin');

        // Remove course-related breadcrumbs.
        $breadcrumbs = $page->navigation->children;
        $breadcrumbs->remove('courses');
    }

}
