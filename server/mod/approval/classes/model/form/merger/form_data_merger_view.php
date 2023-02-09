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
use mod_approval\interactor\application_interactor;
use mod_approval\model\form\form_data;

/**
 * @internal Do not use this class from outside the module.
 */
final class form_data_merger_view extends form_data_merger {
    /**
     * @inheritDoc
     */
    protected function process_form_data(form_data $merged_form_data, form_data $form_data, form_schema $form_schema, application_interactor $interactor): form_data {
        $adjusted_data = $form_data->apply_form_schema($form_schema, $interactor, false);

        // Merge with the previous stage.
        return $merged_form_data->concat($adjusted_data);
    }

    /**
     * @inheritDoc
     */
    protected function finalise_form_data(form_data $form_data, form_schema $form_schema, application_interactor $interactor): form_data {
        return $form_data;
    }
}
