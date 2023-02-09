<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package totara_cloudfiledir
 */

namespace totara_cloudfiledir\local;

final class store {
    /** @var string name of the store */
    private $idnumber;
    /** @var string store purpose description in markdown format */
    private $description = '';
    /** @var bool is store active? */
    private $active = false;
    /** @var bool true means add fields to store */
    private $add = false;
    /** @var bool true means delete files from store */
    private $delete = false;
    /** @var bool true means use for content restore */
    private $restore = false;
    /** @var int null means upload all files immediately. number is maximum size for instant upload */
    private $maxinstantuploadsize = -1;
    /** @var string name of provider */
    private $providername;
    /** @var provider\base|null */
    private $provider;
    /** @var string bucket name if used */
    private $bucket = '';
    /** @var string prefix (for testing only) */
    private $prefix = '';
    /** @var string|null X-Accel-Redirect prefix for NGINX */
    private $x_accel_redirect = null;
    /** @var string temporary directory for downloads */
    private $tempdir;
    /** @var int temporary file name counter */
    private $tempcounter = 0;
    /** @var int number of seconds signed download URLs are supposed to be valid */
    public const SIGNED_URL_TTL = 3600;

    protected function __construct(array $config) {
        $this->idnumber = (string)$config['idnumber'];
        if (preg_match('#[^a-zA-Z0-9_]#', $this->idnumber)) {
            debugging('Invalid character detected in store idnumber: ' . $this->idnumber, DEBUG_ALL);
        }

        if (isset($config['description'])) {
            $this->description = (string)$config['description'];
        }
        if (isset($config['active'])) {
            $this->active = (bool)$config['active'];
        }
        if (isset($config['add'])) {
            $this->add = (bool)$config['add'];
        }
        if (isset($config['delete'])) {
            $this->delete = (bool)$config['delete'];
        }
        if (isset($config['restore'])) {
            $this->restore = (bool)$config['restore'];
        }
        if (isset($config['maxinstantuploadsize'])) {
            $this->maxinstantuploadsize = ($config['maxinstantuploadsize'] < 0) ? -1 : get_real_size($config['maxinstantuploadsize']);
        }
        if (isset($config['bucket'])) {
            $this->bucket = (string)$config['bucket'];
        }
        if (!empty($config['x_accel_redirect'])) {
            $this->x_accel_redirect = (string)$config['x_accel_redirect'];
        }
        $this->providername = (string)$config['provider'];
        $options = isset($config['options']) ? (array)$config['options'] : [];
        if (isset($config['prefix'])) {
            $this->prefix = $config['prefix'];
        }

        $providerclass = "totara_cloudfiledir\\local\\provider\\{$this->providername}";
        if ($this->active and class_exists($providerclass)) {
            $this->provider = new $providerclass($options, $this->idnumber, $this->bucket, $this->prefix);
        } else {
            $this->provider = null;
            $this->active = false;
        }
    }

    /**
     * Returns short store name/identifier.
     *
     * @return string
     */
    final public function get_idnumber(): string {
        return $this->idnumber;
    }

    /**
     * Returns bucket prefix (intended for testing only).
     *
     * @return string
     */
    final public function get_prefix(): string {
        return $this->prefix;
    }

    /**
     * Returns provider name.
     *
     * @return string
     */
    final public function get_provider(): string {
        return $this->providername;
    }

    /**
     * Returns bucket name.
     *
     * @return string
     */
    final public function get_bucket(): string {
        return $this->bucket;
    }

    /**
     * Get maximum file size for instant uploads where
     * -1 means upload all via hook asap,
     * 0 means upload all via cron,
     * any file with size higher than the number gets uploaded later via cron.
     *
     * @return int
     */
    final public function get_maxinstantuploadsize(): int {
        return $this->maxinstantuploadsize;
    }

    /**
     * Returns short description in markdown format.
     *
     * @return string
     */
    public function get_description(): string {
        return $this->description;
    }

    /**
     * Is store active?
     *
     * @return bool
     */
    public function is_active(): bool {
        return $this->active;
    }

    /**
     * Is adding of contents to store allowed?
     *
     * @return bool
     */
    public function add_enabled(): bool {
        return $this->add;
    }

    /**
     * Is deleting of contents from store allowed?
     *
     * @return bool
     */
    public function delete_enabled(): bool {
        return $this->delete;
    }

    /**
     * Is store used for content restore?
     *
     * @return bool
     */
    public function restore_enabled(): bool {
        return $this->restore;
    }

    /**
     * Is content with this file size uploaded instantly or later in cron?
     *
     * @param int $filesize
     * @return bool
     */
    public function is_instant_upload(int $filesize): bool {
        if ($this->maxinstantuploadsize < 0) {
            return true;
        }
        return ($filesize <= $this->maxinstantuploadsize);
    }

    /**
     * Is the content available in external store?
     *
     * @param string $contenthash
     * @param bool $ignoresynctable
     * @return bool
     */
    public function is_content_available(string $contenthash, bool $ignoresynctable = false): bool {
        global $DB;

        if (!$this->active) {
            return false;
        }

        if (!$this->provider->is_ready()) {
            return false;
        }

        $record = $DB->get_record('totara_cloudfiledir_sync', ['idnumber' => $this->idnumber, 'contenthash' => $contenthash]);

        if (!$ignoresynctable) {
            if ($record && $record->timeuploaded) {
                return true;
            } else {
                return false;
            }
        }

        $exists = $this->provider->is_content_available($contenthash);

        // Update sync table only if file exists,
        // we do not want to invalidate the cache if cloud is temporarily unavailable.

        if ($exists) {
            // We cannot find out easily when it was uploaded,
            // so let's pretend it was just now because it is not important.
            if (!$record) {
                $DB->insert_record('totara_cloudfiledir_sync',
                    ['idnumber' => $this->idnumber, 'contenthash' => $contenthash, 'timeuploaded' => time()]);
            } else if (!$record->timeuploaded) {
                $DB->set_field('totara_cloudfiledir_sync', 'timeuploaded', time(), ['id' => $record->id]);
            }
        }

        return $exists;
    }

    /**
     * Add content file to store.
     *
     * @param string $contenthash
     * @param \Closure $streaminfo writes content to specified filepath
     * @return bool success
     */
    public function write_content(string $contenthash, \Closure $streaminfo): bool {
        global $DB;

        if (!$this->active || !$this->add) {
            return false;
        }

        if (!$this->provider->is_ready()) {
            return false;
        }

        if (!$this->is_content_available($contenthash, true)) {
            list($handle, $filesize) = $streaminfo();
            $uploaded = $this->provider->upload_content_stream($contenthash, $handle, $filesize);
            if (!$uploaded) {
                return false;
            }
        }

        $record = $DB->get_record('totara_cloudfiledir_sync', ['idnumber' => $this->idnumber, 'contenthash' => $contenthash]);
        if (!$record) {
            $DB->insert_record('totara_cloudfiledir_sync',
                ['idnumber' => $this->idnumber, 'contenthash' => $contenthash, 'timeuploaded' => time()]);
        } else if (!$record->timeuploaded) {
            $DB->set_field('totara_cloudfiledir_sync', 'timeuploaded', time(), ['id' => $record->id]);
        }

        return true;
    }

    /**
     * Returns temp file.
     *
     * @return string
     */
    private function get_temp_file(): string {
        if ($this->tempdir === null) {
            $this->tempdir = make_request_directory();
        }
        $this->tempcounter++;
        return $this->tempdir . '/' . $this->tempcounter . '.tmp';
    }

    /**
     * Read content from file store.
     *
     * @param string $contenthash
     * @param \Closure $filereader reads content from file parameter
     * @return bool success
     */
    public function read_content(string $contenthash, \Closure $filereader): bool {
        global $DB;

        if (!$this->active) {
            return false;
        }

        if (!$this->provider->is_ready()) {
            return false;
        }

        if (!$this->is_content_available($contenthash, true)) {
            return false;
        }

        $tempfile = $this->get_temp_file();
        if (!$this->provider->download_content($contenthash, $tempfile)) {
            return false;
        }
        if (!file_exists($tempfile)) {
            return false;
        }

        try {
            $result = $filereader($tempfile);
            @unlink($tempfile);
            if ($result) {
                $record = $DB->get_record('totara_cloudfiledir_sync', ['idnumber' => $this->idnumber, 'contenthash' => $contenthash]);
                if (!$record) {
                    $DB->insert_record('totara_cloudfiledir_sync',
                        ['idnumber' => $this->idnumber, 'contenthash' => $contenthash, 'timedownloaded' => time(), 'timeuploaded' => time()]);
                } else {
                    if (!$record->timeuploaded) {
                        $DB->set_field('totara_cloudfiledir_sync', 'timeuploaded', time(), ['id' => $record->id]);
                    }
                    $DB->set_field('totara_cloudfiledir_sync', 'timedownloaded', time(), ['id' => $record->id]);
                }
            }
            return $result;
        } catch (\Throwable $e) {
            error_log('Exception while reading cloud store file content: ' . $contenthash . "\n" . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete content from file store.
     *
     * @param string $contenthash
     * @return bool
     */
    public function delete_content(string $contenthash): bool {
        global $DB;

        if (!$this->active || !$this->delete) {
            return false;
        }

        if (!$this->provider->is_ready()) {
            return false;
        }

        if ($this->provider->delete_content($contenthash)) {
            $DB->delete_records('totara_cloudfiledir_sync', ['idnumber' => $this->idnumber, 'contenthash' => $contenthash]);
        }

        return true;
    }

    /**
     * Fetch list of all contents stored in cloud bucket
     * and store it in 'totara_cloudfiledir_sync' table.
     *
     * @return bool success
     */
    public function fetch_list(): bool {
        global $DB;

        if (!$this->active) {
            return false;
        }

        if (!$this->provider->is_ready()) {
            return false;
        }

        $dbman = $DB->get_manager();

        $xmldb_table = new \xmldb_table('totara_cloudfiledir_temp');
        $xmldb_table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $xmldb_table->add_field('contenthash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
        $xmldb_table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $xmldb_table->add_index('contenthash', XMLDB_KEY_UNIQUE, ['contenthash']);

        if ($dbman->table_exists($xmldb_table)) {
            $dbman->drop_table($xmldb_table);
        }
        $dbman->create_temp_table($xmldb_table);

        $iterator = $this->provider->list_contents();
        if ($iterator === null) {
            // Error fetching list of clooud files, do NOT change anything!
            $dbman->drop_table($xmldb_table);
            return false;
        }

        // Fetch list of all cloud content files to a temp table to make the next operations much much faster.
        $objectiterator = new class ($iterator) extends \IteratorIterator {
            #[\ReturnTypeWillChange]
            public function current() {
                return (object)['contenthash' => parent::current()];
            }
        };
        $DB->insert_records('totara_cloudfiledir_temp', $objectiterator);
        $DB->update_temp_table_stats();

        // Add all missing entries to the sync table.
        $sql = 'INSERT INTO "ttr_totara_cloudfiledir_sync" (idnumber,contenthash,timeuploaded)

                SELECT :idnumber1, t.contenthash, :now
                  FROM "ttr_totara_cloudfiledir_temp" t
             LEFT JOIN "ttr_totara_cloudfiledir_sync" s ON s.contenthash = t.contenthash AND s.idnumber = :idnumber2
                 WHERE s.id IS NULL';
        $DB->execute($sql, ['idnumber1' => $this->idnumber, 'idnumber2' => $this->idnumber, 'now' => time()]);

        // Delete sync records that are neither used in files nor in the cloud.
        $sql = 'DELETE FROM "ttr_totara_cloudfiledir_sync"
                 WHERE idnumber = :idnumber
                       AND NOT EXISTS (SELECT 1 FROM "ttr_totara_cloudfiledir_temp" WHERE "ttr_totara_cloudfiledir_temp".contenthash = "ttr_totara_cloudfiledir_sync".contenthash)
                       AND NOT EXISTS (SELECT 1 FROM "ttr_files" WHERE "ttr_files".contenthash = "ttr_totara_cloudfiledir_sync".contenthash)';
        $DB->execute($sql, ['idnumber' => $this->get_idnumber()]);

        // Clear the timeuploaded flags for content that is not in the cloud.
        $sql = 'UPDATE "ttr_totara_cloudfiledir_sync"
                   SET timeuploaded = NULL
                 WHERE idnumber = :idnumber AND timeuploaded IS NOT NULL 
                       AND NOT EXISTS (SELECT 1 FROM "ttr_totara_cloudfiledir_temp" WHERE "ttr_totara_cloudfiledir_temp".contenthash = "ttr_totara_cloudfiledir_sync".contenthash)';
        $DB->execute($sql, ['idnumber' => $this->get_idnumber()]);

        $dbman->drop_table($xmldb_table);
        return true;
    }

    /**
     * Reset all localproblem flags for given store,
     * this will force a retry in the next push_changes() execution.
     */
    public function reset_localproblem_flag(): void {
        global $DB;
        $DB->set_field('totara_cloudfiledir_sync', 'localproblem', 0, ['idnumber' => $this->get_idnumber()]);
    }

    /**
     * Push local contents to cloud (if 'add' setting true)
     * and delete unused files from cloud (if 'deleted' setting true).
     *
     * NOTE: cloud files that are not in 'totara_cloudfiledir_sync' table
     *       are ignored.
     *
     * @param \Closure|null $logger called for each uploaded or deleted content file
     * @return bool success
     */
    public function push_changes(\Closure $logger = null): bool {
        global $DB;

        if (!$this->active) {
            return false;
        }

        if (!$this->provider->is_ready()) {
            return false;
        }

        $fs = get_file_storage();

        if ($this->add_enabled()) {
            // Add file contents not in cloud store.
            $retries = 10;
            $sql = 'SELECT DISTINCT f.contenthash
                      FROM "ttr_files" f
                 LEFT JOIN "ttr_totara_cloudfiledir_sync" s ON s.contenthash = f.contenthash AND s.idnumber = :idnumber
                     WHERE s.id IS NULL OR (s.timeuploaded IS NULL AND s.localproblem = 0)';
            $params = ['idnumber' => $this->get_idnumber()];
            while ($contenthashes = $DB->get_records_sql($sql, $params, 0, 1000)) {
                foreach ($contenthashes as $contenthash => $unused) {
                    if (!$fs->content_exists($contenthash) || !$fs->validate_content($contenthash, false)) {
                        $this->mark_as_missing($contenthash);
                        continue;
                    }
                    $length = $fs->get_content_length($contenthash);
                    $handle = $fs->get_content_stream($contenthash);
                    if ($length === false || $handle === false) {
                        $this->mark_as_missing($contenthash);
                        continue;
                    }
                    $uploaded = $this->provider->upload_content_stream($contenthash, $handle, $length);
                    if (!$uploaded && !$this->provider->is_content_available($contenthash)) {
                        $retries--;
                        if ($retries <= 0) {
                            return false;
                        }
                        // Continue with other files and retry this contenthash again in the next get_records() while loop.
                        continue;
                    }
                    $record = $DB->get_record('totara_cloudfiledir_sync', ['idnumber' => $this->idnumber, 'contenthash' => $contenthash]);
                    if (!$record) {
                        $DB->insert_record('totara_cloudfiledir_sync',
                            ['idnumber' => $this->idnumber, 'contenthash' => $contenthash, 'timeuploaded' => time()]);
                    } else if (!$record->timeuploaded) {
                        $DB->set_field('totara_cloudfiledir_sync', 'timeuploaded', time(), ['id' => $record->id]);
                    }
                    if ($logger) {
                        $logger($contenthash);
                    }
                }
            }
        }

        if ($this->delete_enabled()) {
            // Add file contents not in cloud store.
            $retries = 10;
            $sql = 'SELECT s.contenthash
                      FROM "ttr_totara_cloudfiledir_sync" s
                 LEFT JOIN "ttr_files" f ON f.contenthash = s.contenthash
                     WHERE s.idnumber = :idnumber AND f.id IS NULL AND s.timeuploaded IS NOT NULL';
            $params = ['idnumber' => $this->get_idnumber()];
            while ($contenthashes = $DB->get_records_sql($sql, $params, 0, 100)) {
                foreach ($contenthashes as $contenthash => $unused) {
                    if (!$this->provider->delete_content($contenthash)) {
                        $retries--;
                        if ($retries <= 0) {
                            return false;
                        }
                        // Continue with other files and retry this contenthash again in the next get_records() while loop.
                        continue;
                    }
                    $DB->delete_records('totara_cloudfiledir_sync', ['idnumber' => $this->idnumber, 'contenthash' => $contenthash]);
                    if ($logger) {
                        $logger($contenthash);
                    }
                }
            }
        }

        return true;
    }

    /**
     * Mark as missing local content hash.
     *
     * @param string $contenthash
     */
    protected function mark_as_missing(string $contenthash): void {
        global $DB;
        $record = $DB->get_record('totara_cloudfiledir_sync', ['idnumber' => $this->idnumber, 'contenthash' => $contenthash]);
        if (!$record) {
            $DB->insert_record('totara_cloudfiledir_sync',
                ['idnumber' => $this->idnumber, 'contenthash' => $contenthash, 'localproblem' => 1]);
        } else {
            $DB->set_field('totara_cloudfiledir_sync', 'localproblem', 1, ['id' => $record->id]);
        }
    }

    /**
     * Cloud based xsendfile support.
     *
     * @return array|null array of response headers to send, null means cloud xsendfile not possible.
     */
    public function xsendfile(string $contenthash): ?array {
        // NOTE: for now there is support for NGINX only, but we can add more options later.
        if (!$this->x_accel_redirect) {
            return null;
        }

        if (!$this->is_content_available($contenthash, false)) {
            return null;
        }

        $downloadlink = null;
        $cachekey = $this->idnumber . '_' . $contenthash;
        $cache = \cache::make('totara_cloudfiledir', 'downloadurls');

        $cached = $cache->get($cachekey);
        if ($cached && time() < $cached['time'] + self::SIGNED_URL_TTL - 20) {
            $downloadlink = $cached['url'];
        } else {
            $downloadlink = $this->provider->create_download_link($contenthash, self::SIGNED_URL_TTL);
            if ($downloadlink) {
                $cached = ['url' => $downloadlink, 'time' => time()];
                $cache->set($cachekey, $cached);
            } else {
                return null;
            }
        }

        if ($this->x_accel_redirect) {
            return ["X-Accel-Redirect: /{$this->x_accel_redirect}/{$downloadlink}"];
        }

        return null;
    }

    /**
     * Returns list of all available stores.
     *
     * NOTE: admins should see the problems by looking at the table listing
     *       all stores in admin UI comparing it with their config.php file.
     *
     * @return store[]
     */
    public static function get_stores(): array {
        global $CFG;

        static $stores = null;

        if (PHPUNIT_TEST) {
            $stores = null;
        }

        if ($stores === null) {
            $stores = [];
            if (!empty($CFG->totara_cloudfiledir_stores)) {
                foreach ($CFG->totara_cloudfiledir_stores as $config) {
                    if (empty($config['provider'])) {
                        // Invalid entry.
                        continue;
                    }
                    if (!isset($config['idnumber'])) {
                        // Missing idnumber.
                        continue;
                    }
                    if (!preg_match('/^[a-z][0-9a-z_]+$/D', $config['idnumber'])) {
                        // Invalid idnumber.
                        continue;
                    }
                    if (isset($stores[$config['idnumber']])) {
                        // Duplicate entry.
                        continue;
                    }
                    $stores[$config['idnumber']] = new self($config);
                }
            }
        }

        return $stores;
    }
}
