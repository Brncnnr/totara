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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_msteams
 */

namespace totara_msteams\botfw\builder;

use totara_core\http\client;
use totara_core\http\clients\curl_client;
use totara_msteams\botfw\auth\authoriser;
use totara_msteams\botfw\auth\default_authoriser;
use totara_msteams\botfw\bot;
use totara_msteams\botfw\logger\logger;
use totara_msteams\botfw\logger\stdout_logger;
use totara_msteams\botfw\logger\syslog_logger;
use totara_msteams\botfw\notification\default_notification;
use totara_msteams\botfw\notification\notification;
use totara_msteams\botfw\resolver\resolver;
use totara_msteams\botfw\resolver\v3_resolver;
use totara_msteams\botfw\router\null_router;
use totara_msteams\botfw\router\router;
use totara_msteams\botfw\storage\database_storage;
use totara_msteams\botfw\storage\storage;
use totara_msteams\botfw\validator\default_validator;
use totara_msteams\botfw\validator\validator;

/**
 * A builder class for a bot.
 */
class bot_builder {
    /** @var router|null */
    private $router = null;

    /** @var authoriser|null */
    private $authoriser = null;

    /** @var client|null */
    private $client = null;

    /** @var resolver|null */
    private $resolver = null;

    /** @var notification|null */
    private $notification = null;

    /** @var storage|null */
    private $storage = null;

    /** @var validator|null */
    private $validator = null;

    /** @var logger|null */
    private $logger = null;

    /**
     * @param router $router
     * @return self
     */
    public function router(router $router): self {
        $this->router = $router;
        return $this;
    }

    /**
     * @param authoriser $authoriser
     * @return self
     */
    public function authoriser(authoriser $authoriser): self {
        $this->authoriser = $authoriser;
        return $this;
    }

    /**
     * @param client $client
     * @return self
     */
    public function client(client $client): self {
        $this->client = $client;
        return $this;
    }

    /**
     * @param resolver $resolver
     * @return self
     */
    public function resolver(resolver $resolver): self {
        $this->resolver = $resolver;
        return $this;
    }

    /**
     * @param notification $notification
     * @return self
     */
    public function notification(notification $notification): self {
        $this->notification = $notification;
        return $this;
    }

    /**
     * @param storage $storage
     * @return self
     */
    public function storage(storage $storage): self {
        $this->storage = $storage;
        return $this;
    }

    /**
     * @param validator $validator
     * @return self
     */
    public function validator(validator $validator): self {
        $this->validator = $validator;
        return $this;
    }

    /**
     * @param logger $logger
     * @return self
     */
    public function logger(logger $logger): self {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @return bot
     */
    public function build(): bot {
        return new bot(
            $this->router ?? new null_router(),
            $this->authoriser ?? new default_authoriser(),
            $this->client ?? new curl_client(),
            $this->resolver ?? new v3_resolver(),
            $this->notification ?? new default_notification(),
            $this->storage ?? new database_storage(),
            $this->validator ?? new default_validator(),
            $this->logger ?? (defined('CLI_SCRIPT') || (defined('PHPUNIT_TEST') && PHPUNIT_TEST) ? new stdout_logger() : new syslog_logger())
        );
    }
}
