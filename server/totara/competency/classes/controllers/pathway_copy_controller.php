<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\controllers;

use context;
use context_system;
use hierarchy;
use hierarchy_competency\event\competency_viewed;
use moodle_url;
use stdClass;
use totara_hierarchy\entity\competency;
use totara_core\advanced_feature;
use totara_mvc\admin_controller;
use totara_mvc\tui_view;

global $CFG;
require_once($CFG->dirroot.'/totara/hierarchy/lib.php');
require_once($CFG->dirroot.'/totara/hierarchy/prefix/competency/lib.php');
require_once($CFG->dirroot.'/totara/hierarchy/item/edit_form.php');

class pathway_copy_controller extends admin_controller {
    /**
     * Hierarchy prefix for competency.
     */
    private const PREFIX = 'competency';

    /**
     * @inheritDoc
     */
    protected $admin_external_page_name = 'competencymanage';

    /**
     * @inheritDoc
     */
    public function action() {
        $data = $this->create_working_data();
        $framework_id = $data->framework_id;
        $framework_name = $data->framework_fullname;
        $competency_name = $data->competency_name;
        $summary_page = $data->competency_summary_page;

        $page = $this->get_page();
        $page->set_url($summary_page->out(false));
        $page->set_title(
            get_string(
                'competency_title',
                'totara_hierarchy',
                ['framework' => $framework_name, 'fullname' => $competency_name]
            )
        );

        $nav_bar = $page->navbar;
        $nav_bar->add(
            $framework_name,
            new moodle_url(
                '/totara/hierarchy/index.php',
                ['prefix' => self::PREFIX, 'frameworkid' => $framework_id]
            )
        );
        $nav_bar->add($competency_name);

        // This event is triggered for 3rd party backwards compatibility with the hierarchy plugin
        competency_viewed::create_from_instance((object)$data->competency->to_array())->trigger();

        return new tui_view('totara_competency/pages/CompetencyCopyPathways', [
            'no-pathways-warning' => $data->competency->pathways->count() === 0,
            'competency-id' => $data->competency->id,
            'competency-name' => $competency_name,
            'has-criteria-pathway' => $data->competency->has_criteria_based_pathway(),
            'back-url' => $summary_page->out_omit_querystring(false),
            'framework-id' => $framework_id,
            'framework-name' => $framework_name
        ]);
    }

    /**
     * Checks whether the user has the correct rights to access this page.
     */
    private function check_access_rights(): void {
        // Feature enabled checks.
        hierarchy::check_enable_hierarchy(self::PREFIX);
        advanced_feature::require('competency_assignment');

        // Capability checks.
        $permissions = hierarchy::load_hierarchy(self::PREFIX)->get_permissions();
        $can_access = !empty($permissions)
            && !empty($permissions['canview'])
            && !empty($permissions['canmanage']);
        if (!$can_access) {
            print_error('accessdenied', 'admin');
        }

        $this->require_capability('totara/hierarchy:updatecompetency', $this->setup_context());
    }

    /**
     * Returns a working data set to be used for rendering the page.
     *
     * @return stdClass the working data set with these fields:
     *  - [competency] competency
     *  - [int] framework_id
     *  - [string] framework_fullname
     *  - [moodle_url] competency_summary_page
     */
    private function create_working_data(): stdClass {
        $this->check_access_rights();

        $competency = competency::repository()
            ->where('id', required_param('id', PARAM_INT))
            ->with('framework')
            ->with('pathways')
            ->one();

        if (!$competency) {
            print_error('competency_does_not_exist', 'totara_competency');
        }

        $framework = $competency->framework;
        $framework_id = $framework->id;
        $framework_fullname = format_string($framework->fullname);

        $summary_page = new moodle_url(
            '/totara/competency/competency_summary.php', ['id' => $competency->id]
        );

        return (object) [
            'competency' => $competency,
            'competency_name' => format_string($competency->display_name),
            'framework_id' => $framework_id,
            'framework_fullname' => $framework_fullname,
            'competency_summary_page' => $summary_page
        ];
    }

    /**
     * @inheritDoc
     */
    protected function setup_context(): context {
        return context_system::instance();
    }
}
