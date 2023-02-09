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
 * @package totara_topic
 */
defined('MOODLE_INTERNAL') || die();

$string['add'] = "Add";
$string['assigntopicshelp'] = "A topic is a particular subject that you discuss or write about"; // todo: this text needed to be updated
$string['bulkadd'] = "Add topics";
$string['bulkaddsuccess'] = "Topics have been successfully added";
$string['component'] = "Component";
$string['confirmdelete'] = "Are you sure you want to delete this topic?";
$string['confirmdeletewithusage'] = "<p>Are you sure you want to delete this topic?</p>

It will be removed from the {\$a} content items currently using it, and their creators will be notified. All content creators will be notified.";
$string['deleteconfirm'] = "Delete topic";
$string['deletetopic'] = "Delete topic";
$string['delete_topic_name'] = "Delete topic {\$a}";
$string['entertopics'] = "Enter topic values (one per line)";
$string['edittopic'] = "Edit topic";
$string['managetopics'] = "Manage topics";
$string['pluginname'] = "Topics";
$string['save'] = "Save";
$string['successdelete'] = "Topic '{\$a}' has successfully been deleted";
$string['successupdate'] = "Topic has successfully updated";
$string['tagcollection_Topics'] = "Topics collection";
$string['timemodified'] = "Modified at";
$string['topic'] = "Topic";
$string['total'] = "Total usage";
$string['topicdeleted'] = "A topic has been deleted";
$string['topicsduplicated'] = "Some topics already exist: {\$a}. Please remove duplicates before adding.";
$string['topicexists'] = "Topic '{\$a}' already exists.";
$string['topicdeletedmessage'] = "The topic '{\$a}' had been deleted from the system. You are receiving this message because your resources had been using this topic. Below is the list of the affected resources:";
$string['unsuccessdelete'] = "Topic '{\$a}' has been unable to be deleted";
$string['usageoftopics'] = "Usage of topics";
$string['update_topic_name'] = "Update topic {\$a}";
$string['value'] = "Value";
$string['yescontinue'] = "Delete";

// Strings for event name
$string['event:topicdeleted'] = "Topic deleted";

// Strings for capability
$string['topic:add'] = "Add topic";
$string['topic:config'] = "Configure topic";
$string['topic:delete'] = "Delete topic";
$string['topic:report'] = "View topics report";
$string['topic:update'] = "Update topic";

// For error message
$string['error:alreadyexist'] = "The topic already exists in the system";
$string['error:nocaptodelete'] = "Cannot delete the topic due to no capability";
$string['error:nocaptoupdate'] = "Cannot update the topic due to no capability";
$string['error:nocaptoadd'] = "Cannot add new topic due to no capability";
$string['error:unabletoaddusage'] = "Cannot add the topic for the instance of component '{\$a}'";
$string['error:unabletodeleteusage'] = "Cannot delete the topic of the instance of component '{\$a}'";

// Strings for message component
$string['messageprovider:deletetopic'] = "Topic's notification";
