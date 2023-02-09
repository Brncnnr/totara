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
import { getJsonAttrs } from './util';
import LinkBlock from 'editor_weka/components/nodes/LinkBlock';
import LinkMedia from 'editor_weka/components/nodes/LinkMedia';
import LinkView from 'editor_weka/components/marks/Link';
import EditLinkModal from 'editor_weka/components/editing/EditLinkModal';
import LinkIcon from 'tui/components/icons/Link';
import { Plugin } from 'ext_prosemirror/state';
import { Slice, Fragment, Node, Mark } from 'ext_prosemirror/model';
import { langString, loadLangStrings } from 'tui/i18n';
import { ToolbarItem } from '../toolbar';
import { getLinkMetadata } from '../api';
import { setAttrs } from '../transaction';
import {
  getMark,
  getMarkRange,
  expandContentResolvedRange,
  ResolvedRange,
} from '../util';
import { pick } from 'tui/util';
import { notify } from 'tui/notifications';

const linkRegexGlobal = /\bhttps?:\/\/[-a-zA-Z0-9@:%._+~#=]+\b(?:[-a-zA-Z0-9@:%_+.~#?&//=]*)/g;
const emailRegexGlobal = /\b[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?/g;

const cardTypes = ['link_block', 'link_media'];

class LinkExtension extends BaseExtension {
  /**
   *
   * @param {Object} opt
   */
  constructor(opt) {
    super(opt);

    this.isMedia = this.isMedia.bind(this);
    this._convertPastedUrl = this._convertPastedUrl.bind(this);
    this._convertPastedEmail = this._convertPastedEmail.bind(this);
    this._getSelectionRange = this._getSelectionRange.bind(this);
  }

  nodes() {
    return {
      link_media: {
        schema: {
          group: 'block',
          atom: true,
          attrs: {
            url: {},
            loading: { default: undefined },
            type: { default: undefined },
            image: { default: undefined },
            title: { default: undefined },
            description: { default: undefined },
            siteName: { default: undefined },
            resolution: { default: undefined },
          },
          parseDOM: [
            {
              tag: '.tui-wekaNodeLinkMedia',
              getAttrs: getJsonAttrs,
            },
          ],
          toDOM(node) {
            return [
              'a',
              {
                class: 'tui-wekaNodeLinkMedia',
                'data-attrs': JSON.stringify(node.attrs),
                href: node.attrs.url,
              },
              node.attrs.url,
            ];
          },
        },

        component: LinkMedia,

        componentContext: {
          urlPlugin: this.urlPlugin.bind(this),
          editCard: this.editCard.bind(this),
          replaceWithTextLink: this.replaceWithTextLink.bind(this),
        },
      },

      link_block: {
        schema: {
          group: 'block',
          atom: true,
          attrs: {
            url: {},
            type: { default: undefined },
            image: { default: undefined },
            title: { default: undefined },
            description: { default: undefined },
            siteName: { default: undefined },
            open_in_new_window: { default: undefined },
          },
          parseDOM: [
            {
              tag: '.tui-wekaNodeLinkBlock',
              getAttrs: dom => {
                let attrs = getJsonAttrs(dom);
                attrs.open_in_new_window = !!dom.getAttribute('target');
                attrs.target = undefined;

                return attrs;
              },
            },
          ],
          toDOM(node) {
            return [
              'a',
              {
                class: 'tui-wekaNodeLinkBlock',
                'data-attrs': JSON.stringify(node.attrs),
                href: node.attrs.url,
                target: node.attrs.open_in_new_window ? '_blank' : null,
              },
              node.attrs.url,
            ];
          },
        },

        component: LinkBlock,

        componentContext: {
          editCard: this.editCard.bind(this),
          replaceWithTextLink: this.replaceWithTextLink.bind(this),
        },
      },
    };
  }

  marks() {
    return {
      link: {
        schema: {
          attrs: {
            href: {},
            title: { default: undefined },
            open_in_new_window: { default: undefined },
          },
          inclusive: false,
          parseDOM: [
            {
              tag: 'a[href]:not([class*=tui-weka])',
              getAttrs: dom => {
                return {
                  href: dom.getAttribute('href'),
                  title: dom.getAttribute('title'),
                  open_in_new_window: !!dom.getAttribute('target'),
                };
              },
            },
          ],
          toDOM(node) {
            let { href, title } = node.attrs;
            const target = node.attrs.open_in_new_window ? '_blank' : undefined;
            return ['a', { href, title, target }, 0];
          },
        },

        component: LinkView,

        componentContext: {
          showMarkDropdown: this.showMarkDropdown.bind(this),
        },
      },
    };
  }

  plugins() {
    return [
      new Plugin({
        props: {
          transformPasted: slice => {
            let content = parsePastedLinks(
              slice.content,
              {
                convert: this._convertPastedUrl,
              },
              linkRegexGlobal
            );

            content = parsePastedLinks(
              content,
              {
                convert: this._convertPastedEmail,
              },
              emailRegexGlobal
            );

            return new Slice(content, slice.openStart, slice.openEnd);
          },
        },
      }),
    ];
  }

  toolbarItems() {
    return [
      new ToolbarItem({
        group: 'embeds',
        label: langString('link', 'editor_weka'),
        iconComponent: LinkIcon,
        execute: () => {
          this.editLink();
        },
      }),
    ];
  }

  loadSerializedVisitor() {
    return {
      link_media: node => {
        // older saved nodes could have the loading field
        delete node.attrs.loading;
        // TL-30663: older versions of the backend validation may have
        // corrupted the resolution field by converting it to an empty array
        if (Array.isArray(node.attrs.resolution)) {
          delete node.attrs.resolution;
        }
      },
    };
  }

  saveSerializedVisitor() {
    return {
      link_media: node => {
        delete node.attrs.loading;
      },
    };
  }

  /**
   * Insert or edit a link at the current selection.
   */
  editLink() {
    const link = this.getSchema().marks.link;
    const range = this._getSelectionRange();

    // edit mark if the cursor is within it
    if (range.from === range.to) {
      const markRange = getMarkRange(this.doc.resolve(range.from), link);
      if (markRange) {
        this._editMarkAt(() => markRange);
        return;
      }
    }

    const text = this.doc.textBetween(range.from, range.to);
    this.showModal(EditLinkModal, {
      isNew: true,
      isMedia: this.isMedia,
      attrs: { url: '', text: text, open_in_new_window: undefined },
      save: async attrs => {
        const info = await this._prepareLinkUpdate(attrs);
        this._updateLink(
          { type: null, getRange: this._getSelectionRange },
          info
        );
        this.editor.view.focus();
      },
    });
  }

  /**
   * Replace the range with text with a link mark.
   *
   * @param {function} getRange
   * @param {object} attrs
   */
  replaceWithTextLink(getRange, { url }) {
    this.editor.execute((state, dispatch) => {
      let text = url;

      // Change email link text to just the email address.
      if (url.startsWith('mailto:')) {
        text = url.substring('mailto:'.length);
      }

      const content = state.schema.text(text, [
        state.schema.mark('link', { href: url }),
      ]);
      const range = getRange();
      dispatch(state.tr.replaceWith(range.from, range.to, content));
    });
  }

  /**
   * Determine which link plugin should handle the specified URL.
   *
   * @param {object} opts
   * @param {string} [opts.type] Type of link plugins to allow
   * @param {string} opts.url URL
   */
  urlPlugin(opts) {
    return this._findLinkPlugin(opts);
  }

  /**
   * Check if the specified URL is able to be rendered as a media block.
   *
   * @param {string} url
   * @returns {boolean}
   */
  isMedia(url) {
    return !!this._findLinkPlugin({ type: 'media', url });
  }

  /**
   * Show edit modal for card (link_block/link_media)
   *
   * @param {function} getRange
   */
  editCard(getRange) {
    const node = this.doc.nodeAt(getRange().from);
    const type = node.type.name;

    if (!cardTypes.includes(type)) {
      return;
    }

    this.showModal(EditLinkModal, {
      isMedia: this.isMedia,
      attrs: {
        type,
        url: node.attrs.url,
        open_in_new_window: node.attrs.open_in_new_window,
      },
      save: async attrs => {
        const info = await this._prepareLinkUpdate(attrs);
        this._updateLink({ type, getRange }, info);
        this.editor.view.focus();
      },
    });
  }

  /**
   * Show dropdown for a link mark
   *
   * @param {function} getRange
   */
  showMarkDropdown(getRange) {
    const mark = this._getLinkMark(getRange);
    if (!mark) return;
    const media = mark && this.isMedia(mark.attrs.href);
    const email = mark && mark.attrs.href.startsWith('mailto:');

    const actions = [
      {
        label: langString('go_to_link_label', 'editor_weka'),
        action: () => this._openLinkMarkAt(getRange),
      },
      {
        label: langString('edit', 'core'),
        action: () => this._editMarkAt(getRange),
      },
    ];

    if (!email) {
      actions.push({
        label: media
          ? langString('display_as_embedded_media', 'editor_weka')
          : langString('display_as_card', 'editor_weka'),
        action: () =>
          this._markToCardAt(media ? 'link_media' : 'link_block', getRange),
      });
    }

    actions.push({
      label: langString('delete', 'editor_weka'),
      action: () => this._removeMarkAt(getRange),
    });

    if (mark.type.name == 'link') {
      actions.push({
        label: langString('remove_link_target', 'editor_weka'),
        action: () => {
          this._removeLink(getRange);
        },
      });
    }

    return this.showActionDropdown(getRange().from, { actions });
  }

  /**
   * Determine which link plugin should handle the specified URL.
   *
   * @param {object} opts
   * @param {string} [opts.type] Type of link plugins to allow
   * @param {string} opts.url URL
   */
  _findLinkPlugin({ type, url }) {
    for (let i = 0; i < linkPlugins.length; i++) {
      const plugin = linkPlugins[i];
      if (type && plugin.type !== type) {
        continue;
      }
      for (let j = 0; j < plugin.matches.length; j++) {
        const matcher = plugin.matches[j];
        const match = matcher.match.exec(url);
        if (match) {
          return {
            plugin,
            details: matcher.details ? matcher.details(match) : {},
          };
        }
      }
    }
    return null;
  }

  /**
   * Update link node/mark.
   *
   * `type` is one of "link" (mark), "link_block", or "link_media"
   *
   * For `current` it can also be null
   *
   * @param {{ type: string, getRange: function }} current
   * @param {LinkUpdateData} info
   */
  _updateLink(current, info) {
    if (!current) {
      current = { type: null, getRange: this._getSelectionRange };
    }

    let oldText = null;
    if (current.type === 'link' || current.type === null) {
      const range = current.getRange();
      oldText = this.doc.textBetween(range.from, range.to);
    }

    this.editor.execute((state, dispatch) => {
      const range = expandContentResolvedRange(
        ResolvedRange.resolve(this.doc, current.getRange())
      );
      if (info.type == 'link') {
        const markType = state.schema.marks.link;
        const newText = info.text || info.url;
        const newMark = markType.create({
          href: info.url,
          open_in_new_window: info.open_in_new_window,
        });
        let tr;
        // if it was a link before and we have unchanged text, we can just
        // update the mark (preserving any other formatting)
        // otherwise, we need to replace the whole range
        if (current.type == 'link' && oldText && newText === oldText) {
          tr = state.tr
            .removeMark(range.$from.pos, range.$to.pos, markType)
            .addMark(range.$from.pos, range.$to.pos, newMark);
        } else {
          tr = state.tr.replaceWith(
            range.$from.pos,
            range.$to.pos,
            newText ? state.schema.text(newText, [newMark]) : Fragment.empty
          );
        }
        dispatch(tr);
      } else if (cardTypes.includes(info.type)) {
        dispatch(
          state.tr.replaceWith(range.$from.pos, range.$to.pos, info.content)
        );
      }
    });
  }

  /**
   * Prepare info we need to insert a card.
   *
   * @param {{ type: string, url: string, text: string, open_in_new_window: bool }} details
   * @returns {LinkUpdateData}
   */
  async _prepareLinkUpdate(details) {
    let { type, url, text, open_in_new_window } = details;

    // Force mailto links to text nodes.
    if (url.startsWith('mailto:')) {
      return this._createEmailLinkUpdate(details);
    }

    if (cardTypes.includes(type)) {
      const schema = this.editor.state.schema;
      const pluginMatch =
        (!type || type == 'link_media') &&
        this._findLinkPlugin({ type: 'media', url });
      if (pluginMatch) {
        let attrs;
        if (pluginMatch.plugin.nodeAttrs) {
          try {
            attrs = await pluginMatch.plugin.nodeAttrs({ url });
          } catch (e) {
            attrs = {};
            console.error(e);
          }
        }
        return new LinkUpdateData({
          type,
          url,
          content: schema.node('link_media', Object.assign({ url }, attrs)),
        });
      } else {
        let attrs;
        try {
          const ogInfo = await getLinkMetadata(url);
          attrs = getCardAttrs(ogInfo);
          if (!attrs) {
            attrs = {};
          }
          attrs.open_in_new_window = open_in_new_window;
        } catch (e) {
          console.error(e);
          attrs = {};
        }
        return new LinkUpdateData({
          type,
          url,
          content: schema.node('link_block', Object.assign({ url }, attrs)),
        });
      }
    } else {
      return new LinkUpdateData({
        type: 'link',
        url,
        text,
        open_in_new_window,
      });
    }
  }

  /**
   * @internal
   * @param {string} url
   * @param {?string} text
   * @return {LinkUpdateData}
   */
  _createEmailLinkUpdate({ url, text }) {
    if (!text) {
      text = url.substring('mailto:'.length);
    }

    return new LinkUpdateData({ type: 'link', url, text });
  }

  /**
   * Convert an email to either a link mark or a link node
   *
   * @internal
   * @param {string} email
   * @returns {Node|Mark}
   */
  _convertPastedEmail(email) {
    return this._convertPastedUrl(`mailto:${email}`);
  }

  /**
   * Convert url to either a mark or a node
   *
   * @internal
   * @param {string} url
   * @returns {Node|Mark}
   */
  _convertPastedUrl(url) {
    const link = this.getSchema().marks.link;
    const media = this.getSchema().nodes.link_media;

    const pluginMatch = this._findLinkPlugin({ type: 'media', url });
    if (pluginMatch) {
      const attrs = { url };
      if (pluginMatch.plugin.nodeAttrs) {
        attrs.loading = true;
      }
      const mediaNode = media.create(attrs);
      if (attrs.loading) {
        pluginMatch.plugin
          .nodeAttrs({ url })
          .then(attrs => {
            this._updatePastedMedia(url, attrs);
          })
          .catch(e => {
            console.error(e);
            this._updatePastedMedia(url, {});
          });
      }
      return mediaNode;
    } else {
      return link.create({ href: url });
    }
  }

  /**
   * Update a pasted media node with new attributes.
   *
   * @internal
   * @param {string} url
   * @param {object} attrs
   */
  _updatePastedMedia(url, attrs) {
    const media = this.getSchema().nodes.link_media;
    let tr = this.editor.state.tr;
    this.editor.state.doc.descendants((node, pos) => {
      if (node.type == media && node.attrs.loading && node.attrs.url === url) {
        const newAttrs = Object.assign(
          {},
          node.attrs,
          { loading: false },
          attrs
        );
        tr = setAttrs(pos, newAttrs)(tr);
      }
    });
    tr.setMeta('addToHistory', false); // prevent from being rolled back by undo
    this.editor.dispatch(tr);
  }

  /**
   * Get pos range from selection.
   *
   * @returns {{ from: number, to: number }}
   */
  _getSelectionRange() {
    return pick(this.editor.state.selection, ['from', 'to']);
  }

  _getLinkMark(getRange) {
    return getMark(
      this.doc.resolve(getRange().from),
      this.getSchema().marks.link
    );
  }

  _openLinkMarkAt(getRange) {
    const mark = this._getLinkMark(getRange);
    if (!mark) return;
    window.open(mark.attrs.href);
  }

  _editMarkAt(getRange) {
    const range = getRange();
    const mark = this._getLinkMark(getRange);
    if (!mark) return;
    const url = mark.attrs.href;
    let text = this.doc.textBetween(range.from, range.to);
    if (url === text) {
      text = null;
    }

    this.showModal(EditLinkModal, {
      isMedia: this.isMedia,
      attrs: { url, text, open_in_new_window: mark.attrs.open_in_new_window },
      save: async attrs => {
        const info = await this._prepareLinkUpdate(attrs);
        this._updateLink({ type: 'link', getRange }, info);
        this.editor.view.focus();
      },
    });
  }

  async _markToCardAt(type, getRange) {
    const mark = this._getLinkMark(getRange);
    if (!mark) return;
    let info;
    try {
      info = await this._prepareLinkUpdate({
        type,
        url: mark.attrs.href,
        open_in_new_window: mark.attrs.open_in_new_window,
      });
    } catch (e) {
      displayLinkError(e);
      return;
    }
    this._updateLink({ type: 'link', getRange }, info);
    this.editor.view.focus();
  }

  _removeLink(getRange) {
    this.editor.execute((state, dispatch) => {
      const range = getRange();
      const node = this.doc.resolve(range.from).nodeAfter;
      let newNode = state.schema.text(node.text);

      const transaction = state.tr.replaceWith(range.from, range.to, newNode);
      dispatch(transaction);
    });
  }

  _removeMarkAt(getRange) {
    this.editor.execute((state, dispatch) => {
      const range = expandContentResolvedRange(
        ResolvedRange.resolve(this.doc, getRange())
      );
      const transaction = state.tr.delete(range.$from.pos, range.$to.pos);
      dispatch(transaction);
    });
    this.editor.view.focus();
  }

  keymap(bind) {
    bind('Mod-k', () => {
      this.editLink();
      return true;
    });
  }
}

class LinkUpdateData {
  constructor(opts) {
    Object.assign(this, opts);
  }
}

const linkPlugins = [
  {
    key: 'youtube',
    type: 'media',
    name: 'YouTube',
    matches: [
      {
        // https://www.youtube.com/watch?v=ParCfETbJ80
        match: /^https?:\/\/(?:www\.)?youtube.com\/watch\?v=([a-zA-Z0-9_-]+)/,
        details: match => ({ id: match[1] }),
      },
      {
        // https://youtu.be/ParCfETbJ80
        match: /^https?:\/\/(?:www\.)?youtu.be\/([a-zA-Z0-9_-]+)/,
        details: match => ({ id: match[1] }),
      },
    ],
    async nodeAttrs({ url }) {
      const ogInfo = await getLinkMetadata(url);
      return getVideoAttrs(ogInfo);
    },
  },
  {
    key: 'vimeo-private',
    type: 'media',
    name: 'Vimeo private',
    matches: [
      {
        // Example:
        // https://vimeo.com/78716671/e123ceeg2
        match: /^https?:\/\/(?:www\.)?vimeo.com\/([0-9]+)\/([0-9a-zA-Z]+)/,
        details: match => ({ id: match[1], privateString: match[2] }),
      },
    ],
    async nodeAttrs({ url }) {
      const ogInfo = await getLinkMetadata(url);
      return getVideoAttrs(ogInfo);
    },
  },
  {
    key: 'vimeo',
    type: 'media',
    name: 'Vimeo',
    matches: [
      {
        // ex:
        // https://vimeo.com/78716671
        // https://vimeo.com/260405189
        match: /^https?:\/\/(?:www\.)?vimeo.com\/([0-9]+)/,
        details: match => ({ id: match[1] }),
      },
    ],
    async nodeAttrs({ url }) {
      const ogInfo = await getLinkMetadata(url);
      return getVideoAttrs(ogInfo);
    },
  },
  {
    key: 'image',
    type: 'media',
    name: 'Image',
    // https://i.imgur.com/Q6qY1rs.jpg
    matches: [{ match: /\.(png|jpe?g|gif|webp|avif)$/ }],
  },
  {
    key: 'audio',
    type: 'media',
    name: 'Audio',
    // https://interactive-examples.mdn.mozilla.net/media/examples/t-rex-roar.mp3
    matches: [{ match: /\.(aac|flac|m4a|mp3|ogg|opus|wav|wma)$/ }],
  },
];

/**
 * Convert text that looks like a link in the provided fragment to either a link
 * mark or link_media node.
 *
 * @internal
 * @param {Fragment} fragment ProseMirror Fragment
 * @param {object} ctx Context object containing { convert() }
 * @param {RegExp} regex The regex to find links, note this must include the /g global flag
 * @returns {Fragment} New fragment
 */
function parsePastedLinks(fragment, ctx, regex) {
  const nodes = [];
  fragment.forEach(function(child) {
    if (child.isText) {
      const text = child.text;
      let pos = 0;
      let match;

      regex.lastIndex = 0;
      while ((match = regex.exec(text))) {
        const start = match.index;
        const end = start + match[0].length;

        // copy text before the link
        if (start > 0) {
          nodes.push(child.cut(pos, start));
        }

        const url = text.slice(start, end);
        const result = ctx.convert(url);
        if (result instanceof Node) {
          nodes.push(result);
        } else if (result instanceof Mark) {
          nodes.push(child.cut(start, end).mark(result.addToSet(child.marks)));
        } else {
          nodes.push(child.cut(start, end));
        }
        pos = end;
      }

      // copy remaining text
      if (pos < text.length) {
        nodes.push(child.cut(pos));
      }
    } else {
      nodes.push(child.copy(parsePastedLinks(child.content, ctx, regex)));
    }
  });

  return Fragment.fromArray(nodes);
}

const nullToUndefined = obj => {
  const newObj = {};
  for (const key in obj) {
    if (obj[key] != null) {
      newObj[key] = obj[key];
    }
  }
  return newObj;
};

/**
 * Get video attributes from open graph info.
 *
 * @internal
 * @param {object} ogInfo
 * @returns {object}
 */
function getVideoAttrs(ogInfo) {
  if (ogInfo === null) {
    return null;
  }
  const attrs = nullToUndefined(
    pick(ogInfo, ['url', 'image', 'title', 'description'])
  );
  const width = Number(ogInfo.videowidth);
  const height = Number(ogInfo.videoheight);
  attrs.resolution =
    !isNaN(width) && !isNaN(height) ? { width, height } : undefined;
  return attrs;
}

/**
 * Get link card attributes from open graph info.
 *
 * @internal
 * @param {object} ogInfo
 * @returns {object}
 */
function getCardAttrs(ogInfo) {
  if (ogInfo === null) {
    return null;
  }
  return nullToUndefined(
    pick(ogInfo, ['url', 'image', 'title', 'description'])
  );
}

async function displayLinkError() {
  const str = langString('error_no_url_info', 'editor_weka');
  await loadLangStrings([str]);
  notify({ type: 'error', message: str.toString() });
}

export default opt => new LinkExtension(opt);
