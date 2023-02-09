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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_notification
 */
namespace totara_notification\interactor;

use totara_core\extended_context;

abstract class notification_interactor {
    /**
     * @var int
     */
    protected int $user_id;

    /**
     * @var extended_context
     */
    protected extended_context $extended_context;

    /**
     * notification_interactor constructor.
     * @param int              $user_id
     * @param extended_context $extended_context
     */
    public function __construct(extended_context $extended_context, int $user_id) {
        $this->user_id = $user_id;
        $this->extended_context = $extended_context;
    }

    abstract public function has_any_capability_for_context(array $extra_capabilities = []): bool;

}