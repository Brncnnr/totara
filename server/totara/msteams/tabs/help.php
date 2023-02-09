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
 * @author Qingyang Liu<qingyang.liu@totaralearning.com>
 * @package totara_msteams
 */

use totara_msteams\botfw\mini_output;
use totara_core\advanced_feature;

require_once(__DIR__ . '/../../../config.php');

advanced_feature::require('totara_msteams');

$SESSION->theme = 'msteams';

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/totara/msteams/tabs/help.php'));
$PAGE->set_pagelayout('noblocks');
$PAGE->set_title(get_string('botfw:output_title', 'totara_msteams'));

$doc_link = html_writer::tag('a', get_string('help_page_product_doc', 'totara_msteams'),
    [
        'href' => 'https://help.totaralearning.com/display/TC/Microsoft+Teams+for+Totara+Cloud',
        'target' => '_blank',
        'rel' => 'noreferrer noopener'
    ]
);
echo $OUTPUT->header();
$renderer = $PAGE->get_renderer('totara_msteams');
echo $renderer->render_from_template(
    'totara_msteams/help',
    [
        'logo' => $OUTPUT->image_url('color', 'totara_msteams'),
        'access_app' => $OUTPUT->image_url('adding_app_to_teams', 'totara_msteams'),
        'find_learning' => $OUTPUT->image_url('find_learning', 'totara_msteams'),
        'library' => $OUTPUT->image_url('engage_library', 'totara_msteams'),
        'pinning_app' => $OUTPUT->image_url('pining_app', 'totara_msteams'),
        'extension' => $OUTPUT->image_url('messaging_extension_conversation', 'totara_msteams'),
        'config_tab' => $OUTPUT->image_url('config_tab', 'totara_msteams'),
        'display_config_tab' => $OUTPUT->image_url('display_config_tab', 'totara_msteams'),
        'doc_link' => $doc_link
    ]
);
echo $OUTPUT->footer();