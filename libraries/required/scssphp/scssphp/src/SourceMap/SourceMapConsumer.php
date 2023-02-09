<?php
/**
 * SCSSPHP
 *
 * @copyright 2012-2019 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */

namespace ScssPhp\ScssPhp\SourceMap;

use ScssPhp\ScssPhp\Exception\CompilerException;

/**
 * Source Map Consumer
 *
 * Based on the _parseMappings() implementation in Mozilla's source-map library:
 * https://github.com/mozilla/source-map/blob/7a0d318/lib/source-map/source-map-consumer.js#L195
 *
 * Copyright (c) 2009-2011, Mozilla Foundation and contributors
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * * Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 * * Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 * * Neither the names of the Mozilla Foundation nor the names of project
 *   contributors may be used to endorse or promote products derived from this
 *   software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @author Nick Fitzgerald <nfitzgerald@mozilla.com>
 * @author Simon Chester <simon.chester@totaralearning.com>
 */
class SourceMapConsumer
{
    /**
     * Base64 VLQ encoder
     *
     * @var \ScssPhp\ScssPhp\SourceMap\Base64VLQ
     */
    protected $vlq;

    /**
     * Source map object
     *
     * @var object
     */
    protected $map;

    /**
     * Decoded mappings
     *
     * @var array[]
     */
    protected $mappings;

    /**
     * Create a new SourceMapConsumer
     *
     * @param object $sourceMap
     */
    public function __construct($sourceMap)
    {
        $this->map = $sourceMap;

        if (!isset($this->map->sources)) {
            $this->map->sources = [];
        }
        if (!isset($this->map->sourcesContent)) {
            $this->map->sourcesContent = [];
        }

        $this->vlq = new Base64VLQ();
        $this->parseMappings($this->map->mappings);
    }

    /**
     * Parse the VLQ-encoded mapping string
     *
     * @param string $str
     *
     * @see https://docs.google.com/document/d/1U1RGAehQwRypUTovF1KRlpiOFze0b-_2gc6fAH0KY0k/edit#
     */
    private function parseMappings($str)
    {
        $i = 0;
        $length = strlen($str);
        $generatedLine = 1;
        $previousGeneratedColumn = 0;
        $previousSource = 0;
        $previousOriginalLine = 0;
        $previousOriginalColumn = 0;
        $mapping = [];

        while ($i < $length) {
            if ($str[$i] == ';') {
                // new line
                $generatedLine++;
                $i++;
                $previousGeneratedColumn = 0;
            } else if ($str[$i] === ',') {
                // new segment
                $i++;
            } else {
                $mapping = [];
                $mapping['generated_line'] = $generatedLine;

                $value = $this->vlq->decode($str, $i);
                $mapping['generated_column'] = $previousGeneratedColumn + $value;
                $previousGeneratedColumn = $mapping['generated_column'];

                // source and original position fields (optional)
                if ($i < $length && ! $this->isMappingSep($str[$i])) {
                    // source
                    $value = $this->vlq->decode($str, $i);
                    $mapping['source_file'] = isset($this->map->sources[$previousSource + $value])
                        ? $this->map->sources[$previousSource + $value]
                        : null;
                    $previousSource += $value;

                    if ($i >= $length || $this->isMappingSep($str[$i])) {
                        throw new CompilerException('Found a source, but no line and column');
                    }

                    // original line
                    $value = $this->vlq->decode($str, $i);
                    $mapping['original_line'] = $previousOriginalLine + $value;
                    $previousOriginalLine = $mapping['original_line'];
                    // source map format stores lines 0-based
                    $mapping['original_line'] += 1;

                    if ($i >= $length || $this->isMappingSep($str[$i])) {
                        throw new CompilerException('Found a source and line, but no column');
                    }

                    // original column
                    $value = $this->vlq->decode($str, $i);
                    $mapping['original_column'] = $previousOriginalColumn + $value;
                    $previousOriginalColumn = $mapping['original_column'];

                    // original name (optional)
                    if ($i < $length && ! $this->isMappingSep($str[$i])) {
                        // we don't need this, so just ignore it
                        $value = $this->vlq->decode($str, $i);
                    }
                }

                $this->mappings[] = $mapping;
            }
        }
    }

    /**
     * Get the original source file and position for the generated line and column
     *
     * @param int $line
     * @param int $column
     *
     * @return array|null Array with keys 'source_file', 'line', and 'column', or null if no mapping was found.
     */
    public function originalPositionFor($line, $column)
    {
        $found = $this->findMapping($this->mappings, $line, $column);

        return $found && isset($found['source_file'], $found['original_line'], $found['original_column'])
            ? [
                'source_file' => $found['source_file'],
                'line' => $found['original_line'],
                'column' => $found['original_column'],
            ]
            : null;
    }

    /**
     * Do a binary search to find the last mapping that is smaller or equal to or the specified line/column
     *
     * @param array $mappings
     * @param int $line
     * @param int $column
     *
     * @return array
     */
    private function findMapping($mappings, $line, $column)
    {
        $low = 0;
        $high = count($mappings) - 1;

        while ($low <= $high) {
            $mid = (int)($high + ( ( $low - $high ) / 2 ));

            $cmp = $this->compareMapping($mappings[$mid], $line, $column);
            if ($cmp > 0) {
                $high = $mid - 1;
            } else if ($cmp < 0) {
                $low = $mid + 1;
            } else {
                return $mappings[$mid];
            }
        }

        // not found
        // $low would now be the insertion position, so return the index of the previous element
        // unless $low is 0, which indicates we never saw a smaller element
        return $low <= 0 ? null : $mappings[$low - 1];
    }

    /**
     * Compare the provided mapping to the provided line and column
     *
     * Returns -1 if the mapping comes before the line/column, 1 if after, and 0 if equal.
     *
     * @param array $mapping
     * @param int $line
     * @param int $column
     *
     * @return int
     */
    private function compareMapping($mapping, $line, $column)
    {
        if ($mapping['generated_line'] < $line) return -1;
        if ($mapping['generated_line'] > $line) return 1;
        if ($mapping['generated_column'] < $column) return -1;
        if ($mapping['generated_column'] > $column) return 1;
        return 0;
    }

    /**
     * Get the content for the provided source file
     *
     * @param string $source
     *
     * @return string|null Source content, or null if no content was provided in the map.
     */
    public function sourceContentFor($source)
    {
        $index = array_search($source, $this->map->sources);
        if ($index === false) {
            throw new CompilerException("Source \"{$source}\" does not exist in map");
        }
        return isset($this->map->sourcesContent[$index]) ? $this->map->sourcesContent[$index] : null;
    }

    /**
     * Determine if the provided character is a mapping separator (, or ;)
     *
     * @return bool
     */
    private function isMappingSep($char)
    {
        return $char == ',' || $char == ';';
    }

    /**
     * Get a list of all source files
     *
     * @return string[]
     */
    public function getSources()
    {
        return $this->map->sources;
    }
}
