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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core
 * @category test
 */

defined('MOODLE_INTERNAL') || die();


class core_thirdpartylibs_testcase extends advanced_testcase {

    /**
     * Tests the static parse charset method.
     */
    public function test_xml_files_are_valid() {
        global $CFG;
            
        $files = [
            'core' => "$CFG->libdir/thirdpartylibs.xml",
            'core_src' => "$CFG->libraries/thirdpartylibs.xml"
        ];

        $plugintypes = core_component::get_plugin_types();
        foreach ($plugintypes as $type => $ignored) {
            $plugins = core_component::get_plugin_list_with_file($type, 'thirdpartylibs.xml', false);
            foreach ($plugins as $plugin => $path) {
                $files[$type.'_'.$plugin] = $path;
            }
        }

        foreach ($files as $component => $xmlpath) {
            try {
                $xml = simplexml_load_file($xmlpath);
                $this->assertInstanceOf(\SimpleXMLElement::class, $xml);
            } catch (Throwable $e) {
                $this->fail('Invalid thirdpartylibs.xml file found: "'.$xmlpath.'" (error: '.$e->getMessage().')');
            }
        }
    }

}

