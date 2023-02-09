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
import { shallowMount } from '@vue/test-utils';
import PlaylistCard from 'totara_playlist/components/card/PlaylistCard';

jest.mock('tui/tui', () => null);

describe('PlaylistCard', () => {
  let wrapper = null;

  beforeAll(() => {
    wrapper = shallowMount(PlaylistCard, {
      mocks: {
        $str(identifier, component, param) {
          return `[${identifier}, ${component} - ${param}]`;
        },

        $url(str) {
          return str;
        },
      },

      propsData: {
        name: 'Hello world',
        instanceId: 1,
        summary: null,
        userId: 15,
        userFullName: 'Bolo bala',
        userProfileImageUrl: 'http://example.com',
        access: 'PUBLIC',
        timeCreated: 'Monday 18th, September, 2019',
        rating: 0,
        totalReactions: 0,
        totalComments: 0,
        sharedbycount: 0,
        extra: JSON.stringify({
          resources: 0,
          actions: false,
          image: 'https://example.com/image.jpg',
        }),
        showBookmark: true,
      },
    });
  });

  it('Checks snapshot', () => {
    expect(wrapper.element).toMatchSnapshot();
  });
});
