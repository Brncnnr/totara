<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Aaron Wells <aaronw@catalyst.net.nz>
 * @author Russell England <russell.england@totaralms.com>
 * @author Simon Player <simon.player@totaralms.com>
 * @package totara
 * @subpackage plan
 */

/**
 * Edit evidence
 */

require_once(__DIR__ . '/../../../../config.php');

// This file has been deprecated and is no longer used, please use /totara/evidence/edit.php instead.
debugging('totara/plan/record/evidence/edit.php has been deprecated and is no longer used, please use totara/evidence/edit.php instead.', DEBUG_DEVELOPER);

redirect(new moodle_url('/totara/evidence/index.php'));
