<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
  @module totara_playlist
-->

<template>
  <div>
    <ConfirmationModal
      :open="showWarningModal"
      :title="$str('add_to_playlist', 'totara_playlist')"
      :confirm-button-text="$str('add', 'totara_core')"
      :loading="updatingAdder"
      @confirm="adderUpdate(privacyWarningSelection)"
      @cancel="cancelPrivacyChange"
    >
      <p>{{ privacyWarningMessage }}</p>
    </ConfirmationModal>
    <EngageAdderModal
      :title="$str('selectcontent', 'totara_playlist')"
      :open="canAdd"
      :show-loading-btn="addingItems || updatingAdder"
      :cards="contribution.cards"
      :filter-value="filterValue"
      filter-component="totara_playlist"
      filter-area="adder"
      @added="processAddToPlaylist"
      @cancel="$emit('close', $event)"
      @topic="filterTopic"
      @search="filterSearch"
      @section="filterSection"
    />
  </div>
</template>

<script>
import EngageAdderModal from 'totara_engage/components/modal/EngageAdderModal';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import { config } from 'tui/config';

// GraphQL
import resources from 'totara_playlist/graphql/resources';
import addResources from 'totara_playlist/graphql/add_resources';
import checkItemsAccess from 'totara_playlist/graphql/check_items_access';

// Mixins
import ContributionMixin from 'totara_engage/mixins/contribution_mixin';

export default {
  components: {
    EngageAdderModal,
    ConfirmationModal,
  },

  mixins: [ContributionMixin],

  props: {
    playlistId: {
      type: [String, Number],
      required: true,
    },
    showAdder: Boolean,
  },

  data() {
    return {
      updatingAdder: false,
      addingItems: false,
      showWarningModal: this.openWarningModal,
      privacyWarningMessage: '',
      privacyWarningSelection: '',
    };
  },

  computed: {
    canAdd() {
      return !this.showWarningModal && this.showAdder;
    },
  },

  watch: {
    showAdder(value) {
      this.skipCardsQuery = !value;
    },

    openWarningModal(value) {
      this.showWarningModal = value;
    },
  },

  created() {
    // Overwrite values defined in ContributionMixin.
    this.skipCardsQuery = true;
  },

  apollo: {
    contribution: {
      query: resources,
      fetchPolicy: 'network-only',
      variables() {
        return Object.assign({}, this.filterValue, {
          playlist_id: this.playlistId,
          area: 'adder',
          include_footnotes: false,
          image_preview_mode: 'totara_engage_adder_thumbnail',
          theme: config.theme.name,
        });
      },
      update({ resources: { cursor, cards } }) {
        return {
          cursor: cursor,
          cards: this.canContribute ? this.$_addContributeCard(cards) : cards,
        };
      },
      skip() {
        return this.skipCardsQuery;
      },
    },
  },

  methods: {
    /**
     * @param {String[]} selection
     */
    async processAddToPlaylist(selection) {
      if (this.addingItems) {
        return;
      }

      this.addingItems = true;

      let items = selection.map(item => JSON.parse(item));

      let warning, message;
      try {
        ({ warning, message } = await this.checkAccessSetting(items));
      } finally {
        this.addingItems = false;
      }

      if (warning) {
        // open warningmodal with the selection data
        this.privacyWarningMessage = message;
        this.privacyWarningSelection = selection;
        this.showWarningModal = true;

        return;
      }

      await this.adderUpdate(selection);
    },

    /**
     * Check the access settings of the items
     * @param {Object} items
     * @return {{warning: String, message: String}}
     */
    async checkAccessSetting(items) {
      const {
        data: {
          result: { warning, message },
        },
      } = await this.$apollo.query({
        refetchQueries: [
          'totara_playlist_cards',
          'totara_playlist_get_playlist',
        ],
        query: checkItemsAccess,
        variables: {
          items: items,
          playlist_id: this.playlistId,
        },
      });

      return {
        warning: warning,
        message: message,
      };
    },

    async adderUpdate(selection) {
      if (this.updatingAdder) {
        return;
      }

      this.updatingAdder = true;

      try {
        await this.$apollo.mutate({
          mutation: addResources,
          refetchAll: false,
          refetchQueries: [
            'totara_playlist_cards',
            'totara_playlist_get_playlist',
          ],
          variables: {
            playlistid: this.playlistId,
            resources: selection.map(item => {
              const resource = JSON.parse(item);
              return resource.itemid;
            }),
          },
        });

        this.showWarningModal = false;
      } finally {
        this.$emit('close');
        this.updatingAdder = false;
      }
    },

    cancelPrivacyChange() {
      this.showWarningModal = false;
    },
  },
};
</script>

<lang-strings>
{
  "totara_playlist": [
    "selectcontent",
    "add_to_playlist"
  ],
  "totara_core": [
    "add"
  ]
}
</lang-strings>
