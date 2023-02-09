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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_reportbuilder
 * @category test
 *
 * Reportbuilder generator.
 */

namespace totara_reportbuilder\testing;

use stdClass, coding_exception;
use reportbuilder, rb_column_option, rb_global_restriction, rb_filter_type;

/**
 * Report builder generator.
 *
 * Usage:
 *    $reportgenerator = $this->getDataGenerator()->get_plugin_generator('totara_reportbuilder');
 */
final class generator extends \core\testing\component_generator {
    protected $globalrestrictioncount = 0;
    protected $savedsearchescount = 0;

    /**
     * To be called from data reset code only,
     * do not use in tests.
     * @return void
     */
    public function reset() {
        parent::reset();

        $this->globalrestrictioncount = 0;
        $this->savedsearchescount = 0;
    }

    /**
     * Create a test restriction.
     *
     * @param array|stdClass $record
     * @return rb_global_restriction
     */
    public function create_global_restriction($record = null) {
        global $CFG;
        require_once("$CFG->dirroot/totara/reportbuilder/classes/rb_global_restriction.php");

        $this->globalrestrictioncount++;
        $i = $this->globalrestrictioncount;

        $record = (object)(array)$record;

        if (!isset($record->name)) {
            $record->name = 'Global report restriction '.$i;
        }

        $rest = new rb_global_restriction();
        $rest->insert($record);

        return $rest;
    }

    /**
     * Add user related data to restriction.
     *
     * Records of this cohort, org, pos or user are visible
     * in report with the restriction.
     *
     * @param stdClass|array $item - must contain prefix, restrictionid, itemid and optionally includechildren
     * @return stdClass the created record
     */
    public function assign_global_restriction_record($item) {
        global $DB;

        $item = (array)$item;

        if (empty($item['restrictionid'])) {
            throw new coding_exception('generator requires $item->restrictionid');
        }
        if (empty($item['prefix'])) {
            throw new coding_exception('generator requires valid $item->prefix');
        }
        if (empty($item['itemid'])) {
            throw new coding_exception('generator requires $item->itemid');
        }

        $tables = array(
            'cohort' => 'reportbuilder_grp_cohort_record',
            'org' => 'reportbuilder_grp_org_record',
            'pos' => 'reportbuilder_grp_pos_record',
            'user' => 'reportbuilder_grp_user_record',
        );

        $prefix = $item['prefix'];
        if ($prefix === 'position') {
            $prefix = 'pos';
        }
        if ($prefix === 'organisation') {
            $prefix = 'org';
        }
        if (!isset($tables[$prefix])) {
            throw new coding_exception('generator requires valid $item->prefix');
        }

        $record = new stdClass();
        $record->reportbuilderrecordid = $item['restrictionid'];
        $record->{$prefix . 'id'} = $item['itemid'];
        $record->timecreated = time();
        if (isset($item['includechildren'])) {
            $record->includechildren = $item['includechildren'];
        }

        $id = $DB->insert_record($tables[$prefix], $record);
        return $DB->get_record($tables[$prefix], array('id' => $id));
    }

    /**
     * Add user who is allowed to select restriction.
     *
     * @param stdClass|array $item - must contain prefix, restrictionid, itemid and optionally includechildren
     * @return stdClass the created record
     */
    public function assign_global_restriction_user($item) {
        global $DB;

        $item = (array)$item;

        if (empty($item['restrictionid'])) {
            throw new coding_exception('generator requires $item->restrictionid');
        }
        if (empty($item['prefix'])) {
            throw new coding_exception('generator requires valid $item->prefix');
        }
        if (empty($item['itemid'])) {
            throw new coding_exception('generator requires $item->itemid');
        }

        $tables = array(
            'cohort' => 'reportbuilder_grp_cohort_user',
            'org' => 'reportbuilder_grp_org_user',
            'pos' => 'reportbuilder_grp_pos_user',
            'user' => 'reportbuilder_grp_user_user',
        );

        $prefix = $item['prefix'];
        if ($prefix === 'position') {
            $prefix = 'pos';
        }
        if ($prefix === 'organisation') {
            $prefix = 'org';
        }
        if (!isset($tables[$prefix])) {
            throw new coding_exception('generator requires valid $item->prefix');
        }

        $record = new stdClass();
        $record->reportbuilderuserid = $item['restrictionid'];
        $record->{$prefix . 'id'} = $item['itemid'];
        $record->timecreated = time();
        if (isset($item['includechildren'])) {
            $record->includechildren = $item['includechildren'];
        }

        $id = $DB->insert_record($tables[$prefix], $record);
        return $DB->get_record($tables[$prefix], array('id' => $id));
    }

    /**
     * Generate saved search
     * @param stdClass $report
     * @param stdClass $user
     * @param array $item
     */
    public function create_saved_search(stdClass $report, stdClass $user, array $item = []) {
        global $DB;

        $this->savedsearchescount++;
        $i = $this->savedsearchescount;

        $name = isset($item['name']) ?  $item['name'] : 'Saved ' . $i;
        $search = isset($item['search']) ? $item['search'] : ['user-fullname' => ['operator' => 0, 'value' => 'user']];
        $ispublic = isset($item['ispublic']) ?  $item['ispublic']  : 0;
        $timemodified = isset($item['timemodified']) ?  $item['timemodified'] : time();

        $saved = new stdClass();
        $saved->reportid = $report->id;
        $saved->userid = $user->id;
        $saved->name = $name;
        $saved->search = serialize($search);
        $saved->ispublic = $ispublic;
        $saved->timemodified = $timemodified;

        $saved->id = $DB->insert_record('report_builder_saved', $saved);
        $saved = $DB->get_record('report_builder_saved', array('id' => $saved->id));
        return $saved;
    }

    /**
     * Set a saved search as a user default
     *
     * @param stdClass $report
     * @param stdClass $user
     * @param stdClass $search
     * @return stdClass The report_builder_saved_user_default record
     */
    public function create_saved_search_user_default(stdClass $report, stdClass $user, stdClass $search) {
        global $DB;

        $default = new stdClass();
        $default->userid = $user->id;
        $default->reportid = $report->id;
        $default->savedid = $search->id;
        $default->id = $DB->insert_record('report_builder_saved_user_default', $default);
        $default = $DB->get_record('report_builder_saved_user_default', array('id' => $default->id));

        return $default;
    }

    /**
     * Generate scheduled report
     * @param stdClass $report Generated report
     * @param stdClass $user Generated user who scheduled report
     * @param array $item
     */
    public function create_scheduled_report(stdClass $report, stdClass $user,  array $item = []) {
        global $DB;

        $savedsearchid = isset($item['savedsearch']) ? $item['savedsearch']->id : 0 ;
        $usermodifiedid = isset($item['usermodified']) ? $item['usermodified']->id : $user->id;
        $format = isset($item['format']) ? $item['format'] : 'csv';
        $frequency = isset($item['frequency']) ? $item['frequency'] : 1; // Default daily.
        $schedule = isset($item['schedule']) ? $item['schedule'] : 0; // Default midnight.
        $exporttofilesystem = isset($item['exporttofilesystem']) ? $item['exporttofilesystem'] : REPORT_BUILDER_EXPORT_EMAIL;
        $nextreport = isset($item['nextreport']) ? $item['nextreport'] : 0; // Default ASAP.
        $lastmodified = isset($item['lastmodified']) ? $item['lastmodified'] : time();

        $scheduledreport = new stdClass();
        $scheduledreport->reportid = $report->id;
        $scheduledreport->savedsearchid = $savedsearchid;
        $scheduledreport->format = $format;
        $scheduledreport->frequency = $frequency;
        $scheduledreport->schedule = $schedule;
        $scheduledreport->exporttofilesystem = $exporttofilesystem;
        $scheduledreport->nextreport = $nextreport;
        $scheduledreport->userid = $user->id;
        $scheduledreport->usermodified = $usermodifiedid;
        $scheduledreport->lastmodified = $lastmodified;
        $scheduledreport->id = $DB->insert_record('report_builder_schedule', $scheduledreport);
        $scheduledreport = $DB->get_record('report_builder_schedule', array('id' => $scheduledreport->id));
        return $scheduledreport;
    }

    /**
     * Add audience to scheduled report
     * @param stdClass $schedulereport
     * @param stdClass $cohort
     * @return stdClass report_builder_schedule_email_audience record
     */
    public function add_scheduled_audience(stdClass $schedulereport, stdClass $cohort) {
        global $DB;

        $recipient = new stdClass();
        $recipient->scheduleid = $schedulereport->id;
        $recipient->cohortid = $cohort->id;
        $recipient->id = $DB->insert_record('report_builder_schedule_email_audience', $recipient);
        $recipient = $DB->get_record('report_builder_schedule_email_audience', array('id' => $recipient->id));
        return $recipient;
    }

    /**
     * Add email to scheduled report
     * @param stdClass $schedulereport
     * @param string $emal
     * @return stdClass report_builder_schedule_email_external record
     */
    public function add_scheduled_email(stdClass $schedulereport, string $email = '') {
        global $DB;

        $recipient = new stdClass();
        $recipient->scheduleid = $schedulereport->id;
        $recipient->email = empty($email) ? uniqid() . '@example.com' : $email;
        $recipient->id = $DB->insert_record('report_builder_schedule_email_external', $recipient);
        $recipient = $DB->get_record('report_builder_schedule_email_external', array('id' => $recipient->id));
        return $recipient;
    }

    /**
     * Add audience to scheduled report
     * @param stdClass $schedulereport
     * @param stdClass $user
     * @return stdClass report_builder_schedule_email_systemuser record
     */
    public function add_scheduled_user(stdClass $schedulereport, stdClass $user) {
        global $DB;

        $recipient = new stdClass();
        $recipient->scheduleid = $schedulereport->id;
        $recipient->userid = $user->id;
        $recipient->id = $DB->insert_record('report_builder_schedule_email_systemuser', $recipient);
        $recipient = $DB->get_record('report_builder_schedule_email_systemuser', array('id' => $recipient->id));
        return $recipient;
    }

    /**
     * Creates report only with the columns/filters defined in the default report properties.
     *
     * @param $record
     *
     * @return int report ID
     */
    public function create_default_custom_report($record) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

        $defaults = [
            'hidden'            => 0,
            'accessmode'        => REPORT_BUILDER_ACCESS_MODE_NONE,
            'contentmode'       => REPORT_BUILDER_CONTENT_MODE_NONE,
            'recordsperpage'    => 40,
            'toolbarsearch'     => 1,
            'globalrestriction' =>  get_config('reportbuilder', 'globalrestrictiondefault'),
            'timemodified'      => time(),
            'defaultsortorder'  => SORT_ASC
        ];

        if (!is_array($record)) {
            $record = (array)$record;
        }

        // Update record defaults here.
        foreach ($defaults as $key => $value) {
            if (!isset($record[$key])) {
                $record[$key] = $value;
            }
        }

        // It is not possible to create custom embedded reports.
        $record['embed'] = 0;

        $id = $DB->insert_record('report_builder', $record, true);

        // Restrict report access to the site administrators only.
        if ($record['accessmode'] == REPORT_BUILDER_ACCESS_MODE_ANY) {
            reportbuilder_set_default_access($id);
        }

        $src = reportbuilder::get_source_object($record['source']);

        // Create columns for new report based on default columns.
        if (isset($src->defaultcolumns) && is_array($src->defaultcolumns)) {
            $so = 1;
            foreach ($src->defaultcolumns as $option) {
                $heading = isset($option['heading']) ? $option['heading'] : null;
                $hidden = isset($option['hidden']) ? $option['hidden'] : 0;
                $column = $src->new_column_from_option($option['type'],
                    $option['value'], null, null, $heading, !empty($heading), $hidden);
                $todb = new stdClass();
                $todb->reportid = $id;
                $todb->type = $column->type;
                $todb->value = $column->value;
                $todb->heading = $column->heading;
                $todb->hidden = $column->hidden;
                $todb->transform = $column->transform;
                $todb->aggregate = $column->aggregate;
                $todb->sortorder = $so;
                $todb->customheading = 0; // initially no columns are customised
                $DB->insert_record('report_builder_columns', $todb);
                $so++;
            }
        }

        // Create filters for new report based on default filters.
        if (isset($src->defaultfilters) && is_array($src->defaultfilters)) {
            $so = 1;
            foreach ($src->defaultfilters as $option) {
                $todb = new stdClass();
                $todb->reportid = $id;
                $todb->type = $option['type'];
                $todb->value = $option['value'];
                $todb->advanced = isset($option['advanced']) ? $option['advanced'] : 0;
                $todb->defaultvalue = isset($option['defaultvalue']) ? serialize($option['defaultvalue']) : '';
                $todb->sortorder = $so;
                $todb->region = isset($option['region']) ? $option['region'] : rb_filter_type::RB_FILTER_REGION_STANDARD;
                $DB->insert_record('report_builder_filters', $todb);
                $so++;
            }
        }

        // Create toolbar search columns for new report based on default toolbar search columns.
        if (isset($src->defaulttoolbarsearchcolumns) && is_array($src->defaulttoolbarsearchcolumns)) {
            foreach ($src->defaulttoolbarsearchcolumns as $option) {
                $todb = new stdClass();
                $todb->reportid = $id;
                $todb->type = $option['type'];
                $todb->value = $option['value'];
                $DB->insert_record('report_builder_search_cols', $todb);
            }
        }

        return $id;
    }

    /**
     * First created the report
     * then injected the default columns
     * for the report
     *
     * @deprecated since Totara 13
     *
     * @param array $record
     * @return int $record id
     */
    public function create_default_standard_report($record) {
        global $DB;
        $addon = array(
            'hidden'            => 0,
            'accessmode'        => 0,
            'contentmode'       => 0,
            'recordsperpage'    => 40,
            'toolbarsearch'     => 1,
            'globalrestriction' =>  0,
            'timemodified'      => time(),
            'defaultsortorder'  => 4,
            'embed'             => 0
        );

        if (!is_array($record)) {
            $record = (array)$record;
        }

        // Update record addon here, if the record does not have any value, then the default value will fallback to add-on
        // value
        foreach ($addon as $key => $value) {
            if (!isset($record[$key])) {
                $record[$key] = $value;
            }
        }

        $id = $DB->insert_record("report_builder", (object)$record, true);

        $src = reportbuilder::get_source_object($record['source']);

        $so = 1;
        $columnoptions = $src->columnoptions;

        /** @var rb_column_option $columnoption */
        foreach ($columnoptions as $columnoption) {
            // By default way, the columns that are deprecated should not be added into the report builder
            if (isset($columnoption->deprecated) && $columnoption->deprecated) {
                continue;
            }

            $item = array(
                'reportid'      => $id,
                'type'          => $columnoption->type,
                'value'         => $columnoption->value,
                'heading'       => $columnoption->name,
                'hidden'        => $columnoption->hidden,
                'transform'     => $columnoption->transform,
                'aggregate'     => $columnoption->aggregate,
                'sortorder'     => $so,
                'customheading' => 0
            );

            $DB->insert_record("report_builder_columns", (object)$item);
            $so+= 1;
        }

        return $id;
    }
}
