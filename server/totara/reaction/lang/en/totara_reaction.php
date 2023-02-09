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
 * @package totara_reaction
 */
defined('MOODLE_INTERNAL') || die();

$string['bracketcount'] = '({$a})';
$string['like'] = 'Like';
$string['likex'] = 'Like "{$a}"';
$string['nolikes'] = 'No Likes';
$string['numberoflikes'] = '{$a} like(s)';
$string['numberofmore'] = 'and {$a} more...';
$string['likesx'] = 'Likes ({$a})';
$string['pluginname'] = "Reaction";
$string['reactioncreated'] = "Reaction created";
$string['reactionremoved'] = "Reaction removed";
$string['user_data_item_reaction'] = 'Like';

// Error string for reaction
$string['error:create_like'] = 'Cannot create like for item';
$string['error:delete'] = 'Cannot delete reaction';
$string['error:remove_like'] = 'Cannot remove like for item';
$string['error:view'] = 'Cannot view the reactions';