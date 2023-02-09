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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\interactor;

/**
 * application_interactor with cached results.
 *
 * This class can be a drop-in replacement for application_interactor when multiple calls to is_pending/can_xxx are bottlenecks.
 * Do not use the instance after the corresponding application is updated.
 *
 * ```
 * - $interactor = application_interactor::from_application_model($application, $user);
 * + $interactor = cached_application_interactor::from_application_model($application, $user);
 *
 * if (!$interactor->can_edit() || !$interactor->can_clone()) throw new Exception('go away');
 * ```
 */
class cached_application_interactor extends application_interactor {
    /** @var boolean[] */
    private $cached_results = [];

    /**
     * @param string $method method name to call
     * @return boolean
     */
    private function invoke(string $method): bool {
        if (isset($this->cached_results[$method])) {
            return $this->cached_results[$method];
        }
        $result = parent::$method();
        $this->cached_results[$method] = $result;
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function is_pending(): bool {
        return $this->invoke('is_pending');
    }

    /**
     * @inheritDoc
     */
    public function can_view(): bool {
        return $this->invoke('can_view');
    }

    /**
     * @inheritDoc
     */
    public function can_clone(): bool {
        return $this->invoke('can_clone');
    }

    /**
     * @inheritDoc
     */
    public function can_edit(): bool {
        return $this->invoke('can_edit');
    }

    /**
     * @inheritDoc
     */
    public function can_delete(): bool {
        return $this->invoke('can_delete');
    }

    /**
     * @inheritDoc
     */
    public function can_withdraw(): bool {
        return $this->invoke('can_withdraw');
    }

    /**
     * @inheritDoc
     */
    public function can_approve(): bool {
        return $this->invoke('can_approve');
    }

    /**
     * @inheritDoc
     */
    public function can_submit(): bool {
        return $this->invoke('can_submit');
    }

    /**
     * @inheritDoc
     */
    public function can_edit_without_invalidating(): bool {
        return $this->invoke('can_edit_without_invalidating');
    }

    /**
     * Flush internal bookkeepings.
     */
    public function refresh(): void {
        $this->cached_results = [];
    }
}
