<?php
/**
 * This file is part of Totara Core
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_msteams
 */
defined("MOODLE_INTERNAL") || die();

/**
 * Environment check for totara msteams plugin.
 *
 * @param environment_results $result
 * @return environment_results|null
 */
function totara_msteams_openssl_php_extension_check(environment_results $result): ?environment_results {
    global $CFG;

    if (empty($CFG->msteams_gateway_url)) {
        // Totara Gateway is not enabled, hence we skip the check.
        return null;
    }

    $result->setInfo(get_string("env:openssl_custom_check", "totara_msteams"));

    // If we are here, $CFG->msteams_gateway_url should be set.
    if (empty($CFG->msteams_gateway_private_key)) {
        // With $CFG->msteams_gateway_url is set, we need to be sure that the msteams private key
        // needed to be set too.
        $result->setErrorCode(INCORRECT_FEEDBACK_FOR_OPTIONAL);
        $result->setRestrictStr(["env:missing_gateway_private_key", "totara_msteams"]);

        return $result;
    }

    if (!extension_loaded("openssl")) {
        // Check that php extension is enabled, as it is required.
        $result->setErrorCode(NO_PHP_EXTENSIONS_NAME_FOUND);
        $result->setFeedbackStr(["env:no_openssl_extension", "totara_msteams"]);

        return $result;
    }

    // Everything is fine.
    $result->setStatus(true);
    $result->setErrorCode(NO_ERROR);
    return $result;
}