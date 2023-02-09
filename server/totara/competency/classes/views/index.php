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

use totara_competency\views\filters\assignments as filters;
use totara_core\output\select_region_panel;
use totara_core\output\select_tree;

class index extends base {

    protected $content_template = 'totara_competency/_assignments';

    protected $title = ['title_index', 'totara_competency'];

    public function __construct(array $data = []) {
        parent::__construct('totara_competency/index', $data);
    }

    protected function create_region_panel(): select_region_panel {
        return filters::create_region_panel();
    }

    protected function prepare_output($output) {
        $output = parent::prepare_output($output);

        $selection_basket_data = [
            'actionBtnList' => [
                (object) [
                    'action' => 'bulkActivate',
                    'label' => get_string('activate', 'totara_core'),
                ],
                (object) [
                    'action' => 'bulkArchive',
                    'label' => get_string('archive', 'totara_core'),
                ],
                (object) [
                    'action' => 'bulkDelete',
                    'label' => get_string('delete', 'totara_core'),
                ],
            ],
            'actionBtnListLabel' => get_string('bulkactions', 'totara_hierarchy'),
            'hasActionBtnList' => true,
            'hasToggleSelection' => true
        ];

        $content_data = [
            'has_level_toggle' => false,
            'has_paging' => true,
            'has_count' => true,
            'heading' => get_string('all_assignments', 'totara_competency'),
            'order_by' => $this->create_sorting(),
            'primary_filter_tree' => filters::create_status_filter(),
            'selection_basket' => $selection_basket_data
        ];

        return array_merge($output, $content_data);
    }

    private function create_sorting() {
        return select_tree::create(
            'sorting',
            get_string('sort', 'totara_competency'),
            false,
            [
                (object)[
                    'name' => get_string('sort_competency_name', 'totara_competency'),
                    'key' => 'competency_name',
                    'default' => true
                ],
                (object)[
                    'name' => get_string('sort_user_group_name', 'totara_competency'),
                    'key' => 'user_group_name',
                ],
                (object)[
                    'name' => get_string('sort_most_recently_updated', 'totara_competency'),
                    'key' => 'most_recently_updated',
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
