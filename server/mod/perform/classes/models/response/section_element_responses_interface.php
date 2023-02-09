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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\response;

use core\collection;
use mod_perform\models\activity\element;
use mod_perform\models\activity\participant_instance;
use mod_perform\models\activity\section_element;

/**
 * Represents the responses (or lack of) to an element from the
 * perspective of a participant or from the perspective of a
 * view-only observer.
 *
 * "response_data" holds the participants response.
 * Other participants (or all participants in the case of a view-only
 * observer) are held in "other_responder_groups".
 *
 * @property-read int section_element_id Foreign key
 * @property-read section_element $section_element The parent section element
 * @property-read element $element The element this is a response to
 * @property-read collection|participant_instance[] $visible_to
 * @property-read collection|responder_group[] $other_responder_groups
 *                Other responses grouped by relationship types (Manager/Appraiser)
 * @property-read int $sort_order The order this element should appear in the section
 */
interface section_element_responses_interface {

    public function get_section_element_id(): int;

    public function get_section_element(): section_element;

    public function get_element(): ?element;

    public function get_visible_to(): collection;

    public function get_other_responder_groups(): ?collection;

    public function get_sort_order(): int;

}