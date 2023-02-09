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

import BaseExtension from './Base';
import { ToolbarItem } from '../toolbar';
import { langString, loadLangStrings } from 'tui/i18n';
import { pickFiles, UploadError } from '../utils/upload';
import ImageBlock from 'editor_weka/components/nodes/ImageBlock';
import VideoBlock from 'editor_weka/components/nodes/VideoBlock';
import AudioBlock from 'editor_weka/components/nodes/AudioBlock';
import FigureCaption from 'editor_weka/components/nodes/FigureCaption';
import ImageIcon from 'tui/components/icons/Image';
import { IMAGE, VIDEO } from '../helpers/media';
import { getJsonAttrs } from './util';
import { notify } from 'tui/notifications';
import {
  Plugin,
  PluginKey,
  TextSelection,
  NodeSelection,
} from 'ext_prosemirror/state';
import { Fragment } from 'ext_prosemirror/model';
import { setAttrs } from 'editor_weka/transaction';
import { pick } from 'tui/util';
import { safeInsert } from 'editor_weka/lib/prosemirror_utils/transforms';

const figurePluginKey = new PluginKey('figure');

class MediaExtension extends BaseExtension {
  /**
   *
   * @param {Object} opt
   */
  constructor(opt) {
    super(opt);
    this.acceptTypes = opt.accept_types || [];
  }

  nodes() {
    return {
      figure: {
        schema: {
          content: 'image? figure_caption',
          group: 'block',
          parseDOM: [{ tag: 'figure' }],
          toDOM: () => ['figure', 0],
          atom: false,
          isolating: true,
          defining: true,
          selectable: false,
          allowGapCursor: false,
        },
      },

      figure_caption: {
        schema: {
          content: 'inline*',
          parseDOM: [{ tag: 'figcaption' }],
          toDOM: () => ['figcaption', 0],
          selectable: false,
          draggable: false,
          atom: false,
        },
        component: FigureCaption,
      },

      image: {
        schema: {
          atom: true,
          group: 'block',
          inline: false,
          selectable: true,
          attrs: {
            filename: {},
            // This is from the support of file_rewrite_plugin_file, it will be picked up by the
            // server side to reformat the plugin file url.
            url: { default: undefined },
            alttext: { default: undefined },
            display_size: { default: undefined },
          },

          parseDOM: [
            {
              tag: 'div.tui-wekaNodeImageBlock',
              getAttrs: getJsonAttrs,
            },
            {
              tag: 'div.tui-imageBlock',
              getAttrs: getJsonAttrs,
            },
          ],

          toDOM(node) {
            return [
              'div',
              {
                class: 'tui-wekaNodeImageBlock',
                'data-attrs': JSON.stringify(
                  pick(node.attrs, [
                    'filename',
                    'alttext',
                    'url',
                    'display_size',
                  ])
                ),
              },
            ];
          },
        },
        component: ImageBlock,
        componentContext: {
          replaceWithAttachment: this._replaceImageWithAttachment.bind(this),
          replaceWithFigure: this._replaceImageWithFigure.bind(this),
          updateImage: this._updateImage.bind(this),
          hasAttachmentNode: this._hasAttachmentsNode.bind(this),
          removeNode: this.removeNode.bind(this),
          getItemId: this._getItemId.bind(this),
          getDownloadUrl: this._getDownloadUrl.bind(this),
        },
      },

      video: {
        schema: {
          atom: true,
          group: 'block',
          inline: false,
          attrs: {
            filename: { default: undefined },
            // This is from the support of file_rewrite_plugin_file, it will be picked up by the
            // server side to reformat the plugin file url.
            url: { default: undefined },
            mime_type: { default: undefined },
            subtitle: { default: undefined },
          },

          parseDOM: [
            {
              tag: 'div.tui-wekaNodeVideoBlock',
              getAttrs: getJsonAttrs,
            },
            {
              tag: 'div.tui-videoBlock',
              getAttrs: getJsonAttrs,
            },
          ],

          toDOM(node) {
            let dataAttrs = {
              filename: node.attrs.filename,
              url: node.attrs.url,
              mime_type: node.attrs.mime_type,
              subtitle: node.attrs.subtitle,
            };

            return [
              'div',
              {
                class: 'tui-wekaNodeVideoBlock',
                'data-attrs': JSON.stringify(dataAttrs),
              },
            ];
          },
        },
        component: VideoBlock,
        componentContext: {
          replaceWithAttachment: this._replaceVideoWithAttachment.bind(this),
          hasAttachmentNode: this._hasAttachmentNode.bind(this),
          removeNode: this.removeNode.bind(this),
          /** @deprecated since Totara 13.3 */
          getFileUrl: () => null,
          getItemId: this._getItemId.bind(this),
          getDownloadUrl: this._getDownloadUrl.bind(this),
          getContextId: this._getContextId.bind(this),
          updateVideoWithSubtitle: this._updateVideoWithSubtitle.bind(this),
        },
      },

      audio: {
        schema: {
          atom: true,
          group: 'block',
          inline: false,
          attrs: {
            filename: { default: undefined },
            // This is from the support of file_rewrite_plugin_file, it will be picked up by the
            // server side to reformat the plugin file url.
            url: { default: undefined },

            // This is needed to display embedded audio file.
            mime_type: { default: undefined },
            transcript: { default: undefined },
          },

          parseDOM: [
            {
              tag: 'div.tui-wekaNodeAudioBlock',
              getAttrs: getJsonAttrs,
            },
            {
              tag: 'div.tui-audioBlock',
              getAttrs: getJsonAttrs,
            },
          ],

          toDOM(node) {
            return [
              'div',
              {
                class: 'tui-wekaNodeAudioBlock',
                'data-attrs': JSON.stringify({
                  filename: node.attrs.filename,
                  url: node.attrs.url,
                  mime_type: node.attrs.mime_type,
                  transcript: node.attrs.transcript,
                }),
              },
            ];
          },
        },

        component: AudioBlock,
        componentContext: {
          hasAttachmentNode: this._hasAttachmentNode.bind(this),
          replaceWithAttachment: this._replaceAudioWithAttachment.bind(this),
          removeNode: this.removeNode.bind(this),
          /** @deprecated since Totara 13.3 */
          getFileUrl: () => null,
          getItemId: this._getItemId.bind(this),
          getDownloadUrl: this._getDownloadUrl.bind(this),
          getContextId: this._getContextId.bind(this),
          updateAudioWithTranscript: this._updateAudioWithTranscript.bind(this),
        },
      },
    };
  }

  plugins() {
    return [
      new Plugin({
        key: figurePluginKey,
        props: {
          handleKeyDown(view, event) {
            if (event.key !== 'Enter') {
              return;
            }

            const { state } = view;
            const { selection, doc } = state;

            if (selection.$from.depth === 0) {
              return;
            }

            const node = doc.nodeAt(selection.$from.before());
            if (node.type.name !== 'figure_caption') {
              return;
            }

            // Only access transaction when needed
            const tr = state.tr;

            const $from = doc.resolve(selection.$from.before());

            const paragraphNode = view.state.schema.nodes.paragraph.createAndFill();
            tr.insert($from.after(), paragraphNode);

            // Move the cursor to the newly created paragraph.
            tr.setSelection(
              new TextSelection(tr.doc.resolve($from.after() + 1))
            );

            view.dispatch(tr);

            return true;
          },
        },

        appendTransaction(txns, oldState, state) {
          let { selection, doc } = state;

          let tr = null;
          const getTr = () => {
            if (!tr) {
              tr = state.tr;
            }

            return tr;
          };

          // The node size is the length of nodes plus another two additional nodes at the start
          // and end of the document. For more info see https://prosemirror.net/docs/ref/#model.Node.nodeSize
          doc.nodesBetween(0, doc.nodeSize - 2, (node, pos) => {
            // Delete figures without images
            if (node.type.name === 'figure') {
              if (node.childCount === 2) {
                return;
              }

              getTr();
              if (txns[0] && txns[0].meta && txns[0].meta.paste) {
                // this a paste (and not a delete)
                let oldNode = NodeSelection.create(
                  oldState.doc,
                  tr.mapping.map(pos)
                ).node;
                let len =
                  txns[0].steps[0].slice.content.content[0].textContent.length;

                // Create the new node and place it in the correct location
                let selection = NodeSelection.create(
                  tr.doc,
                  tr.mapping.map(pos)
                );
                let newNode = state.schema.node(
                  oldNode.type,
                  oldNode.attrs,
                  node.content.content[0].content
                );
                selection.replaceWith(tr, newNode);

                // Put the cursor in the correct location
                let cursor = TextSelection.create(
                  tr.doc,
                  tr.mapping.map(pos) + len + 1
                );
                tr.setSelection(cursor);
                return;
              }

              tr.delete(
                tr.mapping.map(pos),
                tr.mapping.map(pos + node.nodeSize)
              );
              return;
            }

            if (node.type.name === 'figure_caption') {
              // Remove empty captions
              const $pos = doc.resolve(pos);

              if (node.childCount !== 0) {
                return;
              }

              // If selection is within the caption, do not remove it
              if (
                selection.head >= $pos.start() &&
                selection.head <= $pos.end()
              ) {
                return;
              }

              const imagePos = $pos.start();

              tr =
                replaceFigureWithImage(
                  getTr,
                  doc,
                  tr ? tr.mapping.map(imagePos) : imagePos
                ) || tr;
            }
          });

          return tr;
        },
      }),
    ];
  }

  toolbarItems() {
    if (!this.editor.fileStorage.enabled) return [];
    return [
      new ToolbarItem({
        group: 'embeds',
        label: langString('embedded_media', 'editor_weka'),
        iconComponent: ImageIcon,
        execute: editor => {
          pickFiles(editor).then(
            /**
             *
             * @param {FileList|Array} files
             * @return {Promise<void>}
             */
            async files => {
              if (files) {
                this._startUploading(files);
              }
            }
          );
        },
      }),
    ];
  }

  /**
   *
   * @param {FileList|Array} files
   * @return {Promise<void>}
   * @private
   */
  async _startUploading(files) {
    try {
      const submitFiles = await this.editor.fileStorage.uploadFiles(
        files,
        this.acceptTypes
      );

      if (submitFiles.length === 0) {
        return;
      }

      const schema = this.editor.state.schema;
      const images = await Promise.all(
        submitFiles.map(({ filename, url, media_type, mime_type }) => {
          if (IMAGE === media_type) {
            return schema.node('image', {
              filename: filename,
              alttext: null,
              url: url,
            });
          } else if (VIDEO === media_type) {
            return schema.node('video', {
              filename: filename,
              url: url,
              mime_type: mime_type,
              subtitle: null,
            });
          } else {
            return schema.node('audio', {
              url: url,
              filename: filename,
              mime_type: mime_type,
              transcript: null,
            });
          }
        })
      );

      this.editor.execute((state, dispatch) => {
        let tr = state.tr;

        tr = safeInsert(
          images.length > 1 ? Fragment.fromArray(images) : images[0]
        )(tr);

        dispatch(tr);
        this.editor.view.focus();
      });
    } catch (e) {
      console.error(e);
      if (e instanceof UploadError) {
        notify({ type: 'error', message: e.message });
      } else {
        const str = langString('error_upload_failed', 'editor_weka');
        loadLangStrings([str]).then(() =>
          notify({ type: 'error', message: str.toString() })
        );
      }
    }
  }

  /**
   *
   * @param {Function}  getRange
   * @param {String}    filename
   * @param {String}    alttext
   *
   * @private
   */
  async _replaceImageWithAttachment(getRange, { filename, alttext, size }) {
    const info = await this._getFileInfo(filename);

    this.editor.execute((state, dispatch) => {
      let range = getRange();
      const $from = this.editor.state.doc.resolve(range.from);
      const parent = $from.parent;

      let transaction = state.tr;

      // We need to remove the figure first
      if (parent && parent.type && parent.type.name === 'figure') {
        const figurePos = $from.before();

        transaction =
          replaceFigureWithImage(
            () => transaction,
            transaction.doc,
            figurePos
          ) || transaction;

        range = {
          from: figurePos,
          to: figurePos + parent.nodeSize,
        };
      }

      let attachment = state.schema.node('attachment', {
        filename: filename,
        url: info.url,
        size: size,
        option: {
          alttext: alttext,
        },
      });

      dispatch(
        transaction.replaceWith(
          range.from,
          range.to,
          state.schema.node('attachments', null, [attachment])
        )
      );
    });
  }

  /**
   * Replaces a figure with the containing image
   * @param getRange
   * @returns {Promise<void>}
   * @private
   */
  async _replaceFigureWithImage(getRange) {
    this.editor.execute((state, dispatch) => {
      const range = getRange();

      let tr =
        replaceFigureWithImage(() => state.tr, state.doc, range.from) || false;

      if (!tr) {
        return;
      }

      dispatch(tr);
    });
  }

  /**
   * Adds a figure & figure_caption to the image. If this operation has already been performed, caption is selected
   * @param getRange
   * @returns {Promise<void>}
   * @private
   */
  async _replaceImageWithFigure(getRange) {
    this.editor.execute((state, dispatch) => {
      const { tr, doc, schema } = state;

      const range = getRange();

      const $from = doc.resolve(range.from);
      const parent = $from.parent;

      // We've already got a figure & fig caption, instead focus the caption
      if (parent && parent.type && parent.type.name === 'figure') {
        const caption = doc.nodeAt(range.from + $from.nodeAfter.nodeSize);

        tr.setSelection(
          TextSelection.between(
            doc.resolve(range.from + 1),
            doc.resolve(range.from + 1 + caption.nodeSize)
          )
        );

        dispatch(tr);
        this.editor.view.focus();
        return;
      }

      const image = doc.nodeAt(range.from);
      const caption = schema.node('figure_caption', null, []);
      const figure = schema.node('figure', null, [image, caption]);

      if (
        !$from.parent.canReplaceWith(
          $from.index(),
          $from.index() + 1,
          figure.type
        )
      ) {
        return;
      }

      tr.replaceWith(range.from, range.to, figure);

      const captionPos = range.from + figure.nodeSize - caption.nodeSize;

      // Set selection to inside the caption
      tr.setSelection(TextSelection.create(tr.doc, captionPos, captionPos));

      dispatch(tr.scrollIntoView());
      this.editor.view.focus();
    });
  }

  /**
   *
   * @param {Function}  getRange
   * @param {String}    filename
   * @param {Number}    size
   * @param {?Object}   subtitle
   * @private
   */
  async _replaceVideoWithAttachment(getRange, { filename, size, subtitle }) {
    const info = await this._getFileInfo(filename);

    this.editor.execute((state, dispatch) => {
      const transaction = state.tr,
        range = getRange();

      let attachmentAttrs = {
        filename: filename,
        url: info.url,
        size: size,
        option: {},
      };

      if (subtitle) {
        attachmentAttrs.option.subtitle = {
          url: subtitle.url,
          filename: subtitle.filename,
        };
      }

      let attachment = state.schema.node('attachment', attachmentAttrs);

      dispatch(
        transaction.replaceWith(
          range.from,
          range.to,
          state.schema.node('attachments', null, [attachment])
        )
      );
    });
  }

  /**
   *
   * @param {Function}    getRange
   * @param {String}      filename
   * @param {String}      url
   * @param {String}      mime_type
   * @param {Object|null} subtitle
   * @return {Promise<void>}
   * @private
   */
  async _updateVideoWithSubtitle(
    getRange,
    { filename, url, mime_type, subtitle }
  ) {
    this.editor.execute((state, dispatch) => {
      const transaction = state.tr,
        range = getRange();

      let nodeAttributes = {
        filename: filename,
        url: url,
        mime_type: mime_type,
      };

      if (subtitle) {
        nodeAttributes.subtitle = {
          filename: subtitle.filename,
          url: subtitle.url,
        };
      } else {
        // set subtitle if subtitleFile is not an object
        nodeAttributes.subtitle = subtitle;
      }

      const video = state.schema.node('video', nodeAttributes);
      dispatch(transaction.replaceWith(range.from, range.to, video));
    });
  }

  /**
   * @param {function} getRange
   * @param {object} attrs
   *
   * @private
   */
  async _updateImage(getRange, attrs) {
    this.editor.execute((state, dispatch) => {
      const tr = state.tr;
      const pos = getRange().from;
      const node = state.doc.nodeAt(pos);

      dispatch(setAttrs(pos, { ...node.attrs, ...attrs })(tr));
    });
  }

  /**
   *
   * @param {Function}  getRange
   * @param {String}    filename
   * @param {Number}    size
   * @private
   */
  async _replaceAudioWithAttachment(getRange, { filename, size, transcript }) {
    const info = await this._getFileInfo(filename);

    this.editor.execute((state, dispatch) => {
      const transaction = state.tr,
        range = getRange();

      let attachmentAttrs = {
        filename: filename,
        url: info.url,
        size: size,
        option: {},
      };

      if (transcript) {
        attachmentAttrs.option.transcript = {
          url: transcript.url,
          filename: transcript.filename,
        };
      }

      let attachment = state.schema.node('attachment', attachmentAttrs);

      dispatch(
        transaction.replaceWith(
          range.from,
          range.to,
          state.schema.node('attachments', null, [attachment])
        )
      );
    });
  }

  async _updateAudioWithTranscript(
    getRange,
    { filename, url, mime_type, transcript }
  ) {
    this.editor.execute((state, dispatch) => {
      const transaction = state.tr,
        range = getRange();

      let nodeAttributes = {
        filename: filename,
        url: url,
        mime_type: mime_type,
      };

      if (transcript) {
        nodeAttributes.transcript = {
          filename: transcript.filename,
          url: transcript.url,
        };
      }

      let audio = state.schema.node('audio', nodeAttributes);
      dispatch(transaction.replaceWith(range.from, range.to, audio));
    });
  }

  _hasAttachmentNode() {
    return this.editor.hasNode('attachment');
  }

  _hasAttachmentsNode() {
    return this.editor.hasNode('attachments');
  }

  /**
   * @private
   * @param {string} filename
   */
  async _getFileInfo(filename) {
    return this.editor.fileStorage.getFileInfo(filename);
  }

  /**
   * @private
   * @param {string} filename
   */
  async _getDownloadUrl(filename) {
    const info = await this._getFileInfo(filename);
    return info.download_url;
  }

  /**
   * Returning the current item's id where the files are uploaded to.
   *
   * @return {Number | null}
   * @private
   */
  _getItemId() {
    return this.editor.fileStorage.getFileStorageItemId();
  }

  _getContextId() {
    return this.editor.identifier.contextId;
  }
}

/**
 * Replaces a figure with the containing image
 * @param {Function} getTr returns transaction
 * @param {object} doc
 * @param {number} imagePos The position of the image
 * @returns {object} The modified transaction
 */
function replaceFigureWithImage(getTr, doc, imagePos) {
  const $imgFrom = doc.resolve(imagePos);
  const figure = $imgFrom.parent;

  // Image is not already within a figure, nothing to remove
  if (figure.type.name !== 'figure') {
    return false;
  }

  const image = doc.nodeAt(imagePos);
  const figurePos = $imgFrom.before();
  const $figureFrom = doc.resolve(figurePos);

  if (
    !$figureFrom.parent.canReplaceWith(
      $figureFrom.index(),
      $figureFrom.indexAfter(),
      image.type
    )
  ) {
    return false;
  }

  return getTr().replaceWith(figurePos, figurePos + figure.nodeSize, image);
}

export default opt => new MediaExtension(opt);
