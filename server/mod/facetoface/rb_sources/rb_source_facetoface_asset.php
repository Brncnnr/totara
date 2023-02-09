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
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/facetoface/rb_sources/rb_facetoface_base_source.php');
require_once($CFG->dirroot . '/totara/customfield/field/location/define.class.php');

/**
 * Seminar Assets
 */
class rb_source_facetoface_asset extends rb_facetoface_base_source {

    use \mod_facetoface\rb\traits\assets;
    use \mod_facetoface\rb\traits\deprecated_assets_source;

    /**
     * Url of embedded report required for certain actions
     * @var string
     */
    protected $embeddedurl = '';

    /**
     * Report url params to pass through during actions
     * @var array
     */
    protected $urlparams = array();

    public function __construct(rb_global_restriction_set $globalrestrictionset = null) {

        $this->base = '{facetoface_asset}';
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_facetoface_asset');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_facetoface_asset');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_facetoface_asset');
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->paramoptions = $this->define_paramoptions();
        $this->add_customfields();

        parent::__construct();
    }

    protected function define_joinlist() {
        $joinlist = array();

        $joinlist[] = new rb_join(
            'assetdates',
            'LEFT',
            '{facetoface_asset_dates}',
            'assetdates.assetid = base.id',
            REPORT_BUILDER_RELATION_ONE_TO_MANY
        );

        $joinlist[] = new rb_join(
            'sessiondate',
            'LEFT',
            '{facetoface_sessions_dates}',
            'sessiondate.id = assetdates.sessionsdateid',
            REPORT_BUILDER_RELATION_ONE_TO_MANY
        );

        $joinlist[] = new rb_join(
            'assigned',
            'LEFT',
            '(SELECT assetid, COUNT(*) AS cntdates
              FROM {facetoface_asset_dates} fad
              INNER JOIN {facetoface_sessions_dates} fsd ON (fad.sessionsdateid = fsd.id)
              GROUP BY assetid)',
            'assigned.assetid = base.id',
            REPORT_BUILDER_RELATION_ONE_TO_MANY
        );
        return $joinlist;
    }

    protected function define_columnoptions() {
        $columnoptions = array();

        $this->add_assets_fields_to_columns($columnoptions, 'base', true);

        $columnoptions[] = new rb_column_option(
                'asset',
                'actions',
                get_string('actions'),
                'base.id',
                array(
                    'noexport' => true,
                    'nosort' => true,
                    'joins' => 'assigned',
                    'capability' => 'mod/facetoface:managesitewideassets',
                    'displayfunc' => 'f2f_asset_actions',
                    'hidden' => false,
                    'extrafields' => array(
                        'hidden' => 'base.hidden',
                        'cntdates' => 'assigned.cntdates',
                        'custom' => 'base.custom',
                    ),
                )
        );
        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = array();

        $this->add_assets_fields_to_filters($filteroptions, true);

        $filteroptions[] = new rb_filter_option(
            'asset',
            'assetavailable',
            get_string('available', 'rb_source_facetoface_asset'),
            'f2f_assetavailable',
            array(),
            'base.id'
        );
        return $filteroptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'asset',
                'value' => 'namelink'
            ),
            array(
                'type' => 'asset',
                'value' => 'description'
            ),
            array(
                'type' => 'asset',
                'value' => 'published'
            ),
            array(
                'type' => 'asset',
                'value' => 'visible'
            ),
            array(
                'type' => 'asset',
                'value' => 'allowconflicts'
            )
        );
        return $defaultcolumns;
    }

    protected function define_defaultfilters() {
        $defaultfilters = array(
            array(
                'type' => 'asset',
                'value' => 'name'
            ),
            array(
                'type' => 'asset',
                'value' => 'assetavailable'
            ),
        );
        return $defaultfilters;
    }

    protected function define_paramoptions() {
        // this is where you set your hardcoded filters
        $paramoptions = array(
            new rb_param_option(
                'published',
                'base.custom'
            )
        );
        return $paramoptions;
    }

    protected function add_customfields() {
        $this->add_totara_customfield_component(
            'facetoface_asset',
            'base',
            'facetofaceassetid',
            $this->joinlist,
            $this->columnoptions,
            $this->filteroptions
        );
    }

    /**
     * Get the embeddedurl
     *
     * @return string
     */
    public function get_embeddedurl() {
        return $this->embeddedurl;
    }

    /**
     * Get the url params
     *
     * @return mixed
     */
    public function get_urlparams() {
        return $this->urlparams;
    }

    public function post_params(reportbuilder $report) {
        $this->embeddedurl = $report->embeddedurl;
        $this->urlparams = $report->get_current_url_params();
    }

    public function global_restrictions_supported() {
        return true;
    }
}
