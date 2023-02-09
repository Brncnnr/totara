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

import apollo from 'tui/apollo_client';
import repositoryDataQuery from 'editor_weka/graphql/get_repository_data';
import linkMetadataQuery from 'core/graphql/get_linkmetadata';
import draftFileQuery from 'editor_weka/graphql/get_draft_file';

/**
 * @typedef {Object} DraftFile
 * @property {number} file_size
 * @property {string} mime_type
 * @property {string} url
 * @property {string} download_url
 * @property {?string} media_type
 */

/**
 * Get Open Graph metadata for the provided URL.
 *
 * @param {string} url
 * @returns {object}
 */
export async function getLinkMetadata(url) {
  const result = await apollo.query({
    query: linkMetadataQuery,
    variables: { url },
  });
  return result.data.link;
}

/**
 * @param {Number|null} contextId
 * @return {Promise<any>}
 */
export async function getRepositoryData(contextId) {
  const {
    data: { repository_data },
  } = await apollo.query({
    query: repositoryDataQuery,
    variables: { context_id: contextId },
    fetchPolicy: 'no-cache',
  });

  return repository_data;
}

/**
 * @deprecated since Totara 13.3, use getRepositoryData() instead.
 * @param {Number|null} contextId
 * @return {Promise<any>}
 */
export async function prepareDraftFileArea(contextId = null) {
  console.warn(
    '[editor_weka] The function prepareDraftFileArea had been deprecated, ',
    'please use getRepositoryData instead'
  );

  return getRepositoryData(contextId);
}

/**
 * Get media type information.
 *
 * @param {object} opts
 * @param {number} opts.itemId
 * @param {string} opts.filename
 * @return {object}
 *
 * @deprecated since Totara 13.3
 */
export async function getMediaType({ itemId, filename }) {
  console.warn(
    '[editor_weka] getMediaType had been deprecated, please use getDraftFile instead'
  );

  const { media_type, mime_type } = await getDraftFile({ itemId, filename });
  return {
    mediaType: media_type,
    mimeType: mime_type,
  };
}

/**
 *
 * @param {object} opts
 * @returns {Promise<DraftFile>}
 */
export async function getDraftFile({ itemId, filename }) {
  const {
    data: { file },
  } = await apollo.query({
    query: draftFileQuery,
    variables: {
      item_id: itemId,
      filename,
    },
    batch: true,
  });

  return file;
}
