<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package block_totara_report_graph
 */

namespace block_totara_report_graph;

/**
 * Class util for report graph block.
 *
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package block_totara_report_graph
 */
class util {
    /**
     * Default height for graphs in blocks.
     * @deprecated since 14.0
     */
    const DEFAULT_HEIGHT = 400;

    /**
     * Cache key helper.
     *
     * @param int $blockid
     * @param \stdClass $config
     * @param \stdClass $rawreport
     * @return string|null key
     */
    protected static function get_cache_key(int $blockid, \stdClass $config, \stdClass $rawreport): ?string {
        global $USER;

        if (!isset($config->reportfor) || empty($config->reportorsavedid)) {
            // No chart means no caching.
            return null;
        }

        $reportfor = ($config->reportfor ? $config->reportfor : $USER->id);

        if (!empty($config->graph_height) && $config->graph_height > 0) {
            $height = (int)$config->graph_height;
        } else {
            $height = null;
        }

        $key = 'b' . $blockid . 'f' . $reportfor . 'h' . $height . 'l' . current_language();

        // Allow plugins to tweak keys in special cases, such as custom dynamic content restrictions.
        $hook = new \block_totara_report_graph\hook\get_cache_key($key, $config->reportorsavedid, $config->reportfor, $rawreport);
        $hook->execute();
        $key = $hook->key;

        return $key;
    }

    /**
     * Get raw report record from database.
     *
     * @param int $reportorsavedid positive number means real record id, negative number means save id.
     * @return \stdClass|false
     */
    public static function get_report(int $reportorsavedid) {
        global $DB;

        // Fetch report even if type not set - users may fiddle with the setting in reportbuilder.

        if ($reportorsavedid > 0) {
            $sql = 'SELECT r.id, r.fullname, r.timemodified AS rtimemodified, g.type,
                           NULL AS savedid, NULL AS userid, 0 AS gtimemodified, r.globalrestriction, r.contentmode
                     FROM "ttr_report_builder" r
                     JOIN "ttr_report_builder_graph" g ON g.reportid = r.id
                    WHERE r.id = :reportid';
            $report = $DB->get_record_sql($sql, array('reportid' => $reportorsavedid), IGNORE_MISSING);

        } else if ($reportorsavedid < 0) {
            $sql = 'SELECT r.id, s.name AS fullname, r.timemodified AS rtimemodified, g.type,
                           s.id AS savedid, s.userid, g.timemodified AS gtimemodified, r.globalrestriction, r.contentmode
                      FROM "ttr_report_builder" r
                      JOIN "ttr_report_builder_graph" g ON g.reportid = r.id
                      JOIN "ttr_report_builder_saved" s ON s.reportid = r.id
                     WHERE s.id = :savedid AND s.ispublic <> 0';
            $report = $DB->get_record_sql($sql, array('savedid' => - $reportorsavedid), IGNORE_MISSING);

        } else {
            $report = false;
        }

        return $report;
    }

    /**
     * Get chart rendering data.
     *
     * NOTE: Session must be already closed and session language set to required value!
     *
     * @param \stdClass $block
     * @param \stdClass $config
     * @return array|null null means error
     */
    public static function get_chart_data(\stdClass $block, \stdClass $config): ?array {
        global $SESSION, $USER, $DB;

        if (\core\session\manager::is_session_active()) {
            throw new \coding_exception('Session must not be active when rendering chart block data!');
        }

        $blockid = $block->id;

        if (!isset($config->reportfor) || empty($config->reportorsavedid)) {
            error_log($blockid . ': not configured');
            return null;
        }

        $rawreport = self::get_report($config->reportorsavedid);

        if (empty($rawreport->type)) {
            error_log($blockid . ': no graph type');
            return null;
        }

        $key = self::get_cache_key($blockid, $config, $rawreport);
        $cache = null;
        if ($key) {
            $cache = \cache::make('block_totara_report_graph', 'graph');
        }

        try {
            unset($SESSION->reportbuilder[$rawreport->id]); // Not persistent - we closed session already.
            $reportfor = $config->reportfor ? $config->reportfor : null;

            // Switch user if necessary.
            if ($reportfor && $reportfor != $USER->id) {
                $user = $DB->get_record('user', ['id' => $reportfor, 'deleted' => 0]);
                if (!$user) {
                    error_log($blockid . ': invalid reportfor user');
                    return null;
                }
                $USER = $user; // Not persistent - we closed session already.
            }

            $allrestr = \rb_global_restriction_set::create_from_ids(
                $rawreport,
                \rb_global_restriction_set::get_user_all_restrictions_ids($reportfor, true)
            );

            $cfg = new \rb_config();
            $cfg->set_sid($rawreport->savedid);
            $cfg->set_reportfor($reportfor);
            $cfg->set_global_restriction_set($allrestr);
            $report = \reportbuilder::create($rawreport->id, $cfg, true);

            $graph = new \totara_reportbuilder\local\graph\chartjs($report, true);
            // Width is automatic based on column size, height can be customised.
            if (!empty($config->graph_height) && $config->graph_height > 0) {
                $height = (int)$config->graph_height;
            } else {
                $height = null;
            }
            $data = $graph->get_render_data(null, $height);

            // If we go this far than make sure we save the result to the cache no matter what the user does.
            if ($key) {
                ignore_user_abort(true);
                $cacheddata = new \stdClass();
                $cacheddata->data = $data;
                $cacheddata->reportorsavedid = $config->reportorsavedid;
                $cacheddata->reportfor = $config->reportfor;
                $cacheddata->timecreated = time();
                $cacheddata->btimemodified = $block->timemodified;
                $cacheddata->rtimemodified = $rawreport->rtimemodified;
                $cacheddata->gtimemodified = $rawreport->gtimemodified;
                $cache->set($key, $cacheddata);
                if (connection_aborted()) {
                    return null;
                }
                ignore_user_abort(false);
            }

            // Finally return the render data.
            return $data;
        } catch (\Exception $e) {
            error_log($blockid . ': report error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Fetch graph data from cache.
     *
     * @param \stdClass $block
     * @param \stdClass $config
     * @return array|null empty array means error, null means not cached yet
     */
    public static function get_cached_chart_data(\stdClass $block, \stdClass $config): ?array {
        if (!$block->id || !isset($config->reportfor) || empty($config->reportorsavedid)) {
            // Error.
            return [];
        }
        $blockid = $block->id;

        $rawreport = self::get_report($config->reportorsavedid);
        if (empty($rawreport->type)) {
            // Error.
            return [];
        }

        $key = self::get_cache_key($blockid, $config, $rawreport);
        if (!$key) {
            // No caching.
            return null;
        }

        $cache = \cache::make('block_totara_report_graph', 'graph');

        if (empty($config->cachettl)) {
            $config->cachettl = 3600;
        }

        $cacheddata = $cache->get($key);

        if (empty($cacheddata->data)) {
            // No cache yet.
            return null;
        }
        if ($cacheddata->reportorsavedid != $config->reportorsavedid) {
            // Block setting was changed.
            return null;
        }
        if ($cacheddata->reportfor != $config->reportfor) {
            // Block setting was changed.
            return null;
        }
        if ($cacheddata->btimemodified != $block->timemodified) {
            // Force cache purge after block config save even if nothing changed.
            return null;
        }
        if ($cacheddata->timecreated < time() - $config->cachettl) {
            // The cache is too old.
            return null;
        }
        if ($cacheddata->rtimemodified != $rawreport->rtimemodified) {
            // The report settings were changed.
            return null;
        }
        if ($cacheddata->gtimemodified != $rawreport->gtimemodified) {
            // The graph setting was changed.
            return null;
        }

        // Yay - we can use the cached data!
        return $cacheddata->data;
    }
}
