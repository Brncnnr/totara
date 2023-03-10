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
 * @subpackage totara_customfield
 */

class customfield_define_base {

    /**
     * Prints out the form snippet for creating or editing a custom field
     * @param object instance of the moodleform class
     * @param int $unused1 This argument is no longer used as of Totara 17.0.
     * @param string $unused2 This argument is no longer used as of Totara 17.0.
     */
    function define_form(&$form, $unused1 = null, $unused2 = null) {
        if ($unused1 !== null || $unused2 !== null) {
            debugging(
                'customfield_define_base::define_form() second or third argument is no longer used, please review your code',
                DEBUG_DEVELOPER
            );
        }
        $form->addElement('header', '_commonsettings', get_string('commonsettings', 'totara_customfield'));
        $this->define_form_common($form);

        $form->addElement('header', '_specificsettings', get_string('specificsettings', 'totara_customfield'));
        $this->define_form_specific($form);
    }

    /**
     * Prints out the form snippet for the part of creating or
     * editing a custom field common to all data types
     * @param object $form instance of the moodleform class
     * @param int $unused1 This argument is no longer used as of Totara 17.0.
     * @param string $unused2 This argument is no longer used as of Totara 17.0.
     */
    function define_form_common(&$form, $unused1 = null, $unused2 = null) {
        if ($unused1 !== null || $unused2 !== null) {
            debugging(
                'customfield_define_base::define_form_common() second or third argument is no longer used, please review your code',
                DEBUG_DEVELOPER
            );
        }

        global $TEXTAREA_OPTIONS;
        $strrequired = get_string('customfieldrequired', 'totara_customfield');

        $form->addElement('text', 'fullname', get_string('fullname'), 'size="50"');
        $form->addRule('fullname', $strrequired, 'required', null, 'client');
        $form->setType('fullname', PARAM_TEXT);
        $form->addHelpButton('fullname', 'customfieldfullname', 'totara_customfield');

        $form->addElement('text', 'shortname', get_string('shortname', 'totara_customfield'), 'maxlength="100" size="25"');
        $form->addRule('shortname', $strrequired, 'required', null, 'client');
        $form->addRule('shortname', null, 'alphanumeric', null, 'client');
        $form->setType('shortname', PARAM_ALPHANUM);
        $form->addHelpButton('shortname', 'customfieldshortname', 'totara_customfield');

        $form->addElement('editor', 'description_editor', get_string('description', 'totara_customfield'), null, $TEXTAREA_OPTIONS);
        $form->setType('description_editor', PARAM_CLEANHTML);
        $form->addHelpButton('description_editor', 'description', 'totara_customfield');

        $form->addElement('selectyesno', 'required', get_string('customfieldrequired', 'totara_customfield'));
        $form->addHelpButton('required', 'customfieldrequired', 'totara_customfield');
        $form->setDefault('required', 0);

        $form->addElement('selectyesno', 'locked', get_string('locked', 'totara_customfield'));
        $form->addHelpButton('locked', 'customfieldlocked', 'totara_customfield');
        $form->setDefault('locked', 0);

        // Unique disabled for filepicker and URL custom fields.
        if ($form->getElementValue('datatype') != 'file' && $form->getElementValue('datatype') != 'url') {
            $form->addElement('selectyesno', 'forceunique', get_string('forceunique', 'totara_customfield'));
            $form->addHelpButton('forceunique', 'customfieldforceunique', 'totara_customfield');
        } else {
            $form->addElement('hidden', 'forceunique', '0');
            $form->setType('forceunique', PARAM_INT);
        }

        $form->addElement('selectyesno', 'hidden', get_string('visible', 'totara_customfield'));
        $form->addHelpButton('hidden', 'customfieldhidden', 'totara_customfield');

    }

    /**
     * Prints out the form snippet for the part of creating or
     * editing a custom field specific to the current data type
     * @param   object   instance of the moodleform class
     */
    function define_form_specific(&$form) {
        /// do nothing - override if necessary
    }

    /**
     * Validate the data from the add/edit custom field form.
     * Generally this method should not be overwritten by child
     * classes.
     * @param   object   data from the add/edit custom field form
     * @return  array    associative array of error messages
     */
    function define_validate($data, $files, $typeid, $tableprefix) {

        $data = (object)$data;
        $err = array();

        $err += $this->define_validate_common($data, $files, $typeid, $tableprefix);
        $err += $this->define_validate_specific($data, $files, $tableprefix);

        return $err;
    }

    /**
     * Validate the data from the add/edit custom field form
     * that is common to all data types. Generally this method
     * should not be overwritten by child classes.
     * @param   object   data from the add/edit custom field form
     * @return  array    associative array of error messages
     */
    function define_validate_common($data, $files, $typeid, $tableprefix) {
        global $DB;

        $err = array();

        /// Check the shortname was not truncated by cleaning
        if (empty($data->shortname)) {
            $err['shortname'] = get_string('customfieldrequired', 'totara_customfield');

        } else {
            /// Fetch field-record from DB
            $params = array('shortname' => $data->shortname);
            if ($typeid) {
                $params['typeid'] = $typeid;
            }

            $field = $DB->get_record($tableprefix.'_info_field', $params);
        /// Check the shortname is unique
            if ($field and $field->id <> $data->id) {
                $err['shortname'] = get_string('shortnamenotunique', 'totara_customfield');
            }
        }

        /// No further checks necessary as the form class will take care of it
        return $err;
    }

    /**
     * Validate the data from the add/edit custom field form
     * that is specific to the current data type
     * @param   object   data from the add/edit custom field form
     * @return  array    associative array of error messages
     */
    function define_validate_specific($data, $files, $tableprefix) {
        /// do nothing - override if necessary
        return array();
    }

    /**
     * Alter form based on submitted or existing data
     * @param   object   form
     */
    function define_after_data(&$form) {
        /// do nothing - override if necessary
    }

    /**
     * Format the customfield data to be saved.
     * @param   object $data Data from the add/edit custom field form
     * @param   string $tableprefix The table prefix where the custom field is
     * @param \totara_customfield\prefix\type_base|null $customfieldtype The custom field type if known.
     * @return  data to be saved
     */
    function define_save($data, $tableprefix, \totara_customfield\prefix\type_base $customfieldtype = null) {
        global $DB, $TEXTAREA_OPTIONS;
        $old = null;

        if (!empty($data->id)) {
            $old = $DB->get_record($tableprefix.'_info_field', array('id' => $data->id));
            $data->sortorder = $old->sortorder;
        }
        $data->tableprefix = $tableprefix;
        $data = $this->define_save_preprocess($data, $old); // Hook for child classes.
        if (empty($data->sortorder)) {
            if ($customfieldtype) {
                // We have a customfieldtype, lets just ask it for the next sortorder.
                $data->sortorder = $customfieldtype->get_next_sortorder();
            } else {
                // This is a guess. It'll work providing you don't have types or anything like that.
                $data->sortorder = $DB->count_records_select($tableprefix . '_info_field', '') + 1;
            }
        }

        if (empty($data->id)) {
            unset($data->id);
            $data->id = $DB->insert_record($tableprefix.'_info_field', $data);

            // Trigger created event
            $event = \totara_customfield\event\customfield_created::create_by_type($data->id, $tableprefix, (array)$data);
            $event->trigger();
        } else {
            $DB->update_record($tableprefix.'_info_field', $data);

            // Trigger update event
            $event = \totara_customfield\event\customfield_updated::create_by_type($data->id, $tableprefix, (array)$data);
            $event->trigger();
        }
        $data = file_postupdate_standard_editor($data, 'description', $TEXTAREA_OPTIONS, $TEXTAREA_OPTIONS['context'], 'totara_customfield', 'textarea', $data->id);
        $DB->set_field($tableprefix.'_info_field', 'description', $data->description, array('id' => $data->id));
        if ($data->datatype == 'textarea') {
            $data = file_postupdate_standard_editor($data, 'defaultdata', $TEXTAREA_OPTIONS, $TEXTAREA_OPTIONS['context'], 'totara_customfield', 'textarea', $data->id);
            $DB->set_field($tableprefix.'_info_field', 'defaultdata', $data->defaultdata, array('id' => $data->id));
        }
        if ($customfieldtype) {
            // If we have a customfield type lets ask it to absolutely ensure that the sortorder is correct.
            $customfieldtype->reorder_fields();
        }
    }

    /**
     * Preprocess data from the add/edit custom field form
     * before it is saved. This method is a hook for the child
     * classes to override.
     * @param   object   data from the add/edit custom field form
     * @param   object   previous data record
     * @return  object   processed data object
     */
    function define_save_preprocess($data, $old = null) {
        /// do nothing - override if necessary
        return $data;
    }

    /**
     * Preprocess data from the add/edit custom field form
     * before it is loaded. This method is a hook for the child
     * classes to override.
     * @param   object   data from the add/edit custom field table
     * @return  object   processed data object
     */
    public function define_load_preprocess($data) {
        // Do nothing - override if necessary.
        return $data;
    }

}
