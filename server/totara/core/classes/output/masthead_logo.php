<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @copyright 2016 onwards Totara Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Joby Harding <joby.harding@totaralearning.com>
 * @package   totara_core
 */

namespace totara_core\output;

use core\theme\file\favicon_image;
use core\theme\file\logo_image;

defined('MOODLE_INTERNAL') || die();

class masthead_logo implements \renderable, \templatable {

    /**
     * Implements export_for_template().
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output) {
        global $PAGE, $SITE, $OUTPUT, $CFG, $USER;

        $defaultpage = $CFG->wwwroot . '/';
        if (get_home_page() == HOMEPAGE_TOTARA_DASHBOARD) {
            $defaultpage = $CFG->wwwroot . '/totara/dashboard/index.php';

            require_once($CFG->dirroot . '/totara/dashboard/lib.php');
            $availabledash = array_keys(\totara_dashboard::get_user_dashboards($USER->id));

            //Update the homepage dashboard id
            $id = get_user_preferences('user_home_totara_dashboard_id', -1);
            if ($id != -1 && in_array($id, $availabledash)) {
                $defaultpage .= '?id=' . $id;
            }
        } else if (get_home_page() == HOMEPAGE_TOTARA_GRID_CATALOG) {
            $defaultpage = $CFG->wwwroot . '/totara/catalog/index.php';
        }

        $logo = new logo_image($PAGE->theme);
        $logotenantid = $USER->tenantid ?? 0;
        // If not logged in, there may still be a tenant theme in play...
        if (!$logotenantid && (!isloggedin() || isguestuser())) {
            $logotenantid = \core\theme\helper::get_prelogin_tenantid();
        }
        $logo->set_tenant_id($logotenantid);
        $logo_url = $logo->get_current_url();

        $favicon = new favicon_image($PAGE->theme);
        $favicon->set_tenant_id($USER->tenantid ?? 0);
        $favicon_url = $favicon->get_current_url();

        $templatecontext = array(
            'siteurl' => $defaultpage,
            'shortname' => $SITE->shortname,
        );

        if (empty($logo_url)) {
            if (!empty($PAGE->theme->settings->logo)) {
                $templatecontext['logourl'] = $PAGE->theme->setting_file_url('logo', 'logo');
            }

            if (empty($templatecontext['logourl'])) {
                $templatecontext['logourl'] = $OUTPUT->image_url('logo', 'totara_core');
            }

            if (!empty($PAGE->theme->settings->alttext)) {
                $templatecontext['logoalt'] = format_string($PAGE->theme->settings->alttext);
            }
        } else {
            $templatecontext['logourl'] = $logo_url->out();
            $templatecontext['logoalt'] = $logo->get_alt_text();
        }

        if (empty($templatecontext['logoalt'])) {
            $templatecontext['logoalt'] = $logo->get_alt_text();
        }

        if (empty($favicon_url)) {
            if (!empty($PAGE->theme->settings->favicon)) {
                $templatecontext['faviconurl'] = $PAGE->theme->setting_file_url('favicon', 'favicon');
            } else {
                $templatecontext['faviconurl'] = $OUTPUT->favicon();
            }
        } else {
            $templatecontext['faviconurl'] = $favicon_url->out();
        }

        return $templatecontext;
    }

}
