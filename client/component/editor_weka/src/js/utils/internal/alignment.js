/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Aaron Machin <aaron.machin@totaralearning.com>
 * @module editor_weka
 */

/**
 * Ensures the type has a given attribute as part of its schema (checks type.spec.attrs)
 *
 * @param {NodeType} type the type to check against
 * @param {string} attr the attribute to check
 * @returns {boolean}
 */
export function typeHasAttr(type, attr) {
  return Boolean(type.spec.attrs && type.spec.attrs[attr]);
}

/**
 * Searches the range {from} - {to} (and parents) for the given attribute {attrName}
 * @param {Node} rootNode The node to begin searching from
 * @param {int} from
 * @param {int} to
 * @param {string} attrName The attribute key to select
 * @returns {*[]} of unique attribute values matching the {attrName}
 */
function findAttrValuesInRange(rootNode, from, to, attrName) {
  let attrValues = [];

  const checkNode = node => {
    if (!typeHasAttr(node.type, attrName)) {
      return;
    }

    const value = node.attrs[attrName];

    if (attrValues.includes(value)) {
      return;
    }

    attrValues.push(value);
  };

  rootNode.nodesBetween(from, to, checkNode);

  return attrValues;
}

/**
 * Searches the range {from} - {to} (and parents). Finds a single attribute value if all the attribute values found are the same.
 * Utilises findAttrValuesInRange()
 * @param {Node} rootNode The node to begin searching from
 * @param {int} from
 * @param {int} to
 * @param {string} attrName The attribute key to select
 * @returns {boolean|*} The single attribute value, or false if no values were found or not all values were the same
 */
export function findSingleAttrValueInRange(rootNode, from, to, attrName) {
  const attrValues = findAttrValuesInRange(rootNode, from, to, attrName);

  // If we have more than one or no values, the specified attribute is not active in this selection
  if (attrValues.length > 1 || attrValues.length === 0) {
    return false;
  }

  return attrValues[0];
}

/**
 * Sets the given alignment on the node's attrs if the node can be aligned
 * @param {Transaction} tr
 * @param {Node} node
 * @param {number} nodePosition
 * @param {string} alignment
 * @returns {Transaction|boolean}
 */
export function setAlignmentOnNode(tr, node, nodePosition, alignment) {
  if (!typeHasAttr(node.type, 'align')) {
    return false;
  }

  return tr.setNodeMarkup(nodePosition, node.type, {
    ...node.attrs,
    align: alignment,
  });
}
