<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package engage_survey
 */
namespace engage_survey\totara_engage\modal;

use context_user;
use core\entity\user;
use engage_survey\totara_engage\resource\survey;
use totara_engage\modal\modal;
use totara_tui\output\component;

/**
 * A modal medata for the front-end component.
 */
final class survey_modal extends modal {
    /**
     * @return component
     */
    public function get_vue_component(): component {
        return new component('engage_survey/components/CreateSurvey');
    }

    /**
     * @return string
     */
    public function get_label(): string {
        return get_string('defaultlabel', 'engage_survey');
    }

    /**
     * @return bool
     */
    public function is_expandable(): bool {
        return false;
    }

    /**
     * @return int
     */
    public function get_order(): int {
        return 4;
    }

    /**
     * @return bool
     */
    public function show_modal(): bool {
        global $USER;

        return survey::can_create($USER->id);
    }

    /**
     * @return bool
     */
    public function can_share(): bool {
        return has_capability('engage/survey:share', context_user::instance(user::logged_in()->id));
    }
}