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
 * @author  Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

namespace contentmarketplace_linkedin\totara_xapi\handler;

use contentmarketplace_linkedin\core_json\structure\xapi_statement as xapi_statement_structure;
use contentmarketplace_linkedin\model\user_progress;
use core\json\validation_adapter;

class handler {

    /**
     * The result extension keyword, that can help us identify the progress percentage.
     * @var string
     */
    public const PROGRESS_RESULT_KEY = "https://w3id.org/xapi/cmi5/result/extensions/progress";

    /**
     * The verb id keyword, that identifies this statement as a progressed statement.
     * @var string
     */
    public const PROGRESSED_VERB_KEY = "http://adlnet.gov/expapi/verbs/progressed";

    /**
     * The verb id keyword, that identifies this statement as a completed statement.
     * @var string
     */
    public const COMPLETED_VERB_KEY = "http://adlnet.gov/expapi/verbs/completed";

    /**
     * @var string The raw statement as a string.
     */
    protected $raw_statement;

    /**
     * @var mixed The JSON decoded statement.
     */
    protected $statement;

    /**
     * @var int Id of the user the statement is about.
     */
    protected $user_id = null;

    /**
     * @param string $statement
     * @param int|null $user_id
     * @throws \JsonException
     */
    public function __construct(string $statement, ?int $user_id) {
        $this->raw_statement = $statement;
        $this->statement = json_decode($statement, true, 512, JSON_THROW_ON_ERROR);
        $this->user_id = $user_id;
    }

    /**
     */
    public function validate_statement(): bool {
        $validator = validation_adapter::create_default();

        $result = $validator->validate_by_structure_class_name(
            $this->raw_statement,
            xapi_statement_structure::class
        );

        // Don't handle this statement because it doesn't contain the specific structure we require.
        if (!$result->is_valid()) {
            return false;
        }

        // Don't handle this statement because it's not related to PROGRESS or COMPLETION.
        if (!isset($this->statement['verb']['id']) ||
            !in_array($this->statement['verb']['id'], [
                self::PROGRESSED_VERB_KEY,
                self::COMPLETED_VERB_KEY
            ])) {
            return false;
        }

        return true;
    }

    /**
     * @return bool Returns true if the statement was processed.
     */
    public function process(): bool {
        if (is_null($this->user_id)) {
            // Cannot process without a user_id.
            return false;
        }
        $urn = $this->statement["object"]["id"];
        $timestamp = strtotime($this->statement['timestamp']);

        if ($this->statement["result"]["completion"]) {
            user_progress::set_completed($this->user_id, $urn, $timestamp);
        } else {
            $progress = $this->statement["result"]["extensions"][self::PROGRESS_RESULT_KEY];
            user_progress::set_progress($this->user_id, $urn, $progress, $timestamp);
        }

        return true;
    }

}
