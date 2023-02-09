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

import {
  getProfileUrl,
  validateForProfile,
} from 'mod_approval/item_selectors/user';

describe('test getProfileUrl', () => {
  const template = { fullname: 'Kia Ora', email: 'kia.ora@example.com' };

  it('should return a pound sign if neither id nor a card is provided', () => {
    let input = template;
    let result = getProfileUrl(input);
    expect(result).toEqual('#');
  });

  it('should return a pound sign if a card does not provide profile_url', () => {
    let input = Object.assign(
      {
        card_display: { profile_picture_url: '?' },
      },
      template
    );
    let result = getProfileUrl(input);
    expect(result).toEqual('#');
  });

  it('should return the profile_url if a card provide profile_url', () => {
    let input = Object.assign(
      {
        card_display: { profile_url: 'hooray!' },
      },
      template
    );
    let result = getProfileUrl(input);
    expect(result).toEqual('hooray!');
  });

  it('should use a fallback method if a card does not have profile_url', () => {
    let input = Object.assign(template, {
      id: 42,
    });
    let result = getProfileUrl(input);
    expect(result).toContain('/user/profile.php?id=42');
  });
});

describe('test validateForProfile', () => {
  it('should return false if id is not provided or not valid', () => {
    const template = {
      fullname: 'Kia Ora',
      profileimageurl: '?',
      card_display: { profile_picture_url: '?' },
    };

    let input = template;
    let result = validateForProfile(input);
    expect(result).toBeFalse();

    input = Object.assign({ id: 0 }, input);
    result = validateForProfile(input);
    expect(result).toBeFalse();

    input = Object.assign({ id: -1 }, input);
    result = validateForProfile(input);
    expect(result).toBeFalse();

    input = Object.assign({ id: 'hooray!' }, input);
    result = validateForProfile(input);
    expect(result).toBeFalse();
  });

  it('should return false if fullname is not provided', () => {
    const template = {
      id: 42,
      profileimageurl: '?',
      card_display: { profile_picture_url: '?' },
    };
    let input = template;
    let result = validateForProfile(input);
    expect(result).toBeFalse();

    input = Object.assign({ fullname: '' }, template);
    result = validateForProfile(input);
    expect(result).toBeFalse();
  });

  it('should return false if neither profile nor a card is provided', () => {
    const template = {
      id: 1,
      fullname: 'Kia Ora',
    };
    let input = template;
    let result = validateForProfile(input);
    expect(result).toBeFalse();
  });

  it('should return true if profile image is provided', () => {
    const template = {
      id: 42,
      fullname: 'Kia Ora',
    };
    let input = Object.assign({ profileimageurl: '?' }, template);
    let result = validateForProfile(input);
    expect(result).toBeTrue();

    input = Object.assign({ profileimageurlsmall: '?' }, template);
    result = validateForProfile(input);
    expect(result).toBeTrue();
  });

  it('should return true if a card provides profile picture', () => {
    const template = {
      id: '42',
      fullname: 'Kia Ora',
    };
    let input = Object.assign(
      { card_display: { profile_picture_url: '?' } },
      template
    );
    let result = validateForProfile(input);
    expect(result).toBeTrue();
  });
});
