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
 * @author Fabian Derschattan <fabian.derschatta@totaralearning.com>
 * @package editor_weka
 */
defined('MOODLE_INTERNAL') || die();

class editor_weka_upgradelib_fix_attachement_url_testcase extends advanced_testcase {

    public function test_fix_broken_weka_doc() {
        global $CFG;
        require_once $CFG->dirroot . '/lib/editor/weka/db/upgradelib.php';

        $broken_json = $this->get_broken_json();
        $fixed_json = editor_weka_fix_attachments_with_empty_url($broken_json);
        $this->assertNotNull($fixed_json);
        $expected_json = $this->get_expected_json();
        $expected_json = json_encode(json_decode($expected_json), JSON_UNESCAPED_SLASHES);
        $this->assertEquals($expected_json, $fixed_json);

        $result = editor_weka_fix_attachments_with_empty_url($fixed_json);
        $this->assertNull($result);
    }

    private function get_broken_json(): string {
        $json = <<<EOT
            {
              "type": "doc",
              "content": [
                {
                  "type": "attachments",
                  "content": [
                    {
                      "type": "attachment",
                      "attrs": {
                        "filename": "image0.png",
                        "size": 56116,
                        "option": {
                          "alttext": ""
                        },
                        "url": null
                      }
                    }
                  ]
                },
                {
                  "type": "attachments",
                  "content": [
                    {
                      "type": "attachment",
                      "attrs": {
                        "filename": "image1 contains spaces.png",
                        "size": 433680,
                        "option": {
                          "alttext": ""
                        },
                        "url": null
                      }
                    }
                  ]
                },
                {
                  "type": "bullet_list",
                  "content": [
                    {
                      "type": "list_item",
                      "content": [
                        {
                          "type": "paragraph",
                          "content": [
                            {
                              "type": "text",
                              "text": "Bullet point 1"
                            }
                          ]
                        }
                      ]
                    },
                    {
                      "type": "list_item",
                      "content": [
                        {
                          "type": "paragraph",
                          "content": [
                            {
                              "type": "text",
                              "text": "Bullet point 2"
                            }
                          ]
                        }
                      ]
                    },
                    {
                      "type": "list_item",
                      "content": [
                        {
                          "type": "paragraph"
                        },
                        {
                          "type": "attachments",
                          "content": [
                            {
                              "type": "attachment",
                              "attrs": {
                                "filename": "image2.png",
                                "size": 274719,
                                "option": {
                                  "alttext": ""
                                },
                                "url": null
                              }
                            }
                          ]
                        }
                      ]
                    },
                    {
                      "type": "list_item",
                      "content": [
                        {
                          "type": "paragraph",
                          "content": [
                            {
                              "type": "text",
                              "text": "Bullet point 3"
                            }
                          ]
                        }
                      ]
                    },
                    {
                      "type": "list_item",
                      "content": [
                        {
                          "type": "paragraph"
                        },
                        {
                          "type": "image",
                          "attrs": {
                            "filename": "image3.png",
                            "url": "@@PLUGINFILE@@/image3.png",
                            "alttext": ""
                          }
                        }
                      ]
                    },
                    {
                      "type": "list_item",
                      "content": [
                        {
                          "type": "paragraph",
                          "content": [
                            {
                              "type": "text",
                              "text": "Bullet point 4"
                            }
                          ]
                        }
                      ]
                    }
                  ]
                }
              ]
            }
EOT;
        return $json;
    }

    private function get_expected_json(): string {
        $json = <<<EOT
            {
              "type": "doc",
              "content": [
                {
                  "type": "attachments",
                  "content": [
                    {
                      "type": "attachment",
                      "attrs": {
                        "filename": "image0.png",
                        "size": 56116,
                        "option": {
                          "alttext": ""
                        },
                        "url": "@@PLUGINFILE@@/image0.png?forcedownload=1"
                      }
                    }
                  ]
                },
                {
                  "type": "attachments",
                  "content": [
                    {
                      "type": "attachment",
                      "attrs": {
                        "filename": "image1 contains spaces.png",
                        "size": 433680,
                        "option": {
                          "alttext": ""
                        },
                        "url": "@@PLUGINFILE@@/image1%20contains%20spaces.png?forcedownload=1"
                      }
                    }
                  ]
                },
                {
                  "type": "bullet_list",
                  "content": [
                    {
                      "type": "list_item",
                      "content": [
                        {
                          "type": "paragraph",
                          "content": [
                            {
                              "type": "text",
                              "text": "Bullet point 1"
                            }
                          ]
                        }
                      ]
                    },
                    {
                      "type": "list_item",
                      "content": [
                        {
                          "type": "paragraph",
                          "content": [
                            {
                              "type": "text",
                              "text": "Bullet point 2"
                            }
                          ]
                        }
                      ]
                    },
                    {
                      "type": "list_item",
                      "content": [
                        {
                          "type": "paragraph"
                        },
                        {
                          "type": "attachments",
                          "content": [
                            {
                              "type": "attachment",
                              "attrs": {
                                "filename": "image2.png",
                                "size": 274719,
                                "option": {
                                  "alttext": ""
                                },
                                "url": "@@PLUGINFILE@@/image2.png?forcedownload=1"
                              }
                            }
                          ]
                        }
                      ]
                    },
                    {
                      "type": "list_item",
                      "content": [
                        {
                          "type": "paragraph",
                          "content": [
                            {
                              "type": "text",
                              "text": "Bullet point 3"
                            }
                          ]
                        }
                      ]
                    },
                    {
                      "type": "list_item",
                      "content": [
                        {
                          "type": "paragraph"
                        },
                        {
                          "type": "image",
                          "attrs": {
                            "filename": "image3.png",
                            "url": "@@PLUGINFILE@@/image3.png",
                            "alttext": ""
                          }
                        }
                      ]
                    },
                    {
                      "type": "list_item",
                      "content": [
                        {
                          "type": "paragraph",
                          "content": [
                            {
                              "type": "text",
                              "text": "Bullet point 4"
                            }
                          ]
                        }
                      ]
                    }
                  ]
                }
              ]
            }
EOT;
        return $json;
    }
}