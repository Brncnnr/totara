<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 */

namespace mod_approval\event;

use context_system;
use core\event\base;
use mod_approval\entity\form\form_version;
use mod_approval\model\form\form_version as form_version_model;

/**
 * Event triggered when a form_version is updated.
 */
class form_version_updated extends base {

    /**
     * @inheritDoc
     */
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = form_version::TABLE;
    }

    /**
     * Alias method to trigger the event.
     *
     * @param form_version_model $workflow
     * @return void
     */
    public static function execute(form_version_model $form_version): void {
        self::create([
            'objectid' => $form_version->id,
            'context' => context_system::instance(),
            'other' => [
                'form_id' => $form_version->form_id,
                'plugin_name' => $form_version->form->plugin_name,
                'version' => $form_version->version,
            ],
        ])->trigger();
    }

    /**
     * @inheritDoc
     */
    public static function get_name(): string {
        return get_string('event_form_version_updated', 'mod_approval');
    }

    /**
     * @inheritDoc
     */
    public function get_description(): string {
        return get_string(
            'event_form_version_updated_description',
            'mod_approval',
            [
                'userid' => $this->userid,
                'form_version_id' => $this->objectid,
                'form_id' => $this->other['form_id'],
                'plugin_name' => $this->other['plugin_name'],
                'version' => $this->other['version'],
            ]
        );
    }
}