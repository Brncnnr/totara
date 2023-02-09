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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @module tui
 */

import { shallowMount } from '@vue/test-utils';
import MiniProfileCard from 'tui/components/profile/MiniProfileCard';

describe('MiniProfileCard', () => {
  it('matches snapshot', () => {
    const wrapper = shallowMount(MiniProfileCard, {
      propsData: {
        display: {
          profile_picture_url: 'example.com',
          profile_picture_alt: 'My Name',
          profile_url: 'example.com',
          display_fields: [],
        },
      },
    });
    expect(wrapper.element).toMatchSnapshot();
  });

  it('hidden from screen readers when no alt for profile', () => {
    const wrapper = shallowMount(MiniProfileCard, {
      propsData: {
        display: {
          profile_picture_url: 'example.com',
          profile_picture_alt: '',
          profile_url: 'example.com',
          display_fields: [],
        },
      },
    });
    expect(wrapper.element).toMatchSnapshot();

    // if there is no link text inside the avatar link, hide from screen readers
    let link = wrapper.find('.tui-miniProfileCard__avatar');
    expect(link.attributes('tabindex')).toBe('-1');
    expect(link.attributes('aria-hidden')).toBe('true');
  });

  it('No link on profile image when no url supplied', () => {
    const wrapper = shallowMount(MiniProfileCard, {
      propsData: {
        display: {
          profile_picture_url: 'example.com',
          profile_picture_alt: 'blah',
          profile_url: '',
          display_fields: [],
        },
      },
    });
    expect(wrapper.element).toMatchSnapshot();

    // If no Url provided, then link shouldn't exist (but element should)
    expect(wrapper.find('a.tui-miniProfileCard__avatar').exists()).toBe(false);
    expect(wrapper.find('.tui-miniProfileCard__avatar').exists()).toBe(true);
  });
});
