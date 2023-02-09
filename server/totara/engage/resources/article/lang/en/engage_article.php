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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Vernon Denny <vernon.denny@totaralearning.com>
 * @package engage_article
 */
defined('MOODLE_INTERNAL') || die();

$string['addtoplaylist'] = 'Add to playlist';
$string['articlecreated'] = "Resource created";
$string['articledeleted'] = "Resource deleted";
$string['articletitle'] = "Resource title";
$string['articleupdated'] = "Resource updated";
$string['articlereshared'] = 'Resource re-shared';
$string['articleshared'] = 'Resource shared';
$string['appears_in'] = 'This resource appears in the following playlists:';
$string['back_button'] = '{$a}';
$string['cannot_view_article'] = "You do not have permission to view the resource.";
$string['cachedef_catalog_visibility'] = 'Visibility of resource items in the catalog';
$string['content'] = 'Resource content';
$string['createarticle'] = 'Create a new resource resource';
$string['createarticleshort'] = 'Create resource';
$string['created'] = 'Resource successfully created';
$string['delete_resource_confirm_1'] = 'Are you sure you want to delete this resource?';
$string['delete_resource_confirm_2'] = 'It will be permanently removed and people will no longer be able to view it.';
$string['deletewarningtitle'] = 'Delete resource';
$string['editarticlecontent'] = 'Edit {$a} content';
$string['editarticletitle'] = 'Edit {$a} title';
$string['entertitle'] = "Enter resource title";
$string['entercontent'] = "Enter content";
$string['image_alt'] = 'The image for the resource {$a}';
$string['filterlabeltype'] = "Resource";
$string['likearticle'] = 'Like resource "{$a}"';
$string['loadmore'] = "Load more";
$string['mentionbody:comment'] = '<strong>{$a->fullname}</strong> has commented on the resource {$a->title}.';
$string['mentionbody:content'] = '<strong>{$a->fullname}</strong> has mentioned you in the resource {$a->title}.';
$string['mentiontitle:content'] = '{$a} has mentioned you in a resource';
$string['mentionview:content'] = 'View resource';
$string['numberwithinplaylist'] = 'Appears in {$a} playlist(s)';
$string['pluginadministration'] = 'Resource administration';
$string['pluginname'] = 'Article';
$string['removelikearticle'] = 'Remove like for resource "{$a}"';
$string['reportresource'] = 'Report content';
$string['reshareresource'] = 'Reshare resource "{$a}"';
$string['resource'] = "Resource";
$string['resources'] = "Resources";
$string['shareresource'] = 'Share resource "{$a}"';
$string['user_data_item_article'] = 'Resource';
$string['user_data_item_article_completed'] = 'Resource View';
$string['tagarea_engage_article'] = 'Resource';
$string['tagarea_engage_resource'] = 'Engage resource';
$string['timelessthanfive'] = 'Less than 5 mins';
$string['timefivetoten'] = '5-10 mins';
$string['timemorethanten'] = '10+ mins';
$string['viewedresources'] = 'You\'ve viewed {$a} of';

// Capability strings
$string['article:create'] = 'Create resource';
$string['article:share'] = "Share resource";
$string['article:unshare'] = "Unlink resource";

// Error strings
$string['error:create'] = "Cannot create a resource";
$string['error:delete'] = "Cannot delete the resource";
$string['error:sharecapability'] = 'You do not have the required capabilities to share/unshare this resource.';
$string['error:shareprivate'] = 'This resource is viewable by only you. Change who can view this resource in order to share it.';
$string['error:sharerestricted'] = 'This resource is not viewable by everyone and only the owner is allowed to share it.';
$string['error:reportresource'] = "Cannot report the resource";
$string['error:update'] = "Cannot update the resource";

// Field names
$string['field:name'] = 'Name';
$string['field:timecreated'] = 'Time created';
$string['field:topics'] = 'Topics';
$string['field:content'] = 'Content';

// Filter names
$string['filter:timeview'] = 'Time to read';
$string['filter:timeviewlow'] = '0-5 minutes';
$string['filter:timeviewmed'] = '5-10 minutes';
$string['filter:timeviewhigh'] = 'Over 10 minutes';

$string['filter:topics'] = 'Topics';

// Icon names
$string['icon:likes'] = 'Likes';

// Deprecated in 16
$string['deletewarningmsg'] = 'This action is permanent. People with access to this resource will no longer be able to view it.';
