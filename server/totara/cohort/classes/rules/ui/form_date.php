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
 * @author David Curry <david.curry@totaralearning.com>
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @author Aaron Wells <aaronw@catalyst.net.nz>
 * @package totara_cohort
 */

namespace totara_cohort\rules\ui;

defined('MOODLE_INTERNAL') || die();

use totara_cohort\rules\ui\form_empty as base;

/**
 * An empty form with validation for a cohort_rule_ui_date
 */
class form_date extends base {

    /**
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files){
        $errors = parent::validation($data, $files);

        // If they haven't ticked the radio button (somehow), then print an error text over the top row,
        // and highlight the bottom row but without any error text
        if (empty($data['fixedordynamic']) || !in_array($data['fixedordynamic'], array(1,2))) {
            $errors['beforeafterrow'] = get_string('error:baddateoption', 'totara_cohort');
            $errors['durationrow'] = ' ';
        }

        if ($data['fixedordynamic'] == 1 && empty($data['beforeafterdatetime']) &&
            (
                empty($data['beforeafterdate'])
                || !preg_match('/^[0-9]{1,2}[\/\-][0-9]{1,2}[\/\-](19|20)?[0-9]{2}$/', $data['beforeafterdate'])
            )
        ) {
            $errors['beforeafterrow'] = get_string('error:baddate', 'totara_cohort');
        }

        if (
            $data['fixedordynamic'] == 2
            && (
                !isset($data['durationdate'])
                || !preg_match('/^[0-9]+$/', $data['durationdate'])
            )
        ) {
            $errors['durationrow'] = get_string('error:badduration', 'totara_cohort');
        }

        return $errors;
    }
}
