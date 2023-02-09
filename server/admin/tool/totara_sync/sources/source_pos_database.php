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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package totara
 * @subpackage totara_sync
 */

require_once($CFG->dirroot.'/admin/tool/totara_sync/sources/classes/source.pos.class.php');
require_once($CFG->dirroot.'/admin/tool/totara_sync/lib.php');
require_once($CFG->dirroot.'/admin/tool/totara_sync/sources/databaselib.php');

class totara_sync_source_pos_database extends totara_sync_source_pos {
    use \tool_totara_sync\internal\source\database_trait;

    public const USES_FILES = false;

    function config_form(&$mform) {
        $this->config->import_idnumber = "1";
        $this->config->import_fullname = "1";
        $this->config->import_frameworkidnumber = "1";
        $this->config->import_timemodified = "1";
        $this->config->import_deleted = empty($this->element->config->sourceallrecords) ? "1" : "0";

        $this->config_form_add_database_details($mform);

        parent::config_form($mform);
    }

    function config_save($data) {
        $this->config_save_database_details($data);
        parent::config_save($data);
    }

    public function validate_settings($data, $files = []) {
        $errors = parent::validate_settings($data, $files);
        $errors = array_merge($errors, $this->validate_settings_database_details($data, $files));
        return $errors;
    }

    function import_data($temptable) {
        global $DB; // Careful using this in here as we have 2 database connections

        // Get database config
        $dbtype = $this->config->{'database_dbtype'};
        $dbname = $this->config->{'database_dbname'};
        $dbhost = $this->config->{'database_dbhost'};
        $dbuser = $this->config->{'database_dbuser'};
        $dbpass = $this->config->{'database_dbpass'};
        $dbport = $this->config->{'database_dbport'};
        $db_table = $this->config->{'database_dbtable'};

        try {
            $database_connection = setup_sync_DB($dbtype, $dbhost, $dbname, $dbuser, $dbpass, array('dbport' => $dbport));
        } catch (Exception $e) {
            $this->addlog(get_string('databaseconnectfail', 'tool_totara_sync'), 'error', 'importdata');
            return false;
        }

        // Get list of fields to be imported
        $fields = array();
        foreach ($this->fields as $f) {
            if (!empty($this->config->{'import_'.$f})) {
                $fields[] = $f;
            }
        }

        // Sort out field mappings
        $fieldmappings = array();
        foreach ($fields as $i => $f) {
            if (empty($this->config->{'fieldmapping_'.$f})) {
                $fieldmappings[$f] = $f;
            } else {
                $fieldmappings[$f] = $this->config->{'fieldmapping_'.$f};
            }
        }

        $dbfields = [];

        // Finally, perform externaldb to totara db field mapping
        foreach ($fields as $i => $f) {
            if (in_array($f, array_keys($fieldmappings))) {
                $dbfields[$i] = $fieldmappings[$f];
            }
        }

        // Custom fields are made unique as it is permitted to have one column for customfields
        // with the same shortname for example (possible if each field has a different type).
        $dbfields = array_merge(
            $dbfields,
            $this->get_unique_mapped_customfields()
        );

        $fields = array_merge(
            $fields,
            $this->get_unique_mapped_customfields()
        );

        // Check the table exists in the database.
        try {
            $database_connection->get_record_sql("SELECT 1 FROM $db_table", null, IGNORE_MULTIPLE);
        } catch (Exception $e) {
            $this->addlog(get_string('dbmissingtablex', 'tool_totara_sync', $db_table), 'error', 'importdata');
            return false;
        }

        // Check that all fields exists in database.
        $missingcolumns = array();
        foreach ($dbfields as $f) {
            try {
                $database_connection->get_field_sql("SELECT $f from $db_table", array(), IGNORE_MULTIPLE);
            } catch (Exception $e) {
                $missingcolumns[] = $f;
            }
        }
        if (!empty($missingcolumns)) {
            $missingcolumnsstr = implode(', ', $missingcolumns);
            $this->addlog(get_string('dbmissingcolumnx', 'tool_totara_sync', $missingcolumnsstr), 'error', 'importdata');
            $database_connection->dispose();
            return false;
        }

        unset($fieldmappings);

        // Populate temp sync table from remote database
        $datarows = array();  // holds rows of data
        $rowcount = 0;

        $columns = implode(', ', $dbfields);
        $fetch_sql = 'SELECT ' . $columns . ' FROM ' . $db_table;
        $data = $database_connection->get_recordset_sql($fetch_sql);

        foreach ($data as $row) {
            // Setup a db row
            $extdbrow = array_combine($fields, (array)$row);
            $dbrow = array();

            foreach ($this->fields as $field) {
                if (!empty($this->config->{'import_'.$field})) {
                    $dbrow[$field] = $extdbrow[$field];
                }
            }

            // Treat nulls in the 'deleted' database column as not deleted.
            if (!empty($this->config->import_deleted)) {
                $dbrow['deleted'] = empty($dbrow['deleted']) ? 0 : $dbrow['deleted'];
            }

            if (empty($extdbrow['timemodified'])) {
                $dbrow['timemodified'] = 0;
            } else {
                //try to parse the contents - if parse fails assume a unix timestamp and leave unchanged
                $parsed_date = totara_date_parse_from_format(
                    $this->get_csv_date_format(),
                    trim($extdbrow['timemodified']),
                    true
                );
                if ($parsed_date) {
                    $dbrow['timemodified'] = $parsed_date;
                }
            }
            // Custom fields are special - needs to be json-encoded
            if (!empty($this->hierarchy_customfields)) {
                $dbrow['customfields'] = $this->get_customfield_json($extdbrow);
                foreach ($this->hierarchy_customfields as $hierarchy_customfield) {
                    if ($this->is_importing_customfield($hierarchy_customfield)) {
                        unset($dbrow[$hierarchy_customfield->get_default_fieldname()]);
                    }
                }
            }

            $datarows[] = $dbrow;
            $rowcount++;

            if ($rowcount >= TOTARA_SYNC_DBROWS) {
                // Bulk insert
                try {
                    totara_sync_bulk_insert($temptable, $datarows);
                } catch (dml_exception $e) {
                    $msg = debugging() ? $e->getMessage()."\n".$e->debuginfo : $e->getMessage();
                    $this->addlog(get_string('couldnotimportallrecords', 'tool_totara_sync', $msg), 'error', 'populatesynctabledb');
                    $database_connection->dispose();
                    return false;
                }

                $rowcount = 0;
                unset($datarows);
                $datarows = array();

                gc_collect_cycles();
            }
        }

        // Insert remaining rows
        try {
            totara_sync_bulk_insert($temptable, $datarows);
        } catch (dml_exception $e) {
            $msg = debugging() ? $e->getMessage()."\n".$e->debuginfo : $e->getMessage();
            $this->addlog(get_string('couldnotimportallrecords', 'tool_totara_sync', $msg), 'error', 'populatesynctabledb');
            $database_connection->dispose();
            return false;
        }

        // Update temporary table stats once import is done.
        $DB->update_temp_table_stats();

        $database_connection->dispose();
        return true;
    }
    /**
     * Get any notifications that should be displayed for the element source.
     *
     * @return string Notifications HTML.
     */
    public function get_notifications() {
        return $this->get_common_db_notifications();
    }

    /**
     * @return bool False as database sources do not use files.
     */
    function uses_files() {
        return false;
    }
}
