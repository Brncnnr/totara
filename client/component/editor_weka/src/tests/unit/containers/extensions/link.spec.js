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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @module editor_weka
 */

const createLinkExtension = require('editor_weka/extensions/link').default;

describe('Weka link', () => {
  it('displays mailto links as the email address', async () => {
    // Standard simple mailto link.
    let linkUpdateData = await createLinkExtension()._prepareLinkUpdate({
      url: 'mailto:admin@example.com',
    });

    expect(linkUpdateData.url).toEqual('mailto:admin@example.com');
    expect(linkUpdateData.text).toEqual('admin@example.com');

    // With query strings.
    linkUpdateData = await createLinkExtension()._prepareLinkUpdate({
      url: 'mailto:admin@example.com?subject=Hi.',
    });

    expect(linkUpdateData.url).toEqual('mailto:admin@example.com?subject=Hi.');
    expect(linkUpdateData.text).toEqual('admin@example.com?subject=Hi.');
  });

  it('link_block open in new window works as expected', () => {
    let open = createLinkExtension();
    let linkBlockNode = open.nodes().link_block;
    let attrs = {
      url: 'https://www.example.com',
      open_in_new_window: false,
    };

    let currentWindow = linkBlockNode.schema.toDOM({
      attrs,
    });

    expect(currentWindow).toEqual([
      'a',
      {
        class: 'tui-wekaNodeLinkBlock',
        'data-attrs': JSON.stringify(attrs),
        href: attrs.url,
        target: null,
      },
      attrs.url,
    ]);

    attrs.open_in_new_window = true;
    let openWindow = linkBlockNode.schema.toDOM({
      attrs,
    });

    expect(openWindow).toEqual([
      'a',
      {
        class: 'tui-wekaNodeLinkBlock',
        'data-attrs': JSON.stringify(attrs),
        href: attrs.url,
        target: '_blank',
      },
      attrs.url,
    ]);
  });

  it('link open in new window works as expected', () => {
    let open = createLinkExtension();
    let linkBlockNode = open.marks().link;
    let attrs = {
      href: 'https://www.example.com',
      title: 'title',
      open_in_new_window: false,
    };

    let currentWindow = linkBlockNode.schema.toDOM({
      attrs,
    });

    expect(currentWindow).toEqual([
      'a',
      {
        href: attrs.href,
        title: 'title',
        target: undefined,
      },
      0,
    ]);

    attrs.open_in_new_window = true;
    let openWindow = linkBlockNode.schema.toDOM({
      attrs,
    });

    expect(openWindow).toEqual([
      'a',
      {
        href: attrs.href,
        title: 'title',
        target: '_blank',
      },
      0,
    ]);
  });
});
