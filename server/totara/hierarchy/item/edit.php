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
 * @subpackage hierarchy
 */

use core\notification;
use totara_core\advanced_feature;
use totara_hierarchy\event\hierarchy_created;
use totara_hierarchy\event\hierarchy_updated;

require_once(__DIR__ . '/../../../config.php');
/** @var core_config $CFG */
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/totara/customfield/fieldlib.php');
require_once($CFG->dirroot.'/totara/hierarchy/item/edit_form.php');
require_once($CFG->dirroot.'/totara/hierarchy/lib.php');


///
/// Setup / loading data
///

$prefix = required_param('prefix', PARAM_ALPHA);
$shortprefix = hierarchy::get_short_prefix($prefix);

// item id; 0 if creating new item
$id   = optional_param('id', 0, PARAM_INT);

// framework id; required when creating a new framework item
$frameworkid = optional_param('frameworkid', 0, PARAM_INT);
$page       = optional_param('page', 0, PARAM_INT);

hierarchy::check_enable_hierarchy($prefix);

$hierarchy = hierarchy::load_hierarchy($prefix);

// We require either an id for editing, or a framework for creating
if (!$id && !$frameworkid) {
    print_error('incorrectparameters', 'totara_hierarchy');
}

// Make this page appear under the manage competencies admin item
admin_externalpage_setup($prefix.'manage', '', array('prefix' => $prefix));

$context = context_system::instance();

if ($id == 0) {
    // creating new item
    require_capability('totara/hierarchy:create'.$prefix, $context);

    $item = new stdClass();
    $item->id = 0;
    $item->description = '';
    $item->frameworkid = $frameworkid;
    $item->visible = 1;
    $item->typeid = 0;

} else {
    // editing existing item
    require_capability('totara/hierarchy:update'.$prefix, $context);

    if (!$item = $hierarchy->retrieve_hierarchy_item($id)) {
        print_error('incorrectid', 'totara_hierarchy');
    }
    $frameworkid = $item->frameworkid;
    // load custom fields data - customfield values need to be available in $item before the call to set_data
    if ($id != 0) {
        customfield_load_data($item, $prefix, $shortprefix.'_type');
    }
}

// Load framework
/** @var moodle_database $DB */
if (!$framework = $DB->get_record($shortprefix.'_framework', array('id' => $frameworkid))) {
    print_error('invalidframeworkid', 'totara_hierarchy', $prefix);
}
$item->framework = $framework->fullname;


///
/// Display page
///

// create form
$item->descriptionformat = FORMAT_HTML;
/** @var array $TEXTAREA_OPTIONS */
$item = file_prepare_standard_editor($item, 'description', $TEXTAREA_OPTIONS, $TEXTAREA_OPTIONS['context'],
                                          'totara_hierarchy', $shortprefix, $item->id);
$datatosend = array('prefix' => $prefix, 'item' => $item, 'page' => $page, 'hierarchy' => $hierarchy);
$itemform = new item_edit_form(null, $datatosend);
$itemform->set_data($item);

// cancelled
if ($itemform->is_cancelled()) {

    if ($prefix === 'competency' && !empty($item->id)) {
        $url = new moodle_url(
            '/totara/hierarchy/item/view.php',
            ['prefix' => $prefix, 'id' => $item->id, 'page' => $page]
        );
    } else {
        $url = new moodle_url(
            '/totara/hierarchy/index.php',
            ['prefix' => $prefix, 'frameworkid' => $item->frameworkid, 'page' => $page]
        );
    }
    redirect($url);

// Update data
} else if ($itemnew = $itemform->get_data()) {
    if (isset($itemnew->changetype)) {
        $url = new moodle_url(
            '/totara/hierarchy/type/change.php',
            [
                'prefix' => $prefix,
                'frameworkid' => $item->frameworkid,
                'page' => $page,
                'typeid' => $itemnew->typeid,
                'itemid' => $itemnew->id
            ]
        );
        redirect($url);
    }

    $submit_and_edit = $itemnew->submit_and_edit ?? false;

    $itemold = $DB->get_record($hierarchy->shortprefix, array('id' => $itemnew->id));

    $itemnew->timemodified = time();
    /** @var object $USER */
    $itemnew->usermodified = $USER->id;

    // Format any fields unique to this type of hierarchy.
    $itemnew = $hierarchy->process_additional_item_form_fields($itemnew);

    // Save
    $notificationtype = notification::ERROR;
    $notificationtext = 'added';
    $notificationurl = new moodle_url('/totara/hierarchy/item/view.php', ['prefix' => $prefix]);

    if ($itemnew->id == 0) {
        // Add New item
        if ($updateditem = $hierarchy->add_hierarchy_item($itemnew, $itemnew->parentid, $itemnew->frameworkid, false, false)) {
            $itemnew->id = $updateditem->id;
            $itemnew = file_postupdate_standard_editor(
                $itemnew,
                'description',
                $TEXTAREA_OPTIONS,
                $TEXTAREA_OPTIONS['context'],
                'totara_hierarchy',
                $shortprefix,
                $itemnew->id
            );
            $DB->set_field($shortprefix, 'description', $itemnew->description, array('id' => $itemnew->id));

            $notificationurl->param('id', $updateditem->id);
            $notificationtype = notification::SUCCESS;
        } else {
            $notificationtext = 'error:add';
            $notificationurl = new moodle_url('/totara/hierarchy/item/index.php', ['prefix' => $prefix]);
        }
    } else {
        // Update existing item
        $transaction = $DB->start_delegated_transaction();
        $updateditem = $hierarchy->update_hierarchy_item($itemnew->id, $itemnew, false, false);
        // Fix the description field and redirect.
        $itemnew = file_postupdate_standard_editor(
            $itemnew,
            'description',
            $TEXTAREA_OPTIONS,
            $TEXTAREA_OPTIONS['context'],
            'totara_hierarchy',
            $shortprefix,
            $itemnew->id
        );
        $DB->set_field($shortprefix, 'description', $itemnew->description, array('id' => $itemnew->id));
        // Update the items custom fields.
        customfield_save_data($itemnew, $prefix, $shortprefix.'_type');
        $transaction->allow_commit();

        $notificationtext = 'updated';
        $notificationurl->param('id', $itemnew->id);
        $notificationtype = notification::SUCCESS;
    }

    $itemnew = $DB->get_record($shortprefix, array('id' => $itemnew->id));
    if ($notificationtext === 'added') {
        /** @var hierarchy_created $eventclass */
        $eventclass = "\\hierarchy_{$prefix}\\event\\{$prefix}_created";
        $eventclass::create_from_instance($itemnew)->trigger();
    } else if ($notificationtext === 'updated') {
        /** @var hierarchy_updated $eventclass */
        $eventclass = "\\hierarchy_{$prefix}\\event\\{$prefix}_updated";
        $event = $eventclass::create_from_old_and_new($itemnew, $itemold);
        $event->trigger();
    }

    notification::add(
        get_string($notificationtext . $prefix, 'totara_hierarchy', format_string($itemnew->fullname)),
        $notificationtype
    );
    if ($submit_and_edit) {
        $notificationurl = new moodle_url(
            '/totara/hierarchy/item/edit.php',
            ['prefix' => $prefix, 'frameworkid' => $frameworkid, 'id' => $itemnew->id]
        );
    }
    redirect($notificationurl);
}
/** @var moodle_page $PAGE */
$PAGE->navbar->add(
    format_string($framework->fullname),
    new moodle_url('/totara/hierarchy/index.php', array('prefix' => $prefix, 'frameworkid' => $framework->id))
);
if ($item->id) {
    if ($prefix !== 'competency') {
        $PAGE->navbar->add(
            format_string($item->fullname),
            new moodle_url('/totara/hierarchy/item/view.php', array('prefix' => $prefix, 'id' => $item->id))
        );
        $PAGE->navbar->add(get_string('edit'.$prefix, 'totara_hierarchy'));
    } else {
        $tab = get_string('competencytabgeneral', 'totara_hierarchy');
        $header = get_string('edit' . $prefix, 'totara_hierarchy', format_string($item->fullname));
        $title = get_string('edit_competency_title', 'totara_hierarchy', ['header' => $header, 'tab' => $tab]);
        $PAGE->navbar->add($header);
        $PAGE->set_title($title);
    }
} else {
    $PAGE->navbar->add(get_string('addnew'.$prefix, 'totara_hierarchy'));
}

/// Display page header
/** @var core_renderer $OUTPUT */
echo $OUTPUT->header();

if ($prefix === 'competency' && !empty($item->id)) {
    $action_link = $OUTPUT->action_link(
        new moodle_url(
            '/totara/hierarchy/item/view.php',
            ['prefix' => $prefix, 'id' => $item->id, 'page' => $page]
        ),
        get_string('competency_back_to_competency_page', 'totara_hierarchy')
    );
    echo $OUTPUT->container($action_link, 'back-link');
}

if ($item->id == 0) {
    echo $OUTPUT->heading(get_string('addnew'.$prefix, 'totara_hierarchy'));
} else {
    $page_title = get_string('edit'.$prefix, 'totara_hierarchy', format_string($item->fullname));
    echo html_writer::div($page_title, 'competency-edit-general-title');
}

if ($prefix === 'competency' && advanced_feature::is_enabled('competency_assignment')) {
    require_once ($CFG->dirroot . '/totara/hierarchy/renderer.php');
    echo $OUTPUT->render(totara_hierarchy_renderer::get_competency_tabs($item->id, 'editgeneral'));
    echo html_writer::tag('h3', get_string('general'), ['class' => 'competency-edit-general-small-title']);
}

/// Finally display THE form
$itemform->display();

/// and proper footer
echo $OUTPUT->footer();
