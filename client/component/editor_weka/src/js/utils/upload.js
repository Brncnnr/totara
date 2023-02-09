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

import { config } from 'tui/config';
import pending from 'tui/pending';
import FilePicker from 'editor_weka/components/upload/FilePicker';
import Vue from 'vue';

// eslint-disable-next-line no-unused-vars
import Editor from '../Editor';

export class UploadError extends Error {
  constructor(message) {
    super(message);
    this.name = 'UploadError';
  }
}

/**
 *
 * @param {FileList|Array} files
 * @return Array
 */
export function parseFiles(files) {
  if (Array.isArray(files)) {
    return files;
  }

  return Array.prototype.slice.call(files).filter(file => file instanceof File);
}

/**
 *
 * @param {File}            file
 * @param {Number|String}   repositoryId
 * @param {Number|String}   itemId
 * @param {Number|String}   contextId
 * @param {Array}           acceptTypes
 * @param {String}          url
 *
 * @return {Promise<void>}
 */
export async function upload({
  file,
  repositoryId,
  itemId,
  contextId,
  acceptTypes,
  url,
}) {
  return new Promise((resolve, reject) => {
    const data = new FormData();
    const request = new XMLHttpRequest();

    data.append('repo_upload_file', file);
    data.append('title', file.name);
    data.append('sesskey', config.sesskey);
    data.append('repo_id', String(repositoryId));
    data.append('itemid', String(itemId));
    if (contextId != null) {
      data.append('ctx_id', String(contextId));
    }

    acceptTypes.forEach(type => data.append('accepted_types[]', type));

    request.addEventListener('readystatechange', () => {
      if (request.readyState === 4) {
        // Request is done.
        if (request.status !== 200) {
          reject(new Error('Response is not a 200 response'));
          return;
        }
        let result = JSON.parse(request.responseText);

        if (result.error) {
          reject(new UploadError(result.error));
          return;
        }

        resolve(result);
      }
    });

    request.open('POST', url, true);
    request.send(data);
  });
}

/**
 * @param {Editor} editor
 * @return {Promise<void>}
 */
export async function pickFiles(editor) {
  const Component = Vue.extend(FilePicker),
    vm = new Component({
      parent: editor.getParent(),
      propsData: {
        autoTrigger: true,
      },
    });

  const done = pending('weka-file-picker');

  setTimeout(() => {
    done();

    const element = document.createElement('span');
    editor.viewExtrasEl.appendChild(element);
    vm.$mount(element);
  }, 0);

  return new Promise(resolve => {
    vm.$on('picked-files', files => {
      resolve(files);
    });

    vm.$on('dismiss', () => {
      vm.$destroy();
      editor.viewExtrasEl.removeChild(vm.$el);
    });
  });
}
