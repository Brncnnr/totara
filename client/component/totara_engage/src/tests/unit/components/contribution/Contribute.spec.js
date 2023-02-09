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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @module totara_engage
 */

import Contribute from 'totara_engage/components/contribution/Contribute';
import { shallowMount } from '@vue/test-utils';

jest.mock('tui/apollo_client', () => null);

describe('Engage Contribute', function() {
  let wrapper = null;

  beforeAll(function() {
    wrapper = shallowMount(Contribute, {
      mocks: {
        $str(id, component) {
          return `${id}, ${component}`;
        },
      },
    });
  });

  it('Checks snapshot', function() {
    expect(wrapper.element).toMatchSnapshot();
  });
});
