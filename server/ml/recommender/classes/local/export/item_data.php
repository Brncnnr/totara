<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package ml_recommender
 */
namespace ml_recommender\local\export;

use ml_recommender\local\csv\writer;
use ml_recommender\local\unique_id;
use moodle_recordset;
use totara_engage\access\access;
use totara_engage\timeview\time_view;

/**
 * Export class for item (i.e. articles, playlists) data.
 */
class item_data extends export {
    /**
     * @return string
     */
    public function get_name(): string {
        return 'item_data';
    }

    /**
     * @return array
     */
    public static function get_supported_components(): array {
        return [
            'container_course',
            'container_workspace',
            'engage_article',
            'engage_microlearning',
            'totara_playlist',
        ];
    }

    /**
     * @param writer $writer
     * @return bool
     */
    public function export(writer $writer): bool {
        global $DB;

        // Components.
        $component_names = $this->one_hot_components(static::get_supported_components());

        // Component names are not consistently applied in tags table - note differences here.
        $tag_component_names = [
            'container_course' => 'course',
        ];

        $topics = $this->get_topics();

        // Build headings -> id, [components], [topics], document.
        $headings = ['item_id'];
        $headings = array_merge($headings, array_keys($component_names));

        foreach ($topics as $key => $topic) {
            $headings[] = $topic;
        }

        $headings[] = 'document';
        $writer->add_data($headings);

        // Set recordset cursor.
        $recordset = $this->get_export_recordset();
        if (!$recordset->valid()) {
            return false;
        }

        foreach ($recordset as $item) {
            $cells = [$item->uniqueid];
            [$component, $item_id, $raw_component] = unique_id::normalise_unique_id($item->uniqueid);
            $component_onehot = $component_names[$raw_component];

            foreach ($component_onehot as $onehot) {
                $cells[] = $onehot;
            }

            $resource_topics = [];
            if (isset($tag_component_names[$component])) {
                $select = 'itemid = ? and itemtype = ?';
                $this_item_topics = $DB->get_fieldset_select(
                    'tag_instance',
                    'tagid',
                    $select,
                    [$item->id, $tag_component_names[$component]]
                );
            } else {
                $select = 'itemid = ? and component = ?';
                $this_item_topics = $DB->get_fieldset_select('tag_instance', 'tagid', $select, [$item->id, $component]);
            }

            foreach ($this_item_topics as $index => $id) {
                $resource_topics[(int) $id] = true;
            }

            // One-hot encode topics for item.
            foreach ($topics as $id => $topic) {
                if (isset($resource_topics[$id])) {
                    $cells[] = 1;
                } else {
                    $cells[] = 0;
                }
            }

            // Recommenders do not need any links, but they are required for text formatting (otherwise it will throw exception)
            // Loading proper data (contextid, component, filearea) would require a lot more data to fetch for no particular
            // purpose. So we just use mock values for now.
            $item->content = file_rewrite_pluginfile_urls(
                $item->content,
                'index.php',
                0,
                'ml_recommenders',
                'not_used',
                0
            );

            if (!empty($item->content)) {
                // Skip the empty string.
                $item->content = content_to_text($item->content, $item->summaryformat);
            }

            $cells[] = $this->scrubtext($item->title . ' ' . $item->content);

            // Create CSV record.
            $writer->add_data($cells);
        }
        $recordset->close();

        return true;
    }

    /**
     * Prepare and run SQL query to database to get users
     * @return moodle_recordset
     */
    private function get_export_recordset() {
        global $DB, $CFG;

        $params_sql = [];

        $tenant_er_join_sql = '';
        $tenant_tp_join_sql = '';
        $tenant_cw_join_sql = '';
        $tenant_cc_join_sql = '';
        if ($this->tenant) {
            // For user content use tenant cohort.
            $cohortid = $this->tenant->cohortid;
            $tenant_er_join_sql = "INNER JOIN {cohort_members} cm ON (cm.cohortid = $cohortid AND er.userid = cm.userid)";
            $tenant_tp_join_sql = "INNER JOIN {cohort_members} cm ON (cm.cohortid = $cohortid AND tp.userid = cm.userid)";

            $courselevel = CONTEXT_COURSE;
            $tenantid = $this->tenant->id;

            $ornotenant = '';
            if (empty($CFG->tenantsisolated)) {
                $ornotenant = 'OR c.tenantid IS NULL';
            }

            $tenant_cw_join_sql = "
            INNER JOIN {context} c ON (
                c.contextlevel = $courselevel 
                AND cw.id = c.instanceid 
                AND (c.tenantid = $tenantid $ornotenant))
            ";

            $tenant_cc_join_sql = "
            INNER JOIN {context} c ON (
                c.contextlevel = $courselevel 
                AND cc.id = c.instanceid 
                AND (c.tenantid = $tenantid $ornotenant))
            ";
        }

        // Build sql.
        $unique_microlearning_id = $DB->sql_concat("'engage_microlearning'", 'er.id');
        $unique_article_id = $DB->sql_concat("'engage_article'", 'er.id');
        $unique_playlist_id = $DB->sql_concat("'totara_playlist'", 'tp.id');
        $unique_workspace_id = $DB->sql_concat("'container_workspace'", 'cw.id');
        $unique_course_id = $DB->sql_concat("'container_course'", 'cc.id');

        $public = access::PUBLIC;
        $microlearning_time_view = time_view::LESS_THAN_FIVE;

        $sql = "
        SELECT $unique_microlearning_id AS uniqueid, er.id, er.name AS title, ea.content AS content, ea.format as summaryformat 
        FROM {engage_resource} er 
        JOIN {engage_article} ea ON er.instanceid = ea.id
        $tenant_er_join_sql
        WHERE er.resourcetype = 'engage_article' AND er.access = $public AND ea.timeview = $microlearning_time_view 
        UNION ALL
        SELECT $unique_article_id AS uniqueid, er.id, er.name AS title, ea.content AS content, ea.format as summaryformat 
        FROM {engage_resource} er 
        JOIN {engage_article} ea ON er.instanceid = ea.id
        $tenant_er_join_sql
        WHERE er.resourcetype = 'engage_article' AND er.access = $public AND ea.timeview != $microlearning_time_view
        UNION ALL
        SELECT $unique_playlist_id AS uniqueid, tp.id, tp.name AS title, tp.summary AS content, tp.summaryformat
        FROM {playlist} tp
        $tenant_tp_join_sql
        WHERE tp.access = $public
        UNION ALL 
        SELECT $unique_workspace_id AS uniqueid, cw.id, cw.fullname AS title, cw.summary AS content, cw.summaryformat
        FROM {course} cw
        INNER JOIN {workspace} w ON (w.course_id = cw.id AND w.private = 0)
        $tenant_cw_join_sql
        WHERE cw.containertype = 'container_workspace'
        UNION ALL
        SELECT DISTINCT $unique_course_id AS uniqueid, cc.id, cc.fullname AS title, cc.summary AS content, cc.summaryformat
        FROM {course} cc
        INNER JOIN {enrol} te on cc.id = te.courseid 
        $tenant_cc_join_sql
        WHERE cc.containertype = 'container_course' AND te.enrol = 'self' AND te.status = 0
        ";

        return $DB->get_recordset_sql($sql, $params_sql);
    }

    /**
     * Pre-clean text data for processing by content filtering recommender engine.
     *
     * @param string $text
     * @return string
     */
    private function scrubtext(string $text): string {
        $text = str_replace(['"'], "'", $text);
        return trim(str_replace(['\n', '\r', '\t', ','], ' ', $text));
    }

    /**
     * Get a scrubbed list of registered topics and tags to include as item metadata.
     *
     * We want only tags for those courses where self-enrolment is enabled, but all Engage topics.
     * @return array
     */
    private function get_topics() {
        global $DB;

        // Set up database cursor and process records.
        $system_topics = $DB->get_records_sql("
            SELECT id, name, 'topic' AS type FROM {tag}
                WHERE tagcollid = (SELECT id FROM {tag_coll} WHERE component = 'totara_topic')
            UNION ALL
            SELECT DISTINCT(ti.tagid), tg.name, 'tag' AS type FROM {tag_instance} ti
            JOIN {enrol} te on ti.itemid = te.courseid
            JOIN {tag} tg on ti.tagid = tg.id
                WHERE ti.itemtype = 'course' AND ti.component = 'core' AND te.enrol = 'self'
        ");

        $topics = [];
        foreach ($system_topics as $topic) {
            $topics[$topic->id] = $topic->type . "_" . str_replace(['"', "'", '-', ',', '.', '  ', ' '], '', $topic->name);
        }

        return $topics;
    }

    /**
     * Build one-hot encodings for
     * @param array $component_names
     * @return array
     */
    private function one_hot_components(array $component_names) {
        // Reset the list of component names into very basic array with key
        // as the position of that component name.
        $component_names = array_values($component_names);

        $components = [];
        $default = array_fill(0, count($component_names), 0);

        foreach ($component_names as $hot => $component_name) {
            $components[$component_name] = $default;
            $components[$component_name][$hot] = 1;
        }

        return $components;
    }
}
