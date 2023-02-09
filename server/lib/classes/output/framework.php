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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package core
 */

namespace core\output;

/**
 * Output framework interface.
 *
 * Can be implemented by any plugin wanting to introduce a new frontend framework.
 * Allow the framework to hook into the page requirements manager and inject its dependencies.
 */
interface framework {

    /**
     * Returns a new instance of this framework.
     * @return framework
     */
    public static function new_instance(): framework;

    /**
     * Initialise the framework.
     * This is called by the page requirements manager, which will generate an instance of each available framework
     * during its construction.
     */
    public function initialise(): void;

    /**
     * Injects required CSS file URL's
     * This is called by the page requirements manager, which will call this when preparing CSS URLs for the page.
     * @param string[] $urls Passed by reference
     */
    public function inject_css_urls(array &$urls): void;

    /**
     * Injects required JS file URL's
     * This is called by the page requirements manager, which will call this when preparing JS URLs for the page.
     * @param string[] $urls Passed by reference.
     * @param bool $initialiseamd
     */
    public function inject_js_urls(array &$urls, bool $initialiseamd): void;

    /**
     * A hook into page_requirements_manager::get_head_code()
     * @param \moodle_page $page
     * @param \core_renderer $renderer
     */
    public function get_head_code(\moodle_page $page, \core_renderer $renderer): void;

}