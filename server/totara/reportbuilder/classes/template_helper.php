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

use totara_reportbuilder\rb\template\base as template_base;

final class template_helper {

    private static $groups;
    private static $templates;

    /**
     * Get all templates.
     *
     * This includes templates for is_source_ignored and non-selectable sources. It should probably be
     * changed to match get_source_list.
     *
     * @return string[]
     */
    public static function get_templates() : array {
        if (self::$templates === null) {
            self::$templates = \core_component::get_namespace_classes('rb\template', 'totara_reportbuilder\rb\template\base');
        }

        return self::$templates;
    }

    /**
     * Get template groups
     *
     * @param bool $includenonselectable
     * @return template_base[][] first keyed by group label, then keyed by class name
     */
    public static function get_template_groups($includenonselectable = false) : array {
        if (isset(self::$groups[$includenonselectable])) {
            return self::$groups[$includenonselectable];
        }

        $working = [];
        foreach (self::get_templates() as $classname) {
            $template = self::get_template_object($classname);
            $src = \reportbuilder::get_source_object($template->source);

            if ($src->is_source_ignored()) {
                continue;
            }

            if ($src->selectable || $includenonselectable) {
                if (!isset($working[$template->label])) {
                    $working[$template->label] = [];
                }
                $working[$template->label][$classname] = $template;
            }
        }

        self::$groups[$includenonselectable] = $working;
        return self::$groups[$includenonselectable];
    }

    /**
     * Get template class object
     *
     * @param string $template The template class
     * @return template_base|object|false
     */
    public static function get_template_object(string $template) : ?object {
        // $template is the full namespaced classname.
        if (class_exists($template) && in_array($template, self::get_templates())) {
            return new $template();
        }

        // $template just the name of the class without namespace.
        foreach (self::get_templates() as $class) {
            $classname = explode("\\", $class);
            if (end($classname) == $template) {
                return new $class();
            }
        }

        return false;
    }

    /**
     * Create report from template
     *
     * @param string $templateclassname The class name of the template
     * @param string &$error Error string to return on failure
     * @return int|bool The created report id or false if failed to create
     */
    public static function create_from_name(string $templateclassname, string &$error = null) : ?int {
        global $DB;

        $template = self::get_template_object($templateclassname);

        if (!$template) {
            return false;
        }

        // Ensure the source exists.
        if (!\reportbuilder::get_source_class($template->source)) {
            $error = "Source {$template->source} not found";
            return false;
        }
        $src = \reportbuilder::get_source_object($template->source, false, true, null);

        $todb = new \stdClass();
        $todb->shortname            = \reportbuilder::create_shortname($template->shortname);
        $todb->fullname             = $template->fullname;
        $todb->summary              = $template->summary;
        $todb->source               = $template->source;
        $todb->hidden               = $template->hidden;
        $todb->accessmode           = $template->accessmode;
        $todb->contentmode          = $template->contentmode;
        $todb->embedded             = 0;
        $todb->defaultsortcolumn    = $template->defaultsortcolumn;
        $todb->defaultsortorder     = $template->defaultsortorder;

        $transaction = $DB->start_delegated_transaction();
        try {
            $reportid = $DB->insert_record('report_builder', $todb);

            // Add columns.
            $sortorder = 1;
            foreach ($template->columns as $column) {
                // Check data.
                if (!empty($column['aggregate'])) {
                    $classname = "\\totara_reportbuilder\\rb\\aggregate\\" . $column['aggregate'];
                    if (!class_exists($classname)) {
                        $column['aggregate'] = null;
                    }
                }

                $todb = new \stdClass();
                $todb->reportid         = $reportid;
                $todb->type             = $column['type'];
                $todb->value            = $column['value'];
                $todb->heading          = empty($column['customheading']) ? null : $column['heading'];
                $todb->customheading    = empty($column['customheading']) ? 0 : 1;
                $todb->transform        = empty($column['transform']) ? null : $column['transform'];
                $todb->aggregate        = empty($column['aggregate']) ? null : $column['aggregate'];
                $todb->sortorder        = $sortorder++;
                $todb->hidden           = empty($column['hidden']) ? 0 : 1;
                $DB->insert_record('report_builder_columns', $todb);
            }

            // Add filters.
            $sortorder = 1;
            foreach ($template->filters as $filter) {
                $todb = new \stdClass();
                $todb->reportid         = $reportid;
                $todb->type             = $filter['type'];
                $todb->value            = $filter['value'];
                $todb->advanced         = empty($filter['advanced']) ? 0 : 1;
                $todb->defaultvalue     = empty($filter['defaultvalue']) ? null : serialize($filter['defaultvalue']);
                $todb->filtername       = empty($filter['filtername']) ? '' : $filter['filtername'];
                $todb->customname       = empty($filter['filtername']) ? 0 : 1;
                $todb->sortorder        = $sortorder++;
                $todb->region           = isset($filter['region']) ? $filter['region'] : \rb_filter_type::RB_FILTER_REGION_STANDARD;
                $DB->insert_record('report_builder_filters', $todb);
            }

            // Add toolbar search columns.
            foreach ($template->toolbarsearchcolumns as $toolbarsearchcolumn) {
                $todb = new \stdClass();
                $todb->reportid = $reportid;
                $todb->type     = $toolbarsearchcolumn['type'];
                $todb->value    = $toolbarsearchcolumn['value'];
                $DB->insert_record('report_builder_search_cols', $todb);
            }

            // Add graph.
            if ($template->graph) {
                $todb = new \stdClass();
                $todb->reportid     = $reportid;
                $todb->type         = $template->graph['type'];
                $todb->maxrecords   = empty($template->graph['maxrecords']) ? 500 : $template->graph['maxrecords'];
                $todb->category     = $template->graph['category'];
                $todb->series       = $template->graph['series'];
                $todb->settings     = empty($template->graph['settings']) ? '' : $template->graph['settings'];
                $todb->timemodified = time();
                $DB->insert_record('report_builder_graph', $todb);
            }

            // Add content restrictions.
            foreach ($template->contentsettings as $option => $settings) {
                $classname = $src->resolve_content_classname($option);
                if (!$classname) {
                    debugging("Content restriction {$option} is not available", DEBUG_DEVELOPER);
                    continue;
                }
                foreach ($settings as $name => $value) {
                    if (!\reportbuilder::update_setting($reportid, $classname::TYPE, $name, $value)) {
                        throw new \moodle_exception('Error inserting content restrictions');
                    }
                }
            }

            // Add access restrictions.
            foreach ($template->accesssettings as $classname => $settings) {
                if (class_exists('totara_reportbuilder\rb\access\\' . $classname)) {
                    foreach ($settings as $name => $value) {
                        if (!\reportbuilder::update_setting($reportid, $classname . "_access", $name, $value)) {
                            throw new \moodle_exception('Error inserting access restrictions');
                        }
                    }
                }
            }

            $config = (new \rb_config())->set_nocache(true);
            $report = \reportbuilder::create($reportid, $config, false); // No access control for managing of reports here.
            \totara_reportbuilder\event\report_created::create_from_report($report, false)->trigger();
            $transaction->allow_commit();
        } catch (\Exception $e) {
            $transaction->rollback($e);
            $error = $e->getMessage();
            return false;
        }

        return $reportid;
    }

    public static function reset_caches(): void {
        self::$groups = null;
        self::$templates = null;
    }
}