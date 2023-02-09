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

import BaseExtension from './Base';
import { langString } from 'tui/i18n';
import { markItem, blockTypeItem } from '../toolbar';
import { wrapIn, setBlockType, toggleMark } from 'ext_prosemirror/commands';
import {
  wrappingInputRule,
  textblockTypeInputRule,
  ellipsis,
} from 'ext_prosemirror/inputrules';
import TextBoldIcon from 'tui/components/icons/TextBold';
import TextItalicIcon from 'tui/components/icons/TextItalic';
import TextUnderlineIcon from 'tui/components/icons/TextUnderline';

const blockquotedom = ['blockquote', 0],
  emdom = ['em', 0],
  strongdom = ['strong', 0],
  underlinedom = ['u', 0];

const headingEls = ['h4', 'h5'];

const HEADING_LEVELS = 6;

class TextExtension extends BaseExtension {
  nodes() {
    return {
      blockquote: {
        schema: {
          group: 'block',
          content: 'block+',
          parseDOM: [{ tag: 'blockquote' }],
          toDOM() {
            return blockquotedom;
          },
        },
      },

      heading: {
        schema: {
          attrs: {
            level: { default: 1 },
            align: { default: undefined },
          },
          content: 'inline*',
          group: 'block',
          defining: true,
          parseDOM: [
            { tag: 'h1', attrs: { level: 1 } },
            { tag: 'h2', attrs: { level: 2 } },
            { tag: 'h3', attrs: { level: 3 } },
            { tag: 'h4', attrs: { level: 4 } },
            { tag: 'h5', attrs: { level: 5 } },
            { tag: 'h6', attrs: { level: 6 } },
          ],
          toDOM(node) {
            let alignment = node.attrs.align;

            const props = {};

            if (alignment) {
              props.style = `text-align: ${alignment};`;
            }

            return ['h' + node.attrs.level, props, 0];
          },
        },

        // view and parseDOM/toDOM use different heading levels
        // this is to allow for copy-pasting from Google Docs etc with the right
        // heading levels
        view(node) {
          const index = Math.max(node.attrs.level, 1) - 1;
          const element =
            headingEls[index] || headingEls[headingEls.length - 1];
          const el = document.createElement(element);

          if (node.attrs.align) {
            el.style.textAlign = node.attrs.align;
          }

          return {
            dom: el,
            contentDOM: el,
          };
        },
      },

      hard_break: {
        schema: {
          group: 'inline',
          parseDOM: [{ tag: 'br' }],
          toDOM: () => ['br'],
          inline: true,
          selectable: false,
        },
      },
    };
  }

  marks() {
    return {
      // Italic
      em: {
        schema: {
          parseDOM: [
            { tag: 'i' },
            { tag: 'em' },
            { style: 'font-style=italic' },
          ],
          toDOM() {
            return emdom;
          },
        },
      },
      // Bold
      strong: {
        schema: {
          parseDOM: [
            { tag: 'strong' },
            // This works around a Google Docs misbehavior where
            // pasted content will be inexplicably wrapped in `<b>`
            // tags with a font-weight normal.
            {
              tag: 'b',
              getAttrs: node => node.style.fontWeight != 'normal' && null,
            },
            {
              style: 'font-weight',
              getAttrs: value =>
                /^(bold(er)?|[5-9]\d{2,})$/.test(value) && null,
            },
          ],
          toDOM() {
            return strongdom;
          },
        },
      },
      // Underline
      underline: {
        schema: {
          parseDOM: [{ tag: 'u' }, { style: 'text-decoration=underline' }],
          toDOM() {
            return underlinedom;
          },
        },
      },
    };
  }

  toolbarItems() {
    const { paragraph, heading } = this.getSchema().nodes;
    const { strong, em, underline } = this.getSchema().marks;

    return [
      blockTypeItem(
        paragraph,
        {
          ignoreAttrs: ['align'],
        },
        {
          group: 'blocks',
          label: langString('paragraph', 'editor'),
        }
      ),
      blockTypeItem(
        heading,
        {
          attrs: {
            level: 1,
          },
          ignoreAttrs: ['align'],
        },
        {
          group: 'blocks',
          label: langString('heading', 'editor'),
        }
      ),
      blockTypeItem(
        heading,
        {
          attrs: {
            level: 2,
          },
          ignoreAttrs: ['align'],
        },
        {
          group: 'blocks',
          label: langString('subheading', 'editor'),
        }
      ),
      markItem(strong, {
        group: 'text',
        label: langString('bold', 'editor'),
        iconComponent: TextBoldIcon,
      }),
      markItem(em, {
        group: 'text',
        label: langString('italic', 'editor'),
        iconComponent: TextItalicIcon,
      }),
      markItem(underline, {
        group: 'text',
        label: langString('underline', 'editor'),
        iconComponent: TextUnderlineIcon,
      }),
    ];
  }

  keymap(bind) {
    const {
      paragraph,
      blockquote,
      heading,
      hard_break,
    } = this.getSchema().nodes;
    const { strong, em, underline } = this.getSchema().marks;

    bind('Shift-Ctrl-0', setBlockType(paragraph));

    bind('Ctrl->', wrapIn(blockquote));
    for (let i = 1; i <= HEADING_LEVELS; i++) {
      bind('Shift-Ctrl-' + i, setBlockType(heading, { level: i }));
    }

    const breakCmd = (state, dispatch) => {
      dispatch(
        state.tr.replaceSelectionWith(hard_break.create()).scrollIntoView()
      );
      return true;
    };
    bind('Shift-Enter', breakCmd);

    bind('Mod-b', toggleMark(strong));
    bind('Mod-B', toggleMark(strong));
    bind('Mod-i', toggleMark(em));
    bind('Mod-I', toggleMark(em));
    bind('Mod-u', toggleMark(underline));
    bind('Mod-U', toggleMark(underline));
  }

  inputRules() {
    const { blockquote, heading } = this.getSchema().nodes;
    return [
      ellipsis,
      blockQuoteRule(blockquote),
      headingRule(heading, HEADING_LEVELS),
    ];
  }
}

function blockQuoteRule(nodeType) {
  return wrappingInputRule(/^\s*>\s$/, nodeType);
}

function headingRule(nodeType, maxLevel) {
  return textblockTypeInputRule(
    new RegExp('^(#{1,' + maxLevel + '})\\s$'),
    nodeType,
    match => ({ level: match[1].length })
  );
}

export default opt => new TextExtension(opt);
