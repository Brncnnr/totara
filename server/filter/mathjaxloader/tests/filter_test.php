<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package filter_mathjaxloader
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class filter_mathjaxloader_filter_testcase
 */
class filter_mathjaxloader_filter_testcase extends advanced_testcase {

    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->dirroot . '/filter/mathjaxloader/filter.php');
        parent::setUpBeforeClass();
    }

    public function test_filter() {
        global $CFG;


        // Prep the required structure.
        $learner = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($learner->id, $course->id);
        $coursecontext = context_course::instance($course->id);

        // Turn on legacy mode. We'll test that.
        $CFG->forced_plugin_settings['filter_mathjaxloader']['texfiltercompatibility'] = true;

        // Prep some data.
        $text1 = "$$ x^n + y^n = z^n $$";
        $text2 = "[tex] x^n + y^n = z^n [/tex]";
        $text3 = "<tex> x^n + y^n = z^n </tex>";
        $text4 = "\( x^n + y^n = z^n \)";

        // They should all end up looking the same.
        $filtered = '<span class="filter_mathjaxloader_equation"><span class="nolink">\( x^n + y^n = z^n \)</span></span>';

        // Access it like we're the learner.
        $this->setUser($learner);

        // Check it filters at the course context level.
        $filter = new filter_mathjaxloader($coursecontext, []);
        self::assertSame($filtered, $filter->filter($text1));

        $filtered = $filter->filter($text2);
        self::assertSame($filtered, $filter->filter($text2));

        $filtered = $filter->filter($text3);
        self::assertSame($filtered, $filter->filter($text3));

        $filtered = $filter->filter($text4);
        self::assertSame($filtered, $filter->filter($text4));

        self::assertSame($filtered, clean_text($filtered, FORMAT_HTML));
    }

    public function test_is_compatible_with_clean_text() {

        $method = new ReflectionMethod('filter_mathjaxloader', 'is_compatible_with_clean_text');
        $method->setAccessible(true);
        self::assertTrue($method->invoke(null));

    }

    /**
     * Test the functionality of {@link filter_mathjaxloader::map_language_code()}.
     *
     * @param string $moodlelangcode the user's current language
     * @param string $mathjaxlangcode the mathjax language to be used for the moodle language
     *
     * @dataProvider test_map_language_code_expected_mappings
     */
    public function test_map_language_code($moodlelangcode, $mathjaxlangcode) {

        $filter = new filter_mathjaxloader(context_system::instance(), []);
        $this->assertEquals($mathjaxlangcode, $filter->map_language_code($moodlelangcode));
    }

    /**
     * Data provider for {@link self::test_map_language_code}
     *
     * @return array of [moodlelangcode, mathjaxcode] tuples
     */
    public function test_map_language_code_expected_mappings() {

        return [
            ['cz', 'cs'], // Explicit mapping.
            ['cs', 'cs'], // Implicit mapping (exact match).
            ['ca_valencia', 'ca'], // Implicit mapping of a Moodle language variant.
            ['pt_br', 'pt-br'], // Explicit mapping.
            ['en_kids', 'en'], // Implicit mapping of English variant.
            ['de_kids', 'de'], // Implicit mapping of non-English variant.
            ['es_mx_kids', 'es'], // More than one underscore in the name.
            ['zh_tw', 'zh-hant'], // Explicit mapping of the Taiwain Chinese in the traditional script.
            ['zh_cn', 'zh-hans'], // Explicit mapping of the Simplified Chinese script.
        ];
    }

}