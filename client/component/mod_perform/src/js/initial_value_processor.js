/**
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

/**
 * Generates the initial value for an element response.
 * This handles repeating child elements & child elements.
 *
 * @param {Object} element.
 * @param {Object|null} response data.
 * @return Object
 */
export function generateInitialValue(element, response) {
  if (response === null) {
    return { response };
  }

  const childElementConfig = element.element_plugin.child_element_config;
  if (!childElementConfig.supports_child_elements) {
    return { response: response };
  }

  if (childElementConfig.supports_repeating_child_elements) {
    return {
      response: generateRepeatingChildElementInitialValue(element, response),
    };
  }

  if (childElementConfig.supports_child_elements) {
    return {
      response: generateChildElementInitialValues(element, response),
    };
  }
}

/**
 * Generates the initial value for an element with repeating child elements.
 *
 * @param {Object} element.
 * @param {String|null} response data.
 * @return Object
 */
function generateRepeatingChildElementInitialValue(element, response) {
  const childElementConfig = element.element_plugin.child_element_config;
  const repeatingItemIdentifier = childElementConfig.repeating_item_identifier;
  let itemIds = Object.keys(response[repeatingItemIdentifier]);

  itemIds.map(itemId => {
    let parentElementResponse = response[repeatingItemIdentifier][itemId];
    response[repeatingItemIdentifier][
      itemId
    ] = generateChildElementInitialValues(element, parentElementResponse);
  });

  return response;
}

/**
 * Generates the initial value for an element with child elements.
 * @param {Object} parentElement.
 * @param {Object|String|null} parentResponse data.
 */
function generateChildElementInitialValues(parentElement, parentResponse) {
  const childElementConfig = parentElement.element_plugin.child_element_config;
  const childElementResponsesIdentifier =
    childElementConfig.child_element_responses_identifier;

  let childElementIds = Object.keys(
    parentResponse[childElementResponsesIdentifier]
  );

  childElementIds.map(childElementId => {
    let childResponse =
      parentResponse[childElementResponsesIdentifier][childElementId];

    let childElement = parentElement.children.find(
      element => parseInt(element.id) === parseInt(childElementId)
    );

    parentResponse[childElementResponsesIdentifier][
      childElementId
    ] = generateChildElementValue(childElement, childResponse);
  });

  return parentResponse;
}

/**
 * Generates the initial value for a child element response.
 *
 * @param {Object} childElement.
 * @param {Object|String|null} response data.
 * @return Object
 */
function generateChildElementValue(childElement, response) {
  let value = JSON.parse(response.response_data);
  response.response_data = generateInitialValue(childElement, value);

  return response;
}
