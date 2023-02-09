<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_tenant
 */

use totara_tenant\local\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Adds tenant management links to category nav.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param context $context The context of the course
 * @return void|null return null if we don't want to display the node.
 */
function totara_tenant_extend_navigation_category_settings($navigation, $context) {
    global $PAGE, $CFG, $DB, $USER;

    if (empty($CFG->tenantsenabled)) {
        return null;
    }

    if (!$context->tenantid) {
        return null;
    }

    if (!($context instanceof context_coursecat)) {
        return;
    }

    $tenant = $DB->get_record('tenant', ['categoryid' => $context->instanceid]);
    if (!$tenant) {
        return null;
    }
    $tenantcontext = context_tenant::instance($tenant->id);
    $categorycontext = context_coursecat::instance($tenant->categoryid);

    // Put Tenant nodes into new folder.
    if (!empty($USER->tenantid)) {
        $strtenant = get_string('usermanagement', 'totara_tenant');
        if ($tenant->categoryid == $context->instanceid) {
            $categoryname = $DB->get_field('course_categories', 'name', ['id' => $tenant->categoryid]);
            $navigation->text = format_string($categoryname);
        }
    } else {
        $strtenant = format_string($tenant->name);
    }
    $tenantnode = navigation_node::create($strtenant, null, $navigation::TYPE_CATEGORY, null, null);
    $tenantnode->nodetype = navigation_node::NODETYPE_BRANCH;

    $canviewparticipants = false;
    if (has_capability('totara/tenant:viewparticipants', $categorycontext)) {
        $canviewparticipants = true;
    } else if (has_capability('totara/tenant:view', $tenantcontext) and has_capability('moodle/user:viewalldetails', $tenantcontext)) {
        $canviewparticipants = true;
    }

    if ($canviewparticipants) {
        if (!empty($USER->tenantid)) {
            $strusers = get_string('users');
        } else {
            $strusers = get_string('participants', 'totara_tenant');
        }

        $url = new moodle_url('/totara/tenant/participants.php', ['id' => $tenant->id]);
        $node = navigation_node::create(
            $strusers,
            $url,
            navigation_node::NODETYPE_LEAF,
            null,
            null,
            new pix_icon('i/users', '')
        );
        $newuserurl = new moodle_url('/totara/tenant/user_create.php', ['tenantid' => $tenant->id]);
        $otherurl = new moodle_url('/totara/tenant/participants_other.php', ['id' => $tenant->id]);
        if ($PAGE->url->compare($url, URL_MATCH_EXACT) or $PAGE->url->compare($newuserurl, URL_MATCH_EXACT) or $PAGE->url->compare($otherurl, URL_MATCH_EXACT)) {
            $node->make_active();
        }
        $tenantnode->add_node($node);
    }

    // Assign local roles
    $assignableroles = get_assignable_roles($tenantcontext);
    if (!empty($assignableroles)) {
        $assignurl = new moodle_url('/admin/roles/assign.php', array('contextid' => $tenantcontext->id));
        $tenantnode->add(get_string('assignroles', 'role'), $assignurl, $tenantnode::TYPE_SETTING, null, 'roles', new pix_icon('i/assignroles', ''));
    }
    // Override roles
    if (has_capability('moodle/role:review', $tenantcontext) or count(get_overridable_roles($tenantcontext)) > 0) {
        $url = new moodle_url('/admin/roles/permissions.php', array('contextid' => $tenantcontext->id));
        $tenantnode->add(get_string('permissions', 'role'), $url, $tenantnode::TYPE_SETTING, null, 'permissions', new pix_icon('i/permissions', ''));
    }
    // Check role permissions
    if (has_any_capability(array('moodle/role:assign', 'moodle/role:safeoverride',
        'moodle/role:override', 'moodle/role:assign'), $tenantcontext)) {
        $url = new moodle_url('/admin/roles/check.php', array('contextid' => $tenantcontext->id));
        $tenantnode->add(get_string('checkpermissions', 'role'), $url, $tenantnode::TYPE_SETTING, null, 'checkpermissions', new pix_icon('i/checkpermissions', ''));
    }

    // Upload users
    if (has_capability('totara/tenant:userupload', $tenantcontext)) {
        $url = new moodle_url("/totara/tenant/user_upload.php", ['tenantid' => $tenant->id]);
        $tenantnode->add(get_string('uploadusers', 'totara_tenant'), $url, $tenantnode::TYPE_SETTING, null, 'uploadusers');
    }

    if ($tenantnode->children) {
        $tcurl = new moodle_url('/course/index.php', ['categoryid' => $tenant->categoryid]);
        if ($PAGE->url->compare($tcurl, URL_MATCH_EXACT)) {
            $tenantnode->force_open();
        }
        if ($navigation->parent) {
            $navigation->parent->add_node($tenantnode);
        } else {
            $navigation->add_node($tenantnode);
        }
    }
}

/**
 * Serves CSV templates.
 *
 * @param stdClass $course course object
 * @param cm_info $cm course module object
 * @param context $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool|void false if file not found, does not return if found - just send the file
 */
function totara_tenant_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG, $DB;

    if ($context->contextlevel != CONTEXT_TENANT) {
        return false;
    }

    require_login(null, false, null, false);

    if ($filearea !== 'csvtemplate') {
        return false;
    }

    if (!has_capability('totara/tenant:userupload', $context)) {
        return false;
    }

    $filename = array_shift($args);
    if ($filename !== 'users.csv') {
        return false;
    }

    $columns = array_merge(util::get_csv_required_columns(true), util::get_csv_optional_columns(true));
    $content = implode(',', $columns) . "\n" . str_repeat(',', count($columns) - 1) . "\n";

    send_file($content, $filename, 0, 0, true, true, 'text/csv');
}
