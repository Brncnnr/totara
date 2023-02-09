<?php
/**
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_playlist
 */

use core\format;
use totara_core\advanced_feature;
use totara_playlist\formatter\playlist_formatter;
use totara_playlist\playlist;
use totara_playlist\event\playlist_viewed;
use totara_engage\access\access_manager;
use core\notification;
use totara_playlist\totara_engage\interactor\playlist_interactor;
use totara_playlist\totara_engage\link\nav_helper;
use totara_tui\output\component;

require_once(__DIR__ . "/../../config.php");
global $OUTPUT, $PAGE, $USER;

require_login();
advanced_feature::require('engage_resources');

$id = required_param('id', PARAM_INT);
$library_view = optional_param('libraryView', false, PARAM_BOOL);
$source = optional_param('source', null, PARAM_TEXT);

$playlist = playlist::from_id($id);

$PAGE->set_url("/totara/playlist/index.php", ['id' => $playlist->get_id()]);
$PAGE->set_pagelayout('legacynolayout');
$PAGE->set_totara_menu_selected('\totara_engage\totara\menu\library');

$tui = null;

if (!$playlist->is_available()) {
    $message = get_string('playlist_unavailable', 'totara_playlist');

    // Default to system context.
    $PAGE->set_context(\context_system::instance());
    $PAGE->set_title($message);

    $tui = new component(
        'totara_engage/pages/EngageUnavailableResource',
        ['message' => $message]
    );
    $tui->register($PAGE);
} else if (access_manager::can_access($playlist)) {
    $PAGE->set_context($playlist->get_context());
    $PAGE->set_title($playlist->get_name());

    $event = playlist_viewed::from_playlist($playlist);
    $event->trigger();

    $back_button = nav_helper::build_back_button($playlist->get_userid(), $source);
    $interactor = playlist_interactor::create_from_accessible($playlist, $USER->id);

    $playlist_formatter = new playlist_formatter($playlist);

    if ($library_view) {
        $tui = new component('totara_engage/pages/LibraryView', [
            'id' => "playlist_{$id}",
            'title' => $playlist_formatter->format('name', format::FORMAT_PLAIN),
            'content' => [
                'component' => 'PlaylistResourcesContent',
                'tuicomponent' => 'totara_playlist/components/contribution/PlaylistResources',
            ],
            'sidePanel' => [
                'component' => 'PlaylistResourcesSidePanelContent',
                'tuicomponent' => 'totara_playlist/components/sidepanel/content/Playlist',
            ],
            'page-props' => [
                'playlistId' => $playlist->get_id(),
                'back-button' => $back_button ?? null,
                'interactor' => $interactor->to_array(),
            ],
        ]);

    } else {
        $tui = new component(
            'totara_playlist/pages/PlaylistView',
            [
                'playlist-id' => $playlist->get_id(),
                'back-button' => $back_button ?? null,
                'interactor' => $interactor->to_array(),
            ]
        );
    }

    $tui->register($PAGE);
} else {
    $PAGE->set_context(\context_system::instance());
}

// Rendering the page.
echo $OUTPUT->header();

if (null !== $tui) {
    echo $OUTPUT->render($tui);
} else {
    notification::error(get_string('cannotviewplaylist', 'totara_playlist'));
}

echo $OUTPUT->footer();