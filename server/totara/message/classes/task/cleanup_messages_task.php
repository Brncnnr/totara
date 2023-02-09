<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_message
 */
namespace totara_message\task;

use core\task\scheduled_task;

/**
 * Remove orphaned message meta data used for tasks and alerts.
 */
class cleanup_messages_task extends scheduled_task {
    /**
     * @var int
     */
    private const MAX_RECORDS_PER_ROUND = 25000;

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('cleanupmessagestask', 'totara_message');
    }

    /**
     * This function is about tidying up the metadata records that are map with the new notifications table.
     * @return void
     */
    private function clean_up_notifications(): void {
        global $DB;

        $sql = '
            SELECT mm.id
            FROM "ttr_message_metadata" mm
            LEFT JOIN "ttr_notifications" n ON mm.notificationid = n.id
            WHERE n.id IS NULL
            AND mm.messagereadid IS NULL
            AND mm.messageid IS NULL
        ';

        $ids_to_delete = $DB->get_fieldset_sql($sql);
        if (empty($ids_to_delete)) {
            return;
        }

        // We may have really large numbers so split it up into smaller batches.
        $batch_ids = array_chunk($ids_to_delete, self::MAX_RECORDS_PER_ROUND);

        foreach ($batch_ids as $ids) {
            [$insql, $params] = $DB->get_in_or_equal($ids);

            $DB->execute(
                "DELETE FROM \"ttr_message_metadata\" WHERE id {$insql}",
                $params
            );
        }
    }

    /**
     * @return void
     */
    private function clean_up_legacy_messages(): void {
        global $DB;
        $sql = '
            SELECT mm.id
            FROM "ttr_message_metadata" mm
            LEFT JOIN "ttr_message" m ON mm.messageid = m.id
            LEFT JOIN "ttr_message_read" mr ON mm.messagereadid = mr.id
            WHERE m.id IS NULL AND mr.id IS NULL AND mm.notificationid IS NULL
        ';

        $ids_to_delete = $DB->get_fieldset_sql($sql);

        if (empty($ids_to_delete)) {
            return;
        }

        // We may have really large numbers so split it up into smaller batches.
        $batch_ids = array_chunk($ids_to_delete, self::MAX_RECORDS_PER_ROUND);
        foreach ($batch_ids as $ids) {
            [$insql, $params] = $DB->get_in_or_equal($ids);

            $DB->execute(
                "DELETE FROM \"ttr_message_metadata\" WHERE id {$insql}",
                $params
            );
        }
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     * Tidy up orphaned metadata records - shouldn't be any - but odd things could happen with core messages cron.
     */
    public function execute() {
        $this->clean_up_notifications();
        $this->clean_up_legacy_messages();
    }
}

