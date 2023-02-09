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

import PlaylistForm from 'totara_playlist/components/form/PlaylistForm';
import { shallowMount } from '@vue/test-utils';
jest.mock('tui/apollo_client', () => null);
describe('PlaylistForm', function() {
  let wrapper = null;

  beforeAll(function() {
    wrapper = shallowMount(PlaylistForm, {
      mocks: {
        $str(id, component) {
          return `${id}, ${component}`;
        },
        $id() {
          return 'Some random string';
        },
      },
    });
  });

  it('Checks snapshot', function() {
    expect(wrapper.element).toMatchSnapshot();
  });

  it('Checks name changes trigger unsaved changes event', async () => {
    // Setting playlist name should trigger event.
    await wrapper.setData({
      playlist: Object.assign(wrapper.vm.playlist, {
        name: 'New playlist title',
      }),
    });
    expect(wrapper.emitted()['unsaved-changes']).toBeTruthy();
    expect(wrapper.emitted()['unsaved-changes'].length).toBe(1);

    // Also setting summary should not re-trigger the event.
    await wrapper.setData({ summary: 'New summary' });
    expect(wrapper.emitted()['unsaved-changes'].length).toBe(1);
  });

  it('Checks summary changes trigger unsaved changes event', async () => {
    // Setting playlist summary should trigger event.
    await wrapper.setData({ summary: 'New summary' });
    expect(wrapper.emitted()['unsaved-changes']).toBeTruthy();
    expect(wrapper.emitted()['unsaved-changes'].length).toBe(1);

    // Also setting name should not re-trigger the event.
    await wrapper.setData({
      playlist: Object.assign(wrapper.vm.playlist, {
        name: 'New playlist title',
      }),
    });
    expect(wrapper.emitted()['unsaved-changes'].length).toBe(1);
  });
});
