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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @module mod_approval
 */

import { OverallProgressState } from '../constants';

/**
 * Get the application page URL for the dashboard.
 *
 * @param {object} application mod_approval_application overall_progress, page_urls.edit and page_urls.view are required
 * @returns {string}
 */
export function getApplicationPageUrl(application) {
  if (!application.page_urls) {
    return '';
  }
  if (application.overall_progress === OverallProgressState.DRAFT) {
    return application.page_urls.edit;
  } else {
    return application.page_urls.view;
  }
}
