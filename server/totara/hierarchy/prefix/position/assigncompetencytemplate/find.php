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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @package totara
 * @subpackage totara_hierarchy
 */

defined('MOODLE_INTERNAL') || die;

if (function_exists('debugging')) {
    debugging('totara/hierarchy/prefix/position/assigncompetencytemplate/find.php has been deprecated, please remove all includes.');
}

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/totara/core/dialogs/dialog_content_hierarchy.class.php');

require_once($CFG->dirroot.'/totara/hierarchy/prefix/competency/lib.php');
require_once($CFG->dirroot.'/totara/core/js/lib/setup.php');
require_once($CFG->dirroot.'/totara/hierarchy/prefix/position/lib.php');

position::check_feature_enabled();

// Page title
$pagetitle = 'assigncompetencytemplates';

///
/// Params
///

// Assign to id
$assignto = required_param('assignto', PARAM_INT);

// Framework id
$frameworkid = optional_param('frameworkid', 0, PARAM_INT);

// Only return generated tree html
$treeonly = optional_param('treeonly', false, PARAM_BOOL);

///
/// Permissions checks
///

// Setup page
admin_externalpage_setup('positionmanage');

// Load currently assigned competency templates
$positions = new position();
if (!$currentlyassigned = $positions->get_assigned_competency_templates($assignto, $frameworkid)) {
    $currentlyassigned = array();
}

///
/// Display page
///

// Load dialog content generator
$dialog = new totara_dialog_content_hierarchy_multi('competency', $frameworkid);

// Templates only
$dialog->templates_only = true;

// Toggle treeview only display
$dialog->show_treeview_only = $treeonly;

// Load competency templates to display
$dialog->items = $dialog->hierarchy->get_templates();

// Set disabled items
$dialog->disabled_items = $currentlyassigned;

// Set strings
$dialog->string_nothingtodisplay = 'notemplateinframework';
$dialog->select_title = 'locatecompetencytemplate';
$dialog->selected_title = 'selectedcompetencytemplates';

// Disable framework picker
$dialog->disable_picker = true;

// Display
echo $dialog->generate_markup();
