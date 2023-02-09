<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Download tab manifest file.
 *
 * @package totara_msteams
 * @author Lai Wei <lai.wei@enovation.ie>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright (C) 2018 onwards Microsoft, Inc. (http://microsoft.com/)
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use totara_msteams\check\verifier;
use totara_msteams\manifest_helper;
use totara_msteams\settings_helper;

\totara_core\advanced_feature::require('totara_msteams');

$download = optional_param('download', false, PARAM_BOOL);
$debug = optional_param('debug', false, PARAM_BOOL);

if (!empty($debug) && is_siteadmin()) {
    admin_externalpage_setup(settings_helper::NS.'downloadmanifest');
    echo $OUTPUT->header();

    $installedlangs = get_string_manager()->get_list_of_translations(true);
    $output = new \totara_msteams\manifest\outputs\memory_output();
    $generator = new \totara_msteams\manifest\generator();
    $generator->generate_files($output);
    $files = $output->get_files();

    $renderer = $PAGE->get_renderer('totara_msteams');
    echo $renderer->render_manifest_download_debug($installedlangs, $files);

    echo $OUTPUT->footer();
    die;
}

if (!empty($download)) {
    if (!is_siteadmin()) {
        die;
    }
    manifest_helper::download();
} else {
    admin_externalpage_setup(settings_helper::NS.'downloadmanifest');
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('settings:page_totara_app', 'totara_msteams'));

    $renderer = $PAGE->get_renderer('totara_msteams');
    $verifier = new verifier();
    $error = !$verifier->execute();
    $result = $verifier->get_results();
    /** @var totara_msteams_renderer $renderer */
    echo $renderer->render_manifest_download(new moodle_url('/totara/msteams/download_manifest.php', ['download' => 1]), $error, $result);

    echo $OUTPUT->footer();
}
