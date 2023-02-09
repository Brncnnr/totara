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

namespace mod_approval\data_provider\application\filter;

use coding_exception;
use core\orm\entity\filter\filter;
use core\orm\query\builder;
use invalid_parameter_exception;
use mod_approval\entity\application\application_action;
use mod_approval\entity\workflow\workflow_stage;
use mod_approval\model\application\action\reject;
use mod_approval\model\application\action\withdraw_before_submission;
use mod_approval\model\application\action\withdraw_in_approvals;
use mod_approval\model\workflow\stage_type\finished;

/**
 * Filter by overall progress
 */
class overall_progress extends filter {

    /**
     * @var string
     */
    protected $application_table_alias;

    /**
     * @param string $application_table_alias
     */
    public function __construct(string $application_table_alias = 'application') {
        parent::__construct([]);
        $this->application_table_alias = $application_table_alias;
    }

    /**
     * @inheritDoc
     */
    public function apply(): void {
        if (!is_array($this->value)) {
            throw new coding_exception('overall progress filter requires an array for value');
        }

        if (count($this->value) == 0) {
            return;
        }

        if (count($this->value) != 1) {
            throw new coding_exception('overall progress filter only supports one value selected');
        }

        $value = reset($this->value);

        switch ($value) {
            case 'REJECTED':
                $this->builder->join(
                    [application_action::TABLE, 'action'],
                    $this->application_table_alias . '.id',
                    '=',
                    'action.application_id'
                );
                $this->builder->where('action.code', '=', reject::get_code());
                break;

            case 'WITHDRAWN':
                $this->builder->join(
                    [application_action::TABLE, 'action'],
                    $this->application_table_alias . '.id',
                    '=',
                    'action.application_id'
                );
                $this->builder->where('action.code', '=', withdraw_in_approvals::get_code());
                $this->builder->or_where('action.code', '=', withdraw_before_submission::get_code());
                break;

            case 'DRAFT':
                $this->builder->where(
                    $this->application_table_alias . ".is_draft",
                    '=',
                    1
                );
                break;

            case 'FINISHED':
                $this->builder->join(
                    [workflow_stage::TABLE, 'stage'],
                    $this->application_table_alias . '.current_stage_id',
                    '=',
                    'stage.id'
                );
                $this->builder->where(
                    "stage.type_code",
                    '=',
                    finished::get_code()
                );
                break;

            case 'IN_PROGRESS':
                // Not draft.
                $this->builder->where(
                    $this->application_table_alias . ".is_draft",
                    '=',
                    0
                );

                // Not finished.
                $this->builder->join(
                    [workflow_stage::TABLE, 'stage'],
                    $this->application_table_alias . '.current_stage_id',
                    '=',
                    'stage.id'
                );
                $this->builder->where(
                    "stage.type_code",
                    '!=',
                    finished::get_code()
                );

                // Only include applications as in-progress if the last action was not reject or withdraw.
                $this->builder->left_join(
                    [application_action::TABLE, 'action'],
                    $this->application_table_alias . '.id',
                    '=',
                    'action.application_id'
                );
                $this->builder->where(function (builder $builder) {
                    $builder->where_null('action.code');
                    $builder->or_where(function (builder $builder) {
                        $builder->where('action.code', '!=', reject::get_code())
                            ->where('action.code', '!=', withdraw_before_submission::get_code())
                            ->where('action.code', '!=', withdraw_in_approvals::get_code())
                            ->where('action.superseded', '=', 0);
                    });
                });
                break;

            default:
                throw new invalid_parameter_exception('invalid value(s): ' . $value);
        }
    }
}