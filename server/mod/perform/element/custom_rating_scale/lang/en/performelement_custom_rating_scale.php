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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 * @package performelement_custom_rating_scale
 */

defined('MOODLE_INTERNAL') || die();

$string['answer_output'] = '{$a->label} (score: {$a->count})';
$string['answer_score'] = 'Score {$a->index}';
$string['answer_text'] = 'Text {$a->index}';
$string['custom_rating_options'] = 'Custom rating options';
$string['name'] = 'Rating scale: Custom';
$string['option_description'] = 'Option {$a} description';
$string['pluginname'] = 'Custom rating scale element';
$string['score'] = 'Score';
$string['text'] = 'Text';

// Help messages
$string['custom_values_help'] = 'Enter a custom text label and a corresponding numeric score that combined represent a selectable value on the rating scale. Decimal values are permitted for scores, and the values do not have to be unique or in numeric order.';

// Errors
$string['error:answer_required'] = 'Answer is required';
$string['error:question_required'] = 'Question is required';

// Deprecated in 13
$string['question_title'] = 'Question';
$string['required'] = 'Required';
$string['no_response_submitted'] = 'No response submitted';