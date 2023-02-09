<?php
/**
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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package engage_survey
 */

use engage_survey\totara_engage\link\survey_destination;
use totara_engage\link\builder;

defined('MOODLE_INTERNAL') || die();

class engage_survey_link_generator_testcase extends advanced_testcase {
    /**
     * Validate the destination is generated correctly
     */
    public function test_destination_generator() {
        $generator = builder::to('engage_survey', ['id' => 123]);
        $this->assertInstanceOf(survey_destination::class, $generator);

        $url = $generator->url()->out_as_local_url(false);
        $this->assertSame('/totara/engage/resources/survey/redirect.php?id=123', $url);

        $generator->set_attributes(['id' => 55, 'page' => 'view']);
        $url = $generator->url()->out_as_local_url(false);
        $this->assertSame('/totara/engage/resources/survey/survey_view.php?id=55', $url);

        $generator->set_attributes(['id' => 55, 'page' => 'vote']);
        $url = $generator->url()->out_as_local_url(false);
        $this->assertSame('/totara/engage/resources/survey/survey_vote.php?id=55', $url);

        $generator->set_attributes(['id' => 55, 'page' => 'edit']);
        $url = $generator->url()->out_as_local_url(false);
        $this->assertSame('/totara/engage/resources/survey/survey_edit.php?id=55', $url);
    }
}