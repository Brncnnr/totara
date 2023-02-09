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
 * @author Simon Tegg <simon.tegg@totaralearning.com>
 * @module mod_approval
 */
import { createMockClient } from 'mock-apollo-client';

// queries
import getApplication from 'mod_approval/graphql/application';

// fixtures
import application from './application_learner_draft';

export const mockClient = createMockClient();
export const mocks = [
  {
    request: {
      query: getApplication,
    },
    result: {
      data: {
        application,
      },
    },
  },
];

mocks.forEach(({ request, result }) => {
  mockClient.setRequestHandler(request.query, () => Promise.resolve(result));
  // mockClient.setRequestHandler(request.query, () => new Promise());
});
