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
  @module engage_article
-->

<template>
  <BaseCard
    :data-card-unique="instanceId"
    :href="url"
    class="tui-engageArticleCard"
    :show-footnotes="showFootnotes"
    :footnotes="footnotes"
    @mouseover="$_handleHovered(true)"
    @mouseleave="$_handleHovered(false)"
  >
    <ImageHeader
      slot="header-image"
      :show-cover="hovered"
      class="tui-engageArticleCard__imageheader"
    >
      <div
        slot="image"
        :role="getRoleAttr"
        class="tui-engageArticleCard__image"
        :style="imageStyle"
      >
        <span v-if="extraData.alt" class="sr-only">{{ extraData.alt }}</span>
      </div>

      <div slot="actions" class="tui-engageArticleCard__icons">
        <ButtonIcon
          v-for="(action, i) in actions"
          :key="i"
          :aria-label="action.alt"
          :styleclass="{ primary: false, circle: true }"
        >
          <component :is="action.component" />
        </ButtonIcon>
      </div>
    </ImageHeader>

    <CardHeader slot="header" class="tui-engageArticleCard__header">
      <div slot="first" class="tui-engageArticleCard__bar">
        <BookmarkButton
          v-if="showBookmark"
          size="300"
          :bookmarked="innerBookmarked"
          :primary="false"
          :circle="false"
          :small="true"
          :transparent="true"
          class="tui-engageArticleCard__bookmark"
          @click="updateBookmark"
        />
      </div>

      <a slot="second" class="tui-engageArticleCard__link" :href="url">
        <h3 :id="labelId" class="tui-engageArticleCard__title">
          {{ name }}
        </h3>
      </a>

      <div
        v-if="extraData.timeview"
        slot="third"
        class="tui-engageArticleCard__subTitle"
      >
        <TimeIcon
          size="200"
          :alt="$str('time', 'totara_engage')"
          custom-class="tui-icon--dimmed"
        />
        <span class="tui-engageArticleCard__subTitle-text">{{
          getTimeView
        }}</span>
      </div>
    </CardHeader>

    <div slot="footer" class="tui-engageArticleCard__footer">
      <StatIcon
        v-for="statIcon in statIcons"
        :key="statIcon.type"
        :title="statIcon.title"
        :stat-number="statIcon.statNumber"
      >
        <component :is="statIcon.icon" :title="statIcon.title" />
      </StatIcon>

      <AccessIcon
        :access="access"
        size="300"
        custom-class="tui-engageArticleCard__visibilityIcon"
      />
    </div>
  </BaseCard>
</template>

<script>
import BaseCard from 'totara_engage/components/card/BaseCard';
import ImageHeader from 'totara_engage/components/card/ImageHeader';
import StatIcon from 'totara_engage/components/icons/StatIcon';
import CardHeader from 'totara_engage/components/card/CardHeader';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import ShareIcon from 'tui/components/icons/Share';
import AddToListIcon from 'tui/components/icons/AddToList';
import LikeIcon from 'tui/components/icons/Like';
import CommentIcon from 'tui/components/icons/Comment';
import MoreIcon from 'tui/components/icons/More';
import { cardMixin, AccessManager, TimeViewType } from 'totara_engage/index';
import AccessIcon from 'totara_engage/components/icons/access/computed/AccessIcon';
import TimeIcon from 'tui/components/icons/Time';
import BookmarkButton from 'totara_engage/components/buttons/BookmarkButton';

// GraphQL
import updateBookmark from 'totara_engage/graphql/update_bookmark';

export default {
  components: {
    AccessIcon,
    AddToListIcon,
    BaseCard,
    BookmarkButton,
    ButtonIcon,
    CardHeader,
    ImageHeader,
    MoreIcon,
    ShareIcon,
    StatIcon,
    TimeIcon,
  },

  mixins: [cardMixin],

  data() {
    return {
      actions: [
        {
          alt: this.$str('addtoplaylist', 'engage_article'),
          component: 'AddToListIcon',
        },
        {
          alt: this.$str('share', 'totara_engage'),
          component: 'ShareIcon',
        },
        {
          alt: this.$str('more', 'totara_engage'),
          component: 'MoreIcon',
        },
      ],
      extraData: JSON.parse(this.extra),
      // Assign the value to the inner child, as we do not want to mutate the prop.
      innerBookmarked: this.bookmarked,
      hovered: false,
      statIcons: [],
    };
  },

  computed: {
    getTimeView() {
      if (TimeViewType.isLessThanFive(this.extraData.timeview)) {
        return this.$str('timelessthanfive', 'engage_article');
      } else if (TimeViewType.isFiveToTen(this.extraData.timeview)) {
        return this.$str('timefivetoten', 'engage_article');
      } else if (TimeViewType.isMoreThanTen(this.extraData.timeview)) {
        return this.$str('timemorethanten', 'engage_article');
      }
      return null;
    },

    getRoleAttr() {
      return !this.extraData.alt ? 'presentation' : null;
    },

    imageStyle() {
      return {
        backgroundImage: `url(${this.extraData.image}})`,
      };
    },
  },

  created() {
    this.$_setStatIcons();
  },

  methods: {
    $_handleHovered(hovered) {
      this.hovered = hovered;
    },

    $_setStatIcons() {
      if (AccessManager.isPrivate(this.access)) {
        return;
      }

      const restrictedStatIcons = [
        {
          type: 'reaction',
          title: this.$str(
            'numberoflikes',
            'totara_engage',
            this.totalReactions
          ),
          icon: LikeIcon,
          statNumber: this.totalReactions,
        },
        {
          type: 'comment',
          title: this.$str(
            'numberofcomments',
            'totara_engage',
            this.totalComments
          ),
          icon: CommentIcon,
          statNumber: this.totalComments,
        },
      ];

      if (AccessManager.isRestricted(this.access)) {
        this.statIcons = restrictedStatIcons;
        return;
      }

      if (AccessManager.isPublic(this.access)) {
        this.statIcons = restrictedStatIcons.concat([
          {
            type: 'share',
            title: this.$str(
              'numberofshares',
              'totara_engage',
              this.sharedbycount
            ),
            icon: ShareIcon,
            statNumber: this.sharedbycount,
          },
          {
            type: 'playlistUsage',
            title: this.$str(
              'numberwithinplaylist',
              'engage_article',
              this.extraData.usage
            ),
            icon: AddToListIcon,
            statNumber: this.extraData.usage,
          },
        ]);
        return;
      }
    },

    updateBookmark() {
      this.innerBookmarked = !this.innerBookmarked;
      this.$apollo.mutate({
        mutation: updateBookmark,
        refetchAll: false,
        refetchQueries: ['totara_engage_contribution_cards'],
        variables: {
          itemid: this.instanceId,
          component: 'engage_article',
          bookmarked: this.innerBookmarked,
        },
      });
    },
  },
};
</script>

<lang-strings>
  {
    "engage_article": [
      "addtoplaylist",
      "numberwithinplaylist",
      "timelessthanfive",
      "timefivetoten",
      "timemorethanten"
    ],
    "totara_engage": [
      "more",
      "share",
      "numberofcomments",
      "numberoflikes",
      "numberofshares",
      "time"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-engageArticleCard {
  height: 100%;
  min-height: var(---engage-card-height);

  &__image {
    @include card-header-image(
      var(--engage-card-image-width),
      var(--engage-card-image-height),
      var(--gap-4)
    );
  }

  &__link {
    color: var(--color-neutral-7);
    text-decoration: none;

    &:hover,
    &:focus {
      color: var(--color-neutral-7);
    }
  }

  &__icons {
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    justify-content: center;
    & > * + * {
      margin-left: var(--gap-4);
    }
  }

  &__bar {
    height: var(--gap-4);
  }

  &__bookmark {
    // Negative margin here to neutralise the default redundant edges of icon.
    margin-top: 1px;
    margin-right: calc(var(--gap-2) * -1);
  }

  &__title {
    @include tui-font-heading-x-small();
    margin-right: var(--gap-5);
    color: var(--color-text);
    @include tui-wordbreak--hyphens;
  }

  &__subTitle {
    display: inline-flex;
    align-items: center;
    margin-top: var(--gap-2);
    padding: 0 var(--gap-2) 0 var(--gap-1);
    border: var(--border-width-thin) solid var(--color-neutral-5);
    border-radius: 50px;

    &-text {
      margin-left: var(--gap-1);
    }
  }

  &__footer {
    display: flex;
    align-items: flex-end;

    & > * + * {
      margin-left: var(--gap-3);
    }

    & > :last-child {
      margin: 0 -3px 0 auto;
    }
  }
}
</style>
