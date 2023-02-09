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
 * @author  Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_notification
 */

namespace totara_notification\repository;

use core\orm\entity\repository;
use totara_core\extended_context;
use totara_notification\entity\notifiable_event_preference;

class notifiable_event_preference_repository extends repository {
    /**
     * @param string $resolver_class_name
     * @param extended_context $extended_context
     * @return notifiable_event_preference|null
     */
    public function for_context(string $resolver_class_name,
        extended_context $extended_context
    ): ?notifiable_event_preference {
        $this->builder->where('context_id', $extended_context->get_context_id());
        $this->builder->where('component', $extended_context->get_component());
        $this->builder->where('area', $extended_context->get_area());
        $this->builder->where('item_id', $extended_context->get_item_id());
        $this->builder->where('resolver_class_name', ltrim($resolver_class_name, '\\'));

        /** @var notifiable_event_preference|null $entity */
        $entity = $this->builder->one();
        return $entity;
    }

    /**
     * @param extended_context $extended_context
     * @return $this
     */
    public function filter_by_extended_context(extended_context $extended_context): notifiable_event_preference_repository {
        $this->where('context_id', $extended_context->get_context_id());
        $this->where('component', $extended_context->get_component());
        $this->where('area', $extended_context->get_area());
        $this->where('item_id', $extended_context->get_item_id());

        return $this;
    }

    /**
     * @param string $resolver_class_name
     * @return $this
     */
    public function filter_by_resolver_class_name(string $resolver_class_name): notifiable_event_preference_repository {
        $this->where('resolver_class_name', $resolver_class_name);

        return $this;
    }

    /**
     * @param bool $enabled
     * @return $this
     */
    public function filter_by_enabled(bool $enabled): notifiable_event_preference_repository {
        $this->where('enabled', $enabled);

        return $this;
    }

    /**
     * @return $this
     */
    public function filter_by_has_overridden_default_delivery_channels(): notifiable_event_preference_repository {
        $this->where_not_null('default_delivery_channels');

        return $this;
    }
}