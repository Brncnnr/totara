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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package totara
 * @subpackage totara_sync
 */

global $CFG;
require_once($CFG->dirroot.'/admin/tool/totara_sync/sources/classes/source.class.php');
require_once($CFG->dirroot.'/admin/tool/totara_sync/elements/user.php');

abstract class totara_sync_source_user extends totara_sync_source {

    public const HAS_CONFIG = true;
    public const USES_FILES = true;

    protected $fields;
    protected $required_fields;
    protected $customfields, $customfieldtitles;

    /**
     * Implement in child classes
     *
     * Populate the temp table to be used by the sync element
     *
     * @return boolean true on success
     * @throws totara_sync_exception if error
     */
    abstract function import_data($temptable);

    function __construct() {
        global $DB, $CFG;

        $this->temptablename = 'totara_sync_user';
        $this->element = new totara_sync_element_user();
        parent::__construct();

        $this->fields = array(
            'idnumber',
            'timemodified',
            'username',
            'deleted',
            'firstname',
            'lastname',
            'firstnamephonetic',
            'lastnamephonetic',
            'middlename',
            'alternatename',
            'email',
            'emailstop',
            'city',
            'country',
            'timezone',
            'lang',
            'description',
            'url',
            'institution',
            'department',
            'phone1',
            'phone2',
            'address',
            'auth',
            'password',
            'suspended',
        );

        // Fields that require a value if they are included.
        $this->required_fields = array(
            'idnumber',
            'username',
            'firstname',
            'lastname',
            'email',
            'auth'
        );

        // Add multitenancy fields, if enabled.
        if ($CFG->tenantsenabled) {
            $this->fields[] = 'tenantmember';
            $this->fields[] = 'tenantparticipant';
        }

        // Custom fields
        $this->customfields = array();
        $this->customfieldtitles = array();
        $cfields = $DB->get_records('user_info_field');
        foreach ($cfields as $cf) {
            $this->customfields['customfield_'.$cf->shortname] = $cf->shortname;
            $this->customfieldtitles['customfield_'.$cf->shortname] = $cf->name;
        }

    }

    function get_element_name() {
        return 'user';
    }

    /**
     * Override in child classes
     */
    function uses_files() {
        return self::USES_FILES;
    }

    /**
     * Override in child classes
     */
    function get_filepath() {}

    function has_config() {
        return self::HAS_CONFIG;
    }

    /**
     * @param $mform MoodleQuickForm
     */
    function config_form(&$mform) {
        // Fields to import
        $mform->addElement('header', 'importheader', get_string('importfields', 'tool_totara_sync'));
        $mform->setExpanded('importheader');

        foreach ($this->fields as $f) {
            $name = 'import_'.$f;
            if (in_array($f, array('idnumber', 'username', 'timemodified'))) {
                $mform->addElement('hidden', $name, '1');
                $mform->setType($name, PARAM_INT);
            } else if (in_array($f, array('firstname', 'lastname')) && !empty($this->element->config->allow_create)) {
                $mform->addElement('hidden', $name, '1');
                $mform->setType($name, PARAM_INT);
            } else if ($f == 'email' && (!isset($this->element->config->allowduplicatedemails) || !$this->element->config->allowduplicatedemails)) {
                $mform->addElement('hidden', $name, '1');
                $mform->setType($name, PARAM_INT);
            } else if ($f == 'deleted') {
                $mform->addElement('hidden', $name, $this->config->$name);
                $mform->setType($name, PARAM_INT);
            } else {
                $mform->addElement('checkbox', $name, get_string($f, 'tool_totara_sync'));
                if (in_array($f, array('country'))) {
                    $mform->addHelpButton($name, $f, 'tool_totara_sync');
                }
                if (in_array($f, array('tenantmember'))) {
                    $mform->addHelpButton($name, $f, 'tool_totara_sync');
                }
                if (in_array($f, array('tenantparticipant'))) {
                    $mform->addHelpButton($name, $f, 'tool_totara_sync');
                }
            }
        }

        foreach ($this->customfieldtitles as $field => $name) {
            $mform->addElement('checkbox', 'import_'.$field, $name);
        }

        // Field mappings
        $mform->addElement('header', 'mappingshdr', get_string('fieldmappings', 'tool_totara_sync'));
        $mform->setExpanded('mappingshdr');

        foreach ($this->fields as $f) {
            $name = 'fieldmapping_' . $f;

            $mform->addElement('text', $name, $f);
            $mform->setType($name, PARAM_TEXT);
        }

        foreach ($this->customfields as $key => $f) {
            $mform->addElement('text', 'fieldmapping_'.$key, $f);
            $mform->setType('fieldmapping_'.$key, PARAM_TEXT);
        }
    }

    function config_save($data) {

        foreach ($this->fields as $f) {
            $this->set_config('import_'.$f, !empty($data->{'import_'.$f}));
        }
        foreach (array_keys($this->customfields) as $f) {
            $this->set_config('import_'.$f, !empty($data->{'import_'.$f}));
        }
        foreach ($this->fields as $f) {
            $this->set_config('fieldmapping_'.$f, $data->{'fieldmapping_'.$f});
        }
        foreach (array_keys($this->customfields) as $f) {
            $this->set_config('fieldmapping_'.$f, $data->{'fieldmapping_'.$f});
        }
    }

    function get_sync_table() {
        try {
            $temptable = $this->prepare_temp_table();
        } catch (dml_exception $e) {
            throw new totara_sync_exception($this->get_element_name(), 'importdata',
                'temptableprepfail', $e->getMessage());
        }

        $this->import_data($temptable->getName());

        return $temptable->getName();
    }

    function prepare_temp_table($clone = false) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/lib/ddllib.php');

        /// Instantiate table
        $tablename = $this->temptablename;
        if ($clone) {
            $tablename .= '_clone';
        }
        $dbman = $DB->get_manager();
        $table = new xmldb_table($tablename);

        /// Add fields
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('idnumber', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('username', XMLDB_TYPE_CHAR, '100');
        if (!empty($this->config->import_deleted)) {
            $table->add_field('deleted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        }
        if (!empty($this->config->import_firstname)) {
            $table->add_field('firstname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
        }
        if (!empty($this->config->import_lastname)) {
            $table->add_field('lastname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
        }
        if (!empty($this->config->import_firstnamephonetic)) {
            $table->add_field('firstnamephonetic', XMLDB_TYPE_CHAR, '255');
        }
        if (!empty($this->config->import_lastnamephonetic)) {
            $table->add_field('lastnamephonetic', XMLDB_TYPE_CHAR, '255');
        }
        if (!empty($this->config->import_middlename)) {
            $table->add_field('middlename', XMLDB_TYPE_CHAR, '255');
        }
        if (!empty($this->config->import_alternatename)) {
            $table->add_field('alternatename', XMLDB_TYPE_CHAR, '255');
        }
        if (!empty($this->config->import_email)) {
            $table->add_field('email', XMLDB_TYPE_CHAR, '100');
        }
        if (!empty($this->config->import_city)) {
            $table->add_field('city', XMLDB_TYPE_CHAR, '120');
        }
        // Country codes are always 2 chars but extended here so we can use a sanity check for better report logging.
        if (!empty($this->config->import_country)) {
            $table->add_field('country', XMLDB_TYPE_CHAR, '30');
        }
        if (!empty($this->config->import_timezone)) {
            $table->add_field('timezone', XMLDB_TYPE_CHAR, '100');
        }
        if (!empty($this->config->import_lang)) {
            $table->add_field('lang', XMLDB_TYPE_CHAR, '30');
        }
        if (!empty($this->config->import_description)) {
            $table->add_field('description', XMLDB_TYPE_TEXT, 'medium');
        }
        if (!empty($this->config->import_url)) {
            $table->add_field('url', XMLDB_TYPE_CHAR, '255');
        }
        if (!empty($this->config->import_institution)) {
            $table->add_field('institution', XMLDB_TYPE_CHAR, '255');
        }
        if (!empty($this->config->import_department)) {
            $table->add_field('department', XMLDB_TYPE_CHAR, '255');
        }
        if (!empty($this->config->import_phone1)) {
            $table->add_field('phone1', XMLDB_TYPE_CHAR, '20');
        }
        if (!empty($this->config->import_phone2)) {
            $table->add_field('phone2', XMLDB_TYPE_CHAR, '20');
        }
        if (!empty($this->config->import_address)) {
            $table->add_field('address', XMLDB_TYPE_CHAR, '255');
        }
        if (!empty($this->config->import_auth)) {
            $table->add_field('auth', XMLDB_TYPE_CHAR, '20');
        }
        if (!empty($this->config->import_password)) {
            $table->add_field('password', XMLDB_TYPE_CHAR, '255');
        }
        if (!empty($this->config->import_suspended)) {
            $table->add_field('suspended', XMLDB_TYPE_INTEGER, '1');
        }
        if (!empty($this->config->import_emailstop)) {
            $table->add_field('emailstop', XMLDB_TYPE_INTEGER, '1');
        }
        if (!empty($this->config->import_tenantmember)) {
            $table->add_field('tenantmember', XMLDB_TYPE_CHAR, '100');
        }
        if (!empty($this->config->import_tenantparticipant)) {
            $table->add_field('tenantparticipant', XMLDB_TYPE_TEXT);
        }
        $table->add_field('customfields', XMLDB_TYPE_TEXT, 'big');

        /// Add keys
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        /// Add indexes
        $table->add_index('username', XMLDB_INDEX_NOTUNIQUE, array('username'));
        $table->add_index('idnumber', XMLDB_INDEX_NOTUNIQUE, array('idnumber'));
        if (!empty($this->config->import_deleted)) {
            $table->add_index('deleted', XMLDB_INDEX_NOTUNIQUE, array('deleted'));
        }
        if (!empty($this->config->import_email)) {
            $table->add_index('email', XMLDB_INDEX_NOTUNIQUE, array('email'));
        }

        /// Create and truncate the table
        $dbman->create_temp_table($table, false, false);
        $DB->execute("TRUNCATE TABLE {{$tablename}}");

        return $table;
    }
}
