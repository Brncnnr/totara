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

import { getDraftFile, getRepositoryData } from '../api';
import { upload, parseFiles } from '../utils/upload';
import { langString, loadLangStrings } from 'tui/i18n';
import { getReadableSize } from 'tui/file';
import { notify } from 'tui/notifications';

/**
 * File manager for weka editor. This file storage should only support multiple extensions per editor only.
 * Multiple editors should have multiple instances of this kind.
 */
export default class FileStorage {
  /**
   * The file storage will have to know the component, area and instanceId where it
   * is serving the files.
   *
   * @param {Number|null}         contextId
   * @param {Number|String|null}  itemId
   * @constructor
   */
  constructor({ contextId, itemId }) {
    this.repositoryData = null;

    /**
     * @deprecated since Totara 13.3
     */
    this.files = {};

    this.itemId = null;
    if (itemId) {
      this.itemId = itemId;
    }

    this.contextId = contextId;
  }

  /**
   * @returns {boolean}
   */
  get enabled() {
    // both item id and context id are needed for file uploads
    // item id because we need a place to store the files
    // context id for fetching upload configuration
    return !!this.itemId;
  }

  /**
   * @param {Number} value
   */
  updateFileItemId(value) {
    this.itemId = value;
  }

  /**
   * @return {Number|null}
   */
  getContextId() {
    return this.contextId;
  }

  /**
   * @param {Number}         repository_id
   * @param {String}         url
   * @param {Number|String}  max_bytes
   */
  setRepositoryData({ repository_id, url, max_bytes }) {
    if (this.repositoryData) {
      return;
    }

    this.repositoryData = {
      repositoryId: repository_id,
      url: url,
      maxBytes: parseInt(max_bytes),
    };
  }

  /**
   * @return {Promise<void>}
   * @private
   */
  async _fetchRepositoryData() {
    if (this.repositoryData) {
      return;
    }

    const { repository_id, url, max_bytes } = await getRepositoryData(
      this.contextId
    );
    this.setRepositoryData({
      repository_id,
      url,
      max_bytes,
    });
  }

  /**
   * @deprecated since Totara 13.3 - unused
   * @return {?{
   *   repositoryId: Number,
   *   url: String
   * }}
   */
  getRepositoryData() {
    return this.repositoryData;
  }

  /**
   * @return {Number}
   */
  getFileStorageItemId() {
    return this.itemId;
  }

  /**
   * @deprecated since Totara 13.3
   */
  addFile() {
    console.warn(
      '[editor_weka] FileStorage.addFile had been deprecated and no longer used.'
    );
  }

  /**
   * Finding the file object within the file storage base on the filezname. Since it is
   * already had been stored with the filename as a key.
   *
   * @deprecated since Totara 13.3
   * @return null
   */
  async getFile() {
    console.warn(
      '[editor_weka] FileStorage.getFile had been deprecated ',
      'please use FileStorage.getFileInfo instead'
    );

    return null;
  }

  /**
   * @param {File} rawFile
   * @param {Array} acceptTypes
   *
   * @return {Object}
   */
  async uploadFile(rawFile, acceptTypes) {
    await this._fetchRepositoryData();
    const { repositoryId, url } = this.repositoryData;

    const result = await upload({
      file: rawFile,
      itemId: this.itemId,
      contextId: this.contextId,
      acceptTypes,
      repositoryId,
      url,
    });

    if (result.id && result.file) {
      return await this.getFileInfo(result.file);
    } else if (result.event === 'fileexists') {
      const {
        newfile: { filename },
      } = result;

      return await this.getFileInfo(filename);
    }

    let message = langString('invalid_response', 'editor_weka');
    await loadLangStrings([message]);

    throw new Error(message.toString());
  }

  /**
   * @param {FileList|Array}  files
   * @param {Array}           acceptTypes
   *
   * @return {Array}
   */
  async uploadFiles(files, acceptTypes) {
    files = parseFiles(files);

    if (files.length === 0) {
      return [];
    }

    if (await this.checkFilesSizeExceed(files)) {
      // If it goes to this path, meaning that the repository data should
      // have a max bytes by now.
      const maxFileSize = await getReadableSize(this.repositoryData.maxBytes);

      const str = langString('file_size_exceed', 'editor_weka', maxFileSize);
      await loadLangStrings([str]);

      await notify({ type: 'error', message: str.toString() });
      return [];
    }

    return Promise.all(
      files.map(rawFile => this.uploadFile(rawFile, acceptTypes))
    );
  }

  /**
   *
   * @param {Array|FileList} files
   * @return {Boolean}
   */
  async checkFilesSizeExceed(files) {
    await this._fetchRepositoryData();
    if (!this.repositoryData.maxBytes) {
      // No max bytes was returned from server.
      return true;
    }

    const { maxBytes } = this.repositoryData;
    return files.some(({ size }) => size > maxBytes);
  }

  /**
   *
   * @param {String} filename
   * @return {Promise<DraftFile>}
   */
  async getFileInfo(filename) {
    return getDraftFile({ itemId: this.itemId, filename });
  }
}
