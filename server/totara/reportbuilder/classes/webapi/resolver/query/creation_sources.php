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

namespace totara_reportbuilder\webapi\resolver\query;

use core\webapi\execution_context;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use totara_reportbuilder\report_helper;
use totara_reportbuilder\template_helper;
use totara_reportbuilder\webapi\resolver\helper;

/**
 * Query to return all available report builder templates and sources for report creation.
 */
class creation_sources extends query_resolver {

    use helper;

    /**
     * Returns all available report builder templates and sources with filtering supplied by the args..
     *
     * @param array $args
     * @param execution_context $ec
     * @return
     */
    public static function resolve(array $args, execution_context $ec) {
        if (!self::user_can_edit_report()) {
            throw new \coding_exception('No permission to edit reports.');
        }

        global $CFG;
        require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

        // Get args.
        $label = !empty($args['label']) ? $args['label'] : null;
        $search = !empty($args['search']) ? strtolower($args['search']) : null;
        $start = isset($args['start']) ? (int)$args['start'] : 0;
        $limit = isset($args['limit']) ? (int)$args['limit'] : null;
        $sort = isset($args['sort']) ? $args['sort'] : null; // Not currently being used.

        // Templates are first.
        $templates = [];
        foreach (template_helper::get_templates() as $classname) {
            $template = template_helper::get_template_object($classname);
            $src = \reportbuilder::get_source_object($template->source);

            // Do the filtering.
            if ($src->is_source_ignored() || !$src->selectable) {
                continue;
            }

            if ($label && !in_array($template->label, $label)) {
                continue;
            }

            if ($search && strpos(strtolower($template->fullname), $search) === false) {
                continue;
            }

            $templates[] = $template;
        }

        // Sort the templates.
        \core_collator::asort_objects_by_property($templates, 'fullname', \core_collator::SORT_STRING);

        // Then get the sources.
        $sources = [];
        foreach (report_helper::get_sources() as $source) {
            $src = \reportbuilder::get_source_object($source);

            // Do the filtering.
            if ($src->is_source_ignored() || !$src->selectable) {
                continue;
            }

            if ($label && !in_array($src->sourcelabel, $label)) {
                continue;
            }

            if ($search && strpos(strtolower($src->sourcetitle), $search) === false) {
                continue;
            }

            $sources[] = $src;
        }

        // Sort the sources.
        \core_collator::asort_objects_by_property($sources, 'sourcetitle', \core_collator::SORT_STRING);

        $all = array_merge($templates, $sources);

        // Limit results.
        $creationsources = array_slice($all, $start, $limit);

        $data = new \stdClass();
        $data->totalcount = count($all);
        $data->templates = [];
        $data->sources = [];
        foreach ($creationsources as $creationsource) {
            if (is_subclass_of($creationsource, 'totara_reportbuilder\rb\template\base')) {
                // Template.
                $data->templates[]  = $creationsource;
            } else {
                // Source.
                $data->sources[]  = $creationsource;
            }
        }

        return $data;
    }

    public static function get_middleware(): array {
        return [
            require_login::class
        ];
    }

}