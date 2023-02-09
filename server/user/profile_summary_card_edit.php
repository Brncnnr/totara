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
 * @package core_user
 */
use core_user\form\profile_card_form;
use core_user\profile\display_setting;
use core_user\profile\field\field_helper;
use core\notification;

require_once(__DIR__ . '/../config.php');
require_login();

global $CFG, $PAGE, $OUTPUT;
require_once("{$CFG->dirroot}/lib/adminlib.php");

$url = new moodle_url("/user/profile_summary_card_edit.php");
$context = context_system::instance();

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');

admin_externalpage_setup('profilesummarycard');
$PAGE->set_title(get_string('userprofilesummarycard', 'admin'));

$setting_data = display_setting::get_setting_data();
$form = new profile_card_form($setting_data);
$form_data = $form->get_data();

if (null !== $form_data) {
    $fields = [];

    // Convert it to array, so that we don't have to use php dark magic to check for properties.
    $form_data = get_object_vars($form_data);

    for ($i = 0; $i < display_setting::MAGIC_NUMBER_OF_DISPLAY_FIELDS; $i++) {
        $key = field_helper::format_position_key($i);
        if (array_key_exists($key, $form_data)) {
            $fields[$key] = $form_data[$key];
        }
    }

    $non_empty_fields = array_filter(
        $fields,
        function (string $value): bool {
            return !empty($value);
        }
    );

    if (empty($non_empty_fields)) {
        // There are empty fields.
        notification::error(get_string('profilefieldscannotempty', 'admin'));
    } else {
        display_setting::save_display_fields($fields);

        if (isset($form_data['user_picture'])) {
            display_setting::save_display_user_profile($form_data['user_picture']);
        }

        notification::success(get_string('successupdateprofilecard', 'admin'));
        redirect($PAGE->url);
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading($PAGE->title);
echo html_writer::tag(
    'p',
    get_string('userprofilesummarycardformhelp', 'admin'),
    ['class' => 'profileSummaryCardEdit__helpText']
);

echo $form->render();
echo $OUTPUT->footer();