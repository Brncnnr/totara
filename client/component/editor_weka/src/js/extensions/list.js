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
 * @author Alvin Smith <alvin.smith@totaralearning.com>
 * @module editor_weka
 */

import BaseExtension from './Base';
import { langString } from 'tui/i18n';
import { cmdItem } from '../toolbar';
import { toggleList, isListActive } from '../utils/list';
import {
  splitListItem,
  liftListItem,
  sinkListItem,
} from '../lib/prosemirror_schema_list/schema_list';
import { wrappingInputRule } from 'ext_prosemirror/inputrules';
import ListOrdered from 'tui/components/icons/ListOrdered';
import ListUnordered from 'tui/components/icons/ListUnordered';

const olDOM = ['ol', 0];
const ulDOM = ['ul', 0];
const liDOM = ['li', 0];

class ListExtension extends BaseExtension {
  nodes() {
    return {
      bullet_list: {
        schema: {
          content: 'list_item+',
          group: 'block',
          parseDOM: [{ tag: 'ul' }],
          toDOM() {
            return ulDOM;
          },
        },
      },

      ordered_list: {
        schema: {
          content: 'list_item+',
          group: 'block',
          attrs: { order: { default: 1 } },
          parseDOM: [
            {
              tag: 'ol',
              getAttrs: dom => ({
                order: dom.hasAttribute('start')
                  ? +dom.getAttribute('start')
                  : 1,
              }),
            },
          ],
          toDOM(node) {
            return node.attrs.order == 1
              ? olDOM
              : ['ol', { start: node.attrs.order }, 0];
          },
        },
      },

      list_item: {
        schema: {
          content: 'paragraph block*',
          parseDOM: [{ tag: 'li' }],
          toDOM() {
            return liDOM;
          },
          defining: true,
          draggable: false,
        },
      },
    };
  }

  toolbarItems() {
    const { bullet_list, ordered_list, list_item } = this.getSchema().nodes;
    const listTypes = [bullet_list, ordered_list];

    return [
      cmdItem(toggleList(bullet_list, list_item, listTypes), {
        group: 'text',
        label: langString('bulletlist', 'editor'),
        iconComponent: ListUnordered,
        active: editor => isListActive(editor.state, bullet_list),
      }),
      cmdItem(toggleList(ordered_list, list_item, listTypes), {
        group: 'text',
        label: langString('numberedlist', 'editor'),
        iconComponent: ListOrdered,
        active: editor => isListActive(editor.state, ordered_list),
      }),
    ];
  }

  keymap(bind) {
    const { bullet_list, ordered_list, list_item } = this.getSchema().nodes;
    const listTypes = [bullet_list, ordered_list];
    bind('Shift-Ctrl-8', toggleList(bullet_list, list_item, listTypes));
    bind('Shift-Ctrl-9', toggleList(ordered_list, list_item, listTypes));
    bind('Enter', splitListItem(list_item));
    bind('Shift-Tab', liftListItem(list_item));
    bind('Tab', sinkListItem(list_item));
  }

  inputRules() {
    const { bullet_list, ordered_list } = this.getSchema().nodes;
    return [bulletListRule(bullet_list), orderedListRule(ordered_list)];
  }
}

function bulletListRule(nodeType) {
  return wrappingInputRule(/^\s*([-+*])\s$/, nodeType);
}

function orderedListRule(nodeType) {
  return wrappingInputRule(
    /^(\d+)\.\s$/,
    nodeType,
    match => ({ order: +match[1] }),
    (match, node) => node.childCount + node.attrs.order == +match[1]
  );
}

export default opt => new ListExtension(opt);
