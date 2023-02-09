<?php
/*
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package mod_scorm
 */

defined('MOODLE_INTERNAL') || die();

class rb_source_scorm_local_packages extends rb_base_source {
    use core_course\rb\source\report_trait;
    use core_tag\rb\source\report_trait;

    public function __construct() {
        $this->base = 'SELECT f.*, s.id AS scormid, cm.id AS cmid, ctx.tenantid, s.course AS courseid
                         FROM "ttr_files" f
                         JOIN "ttr_context" ctx ON ctx.id = f.contextid AND ctx.contextlevel = 70
                         JOIN "ttr_course_modules" cm ON cm.id = ctx.instanceid
                         JOIN "ttr_modules" md ON md.id = cm.module AND md.name = \'scorm\'
                         JOIN "ttr_scorm" s ON s.id = cm.instance
                        WHERE f.component = \'mod_scorm\' AND f.filearea = \'package\' AND f.itemid = 0 AND f.filepath = \'/\' AND LOWER(f.filename) <> \'imsmanifest.xml\'
                              AND ((s.scormtype = \'local\' AND s.reference = f.filename) OR (s.scormtype = \'localsync\' AND f.filename <> \'.\'))';
        $this->base = '(' . $this->base . ')';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = [];
        $this->paramoptions = [];
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = [];
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_scorm_local_packages');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_scorm_local_packages');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_scorm_local_packages');
        $this->cacheable = false;

        $this->usedcomponents[] = 'mod_scorm';
        $this->usedcomponents[] = 'totara_cohort';

        parent::__construct();
    }

    public function global_restrictions_supported() {
        return false;
    }

    protected function define_joinlist() {
        $joinlist = [];

        $joinlist[] = new rb_join(
            'trusted',
            'LEFT',
            '{scorm_trusted_packages}',
            'trusted.contenthash = base.contenthash',
            REPORT_BUILDER_RELATION_ONE_TO_MANY
        );
        $joinlist[] = new rb_join(
            'scorm',
            'INNER',
            '{scorm}',
            'scorm.id = base.scormid',
            REPORT_BUILDER_RELATION_ONE_TO_MANY
        );

        $this->add_core_course_tables($joinlist, 'base', 'courseid');
        $this->add_core_user_tables($joinlist, 'base', 'userid');
        $this->add_core_tag_tables('core', 'course', $joinlist, 'base', 'courseid');
        $this->add_core_tag_tables('core', 'course_modules', $joinlist, 'base', 'cmid');

        return $joinlist;
    }

    protected function define_columnoptions() {
        $columnoptions = [];

        $columnoptions[] = new rb_column_option(
            'package',
            'filename',
            get_string('package', 'mod_scorm'),
            'base.filename',
            array(
                'joins' => 'scorm',
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'extrafields' => ['contextid' => 'base.contextid', 'revision' => 'scorm.revision', 'courseid' => 'base.courseid'],
                'displayfunc' => 'scorm_package')
        );
        $columnoptions[] = new rb_column_option(
            'package',
            'contenthash',
            get_string('packagecontenthash', 'mod_scorm'),
            'base.contenthash',
            array(
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'displayfunc' => 'trusted_contenthash')
        );
        // Package is not trusted if it got there via some sync option,
        // we trust only packages that went though scorm_add_instance() or scorm_update_instance()
        // as SCORM_TYPE_LOCAL type.
        $columnoptions[] = new rb_column_option(
            'package',
            'trusted',
            get_string('packagetrusted', 'mod_scorm'),
            '(CASE WHEN trusted.id IS NULL THEN 0 ELSE 1 END)',
            array(
                'joins' => 'trusted',
                'dbdatatype' => 'boolean',
                'displayfunc' => 'yes_or_no')
        );
        // Package is not current if parsing failed for whatever reason,
        // this should not happen.
        $columnoptions[] = new rb_column_option(
            'package',
            'current',
            get_string('packagecurrent', 'mod_scorm'),
            '(CASE WHEN trusted.contenthash = scorm.sha1hash THEN 1 ELSE 0 END)',
            array(
                'joins' => ['trusted', 'scorm'],
                'dbdatatype' => 'boolean',
                'displayfunc' => 'yes_or_no')
        );
        $columnoptions[] = new rb_column_option(
            'scorm',
            'title',
            get_string('scormtitle', 'rb_source_scorm'),
            'scorm.name',
            array(
                'joins' => 'scorm',
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'extrafields' => ['contextid' => 'base.contextid', 'scormid' => 'scorm.id', 'cmid' => 'base.cmid'],
                'displayfunc' => 'scorm_title')
        );

        $this->add_core_course_columns($columnoptions);
        $this->add_core_user_columns($columnoptions);
        $this->add_core_tag_columns('core', 'course', $columnoptions);
        $this->add_core_tag_columns('core', 'course_modules', $columnoptions);

        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = [];

        $filteroptions[] = new rb_filter_option(
            'package',
            'filename',
            get_string('package', 'mod_scorm'),
            'text'
        );
        $filteroptions[] = new rb_filter_option(
            'package',
            'contenthash',
            get_string('packagecontenthash', 'mod_scorm'),
            'text'
        );
        $filteroptions[] = new rb_filter_option(
            'package',
            'trusted',
            get_string('packagetrusted', 'mod_scorm'),
            'select',
            [
                'selectchoices' => [0 => get_string('no'), 1 => get_string('yes')],
                'simplemode' => true,
            ]
        );
        $filteroptions[] = new rb_filter_option(
            'package',
            'current',
            get_string('packagecurrent', 'mod_scorm'),
            'select',
            [
                'selectchoices' => [0 => get_string('no'), 1 => get_string('yes')],
                'simplemode' => true,
            ]
        );
        $filteroptions[] = new rb_filter_option(
            'scorm',
            'title',
            get_string('scormtitle', 'rb_source_scorm'),
            'text'
        );

        $this->add_core_course_filters($filteroptions);
        $this->add_core_user_filters($filteroptions);
        $this->add_core_tag_filters('core', 'course', $filteroptions);
        $this->add_core_tag_filters('core', 'course_modules', $filteroptions);

        return $filteroptions;
    }

    protected function define_defaultcolumns() {
        return [
            ['type' => 'course', 'value' => 'courselink'],
            ['type' => 'scorm', 'value' => 'title'],
            ['type' => 'package', 'value' => 'filename'],
            ['type' => 'package', 'value' => 'contenthash'],
            ['type' => 'package', 'value' => 'trusted'],
        ];
    }

    protected function define_defaultfilters() {
        return [
            ['type' => 'package', 'value' => 'filename'],
            ['type' => 'package', 'value' => 'contenthash'],
            ['type' => 'package', 'value' => 'trusted'],
        ];
    }

    /**
     * Returns expected result for column_test.
     * @param rb_column_option $columnoption
     * @return int
     */
    public function phpunit_column_test_expected_count($columnoption) {
        if (!PHPUNIT_TEST) {
            throw new coding_exception('phpunit_column_test_expected_count() cannot be used outside of unit tests');
        }
        return 0;
    }
}

