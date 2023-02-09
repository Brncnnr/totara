/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @module mod_approval
 */

import { shallowMount } from '@vue/test-utils';
import { structuralDeepClone } from 'tui/util';
import component from 'mod_approval/components/cards/MicroProfileCard.vue';

const props = {
  user: {
    id: 42,
    fullname: 'Totara User',
    email: 'totara.user@example.com',
    profileimageurl: 'https://example.com/user.jpg',
  },
};

describe('mod_approval/components/cards/MicroProfileCard', () => {
  let propsData;

  beforeEach(() => {
    propsData = structuralDeepClone(props);
  });

  it('Check snapshot with default', () => {
    const wrapper = shallowMount(component, { propsData });
    expect(wrapper.element).toMatchSnapshot();
  });

  it('Check snapshot with large size', () => {
    propsData.size = 'large';
    const wrapper = shallowMount(component, { propsData });
    expect(wrapper.element).toMatchSnapshot();
  });

  it('Check snapshot with readonly', () => {
    propsData.readOnly = true;
    const wrapper = shallowMount(component, { propsData });
    expect(wrapper.element).toMatchSnapshot();
  });

  it('Check snapshot with custom name', () => {
    propsData.name = 'Cus Tom Name';
    const wrapper = shallowMount(component, { propsData });
    expect(wrapper.element).toMatchSnapshot();
  });

  it('Check snapshot with email', () => {
    propsData.showEmail = true;
    const wrapper = shallowMount(component, { propsData });
    expect(wrapper.element).toMatchSnapshot();
  });

  it('Check snapshot with emphasised', () => {
    propsData.emphasiseName = true;
    const wrapper = shallowMount(component, { propsData });
    expect(wrapper.element).toMatchSnapshot();
  });

  it('Check avatarUrl', () => {
    let wrapper;

    delete propsData.user.profileimageurlsmall;
    delete propsData.user.profileimageurl;
    delete propsData.user.card_display;

    propsData.user.profileimageurlsmall = 'https://example.com/small.jpg';
    wrapper = shallowMount(component, { propsData });
    expect(wrapper.vm.avatarUrl).toEqual('https://example.com/small.jpg');

    propsData.user.profileimageurl = 'https://example.com/medium.jpg';
    wrapper = shallowMount(component, { propsData });
    expect(wrapper.vm.avatarUrl).toEqual('https://example.com/medium.jpg');

    propsData.user.card_display = {
      profile_picture_url: 'https://example.com/large.jpg',
    };
    wrapper = shallowMount(component, { propsData });
    expect(wrapper.vm.avatarUrl).toEqual('https://example.com/large.jpg');
  });

  it('Check avatarAlt', () => {
    let wrapper;

    wrapper = shallowMount(component, { propsData });
    expect(wrapper.vm.avatarAlt).toEqual('');

    propsData.user.card_display = { profile_picture_alt: 'DJ ToT@rA' };
    wrapper = shallowMount(component, { propsData });
    expect(wrapper.vm.avatarAlt).toEqual('DJ ToT@rA');
  });
});
