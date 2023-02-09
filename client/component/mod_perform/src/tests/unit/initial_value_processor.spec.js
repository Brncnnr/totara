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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @module mod_perform
 */

import { generateInitialValue } from 'mod_perform/initial_value_processor';

let regularElementConfig = {
  supports_repeating_child_elements: false,
  supports_child_elements: false,
  repeating_item_identifier: null,
  child_element_responses_identifier: null,
};

let childrenElements = [
  {
    id: 9,
    element_plugin: {
      child_element_config: regularElementConfig,
    },
  },
  {
    id: 5,
    element_plugin: {
      child_element_config: regularElementConfig,
    },
  },
];

describe('Processing initial values', () => {
  it('should process initial values for top elements', function() {
    let regularElement = {
      id: 3,
      element_plugin: {
        child_element_config: regularElementConfig,
      },
    };
    let response = generateInitialValue(regularElement, 'regular response');
    expect(response).toBeObject();
    expect(response).toEqual({ response: 'regular response' });
  });
  it('should process initial values for elements with child elements', function() {
    let parentElementWithChildren = {
      id: 2,
      element_plugin: {
        child_element_config: {
          supports_repeating_child_elements: false,
          repeating_item_identifier: 'contentItemResponses',
          supports_child_elements: true,
          child_element_responses_identifier: 'childElementResponses',
        },
      },
      children: childrenElements,
    };

    let elementResponses = {
      childElementResponses: {
        9: {
          response_data: '"My ninth response"',
          child_element_id: '9',
        },
        5: {
          response_data: '"My fifth response"',
          child_element_id: '5',
        },
      },
    };

    let result = generateInitialValue(
      parentElementWithChildren,
      elementResponses
    );
    expect(result).toBeObject();
    expect(result).toEqual({
      response: {
        childElementResponses: {
          9: {
            response_data: { response: 'My ninth response' },
            child_element_id: '9',
          },
          5: {
            response_data: { response: 'My fifth response' },
            child_element_id: '5',
          },
        },
      },
    });
  });
  it('should process initial values for elements with repeating child elements', function() {
    let parentWithRepeatingChildElement = {
      id: 1,
      element_plugin: {
        child_element_config: {
          supports_repeating_child_elements: true,
          supports_child_elements: true,
          repeating_item_identifier: 'contentItemResponses',
          child_element_responses_identifier: 'childElementResponses',
        },
      },
      children: childrenElements,
    };
    let repeatingChildElementResponses = {
      contentItemResponses: {
        1: {
          childElementResponses: {
            9: {
              response_data: '"My ninth response"',
              child_element_id: '9',
            },
            5: {
              response_data: '"My fifth response"',
              child_element_id: '5',
            },
          },
          content_id: 1,
        },
      },
    };

    let result = generateInitialValue(
      parentWithRepeatingChildElement,
      repeatingChildElementResponses
    );

    expect(result).toBeObject();
    expect(result).toEqual({
      response: {
        contentItemResponses: {
          1: {
            childElementResponses: {
              9: {
                response_data: { response: 'My ninth response' },
                child_element_id: '9',
              },
              5: {
                response_data: { response: 'My fifth response' },
                child_element_id: '5',
              },
            },
            content_id: 1,
          },
        },
      },
    });
  });
});
