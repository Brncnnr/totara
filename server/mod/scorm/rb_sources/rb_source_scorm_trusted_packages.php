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

class rb_source_scorm_trusted_packages extends rb_base_source {
    public function __construct() {
        $this->base = 'SELECT tp.id, tp.contenthash, tp.uploadedby, tp.timecreated, COUNT(f.id) AS usagecount
                         FROM "ttr_scorm_trusted_packages" tp 
                    LEFT JOIN "ttr_files" f ON f.contenthash = tp.contenthash
                        WHERE f.component = \'mod_scorm\' AND f.filearea = \'package\' AND f.itemid = 0 AND f.filepath = \'/\' AND LOWER(f.filename) <> \'imsmanifest.xml\'
                     GROUP BY tp.id, tp.contenthash, tp.uploadedby, tp.timecreated';
        $this->base = '(' . $this->base . ')';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = [];
        $this->paramoptions = [];
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = [];
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_scorm_trusted_packages');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_scorm_trusted_packages');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_scorm_trusted_packages');
        $this->cacheable = false;

        $this->usedcomponents[] = 'mod_scorm';

        parent::__construct();
    }

    public function global_restrictions_supported() {
        return false;
    }

    protected function define_joinlist() {
        $joinlist = [];

        $this->add_core_user_tables($joinlist, 'base', 'uploadedby');

        return $joinlist;
    }

    protected function define_columnoptions() {
        $columnoptions = [];

        $columnoptions[] = new rb_column_option(
            'trusted',
            'contenthash',
            get_string('packagecontenthash', 'mod_scorm'),
            'base.contenthash',
            array(
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'displayfunc' => 'trusted_contenthash')
        );
        $columnoptions[] = new rb_column_option(
            'trusted',
            'usagecount',
            get_string('usagecount', 'rb_source_scorm_trusted_packages'),
            'base.usagecount',
            array(
                'dbdatatype' => 'integer',
                'displayfunc' => 'integer')
        );
        $columnoptions[] = new rb_column_option(
            'trusted',
            'filenames',
            get_string('filenames', 'rb_source_scorm_trusted_packages'),
            'base.contenthash',
            array(
                'dbdatatype' => 'integer',
                'displayfunc' => 'trusted_filenames',
                'nosort' => true,
            )
        );
        $columnoptions[] = new rb_column_option(
            'trusted',
            'actions',
            get_string('actions', 'totara_reportbuilder'),
            'base.contenthash',
            array(
                'capability' => 'mod/scorm:managetrustedpackages',
                'displayfunc' => 'trusted_actions',
                'noexport' => true,
                'nosort' => true,
                'graphable' => false,
                'iscompound' => true,
                'extrafields' => array(
                    'contenthash' => 'base.contenthash'
                )
            )
        );

        $this->add_core_user_columns($columnoptions);

        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = [];

        $filteroptions[] = new rb_filter_option(
            'trusted',
            'contenthash',
            get_string('packagecontenthash', 'mod_scorm'),
            'text'
        );
        $filteroptions[] = new rb_filter_option(
            'trusted',
            'usagecount',
            get_string('usagecount', 'rb_source_scorm_trusted_packages'),
            'number'
        );

        $this->add_core_user_filters($filteroptions);

        return $filteroptions;
    }

    protected function define_defaultcolumns() {
        return [
            ['type' => 'trusted', 'value' => 'contenthash'],
            ['type' => 'trusted', 'value' => 'filenames'],
            ['type' => 'trusted', 'value' => 'usagecount'],
            ['type' => 'trusted', 'value' => 'actions'],
        ];
    }

    protected function define_defaultfilters() {
        return [
            ['type' => 'trusted', 'value' => 'contenthash'],
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

