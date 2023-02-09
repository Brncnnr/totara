<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core
 */

namespace core\event;

use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * The tenant membership of a user changed, moved tenant, added or removed from tenant
 */
class user_tenant_membership_changed extends base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'user';
    }

    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventusertenantmembershipchanged');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return sprintf(
            "The tenant membership of the user with id '%d' changed from '%s' to '%s'.",
            $this->relateduserid,
            $this->other['oldtenantid'],
            $this->other['newtenantid']
        );
    }

    /**
     * Returns relevant URL.
     *
     * @return moodle_url
     */
    public function get_url() {
        return new moodle_url('/totara/tenant/participant_manage.php', ['id' => $this->other['newtenantid']]);
    }

    /**
     * Custom validations.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->relateduserid)) {
            throw new \coding_exception('The \'relateduserid\' must be set.');
        }

        if (!array_key_exists('oldtenantid', $this->other)) {
            throw new \coding_exception('The \'oldenantid\' must be set.');
        }

        if (!array_key_exists('newtenantid', $this->other)) {
            throw new \coding_exception('The \'newtenantid\' must be set.');
        }
    }

    /**
     * Get the previous tenantid
     *
     * @return int|null
     */
    public function get_old_tenant_id(): ?int {
        return $this->other['oldtenantid'];
    }

    /**
     * Get the new tenantid
     *
     * @return int|null
     */
    public function get_new_tenant_id(): ?int {
        return $this->other['newtenantid'];
    }

}
