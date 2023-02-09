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

namespace totara_msteams\botfw\validator;

use totara_msteams\botfw\activity;
use totara_msteams\botfw\auth\bearer;
use totara_msteams\botfw\auth\token\msteams_token;
use totara_msteams\botfw\context;

/**
 * The default implementation of a validator.
 */
class default_validator implements validator {
    /**
     * @inheritDoc
     */
    public function validate_activity(context $context, activity $activity): bool {
        $result = true;
        if ($activity->recipient->id !== $context->get_bot_id()) {
            $context->get_logger()->debug('The bot IDs are not identical. Perhaps a wrong bot?');
            $result = false;
        }
        if ($activity->serviceUrl !== $context->get_service_url()) {
            $context->get_logger()->debug('The service URLs are not identical. Perhaps a wrong bot?');
            $result = false;
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function validate_header(context $context, array $headers): bool {
        $jwt = bearer::validate_header($headers);
        if ($jwt) {
            $token = new msteams_token($jwt);
            if ($token->verify($context)) {
                return true;
            }
        }
        $context->get_logger()->warn('An access to an MS Teams bot has been ignored due to invalid request headers.');
        return false;
    }
}
