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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\data_provider\application\filter;

use coding_exception;
use core\orm\entity\filter\filter;
use core\orm\query\builder;
use invalid_parameter_exception;
use mod_approval\entity\application\application_action;
use mod_approval\entity\assignment\assignment_approver;
use mod_approval\model\application\action\approve;
use mod_approval\model\application\action\reject;
use mod_approval\model\assignment\approver_type\user;
use mod_approval\model\assignment\approver_type\relationship;
use totara_job\entity\job_assignment;

/**
 * Filter by your progress - PENDING, APPROVED, or REJECTED
 */
class your_progress extends filter {

    /**
     * @var string
     */
    protected $application_table_alias;

    /**
     * @var int
     */
    protected $user_id;

    /**
     * @param int $user_id
     * @param string $application_table_alias
     */
    public function __construct(int $user_id, string $application_table_alias = 'application') {
        parent::__construct([]);
        $this->user_id = $user_id;
        $this->application_table_alias = $application_table_alias;
    }

    /**
     * @inheritDoc
     */
    public function apply(): void {
        if (is_array($this->value)) {
            throw new coding_exception('overall progress filter requires a single value');
        }
        switch (strtolower($this->value)) {
            case 'approved':
                $this->builder->join(
                    [application_action::TABLE, 'action'],
                    $this->application_table_alias . '.id',
                    '=',
                    'action.application_id'
                );
                $this->builder->where('action.user_id', '=', $this->user_id);
                $this->builder->where('action.code', '=', approve::get_code());
                break;

            case 'rejected':
                $this->builder->join(
                    [application_action::TABLE, 'action'],
                    $this->application_table_alias . '.id',
                    '=',
                    'action.application_id'
                );
                $this->builder->where('action.user_id', '=', $this->user_id);
                $this->builder->where('action.code', '=', reject::get_code());
                break;

            case 'pending':
                $this->builder->join([assignment_approver::TABLE, 'approver'], function (builder $joining) {
                    $joining->where_field(
                        $this->application_table_alias . '.current_approval_level_id',
                        '=',
                        'approver.workflow_stage_approval_level_id'
                    )
                        ->where_field('approver.approval_id', '=', $this->application_table_alias . '.approval_id')
                        ->where('approver.active', '=', true);
                });
                $this->builder->left_join(
                    [job_assignment::TABLE, 'ja'],
                    $this->application_table_alias . '.job_assignment_id',
                    '=',
                    'ja.id'
                );
                $this->builder->left_join([job_assignment::TABLE, 'managerja'], 'ja.managerjaid', '=', 'managerja.id');
                $this->builder->left_join([job_assignment::TABLE, 'tempmanagerja'], function (builder $joining) {
                    $joining->where_field('ja.tempmanagerjaid', '=', 'tempmanagerja.id')
                        ->where('ja.tempmanagerexpirydate', '>', time());
                });
                // Application current_level_id is equal to a level where you are an approver.
                $this->builder->where(function (builder $where){
                    $where->where('approver.type', '=', user::TYPE_IDENTIFIER)
                        ->where('approver.identifier', '=', $this->user_id);
                });
                // OR application current level is equal to a manager approver level, and you are the applicant's
                // manager or temporary manager.
                $this->builder->or_where(function (builder $where){
                    $where->where('approver.type', '=', relationship::TYPE_IDENTIFIER)
                        ->where(function (builder $subwhere){
                            $subwhere->where('managerja.userid', '=', $this->user_id)
                                ->or_where('tempmanagerja.userid', '=', $this->user_id);
                        });
                });
                break;

            case 'n/a':
            case 'na':
                $this->builder->left_join(
                    [job_assignment::TABLE, 'ja'],
                    $this->application_table_alias . '.job_assignment_id',
                    '=',
                    'ja.id'
                );
                $this->builder->left_join([job_assignment::TABLE, 'managerja'], 'ja.managerjaid', '=', 'managerja.id');
                $this->builder->left_join([job_assignment::TABLE, 'tempmanagerja'], function (builder $joining) {
                    $joining->where_field('ja.tempmanagerjaid', '=', 'tempmanagerja.id')
                        ->where('ja.tempmanagerexpirydate', '>', time());
                });
                $this->builder->left_join([assignment_approver::TABLE, 'approver'], function (builder $joining) {
                    // Finds if the user is an approver for the application's current approval level.
                    $joining->where_field(
                        $this->application_table_alias . '.current_approval_level_id',
                        '=',
                        'approver.workflow_stage_approval_level_id'
                    )
                        ->where('approver.active', '=', true)
                        // Application current_level_id is equal to any level where you are a user approver...
                        ->where(function (builder $where){
                            $where->where('approver.type', '=', user::TYPE_IDENTIFIER)
                                ->where('approver.identifier', '=', $this->user_id)
                            // OR Application current_level_id is equal to any level where you are a manager approver...
                            ->or_where(function (builder $where){
                                $where->where('approver.type', '=', relationship::TYPE_IDENTIFIER)
                                    ->where(function (builder $subwhere){
                                        $subwhere->where('managerja.userid', '=', $this->user_id)
                                            ->or_where('tempmanagerja.userid', '=', $this->user_id);
                                    });
                            });
                        });
                });
                // Filters by applications that do not have approver relationship with the current user
                $this->builder->where_null('approver.id');

                // ... and also you've never approved or denied the application.
                $this->builder->left_join([application_action::TABLE, 'action'], function (builder $joining) {
                    $joining->where_field($this->application_table_alias . '.id', '=', 'action.application_id')
                        ->where('action.user_id', '=', $this->user_id);
                });
                $this->builder->where_null('action.id');
                $this->builder->or_where(function (builder $orwhere) {
                    $orwhere->where('action.code', '!=', approve::get_code())
                        ->where('action.code', '!=', reject::get_code());
                });
                break;

            default:
                throw new invalid_parameter_exception('invalid value');
        }
    }
}