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
 * @author David Curry <david.curry@totaralms.com>>
 * @package totara
 * @subpackage totara_core
 */

use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

function totara_generate_email_user($email) {
    debugging('totara_generate_email_user($email) is deprecated, use \totara_core\totara_user::get_external_user($email) instead', DEBUG_DEVELOPER);
    return \totara_core\totara_user::get_external_user($email);
}

/**
 * Human-readable version of the duration field used to display it to
 * users
 *
 * @param   integer $duration duration in hours
 * @return  string
 */
function format_duration($duration) {
    debugging('format_duration() is deprecated, use format_time() instead', DEBUG_DEVELOPER);
    return format_time($duration);
}

/**
 * Converts minutes to hours
 */
function facetoface_minutes_to_hours($minutes) {
    debugging('facetoface_minutes_to_hours() is deprecated, use format_time() instead', DEBUG_DEVELOPER);
    return format_time($minutes * MINSECS);
}

/**
 * Converts hours to minutes
 */
function facetoface_hours_to_minutes($hours) {
    debugging('facetoface_hours_to_minutes() is deprecated, use format_time() instead', DEBUG_DEVELOPER);
    return format_time($hours * HOURSECS);
}

/**
 * DEPRECATED: Return an array containing any notifications in $SESSION
 *
 * Should be called in the theme's header
 *
 * @deprecated since Totara 13
 * @return  array
 */
function totara_get_notifications() {
    debugging('totara_get_notifications() has been deprecated, please use \core\notification::fetch() instead.', DEBUG_DEVELOPER);

    $notifications = \core\notification::fetch();

    // Ensure notifications are in the format Totara expects from this function.
    return array_map('totara_convert_notification_to_legacy_array', $notifications);
}

/**
 * DEPRECATED: Save a notification message for displaying on the subsequent page view
 *
 * Instead of this function please use with the redirect() function if you are redirecting
 * or one of the following if you just want to stack a notification:
 *   - \core\notification::success()
 *   - \core\notification::error()
 *   - \core\notification::info()
 *   - \core\notification::warning()
 *
 * @deprecated since Totara 13
 * @param string $message Message to display
 * @param string $redirect Url to redirect to (optional)
 * @param array $options Additional options, other than class its use is highly discouraged.
 * @param bool $immediatesend If set to true the notification is immediately sent
 */
function totara_set_notification($message, $redirect = null, $options = array(), $immediatesend = true) {
    debugging('totara_set_notification() has been deprecated, please use redirect() or \core\notification::*() instead.', DEBUG_DEVELOPER);

    // Check options is an array
    if (!is_array($options)) {
        print_error('error:notificationsparamtypewrong', 'totara_core');
    }

    $data = [];
    $data['message'] = $message;
    $data['class'] = isset($options['class']) ? $options['class'] : null;
    // Add anything apart from 'classes' from the options object.
    $data['customdata'] = array_filter($options, function ($key) {
        return !($key === 'class');
    }, ARRAY_FILTER_USE_KEY);

    // Add to notifications queue
    totara_queue_append('notifications', $data);

    // Redirect if requested
    if ($redirect !== null) {
        // Cancel redirect for AJAX scripts.
        if (is_ajax_request($_SERVER)) {
            if (!$immediatesend) {
                ajax_result(true);
            } else {
                ajax_result(true, totara_queue_shift('notifications'));
            }
        } else {
            redirect($redirect);
        }
        exit();
    }
}

/**
 * DEPRECATED: Convert a core\output\notification instance to the legacy array format.
 *
 * @internal
 * @deprecated since Totara 13
 * @param \core\output\notification $notification The templatable to be converted.
 * @return array
 */
function totara_convert_notification_to_legacy_array(\core\output\notification $notification) {
    global $OUTPUT;

    // Intentionally no debugging notice here, notices are printed when calling totara_get_notifications().
    // This function is internal and should never be used externally.

    $type = $notification->get_message_type();
    $variables = $notification->export_for_template($OUTPUT);

    $data = [ 'message' => $variables['message'], 'class' => trim($type . ' ' . $variables['extraclasses'])];

    return array_merge($notification->get_totara_customdata(), $data);
}

/**
 * DEPRECATED: Add an item to a totara session queue
 *
 * @deprecated since Totara 13
 * @param   string  $key    Queue key
 * @param   mixed   $data   Data to add to queue
 * @return  void
 */
function totara_queue_append($key, $data) {
    global $SESSION;

    debugging('totara_queue_append() has been deprecated due to disuse.', DEBUG_DEVELOPER);

    // Since TL-11584 / MDL-30811
    if ($key === 'notifications') {
        \core\notification::add_totara_legacy($data['message'], $data['class'], $data['customdata']);
        return;
    }

    if (!isset($SESSION->totara_queue)) {
        $SESSION->totara_queue = array();
    }

    if (!isset($SESSION->totara_queue[$key])) {
        $SESSION->totara_queue[$key] = array();
    }

    $SESSION->totara_queue[$key][] = $data;
}

/**
 * DEPRECATED: Return part or all of a totara session queue
 *
 * @deprecated since Totara 13
 * @param   string  $key    Queue key
 * @param   boolean $all    Flag to return entire session queue (optional)
 * @return  mixed
 */
function totara_queue_shift($key, $all = false) {
    global $SESSION;

    debugging('totara_queue_shift() has been deprecated due to disuse.', DEBUG_DEVELOPER);

    // Value to return if no items in queue
    $return = $all ? array() : null;

    // Check if an items in queue
    if (empty($SESSION->totara_queue) || empty($SESSION->totara_queue[$key])) {
        return $return;
    }

    // If returning all, grab all and reset queue
    if ($all) {
        $return = $SESSION->totara_queue[$key];
        $SESSION->totara_queue[$key] = array();
        return $return;
    }

    // Otherwise pop oldest item from queue
    return array_shift($SESSION->totara_queue[$key]);
}

/**
 * Returns markup for displaying saved scheduled reports
 *
 * Optionally without the options column and add/delete form
 * Optionally with an additional sql WHERE clause
 * @deprecated since Totara 13.0
 * @access public
 * @param boolean $showoptions SHow icons to edit and delete scheduled reports.
 * @param boolean $showaddform Show a simple form to allow reports to be scheduled.
 * @param array $sqlclause In the form array($where, $params)
 */
function totara_print_scheduled_reports($showoptions=true, $showaddform=true, $sqlclause=array()) {
    global $CFG, $PAGE;

    debugging('totara_print_scheduled_reports() has been deprecated.', DEBUG_DEVELOPER);

    require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');
    require_once($CFG->dirroot . '/totara/core/lib/scheduler.php');
    require_once($CFG->dirroot . '/calendar/lib.php');
    require_once($CFG->dirroot . '/totara/reportbuilder/scheduled_forms.php');

    $scheduledreports = get_my_scheduled_reports_list();

    // If we want the form generate the content so it can be used into the templated.
    if ($showaddform) {
        $mform = new scheduled_reports_add_form($CFG->wwwroot . '/totara/reportbuilder/scheduled.php', array());
        $addform = $mform->render();
    } else {
        $addform = '';
    }

    $renderer = $PAGE->get_renderer('totara_core');
    echo $renderer->scheduled_reports($scheduledreports, $showoptions, $addform);
}

/**
 * TOTARA_SHOWFEATURE has been deprecated in favour of \totara_core\advanced_feature::ENABLED
 *
 * @deprecated since Totara 13
 */
define('TOTARA_SHOWFEATURE', 1);

/**
 * TOTARA_HIDEFEATURE has been deprecated, hidden is not supported anymore, use only enabled or disabled
 *
 * @deprecated since Totara 13
 */
define('TOTARA_HIDEFEATURE', 2);

/**
 * TOTARA_DISABLEFEATURE has been deprecated in favour of \totara_core\advanced_feature::DISABLED
 *
 * @deprecated since Totara 13
 */
define('TOTARA_DISABLEFEATURE', 3);

/**
 * List of strings which can be used with 'totara_feature_*() functions'.
 *
 * Update this list if you add/remove settings in admin/settings/subsystems.php.
 *
 * @deprecated since Totara 13
 * @return array Array of strings of supported features (should have a matching "enable{$feature}" config setting).
 */
function totara_advanced_features_list() {
    debugging('totara_advanced_features_list() has been deprecated in favour of \totara_core\advanced_feature::get_available().', DEBUG_DEVELOPER);

    return advanced_feature::get_available();
}

/**
 * Check the state of a particular Totara feature against the specified state.
 *
 * Used by the totara_feature_*() functions to see if some Totara functionality is visible/hidden/disabled.
 *
 * @deprecated since Totara 13
 * @param string $feature Name of the feature to check, must match options from {@link \totara_core\advanced_feature::get_available()}.
 * @param integer $stateconstant State to check, must match one of TOTARA_*FEATURE constants defined in this file.
 * @return bool True if the feature's config setting is in the specified state.
 */
function totara_feature_check_state($feature, $stateconstant) {
    debugging('totara_feature_check_state() has been deprecated in favour of \totara_core\advanced_feature::* functions.', DEBUG_DEVELOPER);

    switch ($stateconstant) {
        case advanced_feature::ENABLED:
            return advanced_feature::is_enabled($feature);
        case advanced_feature::DISABLED:
            return advanced_feature::is_disabled($feature);
        case TOTARA_HIDEFEATURE:
            return totara_feature_hidden($feature);
        default:
            throw new coding_exception('Unknown state constant for feature check');
    }
}

/**
 * Check to see if a feature is set to be visible in Advanced Features
 *
 * @deprecated since Totara 13
 * @param string $feature The name of the feature from the list in {@link totara_feature_check_support()}.
 * @return bool True if the feature is set to be visible.
 */
function totara_feature_visible($feature) {
    debugging('totara_feature_visible() has been deprecated in favour of \totara_core\advanced_feature::is_enabled().', DEBUG_DEVELOPER);

    return advanced_feature::is_enabled($feature);
}

/**
 * Check to see if a feature is set to be disabled in Advanced Features
 *
 * @deprecated since Totara 13
 * @param string $feature The name of the feature from the list in {@link totara_feature_check_support()}.
 * @return bool True if the feature is disabled.
 */
function totara_feature_disabled($feature) {
    debugging('totara_feature_disabled() has been deprecated in favour of \totara_core\advanced_feature::is_disabled().', DEBUG_DEVELOPER);

    return advanced_feature::is_disabled($feature);
}

/**
 * Check to see if a feature is set to be hidden in Advanced Features
 * Hidden is not supported anymore, use only enabled or disabled!
 *
 * @deprecated since Totara 13
 * @param string $feature The name of the feature from the list in {@link totara_feature_check_support()}.
 * @return bool True if the feature is hidden.
 */
function totara_feature_hidden($feature) {
    debugging('Hiding features is not supported anymore, features can only be enabled or disabled.', DEBUG_DEVELOPER);

    return advanced_feature::check($feature, TOTARA_HIDEFEATURE);
}
