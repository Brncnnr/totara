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
require_once(__DIR__ . '/../../../../../config.php');
define('WEKA_EDITOR_ITEM_ID', 42);
global $CFG, $OUTPUT, $PAGE;

use core\output\notification;
use totara_tui\output\component;

$json_content = optional_param('json_content', null, PARAM_TEXT);
$item_id = optional_param('item_id', null, PARAM_INT);

require_login();
$context = \context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url("/lib/editor/weka/tests/fixtures/weka_with_learn.php");
$PAGE->set_pagelayout('legacynolayout');
$PAGE->set_title("Weka editor test");

$tui = null;
$html_content = null;

if ('development' === $CFG->sitetype) {
    // Set up page and set up bundle.
    $tui = new component(
        'editor_weka/pages/fixtures/WekaWithLearn',
        [
            'default-doc' => $json_content,
            'instance-id' => WEKA_EDITOR_ITEM_ID,
            'sesskey' => sesskey()
        ]
    );

    $tui->register($PAGE);
    $cancel = optional_param('cancel', false, PARAM_BOOL);

    if ($cancel) {
        require_sesskey();
        require_once("{$CFG->dirroot}/lib/filelib.php");

        $fs = get_file_storage();
        $fs->delete_area_files(
            $context->id,
            'editor_weka',
            'learn',
            WEKA_EDITOR_ITEM_ID
        );

        // Delete the json content and item id
        $item_id = null;
        $json_content = null;
    } else if (null !== $item_id && 0 !== $item_id) {
        // This is for the ability to re-render the file on the editor.
        require_once("{$CFG->dirroot}/lib/filelib.php");
        require_sesskey();

        file_save_draft_area_files(
            $item_id,
            $context->id,
            'editor_weka',
            'learn',
            WEKA_EDITOR_ITEM_ID
        );
    }

    if (!empty($json_content)) {
        $html_content = format_text($json_content, FORMAT_JSON_EDITOR);
    }
}

echo $OUTPUT->header();

if (null === $tui) {
    // Not a development site, just output the error message, and note that this site should never be
    // navigated to here via navigation or any sort by a normal user. Hence hard-coded string.
    echo $OUTPUT->notification(
        "This page only exists to facilitate acceptance testing, " .
        "if you are here for any other reason please file an improvement request.",
        notification::NOTIFY_ERROR
    );
} else {
    echo $OUTPUT->render($tui);

    if (null !== $html_content) {
        echo html_writer::empty_tag('hr');
        echo $html_content;
    }
}

echo $OUTPUT->footer();