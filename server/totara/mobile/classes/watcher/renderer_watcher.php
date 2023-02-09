<?php
/*
 * This file is part of Totara LMS
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package totara_mobile
 */

namespace totara_mobile\watcher;

use core\hook\renderer_standard_footer_html_complete;
use totara_mobile\local\util as mobile_util;

defined('MOODLE_INTERNAL') || die();

/**
 * A hook watcher for core renderer hooks.
 */
final class renderer_watcher {
    /**
     * A watcher to add the mobile app banner to the standard html footer.
     *
     * @param renderer_standard_footer_html_complete $hook
     * @return void
     */
    public static function add_mobile_banner(renderer_standard_footer_html_complete $hook): void {

        if (!get_config('totara_mobile', 'enable')) {
            // Do nothing if the mobile app is disabled.
            return;
        }

        $renderer = $hook->renderer;
        $page = $hook->page;

        if (in_array($page->pagetype, ['site-index', 'totara-catalog-index']) || strpos($page->pagetype, 'totara-dashboard-') === 0) {
            $url = mobile_util::app_banner_url();
            if ($url) {
                $data = [
                    'appurl' => $url,
                    'pageurl' => $page->url
                ];
                $newoutput = $renderer->render_from_template('totara_mobile/app_link_banner', $data);
                $hook->output .= $newoutput;
            }
        }
    }
}