/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Simon Tegg <simon.teggfe@totaralearning.com>
 * @module mod_approval
 */
import { createSelector } from 'tui_xstate/util';
import { MY_APPLICATIONS } from '../constants';

const KEY = 'mod_approval_my_applications';

const getMyApplicationsData = context => context[MY_APPLICATIONS][KEY];

export const getMyApplications = createSelector(
  getMyApplicationsData,
  data => data.items
);

export const getMyApplicationsTotal = createSelector(
  getMyApplicationsData,
  data => data.total || 0
);
