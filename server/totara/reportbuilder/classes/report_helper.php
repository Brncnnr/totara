<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder;

use reportbuilder;

global $CFG;
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

final class report_helper {

    /**
     * Get report builder sources
     *
     * @return array
     */
    public static function get_sources() : array {
        $sources = [];

        if (empty($sources) || PHPUNIT_TEST) {
            foreach (\reportbuilder::find_source_dirs() as $dir) {
                if (is_dir($dir) && $dh = opendir($dir)) {
                    while (($file = readdir($dh)) !== false) {
                        if (is_dir($file) || !preg_match('|^rb_source_(.*)\.php$|', $file, $matches)) {
                            continue;
                        }

                        $sources[] = $matches[1];
                    }
                    closedir($dh);
                }
            }
        }

        return $sources;
    }

    /**
     * Create a report
     *
     * @param string $sourcename The report source name excluding the rb_source prefix
     * @return int The report id
     */
    public static function create(string $sourcename) : int {
        global $DB;

        try {
            $src = reportbuilder::get_source_object($sourcename);

            $todb = new \stdClass();
            $todb->fullname = $src->sourcetitle;
            $todb->shortname = reportbuilder::create_shortname($src->sourcetitle);
            $todb->source = $sourcename;
            $todb->hidden = 0;
            $todb->recordsperpage = 40;
            $todb->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;
            $todb->accessmode = REPORT_BUILDER_ACCESS_MODE_ANY; // default to limited access
            $todb->embedded = 0;
            $todb->globalrestriction = get_config('reportbuilder', 'globalrestrictiondefault');
            $todb->timemodified = time();

            $transaction = $DB->start_delegated_transaction();

            $newid = $DB->insert_record('report_builder', $todb);

            // By default we'll require a role but not set any, which will restrict report access to
            // the site administrators only.
            reportbuilder_set_default_access($newid);

            // Create columns for new report based on default columns.
            if (isset($src->defaultcolumns) && is_array($src->defaultcolumns)) {
                $defaultcolumns = $src->defaultcolumns;
                $so = 1;
                foreach ($defaultcolumns as $option) {
                    $heading = isset($option['heading']) ? $option['heading'] : null;
                    $hidden = isset($option['hidden']) ? $option['hidden'] : 0;
                    $column = $src->new_column_from_option($option['type'],
                        $option['value'], null, null, $heading, !empty($heading), $hidden);
                    $todb = new \stdClass();
                    $todb->reportid = $newid;
                    $todb->type = $column->type;
                    $todb->value = $column->value;
                    $todb->heading = $column->heading;
                    $todb->hidden = $column->hidden;
                    $todb->transform = $column->transform;
                    $todb->aggregate = $column->aggregate;
                    $todb->sortorder = $so;
                    $todb->customheading = 0;
                    $DB->insert_record('report_builder_columns', $todb);
                    $so++;
                }
            }

            // Create filters for new report based on default filters.
            if (isset($src->defaultfilters) && is_array($src->defaultfilters)) {
                $defaultfilters = $src->defaultfilters;
                $so = 1;
                foreach ($defaultfilters as $option) {
                    $todb = new \stdClass();
                    $todb->reportid = $newid;
                    $todb->type = $option['type'];
                    $todb->value = $option['value'];
                    $todb->advanced = isset($option['advanced']) ? $option['advanced'] : 0;
                    $todb->defaultvalue = isset($option['defaultvalue']) ? serialize($option['defaultvalue']) : '';
                    $todb->sortorder = $so;
                    $todb->region = isset($option['region']) ? $option['region'] : \rb_filter_type::RB_FILTER_REGION_STANDARD;
                    $DB->insert_record('report_builder_filters', $todb);
                    $so++;
                }
            }

            // Create toolbar search columns for new report based on default toolbar search columns.
            if (isset($src->defaulttoolbarsearchcolumns) && is_array($src->defaulttoolbarsearchcolumns)) {
                foreach ($src->defaulttoolbarsearchcolumns as $option) {
                    $todb = new \stdClass();
                    $todb->reportid = $newid;
                    $todb->type = $option['type'];
                    $todb->value = $option['value'];
                    $DB->insert_record('report_builder_search_cols', $todb);
                }
            }
            $config = (new \rb_config())->set_nocache(true);
            $report = \reportbuilder::create($newid, $config, false); // No access control for managing of reports here.
            \totara_reportbuilder\event\report_created::create_from_report($report, false)->trigger();
            $transaction->allow_commit();
        } catch (\ReportBuilderException $e) {
            $transaction->rollback($e);
            trigger_error($e->getMessage(), E_USER_WARNING);
        } catch (\Exception $e) {
            $transaction->rollback($e);
            throw new \ReportBuilderException(get_string('error:couldnotcreatenewreport', 'totara_reportbuilder'));
        }

        return $newid;
    }
}