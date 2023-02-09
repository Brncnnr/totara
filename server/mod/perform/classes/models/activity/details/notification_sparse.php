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

use coding_exception;
use core\orm\query\builder;
use mod_perform\notification\factory;
use mod_perform\models\activity\activity;

/**
 * @deprecated since Totara 13.2
 */
class notification_sparse implements notification_interface {
    /** @deprecated since Totara 13.2 */
    protected $class_key;

    /** @deprecated since Totara 13.2 */
    protected $activity;

    /** @deprecated since Totara 13.2 */
    protected $broker;

    /**
     * @deprecated since Totara 13.2
     * @param activity $activity
     * @param string $class_key
     */
    public function __construct(activity $activity, string $class_key) {
        debugging(
            '\mod_perform\models\activity\details\notification_sparse is deprecated and should no longer be used.'
            . ' There is no alternative.',
            DEBUG_DEVELOPER
        );
        $this->activity = $activity;
        $this->class_key = $class_key;
        $this->broker = factory::create_broker($class_key);
    }

    /**
     * @deprecated since Totara 13.2
     */
    public function get_activity(): activity {
        debugging(
            '\mod_perform\models\activity\details\notification_sparse is deprecated and should no longer be used.'
            . ' There is no alternative.',
            DEBUG_DEVELOPER
        );
        return $this->activity;
    }

    /**
     * @deprecated since Totara 13.2
     */
    public function get_id(): ?int {
        debugging(
            '\mod_perform\models\activity\details\notification_sparse is deprecated and should no longer be used.'
            . ' There is no alternative.',
            DEBUG_DEVELOPER
        );
        return null;
    }

    /**
     * @deprecated since Totara 13.2
     */
    public function get_class_key(): string {
        debugging(
            '\mod_perform\models\activity\details\notification_sparse is deprecated and should no longer be used.'
            . ' There is no alternative.',
            DEBUG_DEVELOPER
        );
        return $this->class_key;
    }

    /**
     * @deprecated since Totara 13.2
     */
    public function get_active(): bool {
        debugging(
            '\mod_perform\models\activity\details\notification_sparse is deprecated and should no longer be used.'
            . ' There is no alternative.',
            DEBUG_DEVELOPER
        );
        return false;
    }

    /**
     * @deprecated since Totara 13.2
     * @param builder $builder
     * @param bool $active_only
     */
    public function recipients_builder(builder $builder, bool $active_only = false): void {
        debugging(
            '\mod_perform\models\activity\details\notification_sparse is deprecated and should no longer be used.'
            . ' There is no alternative.',
            DEBUG_DEVELOPER
        );
        if ($active_only) {
            $builder->where_raw('1 != 1');
        }
        $builder->add_select_raw('0 AS active');
    }

    /**
     * @deprecated since Totara 13.2
     */
    public function get_triggers(): array {
        debugging(
            '\mod_perform\models\activity\details\notification_sparse is deprecated and should no longer be used.'
            . ' There is no alternative.',
            DEBUG_DEVELOPER
        );
        return $this->broker->get_default_triggers();
    }

    /**
     * @deprecated since Totara 13.2
     */
    public function get_last_run_at(): int {
        debugging(
            '\mod_perform\models\activity\details\notification_sparse is deprecated and should no longer be used.'
            . ' There is no alternative.',
            DEBUG_DEVELOPER
        );
        throw new coding_exception('not available');
    }

    /**
     * @deprecated since Totara 13.2
     */
    public function exists(): bool {
        debugging(
            '\mod_perform\models\activity\details\notification_sparse is deprecated and should no longer be used.'
            . ' There is no alternative.',
            DEBUG_DEVELOPER
        );
        return false;
    }

    /**
     * @deprecated since Totara 13.2
     * @param bool $active
     * @return notification_interface
     */
    public function activate(bool $active = true): notification_interface {
        debugging(
            '\mod_perform\models\activity\details\notification_sparse is deprecated and should no longer be used.'
            . ' There is no alternative.',
            DEBUG_DEVELOPER
        );
        $inst = notification_real::create($this->activity, $this->class_key, $active);
        return $inst;
    }

    /**
     * @deprecated since Totara 13.2
     * @param array $values
     * @return notification_interface
     */
    public function set_triggers(array $values): notification_interface {
        debugging(
            '\mod_perform\models\activity\details\notification_sparse is deprecated and should no longer be used.'
            . ' There is no alternative.',
            DEBUG_DEVELOPER
        );
        throw new coding_exception('not available');
    }

    /**
     * @deprecated since Totara 13.2
     * @param int $time
     * @return notification_interface
     */
    public function set_last_run_at(int $time): notification_interface {
        debugging(
            '\mod_perform\models\activity\details\notification_sparse is deprecated and should no longer be used.'
            . ' There is no alternative.',
            DEBUG_DEVELOPER
        );
        throw new coding_exception('not available');
    }

    /**
     * @deprecated since Totara 13.2
     */
    public function delete(): notification_interface {
        debugging(
            '\mod_perform\models\activity\details\notification_sparse is deprecated and should no longer be used.'
            . ' There is no alternative.',
            DEBUG_DEVELOPER
        );
        return $this;
    }

    /**
     * @deprecated since Totara 13.2
     */
    public function refresh(): notification_interface {
        debugging(
            '\mod_perform\models\activity\details\notification_sparse is deprecated and should no longer be used.'
            . ' There is no alternative.',
            DEBUG_DEVELOPER
        );
        $this->activity->refresh();
        return $this;
    }
}
