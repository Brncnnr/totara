<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Brian Barnes <brian.barnes@totaralearning.com>
  @module tui
-->

<template>
  <div
    class="tui-overflowContainer"
    :class="{ 'tui-overflowContainer--wide': isWide }"
  >
    <OverflowDetector v-slot="{ measuring }" @change="overflowChanged">
      <div class="tui-overflowContainer__container">
        <div
          v-for="(item, index) in items"
          v-show="measuring || index < visible - 1 || total == visible"
          :key="index"
          class="tui-overflowContainer__containerItem"
        >
          <slot :item="item" />
        </div>
        <div
          v-if="total > visible"
          class="tui-overflowContainer__containerItem tui-overflowContainer__containerItem--post"
        >
          <slot name="post">
            <Card
              :clickable="true"
              :has-hover-shadow="true"
              class="tui-overflowContainer__containerItem-viewAll"
              :class="{
                'tui-overflowContainer__containerItem-viewAll--only':
                  visible === 1,
              }"
              :url="viewAllLink"
              :url-aria-hidden="true"
              :url-label="$str('viewallx', 'totara_core', total)"
              :url-tabbable="false"
              @click="$emit('show-all')"
            >
              <GoToAll
                :aria-hidden="true"
                class="tui-overflowContainer__containerItem-viewAllIcon"
              />

              <a
                v-if="viewAllLink"
                class="tui-overflowContainer__containerItem-viewAllAction tui-overflowContainer__containerItem-link"
                :href="viewAllLink"
              >
                {{ $str('viewallx', 'totara_core', total) }}
              </a>

              <Button
                v-else
                class="tui-overflowContainer__containerItem-viewAllAction"
                :styleclass="{ stealth: true }"
                :text="$str('viewallx', 'totara_core', total)"
                @click.prevent="$emit('show-all')"
              />
            </Card>
          </slot>
        </div>
      </div>
    </OverflowDetector>
  </div>
</template>

<script>
import OverflowDetector from 'tui/components/util/OverflowDetector';
import Card from 'tui/components/card/Card';
import GoToAll from 'tui/components/icons/GoToAll';
import { throttle } from 'tui/util';
import { getOffsetRect } from 'tui/dom/position';
import ResizeObserver from 'tui/polyfills/ResizeObserver';
import Button from 'tui/components/buttons/Button';

const THROTTLE_UPDATE = 150;
const WIDE_BREAKPOINT = 1000;

export default {
  components: {
    OverflowDetector,
    Card,
    GoToAll,
    Button,
  },

  props: {
    items: Array,
    total: {
      type: Number,
      required: true,
    },
    viewAllLink: String,
  },

  data() {
    return {
      visible: 0,
      isWide: false,
    };
  },

  mounted() {
    this.resizeObserver = new ResizeObserver(
      throttle(this.$_measure, THROTTLE_UPDATE)
    );
    this.resizeObserver.observe(this.$el);
    this.$_measure();
  },

  methods: {
    /**
     * Handles the overflowDetector change event
     *
     * @param {Object} {visible} How many items are visible
     * @emits Event "load-more" - When there are not enough elements to display (and total suggests there should be more)
     */
    overflowChanged({ visible }) {
      this.visible = visible;

      if (this.visible <= this.total && this.visible > this.items.length) {
        this.$emit('load-more');
      }
    },

    /**
     * Adds a CSS cass when the component is wide enough for increased padding
     */
    $_measure() {
      let width = getOffsetRect(this.$el).width;
      if (width > WIDE_BREAKPOINT) {
        this.isWide = true;
      } else {
        this.isWide = false;
      }
    },
  },
};
</script>
<lang-strings>
{
  "totara_core": [
    "viewallx"
  ]
}
</lang-strings>

<style lang="scss">
.tui-overflowContainer {
  &__container {
    display: flex;

    > * + * {
      margin-left: var(--gap-4);

      .tui-overflowContainer--wide & {
        margin-left: var(--gap-6);
      }
    }
  }

  &__containerItem {
    display: flex;
    flex-basis: 200px;
    flex-direction: column;
    flex-grow: 1;
    flex-shrink: 1;
    min-width: 198px;
    max-width: 248px;

    > * {
      flex-grow: 1;
    }

    &--post {
      color: var(--color-state);

      .tui-overflowContainer__containerItem {
        &-viewAll {
          display: flex;
          flex-direction: column;
          flex-grow: 1;
          margin: auto 0;
          text-align: center;
          background-color: var(--color-neutral-3);

          &--only {
            min-height: 200px;
          }
        }

        &-viewAllIcon {
          display: block;
          width: 48px;
          height: 48px;
          margin: auto auto 0 auto;
          padding: 10px;
          font-size: var(--font-size-24);
          border: var(--border-width-thin) solid var(--color-state);
          border-radius: 50%;
        }

        &-viewAllAction {
          margin: var(--gap-2) 0 auto;
        }

        &-link {
          outline: 0;
        }
      }
    }
  }
}
</style>
