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
 * @author Angela Kuznetsova <angela.jayasinghe@totaralearning.com>
 * @package performelement_date_picker
 */

class behat_performelement_date_picker extends behat_base {

    /**
     * @When /^I click date picker question element$/
     * @deprecated since Totara 13.2
     */
    public function i_click_date_picker_question_element(): void {
        debugging(
            '\behat_performelement_multi_choice_single::i_save_multi_choice_single_question_element_data() is deprecated and should no longer be used.'
            . ' Please use behat_mod_perform::i_add_a_custom_element() with "Date picker" as the parameter',
            DEBUG_DEVELOPER
        );

        behat_hooks::set_step_readonly(false);

        $behat_general = behat_context_helper::get('behat_general');

        $behat_general->i_click_on("Add element","button");
        $behat_general->i_click_on("Date picker","button");
    }

    /**
     * @When /^I save date picker question element data$/
     * @deprecated since Totara 13.2
     */
    public function i_save_date_picker_question_element_data(): void {
        debugging(
            '\behat_performelement_multi_choice_single::i_save_multi_choice_single_question_element_data() is deprecated and should no longer be used.'
            . ' Please use behat_mod_perform::i_save_the_custom_element_settings() with "save" as the parameter',
            DEBUG_DEVELOPER
        );

        behat_hooks::set_step_readonly(false);

        $done_button = $this->find('css', behat_mod_perform::ADMIN_FORM_DONE_BUTTON);
        $done_button->click();
    }

}
