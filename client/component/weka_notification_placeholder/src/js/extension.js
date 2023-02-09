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
 * @author Arshad Anwer <arshad.anwer@totaralearning.com>
 * @module weka_notification_placeholder
 */

import Placeholder from 'weka_notification_placeholder/components/nodes/Placeholder';
import BaseExtension from 'editor_weka/extensions/Base';
import notificationPlaceholder from './plugin';

class PlaceholderExtension extends BaseExtension {
  nodes() {
    return {
      totara_notification_placeholder: {
        schema: {
          group: 'inline',
          inline: true,
          attrs: {
            key: { default: undefined },
            label: { default: undefined },
          },
          parseDOM: [
            {
              tag: 'span.tui-placeholder__text',
              getAttrs(dom) {
                try {
                  return {
                    key: dom.getAttribute('data-key'),
                    label: dom.getAttribute('data-label'),
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
                class: 'tui-placeholder__text',
                'data-key': node.attrs.key,
                'data-label': node.attrs.label,
              },
              '[' + node.attrs.label + ']',
            ];
          },
        },

        component: Placeholder,
      },
    };
  }

  plugins() {
    return [
      notificationPlaceholder(this.editor, this.options.resolver_class_name),
    ];
  }
}

export default opt => new PlaceholderExtension(opt);
