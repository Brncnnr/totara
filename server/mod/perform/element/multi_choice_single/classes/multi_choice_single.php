<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package performelement_multi_choice_single
 */

namespace performelement_multi_choice_single;

use core\collection;
use mod_perform\models\activity\element;
use mod_perform\models\activity\respondable_element_plugin;
use mod_perform\models\activity\single_select_element_plugin_trait;

class multi_choice_single extends respondable_element_plugin {
    use single_select_element_plugin_trait;

    /**
     * @inheritDoc
     */
    public function validate_response(
        ?string $encoded_response_data,
        ?element $element,
        $is_draft_validation = false
    ): collection {
        $element_data = $element->data ?? null;
        $answer_option = $this->decode_response($encoded_response_data, $element_data);

        $errors = new collection();

        if ($this->fails_required_validation(is_null($answer_option), $element, $is_draft_validation)) {
            $errors->append(new option_required_error());
        }

        return $errors;
    }

    /**
     * @inheritDoc
     */
    public function get_participant_print_component(): string {
        return $this->get_participant_form_component();
    }

    /**
     * @inheritDoc
     */
    public function get_sortorder(): int {
        return 30;
    }
}
