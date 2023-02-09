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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_engage
 */
defined('MOODLE_INTERNAL') || die();

use totara_engage\question\question_resolver_factory;
use totara_engage\question\question;
use totara_engage\answer\answer_type;

class totara_engage_create_question_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_create_question(): void {
        global $CFG, $DB;

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        require_once("{$CFG->dirroot}/totara/engage/tests/fixtures/question_resolver.php");
        $resolver = new question_resolver();
        $resolver->set_component('totara_engage');

        question_resolver_factory::phpunit_set_default_resolver($resolver);
        $question = question::create('Hello world ??', answer_type::MULTI_CHOICE, 'totara_engage');

        $this->assertNotEmpty($question->get_id());
        $this->assertTrue($DB->record_exists('engage_question', ['id' => $question->get_id()]));
    }
}