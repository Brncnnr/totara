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
 * @author Alvin Smith <alvin.smith@totaralearning.com>
 * @module container_workspace
 */

import { expect } from '@jest/globals';
import { shallowMount } from '@vue/test-utils';
import LibraryTab from 'container_workspace/components/content/tabs/WorkspaceLibraryTab';

describe('WorkspaceLibraryTab', () => {
  it('Contribution card is displayed when a user can contribute', () => {
    let tab = shallowMount(LibraryTab, {
      propsData: {
        workspaceId: 5,
        units: 3,
        gridDirection: 'horizontal',
      },
      data() {
        return {
          interactor: {
            can_share_resources: true,
          },
          contribution: {
            cursor: 1234,
            cards: [],
          },
        };
      },
      mocks: {
        $apollo: { loading: false },
      },
    });

    const addCard = {
      instanceid: 5,
      component: 'WorkspaceContributeCard',
      tuicomponent:
        'container_workspace/components/card/WorkspaceContributeCard',
      // Populate all the default data.
      name: '',
      user: {},
    };

    // User can contribute
    expect(tab.vm.displayCards.length).toBe(1);
    expect(tab.vm.displayCards[0]).toMatchObject(addCard);

    // User can't contribute
    tab.vm.interactor.can_share_resources = false;
    expect(tab.vm.displayCards.length).toBe(0);

    // can't contribute with one card
    const card = {
      id: 123,
      component: 'someJunk',
    };
    tab.vm.contribution.cards.push(card);
    expect(tab.vm.displayCards[0]).toBe(card);
    expect(tab.vm.displayCards.length).toBe(1);

    // and add back in the contribution ability
    tab.vm.interactor.can_share_resources = true;
    expect(tab.vm.displayCards.length).toBe(2);
    expect(tab.vm.displayCards[0]).toMatchObject(addCard);
    expect(tab.vm.displayCards[1]).toBe(card);
  });
});
