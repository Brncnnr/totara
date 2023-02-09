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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\controllers;

use context;
use context_system;
use hierarchy_competency\event\competency_viewed;
use moodle_url;
use pathway_criteria_group\criteria_group;
use totara_competency\achievement_configuration;
use totara_competency\achievement_criteria;
use totara_competency\overall_aggregation;
use totara_competency\pathway;
use totara_competency\pathway_factory;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\competency_framework;
use totara_core\advanced_feature;
use totara_mvc\admin_controller;
use totara_mvc\tui_view;

global $CFG;
require_once($CFG->dirroot.'/totara/hierarchy/prefix/competency/lib.php');
require_once($CFG->dirroot.'/totara/hierarchy/item/edit_form.php');

class competency_controller extends admin_controller {

    /**
     * @var competency
     */
    protected $competency;

    /**
     * @var competency_framework
     */
    protected $framework;

    protected $admin_external_page_name = 'competencymanage';

    private $prefix = 'competency';

    protected function setup_context(): context {
        return \context_system::instance();
    }

    protected function setup() {
        \hierarchy::check_enable_hierarchy($this->prefix);

        $permissions = $this->get_user_permissions();
        if (!$this->validate_user_access($permissions)) {
            print_error('accessdenied', 'admin');
        }

        $this->competency = competency::repository()
            ->where('id', required_param('id', PARAM_INT))
            ->with('framework')
            ->with('pathways')
            ->one();

        $this->framework = $this->competency->framework;

        $this->get_page()->navbar
            ->add(
                format_string($this->framework->fullname),
                new moodle_url('/totara/hierarchy/index.php', [
                    'prefix' => $this->prefix, 'frameworkid' => $this->framework->id
                ])
            );
    }

    public function action_summary() {
        $this->setup();
        require_capability('totara/hierarchy:viewcompetency', context_system::instance());

        $url = new moodle_url('/totara/competency/competency_summary.php', ['id' => $this->competency->id]);
        $title = get_string('competency_title', 'totara_hierarchy', (object) [
            'framework' => format_string($this->framework->fullname),
            'fullname' => format_string($this->competency->display_name)
        ]);
        $this->get_page()->set_url($url);
        $this->get_page()->set_title($title);
        $this->get_page()->navbar->add(format_string($this->competency->display_name));

        // This event is triggered for 3rd party backwards compatibility with the hierarchy plugin
        competency_viewed::create_from_instance((object)$this->competency->to_array())->trigger();

        $copy_page_url = new moodle_url('/totara/competency/pathway_copy.php');
        $copy_count = $this->get_optional_param('copied_count', 0, PARAM_INT);
        $need_review_count = $this->get_optional_param('need_review_count', 0, PARAM_INT);

        $copy_count_message = null;
        if ($copy_count > 0) {
            if ($need_review_count === 0) {
                $tag = $copy_count === 1
                    ? 'copying_achievement_paths_success_one'
                    : 'copying_achievement_paths_success';
                $copy_count_message = get_string($tag, 'totara_competency', (object)['count' => $copy_count]);
            } else if ($need_review_count > 0 && $need_review_count < $copy_count) {
                if ($need_review_count === 1 && $copy_count === 1) {
                    $tag = 'achievement_paths_need_review_partially_one_one';
                } else if ($need_review_count !== 1 && $copy_count === 1) {
                    $tag = 'achievement_paths_need_review_partially_one_many';
                } else if ($need_review_count === 1 && $copy_count !== 1) {
                    $tag = 'achievement_paths_need_review_partially_many_one';
                } else {
                    $tag = 'achievement_paths_need_review_partially_many_many';
                }

                $copy_count_message = get_string($tag, 'totara_competency', (object)['count' => $copy_count, 'review_count' => $need_review_count]);
            } else {
                $tag = $copy_count === 1
                    ? 'achievement_paths_need_review_completely_one'
                    : 'achievement_paths_need_review_completely';
                $copy_count_message = get_string($tag, 'totara_competency', (object)['count' => $copy_count]);
            }
        }

        $copy_pending_message = (int)$this->competency->copy_op_id > 0
            ? get_string('copying_achievement_paths_warning', 'totara_competency')
            : '';

        return new tui_view('totara_competency/pages/CompetencySummary', [
            'competency-id' => $this->competency->id,
            'competency-name' => format_string($this->competency->display_name),
            'copied-paths-success-message' => $copy_count_message,
            'copy-pending-warning-message' => $copy_pending_message,
            'copy-achievement-path-url' => $copy_page_url->out(false),
            'framework-id' => $this->framework->id,
            'framework-name' => format_string($this->framework->fullname),
            'perform-enabled' => advanced_feature::is_enabled('competency_assignment'),
        ]);
    }

    public function action_edit() {
        global $CFG;
        require_once($CFG->dirroot . '/totara/hierarchy/renderer.php');

        $this->setup();
        require_capability('totara/hierarchy:updatecompetency', context_system::instance());

        $section = $this->get_required_param('s', PARAM_ALPHA);
        $notify = $this->get_optional_param('notify', 0, PARAM_INT);

        $exportmethod = "export_{$section}_edit";
        if (!method_exists($this, $exportmethod)) {
            print_error('invalid_section', 'totara_competency', '', $section);
        }

        $heading = get_string('edit_competency', 'totara_competency', format_string($this->competency->display_name));
        $tab = get_string('competencytab' . $section, 'totara_hierarchy');
        $title = get_string('edit_competency_title', 'totara_competency', ['header' => $heading, 'tab' => $tab]);

        $this->get_page()->navbar->add($heading);

        $this->competency = new competency($this->competency->id);
        $config = new achievement_configuration($this->competency);

        $data = [
            'pending_bulk_copy_id' => (int)$this->competency->copy_op_id,
            'competency_id' => $this->competency->id,
            'scale_id' => $this->competency->scale->id,
            'heading' => $heading,
            'tabs' => \totara_hierarchy_renderer::get_competency_tabs($this->competency->id, "edit$section"),
            'detail' => $this->$exportmethod($config),
            'singleuse' => (int)$config->has_singleuse_criteria(),
        ];

        if (advanced_feature::is_enabled('competency_assignment')) {
            $data['backurl'] = new \moodle_url('/totara/competency/competency_summary.php', ['id' => $this->competency->id]);
        } else {
            $data['backurl'] = new \moodle_url('/totara/hierarchy/item/view.php', ['id' => $this->competency->id, 'prefix' => 'competency']);
        }

        if ($notify) {
            $data['success'] = true;
        }

        return (new \totara_mvc\view(
            'totara_competency/competency_edit',
            $data
        ))->set_title($title);
    }

    /**
     * Validate the user's access to the competency and management of criteria
     *
     * @return bool
     */
    private function validate_user_access($permissions): bool {
        return !empty($permissions) && !empty($permissions['canview']) && !empty($permissions['canmanage']);
    }

    /**
     * Get the user's access permissions for this competency
     *
     * @return array
     */
    private function get_user_permissions(): array {
        global $CFG;

        require_once($CFG->dirroot.'/totara/hierarchy/lib.php');
        require_once($CFG->dirroot.'/totara/hierarchy/prefix/competency/lib.php');

        if (empty($this->permissions)) {
            $hierarchy = \hierarchy::load_hierarchy($this->prefix);
            $this->permissions = $hierarchy->get_permissions();
        }

        return $this->permissions;
    }

    /**
     * Export template and data for editing general attributes of a competency
     *
     * @return array
     */
    private function export_general_edit() {
        $url = new \moodle_url('/totara/hierarchy/item/edit.php',
            [
                'prefix' => $this->prefix,
                'frameworkid' => $this->competency->frameworkid,
                'id' => $this->competency->id,
            ]
        );
        redirect($url);
    }

    /**
     * Export template and data for editing linked courses
     *
     * @return array
     */
    private function export_linkedcourses_edit() {
        return ['templatename' => 'totara_competency/competency_edit_linkedcourses'];
    }

    /**
     * Export template and skeleton data for editing achievement paths
     * On load, the javascript will retrieve the pathways and show them
     *
     * @return array
     */
    private function export_achievementpaths_edit(achievement_configuration $config) {
        advanced_feature::require('competency_assignment');

        // Mustache requires numeric indexes starting at 0
        $pathway_types = $this->get_pathway_types();
        $pathway_types[] =
            [
                'type' => 'singlevalue',
                'name' => get_string('single_value_paths', 'totara_competency'),
                'templatename' => 'totara_competency/scalevalue_pathways_edit',
                'classification' => pathway::PATHWAY_SINGLE_VALUE,
                'singleuse' => false,
            ];

        $results = [
            'templatename' => 'totara_competency/achievement_paths',
            'aggregation_types' => $this->get_overall_aggregation_types(),
            'pathway_types' => $pathway_types,
            'criteria_types' => criteria_group::export_criteria_types(),
            'has_pathways' => count($config->get_active_pathways()) > 0,
            'pathway_groups' => $config->export_pathway_groups(),
        ];

        return $results;
    }

    /**
     * @return array
     */
    private function get_pathway_types(): array {
        $types = pathway_factory::get_pathway_types();
        $types = array_map(function ($type) {
            $pw = pathway_factory::create($type);
            return [
                'type' => $type,
                'name' => $pw->get_title(),
                'templatename' => $pw->get_edit_template(),
                'classification' => $pw->get_classification(),
                'singleuse' => $pw->is_singleuse(),
            ];
        }, $types);

        return array_values(array_filter($types, function ($type) {
            return $type['classification'] == pathway::PATHWAY_MULTI_VALUE;
        }));
    }

     /**
     * @return array
     */
    private function get_overall_aggregation_types(): array {
        $competency_agg_type = $this->competency->scale_aggregation_type ?? achievement_configuration::DEFAULT_AGGREGATION;
        $agg_methods = achievement_criteria::get_available_overall_aggregation_methods();

        return array_map(function (overall_aggregation $method) use ($competency_agg_type) {
            return [
                'type' => $method->get_agg_type(),
                'title' => $method->get_title(),
                'description' => $method->get_description(),
                'editfunction' => $method->get_aggregation_js_function(),
                'selected' => $method->get_agg_type() == $competency_agg_type,
            ];
        }, $agg_methods);
    }

}
