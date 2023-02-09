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
 * @module tui
 */

import { ApolloLink } from 'apollo-link';
import pending from '../../pending';

/**
 * Manipulate query context as needed for server-side support
 */
export function createTuiContextLink() {
  return new ApolloLink((operation, forward) => {
    const context = operation.getContext();
    let headers = context.headers;

    // set nosession flag if needed
    // this isn't really used at the moment, but apollo-link-batch-http batches
    // operations with different headers separately
    if (
      operation.operationName &&
      operation.operationName.endsWith('_nosession')
    ) {
      headers = Object.assign({}, headers, { 'X-Totara-Nosession': '1' });
    }

    operation.setContext({
      headers,
      http: {
        includeExtensions: true,
        // don't need the query, only the operation name
        includeQuery: false,
      },
    });
    // track pending request
    const done = pending('apollo-request');
    return forward(operation).map(data => {
      done();

      if (data.extensions && data.extensions.performance_data) {
        const { db, realtime, memory } = data.extensions.performance_data.core;
        window.dispatchEvent(
          new CustomEvent('graphql-event', {
            detail: {
              operationName: operation.operationName,
              db: db,
              realtime: realtime,
              ram: memory.total,
              ramPeak: memory.peak,
            },
          })
        );
      }
      return data;
    });
  });
}
