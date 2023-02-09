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

import { ApolloLink, Observable } from 'apollo-link';
import { totaraUrl } from '../../util';

let executeDevQuery;

export function createDevLink() {
  return new ApolloLink((operation, forward) => {
    const { operationName } = operation;
    // avoid breaking Apollo DevTools
    // also send queries without names to dev executor
    if (
      process.env.NODE_ENV == 'development' &&
      (operationName == 'IntrospectionQuery' || operationName == null)
    ) {
      return executeDevQuery(operation);
    }

    return forward(operation);
  });
}

if (process.env.NODE_ENV == 'development') {
  // use require rather than import so it can be removed in optimization pass
  const { print } = require('graphql/language/printer');
  executeDevQuery = function({ operationName, variables, query }) {
    const displayName = operationName
      ? `query "${operationName}"`
      : 'unnamed query';

    // warn so we don't accidentally ship code using unnamed queries
    if (!operationName) {
      console.warn(
        `[webapi-link] Running unnamed query using dev GraphQL executor.\n` +
          `This query must be converted to a named query for production use.` +
          (query.loc && query.loc.source ? '\n\n' + query.loc.source.body : '')
      );
    }

    return new Observable(async observer => {
      try {
        const response = await fetch(
          totaraUrl('/totara/webapi/dev_graphql_executor.php'),
          {
            credentials: 'same-origin',
            method: 'post',
            headers: {
              accept: '*/*',
              'content-type': 'application/json',
            },
            body: JSON.stringify({
              operationName,
              variables,
              query: print(query),
            }),
          }
        );
        const result = await response.json();
        observer.next(result);
      } catch (err) {
        console.warn(
          `[webapi-link] Failed to run ${displayName} using dev GraphQL executor. ` +
            (operationName == 'IntrospectionQuery'
              ? 'Schema information will not be available in Apollo DevTools. '
              : '') +
            'Please make sure GRAPHQL_DEVELOPMENT_MODE is defined in config.php.'
        );

        if (operationName == 'IntrospectionQuery') {
          // Apollo DevTools crashes if we report the error, so let's just pretend we got a response
          observer.next({});
        } else {
          observer.error(err);
        }
      }
      observer.complete();
    });
  };
}
