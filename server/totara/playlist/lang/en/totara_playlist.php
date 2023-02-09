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

defined('MOODLE_INTERNAL') || die();

$string['accesssettings'] = 'Settings';
$string['add_to_playlist'] = "Add to playlist";
$string['adddescription'] = "Add a description (optional)";
$string['addrating'] = "Add your rating";
$string['back_button'] = "{\$a}";
$string['by_author'] = "by {\$a}";
$string['cachedef_catalog_visibility'] = 'Visibility of playlist items in the catalog';
$string['cannotviewplaylist'] = "Cannot view the playlist";
$string['change_playlist_visibility_confirm_1'] = "Are you sure you want to make this playlist visible to everyone?";
$string['change_playlist_visibility_confirm_2'] = "Some content in this playlist is only visible to you, or limited to specific people and workspaces. Changing playlist visibility will make all its content visible to everyone. This change can't be undone.";
$string['contribute'] = "Create new playlist";
$string['contributeplaylist'] = "Contribute playlist";
$string['createplaylistshort'] = "Create Playlist";
$string['creator'] = "Playlist creator";
$string['defaultlabel'] = "Playlist";
$string['delete_playlist_confirm_1'] = 'Are you sure you want to delete this playlist?';
$string['delete_playlist_confirm_2'] = 'It will be permanently removed and people will no longer be able to view it. Playlist contents will NOT be deleted.';
$string['deletewarningtitle'] = 'Delete playlist';
$string['edit_playlist_title'] = 'Edit playlist title';
$string['entertitle'] = "Enter playlist title";
$string['image_alt'] = 'The image for the playlist {$a}';
$string['message_playlist'] = "playlist";
$string['mentionbody:comment'] = '<strong>{$a->fullname}</strong> has commented on the playlist {$a->title}.';
$string['mentionbody:playlist'] = '<strong>{$a->fullname}</strong> has mentioned you in the playlist {$a->title}.';
$string['mentiontitle:playlist'] = '{$a} has mentioned you in a playlist';
$string['mentionview:playlist'] = 'View playlist';
$string['move_element'] = 'Move {$a}';
$string['playlistcreated'] = "Playlist created";
$string['playlistdescription'] = "Playlist description";
$string['playlistdeleted'] = "Playlist deleted";
$string['playlistengagement'] = "Playlists Engagement";
$string['playlists'] = 'Playlists';
$string['playlistreshared'] = "Playlist re-shared";
$string['playlistshared'] = "Playlist shared";
$string['playlisttitle'] = 'Playlist title';
$string['pluginname'] = "Playlist";
$string['privacywarningconfirm'] = 'Change visibility';
$string['privacywarningtitle'] = 'Change playlist visibility';
$string['rating'] = 'rating';
$string['ratings'] = 'ratings';
$string['rating_message'] = 'You have a new rating on your playlist {$a}.';
$string['rating_message_view'] = 'View playlist: ';
$string['rating_message_subject'] = 'Someone has rated your playlist';
$string['removeitem'] = 'Remove from playlist';
$string['reshareplaylist'] = 'Reshare playlist "{$a}"';
$string['restricted'] = "Limited people";
$string['savedplaylists'] = "Saved playlists";
$string['selectcontent'] = "Select content to add into playlist";
$string['selectexistingresource'] = "select an existing resource";
$string['shareplaylist'] = 'Share playlist "{$a}"';
$string['tagarea_playlist'] = "Playlist";
$string['userdataitemplaylist'] = "Playlist";
$string['playlist_resource'] = 'Playlist resources';
$string['playlist_unavailable'] = 'This playlist is no longer available';
$string['toaddplaylist'] = 'to add in this playlist';
$string['yourplaylists'] = 'Your playlists';
$string['resourceplaylistposition'] = '{$a->current} of {$a->total} resources';
$string['warning_change_to_public'] = "Are you sure you want to add this content to the playlist and change its visibility settings?

When you add content to a playlist, it takes on the playlist’s visibility settings. This change can’t be undone.";
$string['warning_change_to_restricted'] = "Are you sure you want to add this content to the playlist and change its visibility settings?

When you add content to a playlist, it takes on the playlist’s visibility settings. This change can’t be undone.";

// Error strings
$string['error:access'] = "Cannot access to the playlist";
$string['error:addresource'] = "Cannot add a resource to the playlist";
$string['error:create'] = 'Cannot create playlist';
$string['error:update'] = "Cannot update the playlist";
$string['error:permissiondenied'] = 'Permission denied';
$string['error:removeresource'] = 'Cannot remove the resource from the playlist';
$string['error:sharecapability'] = 'You do not have the required capabilities to share this playlist.';
$string['error:shareprivate'] = 'Playlist is viewable by only you. Change who can view this playlist in order to share it.';
$string['error:sharerestricted'] = 'Playlist is not viewable by everyone and only the owner is allowed to share it.';
$string['error:updateaccess'] = "Cannot update access of the playlist";
$string['error:update_order'] = "Order is out of bounds";

// Field strings
$string['field:name'] = 'Name';
$string['field:resourcenames'] = 'Resource Name(s)';
$string['field:summary'] = 'Summary';
$string['field:timecreated'] = 'Time created';
$string['field:topics'] = 'Topics';

// Capability strings
$string['playlist:create'] = 'Create playlist';
$string['playlist:delete'] = 'Remove playlist';
$string['playlist:share'] = 'Share playlist';
$string['playlist:unshare'] = 'Unlink playlist';
$string['playlist:view_playlist_report'] = 'View playlists engagement report';

// Strings for message component
$string['messageprovider:comment_notification'] = "Comments on your playlists";
$string['messageprovider:rating_playlist_notification'] = "Playlist ratings";
$string['messageprovider:reply_notification'] = "Replies on your playlists";

// Deprecated in 16
$string['privacychangeprivatetorestrictedorpublic'] = 'Some of the resources in this playlist are only visible to you. If you share this playlist, the resources will become visible to all people and workspaces you share it with.';
$string['privacychangerestrictedtopublic'] = 'Some of the resources in this playlist are only visible to limited people and workspaces. If you change the visibility to everyone, all resources in this playlist will become visible.';
$string['deletewarningmsg'] = 'This action is permanent. People with access to this playlist will no longer be able to view it. This action will NOT delete the playlist contents.';