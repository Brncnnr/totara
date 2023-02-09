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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\data_provider\form\filter;

use core\orm\entity\filter\filter;

/**
 * Title filter.
 */
class title extends filter {

    /**
     * @var string
     */
    private $form_table_alias;

    /**
     * Form name filter constructor.
     *
     * @param string $form_table_alias
     */
    public function __construct(string $form_table_alias) {
        parent::__construct([]);
        $this->form_table_alias = $form_table_alias;
    }

    /**
     * @inheritDoc
     */
    public function apply() {
        if (trim($this->value) === '') {
            return;
        }

        $this->builder->where("$this->form_table_alias.title", 'ILIKE', $this->value);
    }
}