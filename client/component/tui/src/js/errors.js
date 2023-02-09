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

import { showErrorInfo } from './internal/error_info';
import { langString } from './i18n';

/**
 * @typedef {Object} ErrorInfo
 * @property {string} type
 * @property {string} title
 * @property {string} debugMessage
 * @property {string} extraInfo
 * @property {string} stack
 */

/**
 * Display the provided error to the user.
 *
 * @param {object} opts
 * @param {string} [opts.error]
 */
export async function showError(error, { vm } = {}) {
  // handle errors that look like apollo errors
  if (error.graphQLErrors) {
    vueApolloErrorHandler(error, vm);
    return;
  }

  showErrorInfo(extractErrorInfo(error, vm));
}

/**
 * Error handler for Vue Apollo.
 *
 * Method signature matches what Vue Apollo expects for an error handler.
 *
 * @param {Error} error
 * @param {Vue} vm
 * @param {string} key Query key.
 * @param {string} type "query" typically
 * @param {object} lastOptions
 * @returns {false}
 */
export function vueApolloErrorHandler(error, vm, key, type, lastOptions) {
  const opName =
    lastOptions && lastOptions.query && getOperationName(lastOptions.query);

  const componentName = vm && getComponentName(vm);

  if (!hasUnhandledErrors(error.graphQLErrors) && error.networkError === null) {
    return false;
  }

  const graphQLErrors = getGraphQLErrors(error);

  if (!graphQLErrors || graphQLErrors.length === 0) {
    // some other error...
    showErrorInfo(extractErrorInfo(error));
    return false;
  }

  // in query foo (mod_test_foo_enabled) used by <MyComponent>
  const context = componentName
    ? `in ${type || 'GraphQL'}${key ? ' "' + key + '"' : ''}` +
      `${opName ? ' (' + opName + ')' : ''} used by ${componentName}`
    : '';

  const errors = graphQLErrors.map(gqlError => {
    const category = gqlError.extensions && gqlError.extensions.category;
    let title, debugMessage;
    if (category === 'graphql' && !gqlError.debugMessage) {
      title = 'GraphQL error';
      debugMessage = gqlError.message;
    } else {
      title = gqlError.message;
      debugMessage = gqlError.debugMessage;
    }

    const extraInfo = [gqlError.path ? `Path: ${gqlError.path.join('.')}` : ''];

    console.error(
      `[GraphQL error] ${title}${context ? ' ' + context : ''}` +
        `${debugMessage ? ': ' + debugMessage : ''}`
    );

    return {
      type: 'server',
      title,
      context,
      debugMessage: debugMessage,
      stack:
        (
          ((gqlError.trace && debugMessage) || '') +
          '\n' +
          (gqlError.trace ? formatPhpStack(gqlError.trace) : '')
        ).trim() || null,
      extraInfo: extraInfo.filter(Boolean).join('\n') || null,
      url: window.location.href,
    };
  });

  showErrorInfo(errors);

  // handled
  return false;
}

/**
 * Get error info from a JS error.
 *
 * @param {Error} error
 * @param {Vue} vm
 * @returns {ErrorInfo[]}
 */
function extractErrorInfo(error, vm) {
  let context = null;
  if (vm) {
    context = `in ${getComponentName(vm)}`;
  }
  return [
    {
      title: langString('error', 'core'),
      context,
      debugMessage: error.message,
      stack: error.stack,
      url: window.location.href,
    },
  ];
}

/**
 * Format a PHP stack trace from the GraphQL API for display.
 *
 * @param {Array<{ call: string, file: string, line: number}>} trace
 * @returns {string}
 */
function formatPhpStack(trace) {
  return trace
    .map((x, i) => `  #${i} ${x.call} called at [${x.file}:${x.line}`)
    .join('\n');
}

/**
 * Extract GraphQLErrors from Apollo error.
 *
 * @param {Error} error Apollo error
 * @returns {object[]}
 */
function getGraphQLErrors(error) {
  if (error.graphQLErrors && error.graphQLErrors.length > 0) {
    return error.graphQLErrors.filter(error => error.handled !== true);
  }

  // sometimes a graphql-error-like object is returned for critical server errors
  if (error.networkError && error.networkError.result) {
    if (Array.isArray(error.networkError.result.errors)) {
      return error.networkError.result.errors;
    }
  }

  return [];
}

/**
 * Get operation name from query.
 *
 * @param {object} query
 * @returns {?string}
 */
function getOperationName(query) {
  try {
    return query.definitions[0].name.value;
  } catch (e) {
    return null;
  }
}

/**
 * Get component name from instance.
 *
 * @param {Vue} vm
 * @returns {string}
 */
function getComponentName(vm) {
  const options = vm.$options || vm.constructor.options;
  let name = options.name || options._componentTag;
  if (!name && options.__file) {
    const match = options.__file.match(/([^/]+)\.vue$/);
    name = match && match[1];
  }
  return name ? `<${name}>` : `<Anonymous>`;
}

/**
 * Checks if all graphql errors have been handled.
 *
 * @param {Array} graphQLErrors.
 * @returns {Boolean}
 */
function hasUnhandledErrors(graphQLErrors) {
  let hasUnhandledError = false;

  if (graphQLErrors && graphQLErrors.length > 0) {
    hasUnhandledError = graphQLErrors.some(error => error.handled !== true);
  }

  return hasUnhandledError;
}
