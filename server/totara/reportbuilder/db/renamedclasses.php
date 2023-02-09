<?php
/*
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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

$renamedclasses = [
    'rb_audience_content' => \totara_reportbuilder\rb\content\audience::class,
    'rb_base_content' => \totara_reportbuilder\rb\content\base::class,
    'rb_completed_org_content' => \totara_reportbuilder\rb\content\completed_org::class,
    'rb_current_org_content' => \totara_reportbuilder\rb\content\current_org::class,
    'rb_current_pos_content' => \totara_reportbuilder\rb\content\current_pos::class,
    'rb_date_content' => \totara_reportbuilder\rb\content\date::class,
    'rb_report_access_content' => \totara_reportbuilder\rb\content\report_access::class,
    'rb_session_roles_content' => \totara_reportbuilder\rb\content\session_roles::class,
    'rb_tag_content' => \totara_reportbuilder\rb\content\tag::class,
    'rb_trainer_content' => \totara_reportbuilder\rb\content\trainer::class,
    'rb_user_content' => \totara_reportbuilder\rb\content\user::class
];
