<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 * @package mod_approval
 */

defined('MOODLE_INTERNAL') || die();

use container_approval\approval as container;
use mod_approval\interactor\category_interactor;
use mod_approval\controllers\workflow\types\index;
use totara_core\advanced_feature;

/**
 * Class rb_approval_workflow_type_embedded
 */
final class rb_approval_workflow_type_embedded extends rb_base_embedded {
    /**
     * @var string
     */
    public $defaultsortcolumn;

    /**
     * @var int
     */
    public $defaultsortorder;

    /**
     * rb_approval_workflow_type_embedded constructor.
     */
    public function __construct() {
        $this->url = index::URL;
        $this->source = "approval_workflow_type";
        $this->shortname = 'approval_workflow_type';
        $this->fullname = get_string('manage_approval_workflows_types', 'mod_approval');

        $this->defaultsortcolumn = "workflow_type_name";
        $this->defaultsortorder = SORT_ASC;

        $this->columns = [
            [
                'type' => 'workflow_type',
                'value' => 'name',
                'heading' => get_string('workflow_type_name_label', 'mod_approval')
            ],
            [
                'type' => 'workflow_type',
                'value' => 'created',
                'heading' => get_string('timemodified', 'mod_approval')
            ],
            [
                'type' => 'workflow_type',
                'value' => 'actions',
                'heading' => get_string('actions', 'mod_approval')
            ]
        ];
        $this->filters = [];
        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;
        parent::__construct();
    }

    /**
     * @return bool
     */
    public function embedded_global_restrictions_supported(): bool {
        return true;
    }

    /**
     * Check if the current viewer is able to view this report.
     *
     * @param int           $userid
     * @param reportbuilder $report
     *
     * @return bool
     */
    public function is_capable(int $userid, reportbuilder $report): bool {
        return (new category_interactor(
            container::get_default_category_context(),
            $userid
        ))->can_manage_workflows();
    }

    /**
     * Hide this source if feature disabled or hidden.
     * @return bool
     */
    public static function is_source_ignored() {
        return advanced_feature::is_disabled('approval_workflows');
    }
}