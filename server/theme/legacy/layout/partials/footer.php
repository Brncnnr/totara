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
 * @package   theme_legacy
 */

defined('MOODLE_INTERNAL') || die();

?>
<footer id="page-footer" class="page-footer">
    <div class="container-fluid page-footer-main-content">
        <div class="row">
            <div id="course-footer"><?php echo $OUTPUT->course_footer(); ?></div>
        </div>

        <?php
        $tenant_id = (!isloggedin() || empty($USER->tenantid)) ? \core\theme\helper::get_prelogin_tenantid() : $USER->tenantid;
        $theme_settings = new core\theme\settings($PAGE->theme, $tenant_id);
        $property = $theme_settings->get_property('custom', 'formcustom_field_customfooter');
        $options = ['allowid' => true];

        if (!empty($property)) {
            echo '<div class="footnote">'.format_text($property['value'], FORMAT_MOODLE, $options).'</div>';
        } else if (!empty($PAGE->theme->settings->footnote)) {
            echo '<div class="footnote">'.format_text($PAGE->theme->settings->footnote, FORMAT_MOODLE, $options).'</div>';
        }

        // since TL-28384 had removed the footer of the page where it reset the counter,
        // hence we are reseting the fail counter here.
        if (isset($SESSION->justloggedin) && !empty($CFG->displayloginfailures)) {
            require_once("{$CFG->dirroot}/user/lib.php");
            user_count_login_failures($USER);
        }

        ?>
        <div class="row">
            <div class="tool_usertours-resettourcontainer"><?php // Reset user tour container ?></div>
            <?php echo $OUTPUT->standard_footer_html(); ?>
        </div>
    </div>
    <small class="page-footer-poweredby"><?php echo $OUTPUT->powered_by_totara(); ?></small>
</footer>
