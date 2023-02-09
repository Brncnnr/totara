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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @module editor_weka
 */

import BaseExtension from './Base';
import { ToolbarItem } from '../toolbar';
import { langString } from 'tui/i18n';
import EmojiSelector from 'editor_weka/components/editing/EmojiSelector';
import Emoji from 'editor_weka/components/nodes/Emoji';
import EmojiIcon from 'tui/components/icons/Emoji';
import Vue from 'vue';
import emoji from '../plugins/emoji';
import { getJsonAttrs } from './util';

class EmojiExtension extends BaseExtension {
  /**
   *
   * @param {Object} opt
   */
  constructor(opt) {
    super(opt);

    if (typeof opt === 'undefined') {
      opt = {};
    }

    this.emojis = opt.emojis || [];

    // Set all the valid characters that this plugin monitors.
    let chars = this.emojis.map(emoji => {
      return emoji.pattern;
    });
    this.charArray = chars.toString().split('');
    this.charArray = this.charArray.filter((a, b) => {
      return this.charArray.indexOf(a) === b;
    });
  }

  nodes() {
    return {
      emoji: {
        schema: {
          group: 'inline',
          inline: true,
          attrs: {
            shortcode: { default: '' },
          },
          parseDOM: [
            {
              tag: 'span.tui-wekaNodeEmoji',
              getAttrs: getJsonAttrs,
            },
          ],
          toDOM(node) {
            return [
              'span',
              {
                class: 'tui-wekaNodeEmoji',
                'data-attrs': JSON.stringify({
                  shortcode: node.attrs.shortcode,
                }),
              },
              String.fromCodePoint('0x' + node.attrs.shortcode),
            ];
          },
        },
        component: Emoji,
      },
    };
  }

  plugins() {
    return [emoji(this.editor, this.emojis, this.charArray)];
  }

  toolbarItems() {
    return [
      new ToolbarItem({
        group: 'embeds',
        popover: {
          title: '',
          component: this.getEmojiPopover(),
        },
        label: langString('insertemoji', 'editor'),
        iconComponent: EmojiIcon,
        enabled: editor => {
          if (!editor.view.editable) {
            return false;
          }

          return editor.view.state.selection.$head.pos > 0;
        },
        reset: editor => {
          editor.view.focus();
        },
      }),
    ];
  }

  getEmojiPopover() {
    return Vue.extend({
      data: () => ({
        insertEmoji: this.insertEmoji.bind(this),
        emojiData: this.emojis,
      }),
      methods: {
        emojiSelected(emoji) {
          this.$emit('close');
          this.insertEmoji(emoji);
        },
      },
      render(h) {
        return h(EmojiSelector, {
          props: { emojis: this.emojiData },
          on: {
            'emoji-selected': this.emojiSelected,
          },
        });
      },
    });
  }

  insertEmoji({ shortcode }) {
    this.editor.execute((state, dispatch) => {
      const node = state.schema.node('emoji', { shortcode }),
        { from, to } = state.selection;

      dispatch(state.tr.replaceWith(from, to, node));
      this.editor.view.focus();
    });
  }
}

export default opt => new EmojiExtension(opt);
