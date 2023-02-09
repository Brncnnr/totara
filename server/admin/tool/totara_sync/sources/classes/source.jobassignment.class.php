<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package tool_totara_sync
 */

use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/admin/tool/totara_sync/sources/classes/source.class.php');
require_once($CFG->dirroot.'/admin/tool/totara_sync/elements/jobassignment.php');

abstract class totara_sync_source_jobassignment extends totara_sync_source {

    public const HAS_CONFIG = true;
    public const USES_FILES = true;

    protected $required_fields = array();
    protected $disabled_fields = array();
    protected $noform_fields = array();

    /**
     * Implement in child classes
     *
     * Populate the temp table to be used by the sync element
     *
     * @return boolean true on success
     * @throws totara_sync_exception if error
     */
    abstract function import_data($temptable);

    public function __construct() {
        $this->temptablename = 'totara_sync_jobassignment';
        $this->set_config('import_idnumber', '1');
        $this->set_config('import_useridnumber', '1');
        $this->set_config('import_timemodified', '1');

        $this->fields = array(
            'idnumber',
            'useridnumber',
            'timemodified',
            'deleted',
            'fullname',
            'startdate',
            'enddate',
            'orgidnumber',
            'posidnumber',
            'appraiseridnumber',
            'manageridnumber',
            'tempmanageridnumber'
        );

        $this->required_fields = array(
            'idnumber',
            'useridnumber',
            'timemodified'
        );

        $this->element = new totara_sync_element_jobassignment();
        if (empty($this->element->config->sourceallrecords)) {
            $this->set_config('import_deleted', '1');
            $this->required_fields[] = 'deleted';
        } else {
            $this->set_config('import_deleted', '0');
            $this->disabled_fields[] = 'deleted';
        }

        if (empty($this->element->config->updateidnumbers)) {
            // Manager.
            $this->fields[] = 'managerjaidnumber';
            $this->noform_fields[] = 'managerjaidnumber';
            if ($this->is_importing_field('manageridnumber')) {
                $this->set_config('import_managerjaidnumber', '1');
            }

            // Temporary manager.
            $this->fields[] = 'tempmanagerjaidnumber';
            $this->noform_fields[] = 'tempmanagerjaidnumber';
            if ($this->is_importing_field('tempmanageridnumber')) {
                $this->set_config('import_tempmanagerjaidnumber', '1');
            }
        } else {
            $this->set_config('import_managerjaidnumber', '0');
            $this->set_config('import_tempmanagerjaidnumber', '0');
        }

        // Temporary manager expiry date is required if importing a temporary manager.
        if ($this->is_importing_field('tempmanageridnumber')) {
            $this->fields[] = 'tempmanagerexpirydate';
            $this->noform_fields[] = 'tempmanagerexpirydate';
            $this->set_config('import_tempmanagerexpirydate', '1');
        } else {
            $this->set_config('import_tempmanagerexpirydate', '0');
        }

        if (advanced_feature::is_disabled('positions')) {
            $this->set_config('import_posidnumber', '0');
            $this->disabled_fields[] = 'posidnumber';
        }

        if (advanced_feature::is_disabled('organisations')) {
            $this->set_config('import_orgidnumber', '0');
            $this->disabled_fields[] = 'orgidnumber';
        }

        parent::__construct();
    }

    function get_element_name() {
        return 'jobassignment';
    }

    public function uses_files() {
        return self::USES_FILES;
    }

    /**
     * Override in child classes.
     */
    public function get_filepath() {}

    public function has_config() {
        return self::HAS_CONFIG;
    }

    /**
     * @param MoodleQuickForm $mform
     */
    public function config_form(&$mform) {
        // Fields to import
        $mform->addElement('header', 'importheader', get_string('importfields', 'tool_totara_sync'));
        $mform->setExpanded('importheader');

        foreach ($this->fields as $f) {
            $name = 'import_'.$f;
            if (in_array($f, $this->required_fields)) {
                $mform->addElement('hidden', $name, '1');
                $mform->setType($name, PARAM_INT);
            } else if (in_array($f, $this->disabled_fields)) {
                $mform->addElement('hidden', $name, '0');
                $mform->setType($name, PARAM_INT);
            } else if (!in_array($f, $this->noform_fields)) {
                $mform->addElement('checkbox', $name, get_string($f, 'tool_totara_sync'));
            }
        }

        // Field mappings.
        $mform->addElement('header', 'mappingshdr', get_string('fieldmappings', 'tool_totara_sync'));
        $mform->setExpanded('mappingshdr');

        foreach ($this->fields as $f) {
            $name = 'fieldmapping_' . $f;

            if (in_array($f, $this->disabled_fields)) {
                $mform->addElement('hidden', $name, '0');
                $mform->setType($name, PARAM_INT);
            } else {
                $mform->addElement('text', $name, $f);
                $mform->setType($name, PARAM_TEXT);
            }
        }
    }

    public function config_save($data) {

        foreach ($this->fields as $f) {
            if (!in_array($f, $this->noform_fields)) {
                $this->set_config('import_' . $f, !empty($data->{'import_' . $f}));
            }
        }
        foreach ($this->fields as $f) {
            $this->set_config('fieldmapping_'.$f, $data->{'fieldmapping_'.$f});
        }
    }

    public function set_config($name, $value) {
        // Manager specific.
        if ($name === 'import_manageridnumber'
            and in_array('managerjaidnumber', $this->fields)) {

            parent::set_config('import_managerjaidnumber', $value);
        }
        // Temporary manager specific.
        if ($name === 'import_tempmanageridnumber') {
            if (in_array('tempmanagerjaidnumber', $this->fields)) {
                parent::set_config('import_tempmanagerjaidnumber', $value);
            }
            parent::set_config('import_tempmanagerexpirydate', $value);
        }

        return parent::set_config($name, $value);
    }

    public function get_sync_table() {
        try {
            $temptable = $this->prepare_temp_table();
        } catch (dml_exception $e) {
            throw new totara_sync_exception($this->get_element_name(), 'importdata',
                'temptableprepfail', $e->getMessage());
        }

        $this->import_data($temptable->getName());

        return $temptable->getName();
    }

    public function prepare_temp_table($clone = false) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/lib/ddllib.php');

        // Instantiate table.
        $tablename = $this->temptablename;
        if ($clone) {
            $tablename .= '_clone';
        }
        $dbman = $DB->get_manager();
        $table = new xmldb_table($tablename);

        // Add fields.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('idnumber', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
        $table->add_field('useridnumber', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);

        if ($this->is_importing_field('deleted')) {
            $table->add_field('deleted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        }
        if ($this->is_importing_field('fullname')) {
            $table->add_field('fullname', XMLDB_TYPE_CHAR, '255');
        }
        if ($this->is_importing_field('startdate')) {
            $table->add_field('startdate', XMLDB_TYPE_CHAR, '20');
        }
        if ($this->is_importing_field('enddate')) {
            $table->add_field('enddate', XMLDB_TYPE_CHAR, '20');
        }
        if ($this->is_importing_field('orgidnumber')) {
            $table->add_field('orgidnumber', XMLDB_TYPE_CHAR, '100');
        }
        if ($this->is_importing_field('posidnumber')) {
            $table->add_field('posidnumber', XMLDB_TYPE_CHAR, '100');
        }
        if ($this->is_importing_field('manageridnumber')) {
            $table->add_field('manageridnumber', XMLDB_TYPE_CHAR, '255');
        }
        if ($this->is_importing_field('managerjaidnumber')) {
            $table->add_field('managerjaidnumber', XMLDB_TYPE_CHAR, '100');
        }
        if ($this->is_importing_field('tempmanageridnumber')) {
            $table->add_field('tempmanageridnumber', XMLDB_TYPE_CHAR, '255');
        }
        if ($this->is_importing_field('tempmanagerjaidnumber')) {
            $table->add_field('tempmanagerjaidnumber', XMLDB_TYPE_CHAR, '100');
        }
        if ($this->is_importing_field('tempmanageridnumber') || $this->is_importing_field('tempmanagerjaidnumber')) {
            $table->add_field('tempmanagerexpirydate', XMLDB_TYPE_CHAR, '20');
        }
        if ($this->is_importing_field('appraiseridnumber')) {
            $table->add_field('appraiseridnumber', XMLDB_TYPE_CHAR, '255');
        }

        // Add keys.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Add indexes.
        $table->add_index('useridnumber', XMLDB_INDEX_NOTUNIQUE, array('useridnumber'));
        $table->add_index('idnumber', XMLDB_INDEX_NOTUNIQUE, array('idnumber'));

        if ($this->is_importing_field('orgidnumber')) {
            $table->add_index('orgidnumber', XMLDB_INDEX_NOTUNIQUE, array('orgidnumber'));
        }
        if ($this->is_importing_field('posidnumber')) {
            $table->add_index('posidnumber', XMLDB_INDEX_NOTUNIQUE, array('posidnumber'));
        }
        if ($this->is_importing_field('manageridnumber')) {
            $table->add_index('manageridnumber', XMLDB_INDEX_NOTUNIQUE, array('manageridnumber'));
        }
        if ($this->is_importing_field('managerjaidnumber')) {
            $table->add_index('managerjaidnumber', XMLDB_INDEX_NOTUNIQUE, array('managerjaidnumber'));
        }
        if ($this->is_importing_field('tempmanageridnumber')) {
            $table->add_index('tempmanageridnumber', XMLDB_INDEX_NOTUNIQUE, array('tempmanageridnumber'));
        }
        if ($this->is_importing_field('tempmanagerjaidnumber')) {
            $table->add_index('tempmanagerjaidnumber', XMLDB_INDEX_NOTUNIQUE, array('tempmanagerjaidnumber'));
        }
        if ($this->is_importing_field('appraiseridnumber')) {
            $table->add_index('appraiseridnumber', XMLDB_INDEX_NOTUNIQUE, array('appraiseridnumber'));
        }

        // Create and truncate the table.
        $dbman->create_temp_table($table);
        $DB->execute("TRUNCATE TABLE {{$tablename}}");

        return $table;
    }
}