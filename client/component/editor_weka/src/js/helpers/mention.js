/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @module editor_weka
 */

/**
 *
 * @param {String} fullname
 * @param {Number} userId
 */
import { getDefaultDocument } from 'editor_weka/helpers/editor';

export function createMentionContent({ fullname, userId }) {
  let contentDocument = getDefaultDocument();

  // First element is alaways a paragraph anyway.
  contentDocument.content[0] = {
    type: 'paragraph',
    content: [
      {
        type: 'mention',
        attrs: {
          id: userId,
          display: fullname,
        },
      },
      {
        type: 'text',
        text: ' ',
      },
    ],
  };

  return contentDocument;
}
