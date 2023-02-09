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

require_once($CFG->dirroot.'/admin/tool/totara_sync/sources/classes/source.user.class.php');
require_once($CFG->dirroot.'/admin/tool/totara_sync/lib.php');
require_once($CFG->dirroot.'/admin/tool/totara_sync/sources/databaselib.php');
require_once($CFG->dirroot.'/admin/tool/totara_sync/elements/user.php');

class totara_sync_source_user_database extends totara_sync_source_user {
    use \tool_totara_sync\internal\source\database_trait;

    public const USES_FILES = false;

    function config_form(&$mform) {
        $this->config->import_idnumber = "1";
        $this->config->import_username = "1";
        $this->config->import_timemodified = "1";
        if (!empty($this->element->config->allow_create)) {
            $this->config->import_firstname = "1";
            $this->config->import_lastname = "1";
        }
        if (empty($this->element->config->allowduplicatedemails)) {
            $this->config->import_email = "1";
        }
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
        global $CFG, $DB; // Careful using this in here as we have 2 database connections

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
            // Prevent allow_delete == SUSPEND_USERS and suspended column being set
            // at the same time.
            if ($f == 'suspended' && $this->element->config->allow_delete == totara_sync_element_user::SUSPEND_USERS) {
                unset($this->config->import_suspended);
            }

            if (!empty($this->config->{'import_'.$f})) {
                $fields[] = $f;
            }
        }

        // Same for customfields
        foreach ($this->customfields as $name => $value) {
            if (!empty($this->config->{'import_'.$name})) {
                $fields[] = $name;
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

        // Finally, perform externaldb to totara db field mapping
        foreach ($fields as $i => $f) {
            if (in_array($f, array_keys($fieldmappings))) {
                $fields[$i] = $fieldmappings[$f];
            }
        }

        // Check the table exists in the database.
        try {
            $database_connection->get_record_sql("SELECT 1 FROM $db_table", null, IGNORE_MULTIPLE);
        } catch (Exception $e) {
            $this->addlog(get_string('dbmissingtablex', 'tool_totara_sync', $db_table), 'error', 'importdata');
            return false;
        }

        // Check that all fields exists in database.
        $missingcolumns = array();
        foreach ($fields as $f) {
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

        $now = time();
        $datarows = array();  // holds rows of data
        $rowcount = 0;
        $csvdateformat = (isset($CFG->csvdateformat)) ? $CFG->csvdateformat : get_string('csvdateformatdefault', 'totara_core');

        $columns = implode(', ', $fields);
        $fetch_sql = 'SELECT ' . $columns . ' FROM ' . $db_table;
        $data = $database_connection->get_recordset_sql($fetch_sql);

        foreach ($data as $row) {
            // Setup a db row
            $extdbrow = array_combine($fields, (array)$row);
            $dbrow = array();

            foreach ($this->fields as $f) {
                if (!empty($this->config->{'import_'.$f})) {
                    if (!empty($this->config->{'fieldmapping_'.$f})) {
                        $dbrow[$f] = $extdbrow[$this->config->{'fieldmapping_'.$f}];
                    } else {
                        $dbrow[$f] = $extdbrow[$f];
                    }
                }
            }

            // Treat nulls in the 'deleted' database column as not deleted.
            if (!empty($this->config->import_deleted)) {
                $dbrow['deleted'] = empty($dbrow['deleted']) ? 0 : $dbrow['deleted'];
            }

            if (empty($dbrow['firstname'])) {
                $dbrow['firstname'] = '';
            }

            if (empty($dbrow['lastname'])) {
                $dbrow['lastname'] = '';
            }

            if (empty($dbrow['username'])) {
                $dbrow['username'] = '';
            }

            if (empty($extdbrow['timemodified'])) {
                $dbrow['timemodified'] = 0;
            } else {
                //try to parse the contents - if parse fails assume a unix timestamp and leave unchanged
                $parsed_date = totara_date_parse_from_format($csvdateformat, trim($extdbrow['timemodified']), true);
                if ($parsed_date) {
                    $dbrow['timemodified'] = $parsed_date;
                }
            }

            if (isset($dbrow['suspended'])) {
                $dbrow['suspended'] = empty($dbrow['suspended']) ? 0 : 1;
            }

            // Custom fields are special - needs to be json-encoded
            if (!empty($this->customfields)) {
                $cfield_data = array();
                foreach (array_keys($this->customfields) as $cf) {

                    if (empty($this->config->{'import_'.$cf})) { // Not a field to import.
                        continue;
                    }

                    $value = empty($this->config->{'fieldmapping_'.$cf}) ? $extdbrow[$cf] : $extdbrow[$this->config->{'fieldmapping_'.$cf}];

                    if (is_null($value)) { // Null means skip, don't import.
                        continue;
                    }

                    $value = trim($value);

                    // Get shortname and check if we need to do field type processing.
                    $shortname = str_replace("customfield_", "", $cf);
                    $datatype = $DB->get_field('user_info_field', 'datatype', array('shortname' => $shortname));
                    switch ($datatype) {
                        case 'datetime':
                            //try to parse the contents - if parse fails assume a unix timestamp and leave unchanged
                            $parsed_date = totara_date_parse_from_format($csvdateformat, $value, true);
                            if ($parsed_date) {
                                $value = $parsed_date;
                            }
                            break;
                        case 'date':
                            //try to parse the contents - if parse fails assume a unix timestamp and leave unchanged
                            $parsed_date = totara_date_parse_from_format($csvdateformat, $value, true, 'UTC');
                            if ($parsed_date) {
                                $value = $parsed_date;
                            }
                            break;
                        default:
                            break;
                    }

                    $cfield_data[$cf] = $value;
                    unset($dbrow[$cf]);
                }
                $dbrow['customfields'] = json_encode($cfield_data);
                unset($cfield_data);
            }

            $datarows[] = $dbrow;

            $rowcount++;

            if ($rowcount >= TOTARA_SYNC_DBROWS) {
                // bulk insert
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
     * Returns a list of possible date formats
     * Based on the list at http://en.wikipedia.org/wiki/Date_format_by_country
     *
     * @return array
     */
    public function get_dateformats() {
        $separators = array('-', '/', '.', ' ');
        $endians = array('yyyy~mm~dd', 'yy~mm~dd', 'dd~mm~yyyy', 'dd~mm~yy', 'mm~dd~yyyy', 'mm~dd~yy');
        $formats = array();

        // Standard datetime format.
        $formats['Y-m-d H:i:s'] = 'yyyy-mm-dd hh:mm:ss';

        foreach ($endians as $endian) {
            foreach ($separators as $separator) {
                $display = str_replace( '~', $separator, $endian);
                $format = str_replace('yyyy', 'Y', $display);
                $format = str_replace('yy', 'y', $format); // Don't think 2 digit years should be allowed.
                $format = str_replace('mm', 'm', $format);
                $format = str_replace('dd', 'd', $format);
                $formats[$format] = $display;
            }
        }
        return $formats;
    }

    /**
     * Get any notifications that should be displayed for the element source.
     *
     * @return string Notifications HTML.
     */
    public function get_notifications() {
        global $OUTPUT;

        $notifications = $this->get_common_db_notifications();
        // Show a notification about delete suspending/unsuspending users
        if (isset($this->element->config->allow_delete) && $this->element->config->allow_delete == totara_sync_element_user::SUSPEND_USERS) {
            $suspenddelete = get_string('suspendcolumndisabled', 'tool_totara_sync');
            $notifications .= $OUTPUT->notification($suspenddelete, \core\output\notification::NOTIFY_WARNING);
        }

        return $notifications;
    }

    /**
     * @return bool False as database sources do not use files.
     */
    function uses_files() {
        return false;
    }
}
