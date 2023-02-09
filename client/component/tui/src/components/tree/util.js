/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
 *
 * Totara Enterprise Extensions is provided only to Totara
 * Learning Solutions LTD's customers and partners, pursuant to
 * the terms and conditions of a separate agreement with Totara
 * Learning Solutions LTD or its affiliate.
 *
 * If you do not have an agreement with Totara Learning Solutions
 * LTD, you may not access, use, modify, or distribute this software.
 * Please contact [licensing@totaralearning.com] for more information.
 *
 * @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
 * @module tui
 */

/**
 * Return an array of available node ID's included in the dataset
 *
 * @param {array} data
 * @returns {array}
 */
export function getAllNodeKeys(data) {
  /**
   * Gets available list of node ID's
   *
   * @param {Object} node
   */
  function getKeyList(node) {
    // Add Id to list if not already included (check for duplicates)
    if (!fullKeyList.includes(node.id)) {
      fullKeyList.push(node.id);
    }

    // If there is child data iterate through it
    if (node.children) {
      node.children.forEach(subNode => {
        getKeyList(subNode);
      });
    }
  }

  let fullKeyList = [];
  data.forEach(tree => {
    getKeyList(tree);
  });
  return fullKeyList;
}

/**
 * Return an array of all parent keys for the provided key
 *
 * @param {array} data
 * @param {string} key
 * @returns {array}
 */
export function getAllParentKeys(data, key) {
  /**
   * Gets parent node ID's for provided key
   *
   * @param {Object} node
   * @param {string} key
   */
  function getKeyList(node, path) {
    let currentPath = [].concat(path);
    currentPath.push(node.id);

    if (node.id === key) {
      fullPath = currentPath;
      return;
    }

    // If there is child data iterate through it
    if (node.children) {
      node.children.forEach(subNode => {
        getKeyList(subNode, currentPath);
      });
    }
  }

  let fullPath = [];
  data.forEach(tree => {
    getKeyList(tree, []);
  });
  return fullPath;
}
