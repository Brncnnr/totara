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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @module editor_weka
 */

/**
 * Execute visitor on the provided serialized node, and its children recursively.
 *
 * @param {object} doc
 * @param {{ [key: string]: (node: any) => void, any?: (node: any) => void }} visitor
 */
export function traverseNode(node, visitor) {
  if (visitor[node.type]) {
    visitor[node.type](node);
  }
  if (visitor.any) {
    visitor.any(node);
  }
  if (Array.isArray(node.content)) {
    node.content.forEach(child => traverseNode(child, visitor));
  }
}
