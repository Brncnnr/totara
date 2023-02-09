/*
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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @module tui
 */
import require_login_category from './require_login_category';
import require_refresh_category from './require_refresh_category';

let categories = [require_login_category, require_refresh_category];

/**
 * Handles pre-defined graphql error categories.
 *
 * @param {Object} payload
 * @returns {Array}
 */
export function handleDefinedCategoryErrors(payload) {
  categories.forEach(category => {
    if (
      typeof category.process !== 'function' ||
      typeof category.name !== 'string'
    ) {
      throw new Error(
        'coding error: category should have both name string property and process function.'
      );
    }
    category.process(payload.graphQLErrors);
  });

  markErrorsAsHandled(
    payload.graphQLErrors,
    categories.map(category => category.name)
  );
}

/**
 * Marks registered category errors as handled.
 *
 * @param {Array} graphQLErrors
 * @param {Array} categories
 * @returns {Array}
 */
function markErrorsAsHandled(graphQLErrors, categories) {
  return graphQLErrors.map(error => {
    if (categories.includes(error.extensions.category)) {
      error.handled = true;
    }

    return error;
  });
}
