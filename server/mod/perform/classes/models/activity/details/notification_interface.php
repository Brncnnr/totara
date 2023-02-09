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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\activity\details;

use core\orm\query\builder;
use mod_perform\models\activity\activity;

/**
 * @deprecated since Totara 13.2
 */
interface notification_interface {
    /**
     * @deprecated since Totara 13.2
     */
    public function get_activity(): activity;

    /**
     * @deprecated since Totara 13.2
     */
    public function get_class_key(): string;

    /**
     * @deprecated since Totara 13.2
     */
    public function get_active(): bool;

    /**
     * @param builder $builder
     * @param bool $active_only
     * @deprecated since Totara 13.2
     */
    public function recipients_builder(builder $builder, bool $active_only = false): void;

    /**
     * @deprecated since Totara 13.2
     */
    public function get_triggers(): array;

    /**
     * @deprecated since Totara 13.2
     */
    public function get_last_run_at(): int;

    /**
     * @deprecated since Totara 13.2
     */
    public function exists(): bool;

    /**
     * @deprecated since Totara 13.2
     * @param bool $active
     * @return notification_interface
     */
    public function activate(bool $active = true): notification_interface;

    /**
     * @deprecated since Totara 13.2
     * @param array $values
     * @return notification_interface
     */
    public function set_triggers(array $values): notification_interface;

    /**
     * @deprecated since Totara 13.2
     * @param int $time
     * @return notification_interface
     */
    public function set_last_run_at(int $time): notification_interface;

    /**
     * @deprecated since Totara 13.2
     */
    public function delete(): notification_interface;

    /**
     * @deprecated since Totara 13.2
     */
    public function refresh(): notification_interface;
}
