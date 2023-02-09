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
 * @module totara_playlist
 */

import AddNewPlaylistCard from 'totara_playlist/components/card/AddNewPlaylistCard';
import { shallowMount } from '@vue/test-utils';

jest.mock('tui/apollo_client', () => null);
import { AccessManager } from 'totara_engage/index';

describe('AddNewPlaylistCard', function() {
  let wrapper = null;

  beforeAll(function() {
    wrapper = shallowMount(AddNewPlaylistCard, {
      propsData: {
        playlistId: 1,
        access: AccessManager.PRIVATE,
      },

      mocks: {
        $str(a, b) {
          return `${a}-${b}`;
        },
      },
    });
  });

  it('Checks snapshot', function() {
    expect(wrapper.element).toMatchSnapshot();
  });
});
