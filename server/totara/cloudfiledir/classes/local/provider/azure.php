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

namespace totara_cloudfiledir\local\provider;

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;

/**
 * Microsoft Azure cloud file storage provider.
 */
final class azure extends base {
    /**
     * @var BlobRestProxy
     */
    private $client;

    /**
     * Is the provider ready to be connected?
     *
     * @return bool
     */
    public function is_ready(): bool {
        if (empty($this->bucket)) {
            return false;
        }
        include_once(__DIR__ . '/../../../../../../libraries/optional/autoload.php');
        if (!class_exists('MicrosoftAzure\Storage\Blob\BlobRestProxy')) {
            error_log('Missing Azure Blob Storage SDK, use composer to install optional dependencies in /libraries/ directory');
            return false;
        }
        return true;
    }

    /**
     * Get fully configured and ready to use Azure Blob client.
     *
     * @return BlobRestProxy|null
     */
    protected function get_client(): ?BlobRestProxy {
        if ($this->client !== null) {
            return ($this->client === false) ? null : $this->client;
        }
        if (!$this->is_ready()) {
            $this->client = false;
            return null;
        }

        // see for more details: https://github.com/Azure/azure-storage-php

        $connection = [];
        foreach ($this->options as $k => $v) {
            $connection[] = $k . '=' . $v;
        }
        $connection = implode(';', $connection);
        $this->client = BlobRestProxy::createBlobService($connection);
        return $this->client;
    }

    /**
     * Test connection to provider.
     *
     * @return bool success
     */
    public function test_connection(): bool {
        $client = $this->get_client();
        if (!$client) {
            return false;
        }

        try {
            $client->getContainerProperties($this->bucket);
            return true;
        } catch (ServiceException $ex) {
            $this->log_exception($ex, 'Error testing connection');
            return false;
        }
    }

    /**
     * Is the content with given contenthash available from this store?
     *
     * @param string $contenthash
     * @return bool
     */
    public function is_content_available(string $contenthash): bool {
        $client = $this->get_client();
        if (!$client) {
            return false;
        }

        try {
            $client->getBlob($this->bucket, $this->get_object_name($contenthash));
            return true;
        } catch (ServiceException $ex) {
            if (strpos($ex->getMessage(), 'The specified blob does not exist') !== false) {
                return false;
            }
            $this->log_exception($ex, 'Cannot find if content file available ' . $contenthash);
            return false;
        }
    }

    /**
     * Upload content file to cloud store.
     *
     * @param string $contenthash
     * @param string $filepath
     * @return bool success
     */
    public function upload_content(string $contenthash, string $filepath): bool {
        $handle = fopen($filepath, 'r');
        if (!$handle) {
            return false;
        }
        $filesize = filesize($filepath);

        $uploaded = $this->upload_content_stream($contenthash, $handle, $filesize);
        return $uploaded;
    }

    /**
     * Upload content file to cloud store from file handle.
     *
     * @param string $contenthash
     * @param resource $handle
     * @param int $contentlength
     * @return bool success
     */
    public function upload_content_stream(string $contenthash, $handle, int $contentlength): bool {
        $client = $this->get_client();
        if (!$client) {
            return false;
        }
        if (!$handle) {
            return false;
        }

        try {
            $client->createBlockBlob($this->bucket, $this->get_object_name($contenthash), $handle);
            // Azure client closes the handle automatically.
            return true;
        } catch (ServiceException $ex) {
            @fclose($handle);
            $this->log_exception($ex, 'Cannot upload content file ' . $contenthash);
            return false;
        }
    }

    /**
     * Download content file from store.
     *
     * @param string $contenthash
     * @param string $filepath
     * @return bool success, false if file does not exist or on error
     */
    public function download_content(string $contenthash, string $filepath): bool {
        $client = $this->get_client();
        if (!$client) {
            return false;
        }

        try {
            $blob = $client->getBlob($this->bucket, $this->get_object_name($contenthash));
            $result = file_put_contents($filepath, $blob->getContentStream());
            return ($result !== false);
        } catch (ServiceException $ex) {
            $this->log_exception($ex, 'Cannot download content file ' . $contenthash);
            return false;
        }
    }

    /**
     * Delete content file from store
     *
     * @param string $contenthash
     * @return bool success
     */
    public function delete_content(string $contenthash): bool {
        $client = $this->get_client();
        if (!$client) {
            return false;
        }

        try {
            $client->deleteBlob($this->bucket, $this->get_object_name($contenthash));
            return true;
        } catch (ServiceException $ex) {
            if (strpos($ex->getMessage(), '<Code>BlobNotFound</Code>') !== false) {
                return true;
            }
            $this->log_exception($ex, 'Cannot delete content file ' . $contenthash);
            return false;
        }
    }

    /**
     * List all contents in the cloud bucket.
     *
     * @return \Iterator|null returning content hashes null means error
     */
    public function list_contents(): ?\Iterator {
        $client = $this->get_client();
        if (!$client) {
            return null;
        }

        try {
            $nameparser = $this->get_object_name_parser();

            return new class($client, $this->bucket, $this->prefix, $nameparser) implements \Iterator {
                private $contenthashes;
                private $i = 0;
                /** @var BlobRestProxy */
                private $client;
                private $bucket;
                private $prefix;
                /** @var callable */
                private $nameparser;
                private $continueationtoken;
                public function __construct(BlobRestProxy $client, string $bucket, string $prefix, callable $nameparser) {
                    $this->client = $client;
                    $this->bucket = $bucket;
                    $this->prefix = $prefix;
                    $this->nameparser = $nameparser;
                    $this->preload_contents();
                }

                private function preload_contents() {
                    if (!$this->client) {
                        return;
                    }
                    $options = new \MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions();
                    if ($this->continueationtoken) {
                        $options->setContinuationToken($this->continueationtoken);
                    }
                    if ($this->prefix !== '') {
                        $options->setPrefix($this->prefix);
                    }
                    $result = $this->client->listBlobs($this->bucket, $options);
                    foreach ($result->getBlobs() as $blob) {
                        $contenthash = call_user_func($this->nameparser, $blob->getName());
                        if ($contenthash === false) {
                            continue;
                        }
                        $this->contenthashes[] = $contenthash;
                    }
                    $this->continueationtoken = $result->getContinuationToken();
                    if (!$this->continueationtoken) {
                        $this->client = null;
                        return;
                    }
                    if (!$this->contenthashes) {
                        $this->preload_contents();
                    }
                }

                #[\ReturnTypeWillChange]
                public function current() {
                    return reset($this->contenthashes);
                }

                #[\ReturnTypeWillChange]
                public function next() {
                    if (!$this->contenthashes) {
                        $this->preload_contents();
                    }
                    if (!$this->contenthashes) {
                        return;
                    }
                    $this->i++;
                    reset($this->contenthashes);
                    unset($this->contenthashes[key($this->contenthashes)]);
                }

                #[\ReturnTypeWillChange]
                public function key() {
                    return $this->i;
                }

                #[\ReturnTypeWillChange]
                public function valid() {
                    return $this->contenthashes;
                }

                #[\ReturnTypeWillChange]
                public function rewind() {
                    if ($this->i !== 0) {
                        throw new \coding_exception('contents iterator cannot be rewound');
                    }
                }
            };
        } catch (ServiceException $ex) {
            $this->log_exception($ex, 'Cannot obtain list of content files');
            return null;
        }
    }

    /**
     * Delete all files from bucket, this is intended for tests only!
     *
     * @internal
     * @return bool success
     */
    public function clear_test_bucket(): bool {
        if (!PHPUNIT_TEST) {
            throw new \coding_exception('Bucket clearing is intended for phpunit tests only');
        }

        $client = $this->get_client();
        if (!$client) {
            return false;
        }

        $contenthashes = $this->list_contents();
        if ($contenthashes === null) {
            return false;
        }

        foreach ($contenthashes as $contenthash) {
            // NOTE: no need to optimise this, it is for testing only!
            $client->deleteBlob($this->bucket, $this->get_object_name($contenthash));
        }
        return true;
    }
}
