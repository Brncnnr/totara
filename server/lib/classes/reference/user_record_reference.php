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
 * @author Michael Ivanov <michael.ivanov@totaralearning.com>
 * @package core
 */

namespace core\reference;

use core\entity\user;
use core\exception\unresolved_record_reference;
use core\webapi\reference\base_record_reference;
use core_user\access_controller;
use stdClass;

/**
 * User record reference. Used to find one record by provided parameters
 */
class user_record_reference extends base_record_reference {
    /**
     * @var bool - specify for a user query whether to filter for a user as a matching tenant participant in the cohort_members
     * table, rather than as a matching tenant member.
     */
    private bool $allow_tenant_participants = false;

    /**
     * @param bool $allow
     * @return void
     */
    public function set_allow_tenant_participants(bool $allow): void {
        $this->allow_tenant_participants = $allow;
    }

    /**
     * @inheritDoc
     */
    protected array $refine_columns = ['id', 'username', 'email', 'idnumber'];

    /**
     * @inheritDoc
     */
    protected function get_table_name(): string {
        return user::TABLE;
    }

    /**
     * @inheritDoc
     */
    protected function get_entity_name(): string {
        return 'User';
    }

    /**
     * @inheritDoc
     */
    public function get_record(array $ref_columns = []): stdClass
    {
        $record = parent::get_record($ref_columns);
        $controller = access_controller::for($record);
        try {
            $can_view = $controller->can_view_profile();
        } catch (\Throwable $exception) {
            $can_view = false;
        }
        if (!$can_view) {
            throw new unresolved_record_reference('No capabilities to view user.');
        }
        return $record;
    }

    /**
     * @inheritDoc
     */
    protected function convert_ref_columns_to_conditions(array $ref_columns = []): array {
        $conditions = parent::convert_ref_columns_to_conditions($ref_columns);
        $conditions['deleted'] = 0;

        return $conditions;
    }

    /**
     * Make sure the reference record is not a guest user
     * @return $this
     */
    public function not_a_guest(): self {
        $this->filter(function (stdClass $record): stdClass {
            if ($record->username === 'guest' or isguestuser($record)) {
                throw new unresolved_record_reference(
                    'Guest user can not be specified for ' . $this->entity_name . '.'
                );
            }
            return $record;
        });
        return $this;
    }

    /**
     * Make sure the reference record is not an admin user.
     * @return $this
     */
    public function not_an_admin(): self {
        $this->filter(function (stdClass $record): stdClass {
            if ($record->auth === 'manual' and is_siteadmin($record->id)) {
                throw new unresolved_record_reference('Admin user can not be specified for ' . $this->entity_name . '.');
            }
            return $record;
        });
        return $this;
    }
}
