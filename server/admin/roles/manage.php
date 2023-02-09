<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Lets the user define and edit roles.
 *
 * Responds to actions:
 *   [blank]   - list roles.
 *   delete    - delete a role (with are-you-sure)
 *   moveup    - change the sort order
 *   movedown  - change the sort order
 *
 * For all but the first two of those, you also need a roleid parameter, and
 * possibly some other data.
 *
 * @package    core_role
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot . '/' . $CFG->admin . '/roles/lib.php');

$action = optional_param('action', '', PARAM_ALPHA);
if ($action) {
    $roleid = required_param('roleid', PARAM_INT);
} else {
    $roleid = 0;
}

// Get the base URL for this and related pages into a convenient variable.
$baseurl = $CFG->wwwroot . '/' . $CFG->admin . '/roles/manage.php';
$defineurl = $CFG->wwwroot . '/' . $CFG->admin . '/roles/define.php';

// Check access permissions.
$systemcontext = context_system::instance();
require_login();
require_capability('moodle/role:manage', $systemcontext);
admin_externalpage_setup('defineroles');

// Get some basic data we are going to need.
$roles = role_fix_names(get_all_roles(), $systemcontext, ROLENAME_ORIGINAL);

// Check all assigned roles under User Policies and other references (e.g. self-enrolment default role).
$hook = new \core_role\hook\discover_undeletable_roles(
    core_role_user_policies::get_roles()
);
$hook->execute();

// Process submitted data.
$confirmed = (optional_param('confirm', false, PARAM_BOOL) && data_submitted() && confirm_sesskey());
switch ($action) {
    case 'delete':
        if ($hook->is_role_undeletable($roleid)) {
            redirect(
                new moodle_url($baseurl),
                get_string('cannotdeletethisrole', 'error'),
                null,
                \core\notification::ERROR
            );
        }
        if (!$confirmed) {
            // Show confirmation.
            echo $OUTPUT->header();
            $optionsyes = array('action'=>'delete', 'roleid'=>$roleid, 'sesskey'=>sesskey(), 'confirm'=>1);
            $a = new stdClass();
            $a->id = $roleid;
            $a->name = $roles[$roleid]->name;
            $a->shortname = $roles[$roleid]->shortname;
            $a->count = $DB->count_records_select('role_assignments', 'roleid = ?', [$roleid], 'COUNT(DISTINCT userid)');

            $formcontinue = new single_button(new moodle_url($baseurl, $optionsyes), get_string('yes'));
            $formcancel = new single_button(new moodle_url($baseurl), get_string('no'), 'get');
            echo $OUTPUT->confirm(get_string('deleterolesure', 'core_role', $a), $formcontinue, $formcancel);
            echo $OUTPUT->footer();
            die;
        }
        if (!delete_role($roleid)) {
            // The delete failed.
            redirect(
                new moodle_url($baseurl),
                get_string('cannotdeleterolewithid', 'error', $baseurl, $roleid),
                null,
                \core\notification::ERROR
            );
        }
        // Deleted a role sitewide...
        redirect($baseurl);
        break;

    case 'moveup':
        if (confirm_sesskey()) {
            $prevrole = null;
            $thisrole = null;
            foreach ($roles as $role) {
                if ($role->id == $roleid) {
                    $thisrole = $role;
                    break;
                } else {
                    $prevrole = $role;
                }
            }
            if (is_null($thisrole) || is_null($prevrole)) {
                print_error('cannotmoverolewithid', 'error', '', $roleid);
            }
            if (!switch_roles($thisrole, $prevrole)) {
                print_error('cannotmoverolewithid', 'error', '', $roleid);
            }
        }

        redirect($baseurl);
        break;

    case 'movedown':
        if (confirm_sesskey()) {
            $thisrole = null;
            $nextrole = null;
            foreach ($roles as $role) {
                if ($role->id == $roleid) {
                    $thisrole = $role;
                } else if (!is_null($thisrole)) {
                    $nextrole = $role;
                    break;
                }
            }
            if (is_null($nextrole)) {
                print_error('cannotmoverolewithid', 'error', '', $roleid);
            }
            if (!switch_roles($thisrole, $nextrole)) {
                print_error('cannotmoverolewithid', 'error', '', $roleid);
            }
        }

        redirect($baseurl);
        break;

}

// Print the page header and tabs.
echo $OUTPUT->header();

$currenttab = 'manage';
require('managetabs.php');

// Initialise table.
$table = new html_table();
$table->colclasses = array('leftalign', 'leftalign', 'leftalign', 'leftalign');
$table->id = 'roles';
$table->attributes['class'] = 'admintable generaltable';
$table->head = array(
    get_string('role') . ' ' . $OUTPUT->help_icon('roles', 'core_role'),
    get_string('description'),
    get_string('roleshortname', 'core_role'),
    get_string('roleassignmentsnum', 'totara_core'), // Totara: show number of role assignments.
    get_string('edit')
);

// Get some strings outside the loop.
$stredit = get_string('edit');
$strdelete = get_string('delete');
$strmoveup = get_string('moveup');
$strmovedown = get_string('movedown');

// Print a list of roles with edit/copy/delete/reorder icons.
$table->data = array();
$firstrole = reset($roles);
$lastrole = end($roles);
foreach ($roles as $role) {
    // Basic data.
    // The hyphen causing the problem in SQL query, lets warn admin.
    $warn = preg_match('/-/', $role->shortname) ?
        $OUTPUT->pix_icon('i/warning', get_string('errorbadroleshortname', 'core_role'), 'moodle') :
        '';
    $rolenamelink = \html_writer::link(
        new \moodle_url('/admin/roles/define.php', ['action' => 'view', 'roleid' => $role->id]),
        $role->localname . $warn
    );
    if ($hook->is_role_undeletable($role->id)) {
        $rolenamelink .= \html_writer::empty_tag('br');
        $rolenamelink .= \html_writer::span(
            $hook->get_undeletable_role_label($role->id),
            'user-policies-title'
        );
    }
    $row = [$rolenamelink, role_get_description($role), s($role->shortname), ''];

    // Move up.
    if ($role->sortorder != $firstrole->sortorder) {
        $row[3] .= get_action_icon($baseurl . '?action=moveup&amp;roleid=' . $role->id . '&amp;sesskey=' . sesskey(), 'up', $strmoveup, $strmoveup);
    } else {
        $row[3] .= get_spacer();
    }
    // Move down.
    if ($role->sortorder != $lastrole->sortorder) {
        $row[3] .= get_action_icon($baseurl . '?action=movedown&amp;roleid=' . $role->id . '&amp;sesskey=' . sesskey(), 'down', $strmovedown, $strmovedown);
    } else {
        $row[3] .= get_spacer();
    }
    // Edit.
    $row[3] .= get_action_icon($defineurl . '?action=edit&amp;roleid=' . $role->id,
            'edit', $stredit, get_string('editxrole', 'core_role', $role->localname));
    // Delete.
    $role_assignment_count = $DB->count_records('role_assignments', array('roleid' => $role->id));
    if ($hook->is_role_undeletable($role->id) || $role_assignment_count > 0) {
        $row[3] .= get_spacer();
    } else {
        $row[3] .= get_action_icon($baseurl . '?action=delete&amp;roleid=' . $role->id,
              'delete', $strdelete, get_string('deletexrole', 'core_role', $role->localname));
    }
    // Totara: show number of users.
    $row[4] = $row[3];
    $row[3] = $role_assignment_count;

    $table->data[] = $row;
}
echo html_writer::table($table);

echo $OUTPUT->container_start('buttons');
echo $OUTPUT->single_button(new moodle_url($defineurl, array('action' => 'add')), get_string('addrole', 'core_role'), 'get');
echo $OUTPUT->container_end();

echo $OUTPUT->footer();
die;

function get_action_icon($url, $icon, $alt, $tooltip) {
    global $OUTPUT;
    return '<a title="' . $tooltip . '" href="'. $url . '">' .
            $OUTPUT->flex_icon('core|t/' . $icon, array('alt' => $alt)) . '</a> ';
}
function get_spacer() {
    global $OUTPUT;
    return $OUTPUT->flex_icon('spacer');
}
