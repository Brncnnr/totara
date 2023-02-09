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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\content;

/*
 * Restrict content by offical tags
 *
 * Pass in a column that contains a pipe '|' separated list of official tag ids
 */
class tag extends base {

    const TYPE = 'tag_content';

    /**
     * Generate the SQL to apply this content restriction
     *
     * @param string $field SQL field to apply the restriction against
     * @param integer $reportid ID of the report
     *
     * @return array containing SQL snippet to be used in a WHERE clause, as well as array of SQL params
     */
    public function sql_restriction($field, $reportid) {
        global $DB;

        $include_sql = array();
        $exclude_sql = array();

        // get arrays of included and excluded tags
        $settings = \reportbuilder::get_all_settings($reportid, self::TYPE);
        $itags = ($settings['included']) ?
            explode('|', $settings['included']) : array();
        $etags = ($settings['excluded']) ?
            explode('|', $settings['excluded']) : array();
        $include_logic = (isset($settings['include_logic']) &&
            $settings['include_logic'] == 0) ? ' AND ' : ' OR ';
        $exclude_logic = (isset($settings['exclude_logic']) &&
            $settings['exclude_logic'] == 0) ? ' OR ' : ' AND ';

        // loop through current official tags
        $tags = $DB->get_records('tag', array('isstandard' => 1), 'name');
        $params = array();
        $count = 1;
        foreach ($tags as $tag) {
            // if found, add the SQL
            // we can't just use LIKE '%tag%' because we might get
            // partial number matches
            if (in_array($tag->id, $itags)) {
                $uniqueparam = rb_unique_param("cctre_{$count}_");
                $elike = $DB->sql_like($field, ":{$uniqueparam}");
                $params[$uniqueparam] = $DB->sql_like_escape($tag->id);

                $uniqueparam = rb_unique_param("cctrew_{$count}_");
                $ewlike = $DB->sql_like($field, ":{$uniqueparam}");
                $params[$uniqueparam] = $DB->sql_like_escape($tag->id).'|%';

                $uniqueparam = rb_unique_param("cctrsw_{$count}_");
                $swlike = $DB->sql_like($field, ":{$uniqueparam}");
                $params[$uniqueparam] = '%|'.$DB->sql_like_escape($tag->id);

                $uniqueparam = rb_unique_param("cctrsc_{$count}_");
                $clike = $DB->sql_like($field, ":{$uniqueparam}");
                $params[$uniqueparam] = '%|'.$DB->sql_like_escape($tag->id).'|%';

                $include_sql[] = "({$elike} OR
                {$ewlike} OR
                {$swlike} OR
                {$clike})\n";

                $count++;
            }
            if (in_array($tag->id, $etags)) {
                $uniqueparam = rb_unique_param("cctre_{$count}_");
                $enotlike = $DB->sql_like($field, ":{$uniqueparam}", true, true, true);
                $params[$uniqueparam] = $DB->sql_like_escape($tag->id);

                $uniqueparam = rb_unique_param("cctrew_{$count}_");
                $ewnotlike = $DB->sql_like($field, ":{$uniqueparam}", true, true, true);
                $params[$uniqueparam] = $DB->sql_like_escape($tag->id).'|%';

                $uniqueparam = rb_unique_param("cctrsw_{$count}_");
                $swnotlike = $DB->sql_like($field, ":{$uniqueparam}", true, true, true);
                $params[$uniqueparam] = '%|'.$DB->sql_like_escape($tag->id);

                $uniqueparam = rb_unique_param("cctrsc_{$count}_");
                $cnotlike = $DB->sql_like($field, ":{$uniqueparam}", true, true, true);
                $params[$uniqueparam] = '%|'.$DB->sql_like_escape($tag->id).'|%';

                $include_sql[] = "({$enotlike} AND
                {$ewnotlike} AND
                {$swnotlike} AND
                {$cnotlike})\n";

                $count++;
            }
        }

        // merge the include and exclude strings separately
        $includestr = implode($include_logic, $include_sql);
        $excludestr = implode($exclude_logic, $exclude_sql);

        // now merge together
        if ($includestr && $excludestr) {
            return array(" ($includestr AND $excludestr) ", $params);
        } else if ($includestr) {
            return array(" $includestr ", $params);
        } else if ($excludestr) {
            return array(" $excludestr ", $params);
        } else {
            // using 1=0 instead of FALSE for MSSQL support
            return array('1=0', $params);
        }
    }

    /**
     * Generate a human-readable text string describing the restriction
     *
     * @param string $title Name of the field being restricted
     * @param integer $reportid ID of the report
     *
     * @return string Human readable description of the restriction
     */
    public function text_restriction($title, $reportid) {
        global $DB;

        $settings = \reportbuilder::get_all_settings($reportid, self::TYPE);

        $include_text = array();
        $exclude_text = array();

        $itags = ($settings['included']) ?
            explode('|', $settings['included']) : array();
        $etags = ($settings['excluded']) ?
            explode('|', $settings['excluded']) : array();
        $include_logic = (isset($settings['include_logic']) &&
            $settings['include_logic'] == 0) ? 'and' : 'or';
        $exclude_logic = (isset($settings['exclude_logic']) &&
            $settings['exclude_logic'] == 0) ? 'and' : 'or';

        $tags = $DB->get_records('tag', array('isstandard' => 1), 'name');
        foreach ($tags as $tag) {
            if (in_array($tag->id, $itags)) {
                $include_text[] = '"' . $tag->name . '"';
            }
            if (in_array($tag->id, $etags)) {
                $exclude_text[] = '"' . $tag->name . '"';
            }
        }

        if (count($include_text) > 0) {
            $includestr = $title . ' ' . get_string('istaggedwith', 'totara_reportbuilder') .
                ' ' . implode(get_string($include_logic, 'totara_reportbuilder'), $include_text);
        } else {
            $includestr = '';
        }
        if (count($exclude_text) > 0) {
            $excludestr = $title . ' ' . get_string('isnttaggedwith', 'totara_reportbuilder') .
                ' ' . implode(get_string($exclude_logic, 'totara_reportbuilder'), $exclude_text);
        } else {
            $excludestr = '';
        }

        if ($includestr && $excludestr) {
            return $includestr . get_string('and', 'totara_reportbuilder') . $excludestr;
        } else if ($includestr) {
            return $includestr;
        } else if ($excludestr) {
            return $excludestr;
        } else {
            return '';
        }
    }


    /**
     * Adds form elements required for this content restriction's settings page
     *
     * @param object &$mform Moodle form object to modify (passed by reference)
     * @param integer $reportid ID of the report being adjusted
     * @param string $title Name of the field the restriction is acting on
     */
    public function form_template(&$mform, $reportid, $title) {
        global $DB;

        $enable = \reportbuilder::get_setting($reportid, self::TYPE, 'enable');
        $include_logic = \reportbuilder::get_setting($reportid, self::TYPE, 'include_logic');
        $exclude_logic = \reportbuilder::get_setting($reportid, self::TYPE, 'exclude_logic');
        $activeincludes = explode('|', \reportbuilder::get_setting($reportid, self::TYPE, 'included'));
        $activeexcludes = explode('|', \reportbuilder::get_setting($reportid, self::TYPE, 'excluded'));

        $mform->addElement('header', 'tag_header', get_string('showbytag', 'totara_reportbuilder'));
        $mform->setExpanded('tag_header');
        $mform->addHelpButton('tag_header', 'reportbuildertag', 'totara_reportbuilder');

        $mform->addElement('checkbox', 'tag_enable', '',
            get_string('tagenable', 'totara_reportbuilder'));
        $mform->setDefault('tag_enable', $enable);
        $mform->disabledIf('tag_enable', 'contentenabled', 'eq', 0);

        $mform->addElement('html', \html_writer::empty_tag('br'));

        // include the following tags
        $tags = $DB->get_records('tag', ['isstandard' => 1], 'name');
        if (!empty($tags)) {
            $checkgroup = [];
            $opts = [
                1 => get_string('anyofthefollowing', 'totara_reportbuilder'),
                0 => get_string('allofthefollowing', 'totara_reportbuilder')
            ];
            $mform->addElement('select', 'tag_include_logic', get_string('includetags', 'totara_reportbuilder'), $opts);
            $mform->setDefault('tag_include_logic', $include_logic);
            $mform->disabledIf('tag_enable', 'contentenabled', 'eq', 0);
            foreach ($tags as $tag) {
                $checkgroup[] =& $mform->createElement('checkbox',
                    'tag_include_option_' . $tag->id, '', $tag->name, 1);
                $mform->disabledIf('tag_include_option_' . $tag->id,
                    'tag_exclude_option_' . $tag->id, 'checked');
                if (in_array($tag->id, $activeincludes)) {
                    $mform->setDefault('tag_include_option_' . $tag->id, 1);
                }
            }
            $mform->addGroup($checkgroup, 'tag_include_group', '', \html_writer::empty_tag('br'), false);
        }
        $mform->disabledIf('tag_include_group', 'contentenabled', 'eq', 0);
        $mform->disabledIf('tag_include_group', 'tag_enable',
            'notchecked');

        $mform->addElement('html', str_repeat(\html_writer::empty_tag('br'), 2));

        // exclude the following tags
        if (!empty($tags)) {
            $checkgroup = [];
            $opts = [
                1 => get_string('anyofthefollowing', 'totara_reportbuilder'),
                0 => get_string('allofthefollowing', 'totara_reportbuilder')
            ];
            $mform->addElement('select', 'tag_exclude_logic', get_string('excludetags', 'totara_reportbuilder'), $opts);
            $mform->setDefault('tag_exclude_logic', $exclude_logic);
            $mform->disabledIf('tag_enable', 'contentenabled', 'eq', 0);
            foreach ($tags as $tag) {
                $checkgroup[] =& $mform->createElement('checkbox',
                    'tag_exclude_option_' . $tag->id, '', $tag->name, 1);
                $mform->disabledIf('tag_exclude_option_' . $tag->id,
                    'tag_include_option_' . $tag->id, 'checked');
                if (in_array($tag->id, $activeexcludes)) {
                    $mform->setDefault('tag_exclude_option_' . $tag->id, 1);
                }
            }
            $mform->addGroup($checkgroup, 'tag_exclude_group', '', \html_writer::empty_tag('br'), false);
        }
        $mform->disabledIf('tag_exclude_group', 'contentenabled', 'eq', 0);
        $mform->disabledIf('tag_exclude_group', 'tag_enable', 'notchecked');
    }


    /**
     * Processes the form elements created by {@link form_template()}
     *
     * @param integer $reportid ID of the report to process
     * @param object $fromform Moodle form data received via form submission
     *
     * @return boolean True if form was successfully processed
     */
    public function form_process($reportid, $fromform) {
        global $DB;

        $status = true;

        // enable checkbox option
        $enable = (isset($fromform->tag_enable) &&
            $fromform->tag_enable) ? 1 : 0;
        $status = $status && \reportbuilder::update_setting($reportid, self::TYPE,
            'enable', $enable);

        // include with any or all
        $includelogic = (isset($fromform->tag_include_logic) &&
            $fromform->tag_include_logic) ? 1 : 0;
        $status = $status && \reportbuilder::update_setting($reportid, self::TYPE,
            'include_logic', $includelogic);

        // exclude with any or all
        $excludelogic = (isset($fromform->tag_exclude_logic) &&
            $fromform->tag_exclude_logic) ? 1 : 0;
        $status = $status && \reportbuilder::update_setting($reportid, self::TYPE,
            'exclude_logic', $excludelogic);

        // tag settings
        $tags = $DB->get_records('tag', array('isstandard' => 1));
        if (!empty($tags)) {
            $activeincludes = array();
            $activeexcludes = array();
            foreach ($tags as $tag) {
                $includename = 'tag_include_option_' . $tag->id;
                $excludename = 'tag_exclude_option_' . $tag->id;

                // included tags
                if (isset($fromform->$includename)) {
                    if ($fromform->$includename == 1) {
                        $activeincludes[] = $tag->id;
                    }
                }

                // excluded tags
                if (isset($fromform->$excludename)) {
                    if ($fromform->$excludename == 1) {
                        $activeexcludes[] = $tag->id;
                    }
                }
            }

            // implode into string and update setting
            $status = $status && \reportbuilder::update_setting($reportid,
                self::TYPE, 'included', implode('|', $activeincludes));

            // implode into string and update setting
            $status = $status && \reportbuilder::update_setting($reportid,
                self::TYPE, 'excluded', implode('|', $activeexcludes));
        }
        return $status;
    }
}
