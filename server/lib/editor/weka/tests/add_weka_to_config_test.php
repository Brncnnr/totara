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
 * @package editor_weka
 */
defined('MOODLE_INTERNAL') || die();

class editor_weka_add_weka_to_config_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_add_editor_weka_after_atto(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/editor/weka/db/upgradelib.php");

        set_config('texteditors', 'atto,oka,textarea,empty_editor,ako');
        $this->assertEquals('atto,oka,textarea,empty_editor,ako', $CFG->texteditors);

        editor_weka_add_weka_to_texteditors();
        $this->assertEquals('atto,weka,oka,textarea,empty_editor,ako', $CFG->texteditors);
    }

    public function test_add_editor_weka_before_textarea_without_atto_editor(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/editor/weka/db/upgradelib.php");

        set_config('texteditors', 'editor_text,dd,textarea,xx');
        $this->assertEquals('editor_text,dd,textarea,xx', $CFG->texteditors);

        editor_weka_add_weka_to_texteditors();
        $this->assertEquals('editor_text,dd,weka,textarea,xx', $CFG->texteditors);
    }

    /**
     * @return void
     */
    public function test_add_editor_weka_without_atto_and_textarea(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/editor/weka/db/upgradelib.php");

        set_config('texteditors', 'editor_text,editor_textarea');
        $this->assertEquals('editor_text,editor_textarea', $CFG->texteditors);

        editor_weka_add_weka_to_texteditors();
        $this->assertEquals('editor_text,editor_textarea,weka', $CFG->texteditors);
    }
}