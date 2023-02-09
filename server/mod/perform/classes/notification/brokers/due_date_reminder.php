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

namespace mod_perform\notification\brokers;

use mod_perform\models\activity\details\subject_instance_notification;
use mod_perform\notification\broker;
use mod_perform\notification\condition;
use mod_perform\notification\triggerable;

/**
 * due_date_reminder handler
 */
class due_date_reminder implements broker, triggerable {
    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    public function get_default_triggers(): array {
        return [DAYSECS];
    }

    public function is_triggerable_now(condition $condition, subject_instance_notification $record): bool {
        if (empty($record->due_date)) {
            return false;
        }
        return $condition->pass($record->due_date);
    }
}
