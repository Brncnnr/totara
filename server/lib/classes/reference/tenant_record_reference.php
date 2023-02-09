<?php
/**
 * This file is part of Totara Core
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
 * @author Scott Davies <scott.davies@totaralearning.com>
 * @package totara_api
 */
namespace core\reference;

use core\entity\tenant;
use core\exception\unresolved_record_reference;
use core\webapi\reference\base_record_reference;
use stdClass;

/**
 * Tenant record reference. Used to find one record by provided parameters.
 */
class tenant_record_reference extends base_record_reference {
    /**
     * @inheritDoc
     */
    protected array $refine_columns = ['id', 'name', 'idnumber', 'suspended'];

    /**
     * @inheritDoc
     */
    protected function get_table_name(): string {
        return tenant::TABLE;
    }

    /**
     * @inheritDoc
     */
    protected function get_entity_name(): string {
        return 'Tenant';
    }

    /**
     * @param int|null $api_client_tenant_id
     * @return $this
     * @throws unresolved_record_reference
     */
    public function target_user_matches_tenant_input(?int $input_tenant_id) {
        $this->filter(function (stdClass $record) use ($input_tenant_id): stdClass {
            if (!is_null($input_tenant_id) && $input_tenant_id !== $record->id) {
                throw new unresolved_record_reference('You can update user within your tenant only.');
            }
            return $record;
        });
        return $this;
    }
}
