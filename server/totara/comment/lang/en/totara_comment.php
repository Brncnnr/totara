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
 * @package totara_comment
 */
defined('MOODLE_INTERNAL') || die();

$string['bracketnumber'] = '({$a})';
$string['cachedef_author_access'] = "Comment's author access cache";
$string['comments'] = 'Comments ({$a})';
$string['comment_author'] = 'Comment author';
$string['comment_created'] = 'Comment created';
$string['comment_placeholder_content_text'] = 'Text';
$string['commenter_placeholder_group'] = 'Commenter\'s {$a}';
$string['comment_soft_deleted'] = 'Comment deleted';
$string['delete_comment_confirm_1'] = 'Are you sure you want to delete this comment?';
$string['delete_comment_confirm_2'] = 'All replies to this comment will remain.';
$string['delete_comment_confirm_title'] = 'Delete comment';
$string['deletedcomment'] = "This comment has been deleted.";
$string['done'] = 'Done';
$string['edited'] = 'Edited';
$string['entercomment'] = 'Enter a comment';
$string['enterreply'] = 'Enter a reply';
$string['item_owner_placeholder_group'] = 'Item owner\'s {$a}';
$string['likecomment'] = "Like the comment";
$string['loadmorecomments'] = "View earlier comments";
$string['owner'] = 'Owner';
$string['post'] = "Post";
$string['pluginname'] = "Totara comment";
$string['removedcomment'] = "This comment has been removed.";
$string['removelikeforcomment'] = "Remove like for the comment";
$string['reportcontent'] = "Report";
$string['reply'] = "Reply";
$string['triggermenu'] = "Menu trigger";
$string['user_data_item_comment'] = 'Comment';
$string['user_data_item_comment_help'] = 'When purging, all the replies, reactions associated with the comment will be deleted';
$string['user_data_item_reply'] = 'Reply';
$string['user_data_item_reply_help'] = 'When purging, all the reactions associated with the reply will be deleted';
$string['viewreplies'] = "View replies";

// Using for error exceptions.
$string['error:accessdenied'] = "Comment access denied";
$string['error:create'] = "Cannot create a comment";
$string['error:update'] = "Cannot update a comment";
$string['error:reportcomment'] = "Cannot report the comment";
$string['error:reportreply'] = "Cannot report the reply";
$string['error:softdelete'] = "Cannot delete a comment";

// Deprecated in 16

$string['deletecommentconfirm'] = 'Your comment will be deleted. Any replies to your comment will remain.';