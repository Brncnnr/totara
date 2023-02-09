<?php
/*
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
* @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
* @package block_totara_user_profile
*/


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/edit_form.php');

/**
 * Class block_totara_user_profile_edit_form
 *
 * This is the edit form for the block
 */
class block_totara_user_profile_edit_form extends block_edit_form {

    /**
     * Defines the form for the custom block options
     *
     * @param MoodleQuickForm $mform
     */
    protected function specific_definition($mform) {
        global $DB, $USER, $CFG;
        parent::specific_definition($mform);
        $mform->addElement('header', 'configheader', get_string('customblocksettings', 'block'));

        if (has_any_capability(['moodle/block:edit', 'moodle/user:manageblocks'], $this->block->context)) {
            $user = $DB->get_record('user', array('id' => $USER->id), '*', MUST_EXIST);
            $tree = core_user\output\myprofile\manager::build_tree($user, true, null, true);

            // We also need to ensure empty custom fields are included.
            $nodes = $tree->nodes;
            if ($categories = $DB->get_records('user_info_category', null, 'sortorder ASC')) {
                foreach ($categories as $category) {
                    if ($fields = $DB->get_records('user_info_field', array('categoryid' => $category->id), 'sortorder ASC')) {
                        foreach ($fields as $field) {
                            require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
                            $newfield = 'profile_field_'.$field->datatype;
                            $formfield = new $newfield($field->id, $user->id);

                            if (isset($nodes['custom_field_' . $formfield->field->shortname])) {
                                continue;
                            }

                            if ($formfield->is_visible() and $formfield->is_empty()) {
                                $node = new core_user\output\myprofile\node('contact', 'custom_field_' . $formfield->field->shortname,
                                    format_string($formfield->field->name), null, null, $formfield->display_data());
                                $tree->add_node($node);
                            }
                        }
                    }
                }
            }

            $admintree = block_totara_user_profile::block_user_profile_settings($tree);
            $admintree->sort_categories();
            $categories = $admintree->categories;
            $catitems = [];
            $groups = [];

            //Show proper fields depends on chosen category
            foreach ($categories as $category => $cat) {
                $catitems[$category] = $cat->title;
                $nodeobjs = [];
                foreach ($cat->nodes as $nodename => $node) {
                    $nodeelemname = "config_" .  $nodename;
                    $elem_node  = $mform->createElement('advcheckbox', $nodeelemname, '', $node->title);
                    $mform->setDefault($nodeelemname, 1);
                    $nodeobjs[] = $elem_node;
                }

                //Prevent display only one field in category
                if (sizeof($nodeobjs) > 1) {
                    $groups[$category] = $mform->createElement(
                        'group',
                        $category . '_grp',
                        get_string('hidecategorynode', 'block_totara_user_profile', $cat->title),
                        $nodeobjs,
                        '</br>',
                        false
                    );
                }
            }
            $mform->addElement('select', 'config_category', get_string('displaycategory', 'block_totara_user_profile'), $catitems);

            foreach ($groups as $category => $group) {
                $mform->addElement($group);
                $mform->hideIf($category . '_grp', 'config_category', 'noteq', $category);
            }
        } else {
            $mform->addElement('static', 'warning', '', get_string('userwarning', 'block_totara_user_profile'));
                  return;
        }
    }
}
