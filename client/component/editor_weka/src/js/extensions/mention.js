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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @module editor_weka
 */

import Mention from 'editor_weka/components/nodes/Mention';
import BaseExtension from './Base';
import mention from '../plugins/mention';

class MentionExtension extends BaseExtension {
  nodes() {
    return {
      mention: {
        schema: {
          group: 'inline',
          inline: true,
          attrs: {
            type: { default: undefined },
            id: { default: undefined },
            display: { default: '' },
          },
          parseDOM: [
            {
              tag: 'span.tui-wekaNodeMention',
              getAttrs(dom) {
                try {
                  return {
                    type: JSON.parse(dom.getAttribute('data-type')),
                    id: JSON.parse(dom.getAttribute('data-id')),
                    display: dom.textContent.trim().slice(1),
                  };
                } catch (e) {
                  return {};
                }
              },
            },
          ],
          toDOM(node) {
            return [
              'span',
              {
                class: 'tui-wekaNodeMention',
                'data-type': JSON.stringify(node.attrs.type),
                'data-id': JSON.stringify(node.attrs.id),
              },
              '@' + node.attrs.display,
            ];
          },
        },

        component: Mention,
      },
    };
  }

  plugins() {
    return [mention(this.editor)];
  }
}

export default opt => new MentionExtension(opt);
