<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_catalog
 */

require_once(__DIR__ . '/../../config.php');

global $CFG, $OUTPUT, $PAGE;

require_login();

$pageurl = new moodle_url('/totara/catalog/index.php');
// Set grid catalog as homepage for user when user home page preference is enabled.
if (optional_param('setdefaulthome', 0, PARAM_BOOL)) {
    if (!empty($CFG->allowdefaultpageselection) && $CFG->catalogtype === 'totara' && !isguestuser()) {
        require_sesskey();
        set_user_preference('user_home_page_preference', HOMEPAGE_TOTARA_GRID_CATALOG);
        \core\notification::success(get_string('userhomepagechanged', 'totara_dashboard'));
        redirect($pageurl);
    }
}

// Set page context.
$systemcontext = context_system::instance();
$heading = get_string('catalog_heading', 'totara_catalog');
$PAGE->set_context($systemcontext);
$PAGE->set_title($heading);
$PAGE->set_heading($heading);
$PAGE->set_pagelayout('columnpage');
$PAGE->set_url($pageurl);

// Page editing must be set up after page context is set.
$edit = optional_param('edit', -1, PARAM_BOOL);
if (!isset($USER->editing)) {
    $USER->editing = 0;
}
if ($PAGE->user_allowed_editing()) {
    if ($edit == 1 && confirm_sesskey()) {
        $USER->editing = 1;
        redirect($PAGE->url);
    } else if ($edit == 0 && confirm_sesskey()) {
        $USER->editing = 0;
        redirect($PAGE->url);
    }
} else {
    $USER->editing = 0;
}

// Start page output.
echo $OUTPUT->header();
echo $OUTPUT->heading($heading, 2, 'tw-catalog__title');

if ($CFG->catalogtype !== 'totara') {
    $redirect_url = $CFG->catalogtype === 'enhanced' ? '/totara/coursecatalog/courses.php' : '/course/index.php';
    $redirect_link = html_writer::link(
        new moodle_url($redirect_url),
        get_string('redirect_message_go_to_active_catalog_link_text', 'totara_catalog')
    );
    echo $OUTPUT->notification(
        get_string('redirect_message_catalog_not_configured', 'totara_catalog', ['go_to_active_catalog' => $redirect_link])
        , 'info'
    );
} else {
    try {
        echo $OUTPUT->render(\totara_catalog\local\param_processor::get_template());
    } catch (coding_exception $ex) {
        if (empty($CFG->debugdeveloper)) {
            $ex = new moodle_exception('error:pagepermissions', 'totara_core');
        }
        throw $ex;
    }
}

echo $OUTPUT->footer();
