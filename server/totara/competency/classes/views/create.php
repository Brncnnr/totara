<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\views;

use totara_competency\views\filters\competencies as filters;
use totara_core\output\select_region_panel;
use totara_core\output\select_tree;

class create extends base {

    protected $content_template = 'totara_competency/_competencies';

    protected $title = ['title_create', 'totara_competency'];

    public function __construct(array $data = []) {
        parent::__construct('totara_competency/create', $data);
    }

    protected function prepare_output($output) {
        $output = parent::prepare_output($output);

        $selection_basket_data = [
            'actionBtn' => [
                (object)[
                    'action' => 'assign',
                    'label' => get_string('assign', 'totara_hierarchy'),
                ],
            ],
            'hasToggleSelection' => true
        ];

        $content_data = [
            'crumbtrail_template_name' => 'totara_competency/crumb_with_title',
            'expandTemplate' => 'totara_competency/competency_expanded',
            'expandTemplateWebservice' => 'totara_competency_competency_show',
            'expandTemplateWebserviceArgs' => json_encode(['include' => ['crumbs' => 1, 'usergroups' => 1]]),
            'has_crumbtrail' => true,
            'has_level_toggle' => true,
            'has_count' => true,
            'has_paging' => true,
            'heading' => get_string('all_competencies', 'totara_competency'),
            'order_by' => $this->create_sorting(),
            'primary_filter_tree' => filters::create_framework_filter(),
            'selection_basket' => $selection_basket_data
        ];

        return array_merge($output, $content_data);
    }

    protected function create_region_panel(): select_region_panel {
        return filters::create_region_panel();
    }

    private function create_sorting() {
        return select_tree::create(
            'sorting',
            get_string('sort', 'totara_competency'),
            false,
            [
                (object)[
                    'name' => get_string('sort_framework_hierarchy', 'totara_competency'),
                    'key' => 'framework_hierarchy',
                    'default' => true
                ],
                (object)[
                    'name' => get_string('sort_competency_name', 'totara_competency'),
                    'key' => 'fullname',
                ],
            ],
            null,
            true,
            false,
            null,
            false
        );
    }
}
