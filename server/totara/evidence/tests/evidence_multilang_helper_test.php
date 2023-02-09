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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 * @category test
 */

use totara_evidence\models\evidence_type;
use totara_evidence\models\helpers\multilang_helper;

global $CFG;
require_once($CFG->dirroot . '/totara/evidence/tests/evidence_testcase.php');

/**
 * @group totara_evidence
 */
class totara_evidence_multilang_helper_testcase extends totara_evidence_testcase {

    protected function setUp(): void {
        parent::setUp();

        // Enable multi-language filter
        filter_manager::reset_caches();
        filter_set_global_state('multilang', TEXTFILTER_ON);
        filter_set_applies_to_strings('multilang', true);
    }

    public function test_parse_regular_lang_string(): void {
        $multilang_input = '<span class="multilang" lang="en">English</span><span class="multilang" lang="fr">French</span>';
        $multilang_output = 'English';

        $this->assertEquals($multilang_output, multilang_helper::parse_string($multilang_input));
        $this->assertEquals($multilang_output, multilang_helper::parse_type_name_string($multilang_input));
        $this->assertEquals($multilang_output, multilang_helper::parse_field_name_string($multilang_input));

        // We are parsing as raw, which means format_string() won't be called.
        $this->assertEquals($multilang_input, multilang_helper::parse_string($multilang_input, '', true));
        $this->assertEquals($multilang_input, multilang_helper::parse_type_description_string($multilang_input));
    }

    /**
     * We want to verify that using user-entered multilang strings and uploaded images is compatible with our multilang helper.
     */
    public function test_parse_type_description_with_images(): void {
        global $CFG;

        $type = $this->generator()->create_evidence_type_entity(['field_types' => ['textarea']]);

        $file_name = 'file.jpg';
        $file = $this->generator()->create_test_file([
            'itemid' => $type->id,
            'filearea' => evidence_type::DESCRIPTION_FILEAREA,
            'filename' => $file_name,
        ]);
        $file_url = $CFG->wwwroot . '/pluginfile.php/' . context_system::instance()->id . '/totara_evidence/' .
            evidence_type::DESCRIPTION_FILEAREA . '/' . $type->id . '/' . $file->get_filename();

        $type->description = '<p><span class="multilang" lang="en">English</span>' .
            '<span class="multilang" lang="fr">French</span></p>' .
            '<p><img src="@@PLUGINFILE@@/' . $file_name . '"></p>';
        $type->save();

        $formatted_description = evidence_type::load_by_entity($type)->get_display_description();
        $expected_output = '<p>English</p><p><img src="' . $file_url . '" alt="' . $file_name . '" /></p>';
        $this->assertEquals($expected_output, $formatted_description);
    }

    public function test_parse_xss_string(): void {
        $xss_input = "Dangerous!<script>alert('Bad!');</script>";
        $xss_output = 'Dangerous!alert(&#39;Bad!&#39;);';

        $this->assertEquals($xss_output, multilang_helper::parse_string($xss_input));
        $this->assertEquals($xss_output, multilang_helper::parse_type_name_string($xss_input));
        $this->assertEquals($xss_output, multilang_helper::parse_field_name_string($xss_input));

        // We are parsing as raw, which means format_string() won't be called.
        $this->assertEquals($xss_input, multilang_helper::parse_string($xss_input, '', true));
        $this->assertEquals($xss_input, multilang_helper::parse_type_description_string($xss_input));
    }

    public function test_parse_system_type_name(): void {
        $system_name_input = ['multilang:completion_course', 'system_type_name:'];
        $system_name_output = 'Course completion import (system type)';

        $this->assertEquals($system_name_output, multilang_helper::parse_string(...$system_name_input));
        $this->assertEquals($system_name_output, multilang_helper::parse_type_name_string(...$system_name_input));
    }

    public function test_parse_system_type_description(): void {
        $system_field_input = ['multilang:completion_course', 'system_type_desc:'];
        $system_description_output = 'This is a system type used for importing course completion records';

        $this->assertEquals($system_description_output, multilang_helper::parse_string(...$system_field_input));
        $this->assertEquals(
            $system_description_output,
            multilang_helper::parse_type_description_string(...$system_field_input)
        );
    }

    public function test_parse_system_field_name(): void {
        $system_field_input = ['multilang:grade', 'system_field_name:'];
        $system_field_output = 'Grade';

        $this->assertEquals($system_field_output, multilang_helper::parse_string(...$system_field_input));
        $this->assertEquals($system_field_output, multilang_helper::parse_field_name_string(...$system_field_input));
    }

}
