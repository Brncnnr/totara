<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

use contentmarketplace_linkedin\workflow\core_course\coursecreate\contentmarketplace;
use contentmarketplace_linkedin\workflow\totara_contentmarketplace\exploremarketplace\linkedin;
use contentmarketplace_linkedin\workflow\mod_contentmarketplace\create_marketplace_activity\linkedin as linkedin_activity;

/**
 * Totara workflow install hook.
 */
function xmldb_contentmarketplace_linkedin_install() {
    global $CFG;
    require_once("{$CFG->dirroot}/totara/notification/db/upgradelib.php");

    // Enable Linked In Learning course create workflow on install.
    $workflow = contentmarketplace::instance();
    $workflow->enable();

    // Enable Linked In Learning Explore marketplace workflow on install.
    $workflow = linkedin::instance();
    $workflow->enable();

    // Enable LinkedIn learning Activity Creation workflow on install.
    $workflow = linkedin_activity::instance();
    $workflow->enable();

    // Install the built in notification
    totara_notification_sync_built_in_notification('contentmarketplace_linkedin');
}