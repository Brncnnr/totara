<?php
/*
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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package totara_catalog
*/

namespace totara_certification\totara_catalog\certification\dataformatter;

defined('MOODLE_INTERNAL') || die();

class activeperiod extends \totara_catalog\dataformatter\text {

    /**
     * Given a text string, returns it.
     * @see totara\certification\edit_certification.php save method
     * @param array $data
     * @param \context $context
     * @return string
     */
    public function get_formatted_value(array $data, \context $context): string {

        if (!array_key_exists('text', $data)) {
            throw new \coding_exception("Text data formatter expects 'text'");
        }
        // Hack to get a translation string for day(s)/week(s)/month(s)/year(s)
        // the explode() depends from the separation string in totara\certification\edit_certification.php
        // see $certification->activeperiod = $data->activenum.' '.$data->activeperiod;
        $value = explode(' ', trim($data['text']));
        if (isset($value[0]) && isset($value[1])) {
            $num = (int)trim($value[0]);
            $duration = trim($value[1]);
            if ($num > 1) {
                $duration .= 's';
            }
            $duration = get_string($duration);
            $data['text'] = $num . ' ' . $duration;
        }
        return parent::get_formatted_value($data, $context);
    }
}