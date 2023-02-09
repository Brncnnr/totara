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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\model\form\merger;

use mod_approval\form_schema\form_schema;
use mod_approval\model\workflow\workflow_stage;

/**
 * @internal Do not use this class from outside the module.
 */
final class form_schema_merger_view extends form_schema_merger {
    /**
     * @inheritDoc
     */
    protected function process_form_schema(form_schema $merged_schema, form_schema $stage_schema, workflow_stage $stage): form_schema {
        return $merged_schema->concat($stage_schema);
    }

    /**
     * @inheritDoc
     */
    protected function finalise_form_schema(form_schema $merged_schema, form_schema $base_schema): form_schema {
        return $merged_schema;
    }
}
