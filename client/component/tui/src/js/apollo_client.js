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

import { ApolloClient } from 'apollo-client';
import {
  InMemoryCache,
  IntrospectionFragmentMatcher,
} from 'apollo-cache-inmemory';
import { ApolloLink } from 'apollo-link';
import { createHttpLink } from 'apollo-link-http';
import { BatchHttpLink } from 'apollo-link-batch-http';
import { createTuiContextLink } from './internal/apollo/tui_context_link';
import { createDevLink } from './internal/apollo/dev_link';
import { createErrorLink } from './internal/apollo/error_link';
import { createUnloadSuppressionLink } from './internal/apollo/unload_suppression_link';
import { config } from './config';
import { getQueryStringParam, totaraUrl, url } from './util';

const lang = config.locale.language;

const endpoint = totaraUrl('/totara/webapi/ajax.php');

const params = { lang };
// Add 'strings' query string parameter to graphql queries where we have the config option enabled
if (config.locale.debugstringids) {
  const strings = getQueryStringParam('strings');
  if (strings) {
    params.strings = strings;
  }
}

const httpLinkOptions = {
  uri: url(endpoint, params),
  credentials: 'same-origin',
  headers: {
    'X-Totara-Sesskey': config.sesskey,
  },
};

const link = ApolloLink.from([
  createTuiContextLink(),
  createDevLink(),
  createErrorLink(),
  createUnloadSuppressionLink(),
  ApolloLink.split(
    operation => operation.getContext().batch,
    new BatchHttpLink(httpLinkOptions),
    createHttpLink({
      ...httpLinkOptions,
      uri: operation =>
        url(endpoint, {
          operation: operation && operation.operationName,
          ...params,
        }),
    })
  ),
]);

const fragmentMatcher = new IntrospectionFragmentMatcher({
  introspectionQueryResultData: {
    __schema: {
      types: [],
    },
  },
});

const cache = new InMemoryCache({
  addTypename: false,
  freezeResults: true,
  fragmentMatcher,
});

const apolloClient = new ApolloClient({
  link,
  cache,
  assumeImmutableResults: true,
  resolvers: {},
});

// monkey patch .mutate() to add automatic refetch option
const originalMutate = apolloClient.queryManager.mutate;
apolloClient.queryManager.mutate = function(options) {
  return originalMutate
    .apply(apolloClient.queryManager, arguments)
    .then(result => {
      if (options.refetchAll) {
        apolloClient.reFetchObservableQueries();
      }
      return result;
    });
};

export default apolloClient;
