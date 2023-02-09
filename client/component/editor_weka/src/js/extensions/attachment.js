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
import Attachment from 'editor_weka/components/nodes/Attachment';
import Attachments from 'editor_weka/components/nodes/Attachments';
import AttachmentIcon from 'tui/components/icons/Attachment';
import { ToolbarItem } from '../toolbar';
import { langString, loadLangStrings } from 'tui/i18n';
import { attachment } from '../plugins/attachment';
import { getJsonAttrs } from './util';
import { TextSelection, NodeSelection } from 'ext_prosemirror/state';
import { pickFiles } from '../utils/upload';
import { notify } from 'tui/notifications';
import { findParentNodeOfTypeClosestToPos } from 'editor_weka/lib/prosemirror_utils/selection';

class AttachmentExtension extends BaseExtension {
  constructor(opt) {
    if (typeof opt === 'undefined') {
      opt = {};
    }

    super(opt);
    this.acceptTypes = opt.accepttypes || [];
  }

  nodes() {
    return {
      attachment: {
        schema: {
          inline: true,
          atom: true,
          attrs: {
            // For component, area and context-id: these elements will be decided by the back-end system,
            // not the front-end. Therefore we don't control these properties at the front-end, but we
            // need them for the back-end to be populated.
            filename: { default: undefined },
            size: { default: undefined },
            // For the alt-text/subtitle/transcript of the attachment.
            option: { default: undefined },
            // This is from the support of file_rewrite_plugin_file, it will be picked up by the
            // server side to reformat the plugin file url.
            url: { default: undefined },
          },

          parseDOM: [
            {
              tag: 'div.tui-wekaNodeAttachment',
              getAttrs: getJsonAttrs,
            },
          ],

          toDOM(node) {
            return [
              'div',
              {
                class: 'tui-wekaNodeAttachment',
                'data-attrs': JSON.stringify({
                  filename: node.attrs.filename,
                  option: node.attrs.option,
                  url: node.attrs.url,
                }),
              },
            ];
          },
        },

        component: Attachment,
        componentContext: {
          convertToImage: this._convertToImage.bind(this),
          convertToVideo: this._convertToVideo.bind(this),
          convertToAudio: this._convertToAudio.bind(this),
          updateNode: this._updateNode.bind(this),
          hasImageNode: this._hasImageNode.bind(this),
          hasVideoNode: this._hasVideoNode.bind(this),
          removeNode: this.removeNode.bind(this),
          hasAudioNode: this._hasAudioNode.bind(this),
          getItemId: this._getItemId.bind(this),
          getDownloadUrl: this._getDownloadUrl.bind(this),
        },
      },

      // A collection of attachments.
      attachments: {
        schema: {
          group: 'block',
          allowGapCursor: true,
          content: 'attachment*',
          parseDOM: [{ tag: 'div.tui-wekaNodeAttachments' }],
          toDOM() {
            return [
              'div',
              {
                class: 'tui-wekaNodeAttachments',
              },
              0,
            ];
          },
        },
        component: Attachments,
      },
    };
  }

  toolbarItems() {
    if (!this.editor.fileStorage.enabled) return [];
    return [
      new ToolbarItem({
        group: 'embeds',
        label: langString('attachment', 'editor_weka'),
        iconComponent: AttachmentIcon,
        execute: editor => {
          pickFiles(editor).then(files => {
            this.startUploadingFiles(files);
          });
        },
      }),
    ];
  }

  plugins() {
    if (!this.editor.fileStorage.enabled) return [];
    return [
      attachment({
        onDrop: this._onDropEvent.bind(this),
        onKeyDown: this._onKeyDownEvent.bind(this),
      }),
    ];
  }

  /**
   * Given the transaction of the editor, this function should be able to find the block
   * range of the attachment collection node.
   *
   * $from          is where the collection node begin. The position outside of this node.
   * $to            is where the collection node end. The position outside of this node.
   * $insertPoint   is where the new single attachment node should be added.
   *
   * @param {Transaction} transaction
   * @return {null|{
   *  from: Number,
   *  to: Number,
   *  node: Object,
   *  insertPoint: Number
   * }}
   */
  _findAttachmentCollectionBlock(transaction) {
    const from = transaction.selection.$from,
      node = from.parent;

    if (!node || node.type.name !== 'attachments') {
      // Not a collection node.
      return null;
    }

    // By default, it should be at the current cursor of the selection.
    let insertPoint = from.pos;
    if (!transaction.selection.empty) {
      // However, when the selection is not empty, we need to move it toward to the next point
      insertPoint += 1;
    }

    // Righty, we found the parent node as an attachments.
    return {
      from: from.before(),
      to: from.after(),
      insertPoint: insertPoint,
      node: node,
    };
  }

  /**
   *
   * @param {FileList|Array}  rawFiles
   * @return {Promise<void>}
   */
  async startUploadingFiles(rawFiles) {
    try {
      const submitFiles = await this.editor.fileStorage.uploadFiles(
        rawFiles,
        this.acceptTypes
      );

      if (submitFiles.length === 0) {
        // Nope, nothing to uploaded.
        return;
      }

      const schema = this.editor.state.schema,
        view = this.editor.view;

      let transaction = view.state.tr,
        collectionNode = this._findAttachmentCollectionBlock(transaction);
      // Uploading one by one files, but the place holder will be hold on to the screen
      // as long as all the files are completely finished.

      const attachments = submitFiles.map(({ filename, url, file_size }) => {
        return schema.node('attachment', {
          filename: filename,
          url: url,
          size: file_size,
          option: {},
        });
      });

      if (!collectionNode) {
        // Creating a new collection node, if the current cursor is not in any of it.
        const node = schema.node('attachments', null, attachments),
          position = transaction.selection.$from.pos;

        view.dispatch(transaction.replaceWith(position, position, node));
      } else {
        // Update the current attachments, with inserting, but it need to step inside the attachments.
        transaction.insert(collectionNode.insertPoint, attachments);
        view.dispatch(transaction);
      }
    } catch (e) {
      console.error(e);
      const str = langString('error_upload_failed', 'editor_weka');
      loadLangStrings([str]).then(() =>
        notify({ type: 'error', message: str.toString() })
      );
    }
  }

  /**
   *
   * @param {EditorView} view
   * @param {Event} event
   *
   * @return {Boolean}
   */
  _onKeyDownEvent({ view, event }) {
    const { tr: transaction } = view.state,
      collectionNode = this._findAttachmentCollectionBlock(transaction);

    if (!collectionNode) {
      // Not in the attachments node, skip it.
      return false;
    }

    if (event.key === 'Enter') {
      // It is in collectio node. Start quiting the node, and create a new paragraph
      const node = view.state.schema.nodes.paragraph.createAndFill();
      transaction.insert(collectionNode.to, node);

      // Move the cursor to the newly created paragraph.
      transaction.setSelection(
        new TextSelection(transaction.doc.resolve(collectionNode.to + 1))
      );

      view.dispatch(transaction);
      return true;
    } else if (event.key === 'Backspace') {
      if (!transaction.selection.$from.nodeBefore) {
        // This will be because when the cursor at the beginning of the attachments node.
        return false;
      }

      const attachments = collectionNode.node;

      if (attachments.content.size === 1) {
        // There is only one attachment left, delete the whole block now
        transaction.delete(collectionNode.from, collectionNode.to);
        view.dispatch(transaction);

        return true;
      }
    }

    return false;
  }

  /**
   * @param {EditorView} view
   * @return {Boolean}
   */
  _onDropEvent({ event }) {
    if (
      !event.dataTransfer ||
      !event.dataTransfer.files ||
      !event.dataTransfer.files.length
    ) {
      return false;
    }

    // We need event.preventDefault() so that browser won't open the file.
    event.preventDefault();

    this.startUploadingFiles(event.dataTransfer.files);
    return true;
  }

  /**
   * When converting an attachment to an image, there is a possibility that the attachment is within the
   * collection of attachments. Hence we need to move the cursor to the outside of the collection.
   * @param {Function}  getRange
   * @param {Number}    draftid
   * @param {String}    filename
   * @param {String}    alttext
   * @private
   */
  async _convertToImage(getRange, { filename, alttext }) {
    const info = await this._getFileInfo(filename);

    this.editor.execute((state, dispatch) => {
      const image = state.schema.node('image', {
        filename: filename,
        url: info.url,
        alttext: alttext,
      });

      this._doConvertToMedia(image, state.tr, dispatch, getRange);
    });
  }

  /**
   *
   * @param {Function}    getRange
   * @param {String}      filename
   * @param {String}      mimeType
   * @param {Object|null} subtitle
   * @private
   */
  async _convertToVideo(getRange, { filename, mimeType, subtitle }) {
    const info = await this._getFileInfo(filename);

    this.editor.execute((state, dispatch) => {
      let videoAttrs = {
        filename: filename,
        url: info.url,
        mime_type: mimeType,
        subtitle: subtitle,
      };

      const video = state.schema.node('video', videoAttrs);

      this._doConvertToMedia(video, state.tr, dispatch, getRange);
    });
  }

  /**
   *
   * @param {Function} getRange
   * @param {String} filename
   * @param {Object} option
   * @param {Number} size
   * @param {Object|null} transcript
   *
   * @private
   */
  async _updateNode(getRange, { filename, option, size, transcript }) {
    const info = await this._getFileInfo(filename);

    this.editor.execute((state, dispatch) => {
      const transaction = state.tr,
        range = getRange();

      let audioAttrs = {
        filename: filename,
        url: info.url,
        option: option,
        size: size,
      };

      if (transcript) {
        audioAttrs.transcript = {
          url: transcript.url,
          filename: transcript.filename,
        };
      }

      dispatch(
        transaction.replaceWith(
          range.from,
          range.to,
          state.schema.node('attachment', audioAttrs)
        )
      );

      this.editor.view.focus();
    });
  }

  _hasImageNode() {
    return this.editor.hasNode('image');
  }

  _hasVideoNode() {
    return this.editor.hasNode('video');
  }

  _hasAudioNode() {
    return this.editor.hasNode('audio');
  }

  /**
   *
   * @param getRange
   */
  removeNode(getRange) {
    this.editor.execute((state, dispatch) => {
      const range = getRange();
      let transaction = state.tr;
      const collectionNode = findParentNodeOfTypeClosestToPos(
        transaction.doc.resolve(range.from),
        this.editor.schema.nodes.attachments
      );
      let selection;

      if (collectionNode.node.content.size === 1) {
        selection = NodeSelection.create(transaction.doc, collectionNode.pos);
      } else {
        selection = NodeSelection.create(transaction.doc, range.from);
      }

      // Replace with no second parameter removes the selection
      selection.replace(transaction);
      dispatch(transaction);
    });
  }

  /**
   *
   * @param {Function}    getRange
   * @param {String}      filename
   * @param {String}      mimeType
   * @param {Object|null} transcript
   * @private
   */
  async _convertToAudio(getRange, { filename, mimeType, transcript }) {
    const info = await this._getFileInfo(filename);
    let audioAttrs = {
      filename: filename,
      url: info.url,
      mime_type: mimeType,
      transcript: transcript,
    };

    this.editor.execute((state, dispatch) => {
      const node = state.schema.node('audio', audioAttrs);
      this._doConvertToMedia(node, state.tr, dispatch, getRange);
    });
  }

  /**
   *
   * @param {Object}      node
   * @param {Transaction} transaction
   * @param {Function}    dispatch
   * @param {Function}    getRange
   * @private
   */
  _doConvertToMedia(node, transaction, dispatch, getRange) {
    const range = getRange();
    const collectionNode = findParentNodeOfTypeClosestToPos(
      transaction.doc.resolve(range.from),
      this.editor.schema.nodes.attachments
    );
    let selection;

    if (collectionNode.node.content.size === 1) {
      selection = NodeSelection.create(transaction.doc, collectionNode.pos);
    } else {
      selection = NodeSelection.create(transaction.doc, range.from);
    }

    selection.replaceWith(transaction, node);
    dispatch(transaction);
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
   * Returning the current item's id that is being used to upload all the files for the editor.
   *
   * @return {Number|null}
   * @private
   */
  _getItemId() {
    return this.editor.fileStorage.getFileStorageItemId();
  }
}

export default opt => new AttachmentExtension(opt);
