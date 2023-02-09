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

  @author Alvin Smith <alvin.smith@totaralearning.com>
  @package engage_article
-->

<template>
  <Card
    class="tui-engageArticleRelatedCard"
    :clickable="true"
    @click="handleClickCard"
  >
    <div class="tui-engageArticleRelatedCard__img" :style="imageStyle">
      <span v-if="imageAlt" class="sr-only">{{ imageAlt }}</span>
    </div>
    <section class="tui-engageArticleRelatedCard__content">
      <a :href="url">
        {{ name }}
      </a>

      <p>
        <span
          v-if="timeviewString"
          class="tui-engageArticleRelatedCard__timeview"
        >
          <TimeIcon
            size="200"
            :alt="$str('time', 'totara_engage')"
            custom-class="tui-engageArticleRelatedCard--dimmed"
          />
          {{ timeviewString }}
        </span>
        <Like
          size="200"
          :alt="$str('like', 'totara_engage')"
          custom-class="tui-engageArticleRelatedCard--dimmed"
        />
        <span>{{ reactions }}</span>
      </p>
    </section>
    <BookmarkButton
      size="300"
      :bookmarked="innerBookmarked"
      :primary="false"
      :circle="false"
      :small="true"
      :transparent="true"
      class="tui-engageArticleRelatedCard__bookmark"
      @click="handleClickBookmark"
    />
  </Card>
</template>

<script>
import Card from 'tui/components/card/Card';
import TimeIcon from 'tui/components/icons/Time';
import Like from 'tui/components/icons/Like';

import { TimeViewType } from 'totara_engage/index';
import BookmarkButton from 'totara_engage/components/buttons/BookmarkButton';

export default {
  components: {
    BookmarkButton,
    Card,
    TimeIcon,
    Like,
  },

  props: {
    resourceId: {
      type: [Number, String],
      required: true,
    },
    name: {
      type: String,
      required: true,
    },
    bookmarked: {
      type: Boolean,
      default: false,
    },
    image: {
      type: String,
      required: true,
    },
    imageAlt: String,
    reactions: {
      type: [Number, String],
      required: true,
    },
    timeview: {
      type: String,
      default: '',
    },
    url: {
      type: String,
      required: true,
    },
  },

  data() {
    return {
      innerBookmarked: this.bookmarked,
    };
  },

  computed: {
    timeviewString() {
      if (TimeViewType.isLessThanFive(this.timeview)) {
        return this.$str('timelessthanfive', 'engage_article');
      }

      if (TimeViewType.isFiveToTen(this.timeview)) {
        return this.$str('timefivetoten', 'engage_article');
      }

      if (TimeViewType.isMoreThanTen(this.timeview)) {
        return this.$str('timemorethanten', 'engage_article');
      }

      return '';
    },
    imageStyle() {
      return {
        backgroundImage: `url(${this.image}})`,
      };
    },
  },

  methods: {
    handleClickBookmark() {
      this.innerBookmarked = !this.innerBookmarked;
      this.$emit('update', this.resourceId, this.innerBookmarked);
    },
    handleClickCard() {
      window.location.href = this.url;
    },
  },
};
</script>

<lang-strings>
  {
    "totara_engage": [
      "time",
      "like"
    ],
    "engage_article": [
      "timelessthanfive",
      "timefivetoten",
      "timemorethanten"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-engageArticleRelatedCard {
  display: flex;
  min-width: 120px;
  height: var(--engage-sidepanel-card-height);
  background-color: var(--color-neutral-1);

  &__img {
    @include card-header-image(
      var(--engage-sidepanel-card-height),
      var(--engage-sidepanel-card-height),
      var(--gap-2),
      'horizontal'
    );
    border-top-left-radius: 3px;
    border-bottom-left-radius: 3px;
  }

  &__content {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    justify-content: space-between;
    margin-left: var(--gap-2);
    padding: var(--gap-4) 0 var(--gap-2) 0;
    overflow: hidden;

    & > * {
      overflow: hidden;
      white-space: nowrap;
      text-overflow: ellipsis;
    }

    > :first-child {
      @include tui-font-heading-label-small();
      @include tui-font-heavy();
      color: inherit;
      text-decoration: none;
    }

    > :last-child {
      display: flex;
      align-items: center;
      margin: 0;
      @include tui-font-body-x-small();
    }
  }

  &__bookmark {
    align-self: flex-start;
    // neutralize the default icon padding
    margin-top: -2px;
  }

  &__timeview {
    display: flex;
    margin-right: var(--gap-4);
    padding: 2px;
    padding-right: var(--gap-1);
    border: var(--border-width-thin) solid var(--color-neutral-5);
    border-radius: 15px;
  }

  &--dimmed {
    color: var(--color-neutral-6);
  }
}
</style>
