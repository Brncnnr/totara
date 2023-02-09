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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_contentmarketplace
 */

namespace totara_contentmarketplace\workflow;

use moodle_url;
use totara_contentmarketplace\local;
use totara_contentmarketplace\plugininfo\contentmarketplace;
use totara_workflow\workflow\base;
use totara_workflow\workflow_manager\base as workflow_manager;

abstract class marketplace_workflow extends base {

    /**
     * @var contentmarketplace
     */
    protected $plugin;

    final public function __construct(workflow_manager $workflowmanager) {
        parent::__construct($workflowmanager);
        $class = explode('\\', static::class);
        $this->plugin = contentmarketplace::plugin(reset($class));
    }

    /**
     * @inheritDoc
     */
    protected function get_workflow_url(): moodle_url {
        $url = new moodle_url('/totara/contentmarketplace/explorer.php', [
            'marketplace' => $this->plugin->contentmarketplace()->name,
        ]);
        $url->params($this->manager->get_params());
        return $url;
    }

    /**
     * @inheritDoc
     */
    public function get_image(): moodle_url {
        return $this->plugin->contentmarketplace()->get_padded_logo_url();
    }

    /**
     * @return bool
     */
    public function can_access(): bool {
        return local::is_enabled() && $this->plugin->is_enabled();
    }

}
