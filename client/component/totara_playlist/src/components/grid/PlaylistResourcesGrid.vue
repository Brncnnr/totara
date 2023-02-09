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

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module totara_playlist
-->

<template>
  <div
    class="tui-playlistResourcesGrid"
    :data-max-units="maxUnits"
    :data-single-card-units="cardUnits"
    :data-size="size"
  >
    <template v-if="!$apollo.loading">
      <Droppable
        v-slot="{ attrs, events }"
        :source-id="$id('playlist-grid')"
        source-name="Playlist Resources Grid"
        :accept-drop="() => updateAble"
        layout-interaction="grid-line"
        axis="horizontal"
        @drop="handleDrop"
      >
        <div v-bind="attrs" v-on="events">
          <CoreGrid
            v-for="(row, index) in rows"
            :key="row.index"
            :direction="gridDirection"
            :max-units="maxUnits"
            class="tui-playlistResourcesGrid__row"
          >
            <GridItem
              v-for="(card, i) in row.items"
              :key="i"
              :grows="true"
              :units="cardUnits"
              class="tui-playlistResourcesGrid__card"
            >
              <template v-if="card.component === 'AddNewPlaylistCard'">
                <AddNewPlaylistCard
                  :playlist-id="playlistId"
                  :access="access"
                  @contribute="addResource"
                />
              </template>
              <template v-else-if="card.component !== 'FillSlot'">
                <Draggable
                  v-slot="{ dragging, anyDragging, attrs, events }"
                  :index="index * itemsPerRow + i"
                  type="playlist-grid-item"
                  :value="card"
                  :aria-label="
                    $str('move_element', 'totara_playlist', card.name)
                  "
                >
                  <PropsProvider :provide="{ nativeListeners: events }">
                    <div
                      v-bind="attrs"
                      class="tui-playlistResourcesGrid__card-item"
                      :class="{
                        'tui-playlistResourcesGrid__card-item--dragging':
                          updateAble && dragging,
                      }"
                      v-on="events"
                    >
                      <div
                        v-if="updateAble && (!anyDragging || dragging)"
                        class="tui-playlistResourcesGrid__card-item-moveIcon"
                      >
                        <DragHandleIcon />
                      </div>
                      <EngageCard
                        :card-attribute="card"
                        :aria-labelledby="$id(`row-${index}-${i}-label`)"
                        :label-id="$id(`row-${index}-${i}-label`)"
                        :aria-posinset="index * itemsPerRow + i + 1"
                        :aria-setsize="cards.length"
                        :show-footnotes="updateAble"
                        @refetch="$emit('refetch', $event)"
                      />
                    </div>
                  </PropsProvider>
                </Draggable>
              </template>
              <template v-else>
                <AddNewPlaylistCard
                  :style="{ visibility: 'hidden' }"
                  :playlist-id="playlistId"
                  :access="access"
                  @contribute="addResource"
                />
              </template>
            </GridItem>
          </CoreGrid>
        </div>
      </Droppable>
    </template>
  </div>
</template>

<script>
import CoreGrid from 'tui/components/grid/Grid';
import Draggable from 'tui/components/drag_drop/Draggable';
import DragHandleIcon from 'tui/components/icons/DragHandle';
import Droppable from 'tui/components/drag_drop/Droppable';
import GridItem from 'tui/components/grid/GridItem';
import PropsProvider from 'tui/components/util/PropsProvider';
import EngageCard from 'totara_engage/components/card/compute/EngageCard';
import { calculateRow } from 'totara_engage/index';
import AddNewPlaylistCard from 'totara_playlist/components/card/AddNewPlaylistCard';
import { playlistGrid } from 'totara_playlist/index';

// GraphQL queries
import addResources from 'totara_playlist/graphql/add_resources';

export default {
  components: {
    AddNewPlaylistCard,
    CoreGrid,
    Draggable,
    DragHandleIcon,
    Droppable,
    EngageCard,
    GridItem,
    PropsProvider,
  },

  props: {
    access: {
      type: String,
      required: true,
    },
    cards: {
      type: Array,
      required: true,
    },
    contributable: {
      type: Boolean,
      default: true,
    },
    libraryView: Boolean,
    maxUnits: {
      type: [String, Number],
      required: true,
    },
    playlistId: {
      type: [Number, String],
      required: true,
    },
    size: {
      type: String,
      required: true,
    },
    updateAble: Boolean,
    interactor: {
      type: Object,
      default: () => ({
        user_id: 0,
        can_bookmark: false,
        can_comment: false,
        can_rate: false,
        can_react: false,
        can_share: false,
        can_add_resource: false,
      }),
    },
  },

  data() {
    return {
      loadingComponents: false,
    };
  },

  computed: {
    gridSize() {
      return playlistGrid[this.size];
    },

    itemsPerRow() {
      if (1 >= this.maxUnits) {
        // Max unit is so low, so that we will only accept 1 card for now.
        return 1;
      }

      let items = Math.floor(this.maxUnits / this.cardUnits);
      let maxItems = this.gridSize.maxItemsPerRow;

      if (items > maxItems) {
        return maxItems;
      }

      if (1 >= items) {
        if ('small' === this.size && this.libraryView) {
          // Library view hack - this is terrible !!
          return 2;
        }

        return 1;
      }

      return items;
    },

    rows() {
      return calculateRow(this.allCards, this.itemsPerRow, true);
    },

    cardUnits() {
      if ('small' === this.size && this.libraryView) {
        // Magic number, for library view. Since within library view, we want to show
        // 2 cards per row on a small screen.
        return Math.floor(this.maxUnits / 2);
      }

      return this.gridSize.cardUnits;
    },

    gridDirection() {
      if (this.libraryView) {
        // Library will be treated differently from the playlist grid.
        if ('small' === this.size) {
          return 'horizontal';
        } else if ('xsmall' === this.size) {
          return 'vertical';
        }
      }

      return this.gridSize.cardDirection;
    },

    allCards() {
      if (!this.contributable || !this.interactor.can_add_resource) {
        return this.cards;
      }

      return [
        {
          component: 'AddNewPlaylistCard',
        },
      ].concat(this.cards);
    },
  },

  methods: {
    /**
     *
     * @param {Number} resourceId
     */
    addResource({ resourceId }) {
      this.$apollo
        .mutate({
          mutation: addResources,
          refetchAll: false,
          refetchQueries: [
            'totara_playlist_cards',
            'totara_playlist_get_playlist',
          ],
          variables: {
            playlistid: this.playlistId,
            resources: [resourceId],
          },
        })
        .then(() => {
          this.$emit('resource-added');
        });
    },
    handleDrop(info) {
      if (info.destination.sourceId == info.source.sourceId) {
        const list = this.cards.slice();

        // -1 since card index from 1. The index 0 is the AddResourceCard
        const item = list.splice(info.source.index - 1, 1)[0];

        // Same reason, -1 to access the real index
        list.splice(info.destination.index - 1, 0, item);

        const { instanceid } = item;
        const destinationIndex = info.destination.index - 1;
        const playlistId = this.playlistId;

        this.$emit('resource-reordered', {
          list,
          instanceid,
          destinationIndex,
          playlistId,
        });
      }
    },
  },
};
</script>

<lang-strings>
{
  "totara_playlist": [
    "playlist_resource",
    "move_element"
  ]
}
</lang-strings>

<style lang="scss">
:root {
  --playlistResources-gridCard-max-height: 347px;
}

.tui-playlistResourcesGrid {
  &__row {
    margin-bottom: var(--gap-5);
  }

  &__card {
    max-height: var(--playlistResources-gridCard-max-height);

    &-item {
      position: relative;
      height: 100%;

      &-moveIcon {
        position: absolute;
        top: var(--gap-2);
        left: var(--gap-2);
        display: none;
      }

      &:hover &-moveIcon,
      &--dragging &-moveIcon {
        z-index: 1;
        display: block;
      }
    }
  }
}
</style>
