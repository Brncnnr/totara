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
  <BaseCard
    :data-card-unique="instanceId"
    :href="url"
    :show-footnotes="showFootnotes"
    :footnotes="footnotes"
    class="tui-playlistCard"
    @remove-resource="$emit('remove-resource', $event)"
    @mouseover="$_handleHovered(true)"
    @mouseleave="$_handleHovered(false)"
  >
    <ImageHeader slot="header-image" :show-cover="hovered">
      <div
        slot="image"
        class="tui-playlistCard__imageHeader"
        :style="imageStyle"
      >
        <span class="sr-only">{{ name }}</span>
        <div class="tui-playlistCard__numberOfResourcesContainer">
          <div class="tui-playlistCard__numberOfResources">
            <!-- This box will be gone if the card is hovered -->
            <p>{{ extraData.resources }}</p>
          </div>
        </div>
      </div>

      <div slot="actions" class="tui-playlistCard__icons">
        <ButtonIcon
          :aria-label="$str('share', 'totara_engage')"
          :styleclass="{ primary: false, circle: true }"
          @click.prevent="$_handleShare"
        >
          <ShareIcon />
        </ButtonIcon>

        <ButtonIcon
          v-if="extraData.actions"
          :aria-label="$str('more', 'totara_engage')"
          :styleclass="{ primary: false, circle: true }"
          @click.prevent="$_handleMoreActions"
        >
          <MoreIcon />
        </ButtonIcon>
      </div>
    </ImageHeader>

    <CardHeader slot="header" class="tui-playlistCard__header">
      <div slot="first" class="tui-playlistCard__bar">
        <BookmarkButton
          v-if="showBookmark"
          size="300"
          :bookmarked="innerBookmarked"
          :primary="false"
          :circle="false"
          :small="true"
          :transparent="true"
          class="tui-playlistCard__bookmark"
          @click="updateBookmark"
        />
      </div>
      <a slot="second" class="tui-playlistCard__link" :href="url">
        <h4 :id="labelId" class="tui-playlistCard__title">
          {{ name }}
        </h4>
      </a>
    </CardHeader>

    <StarRating
      slot="info-content"
      :rating="showRating"
      :read-only="true"
      :increment="0.1"
      :max-rating="5"
      :title="starTitle"
      class="tui-playlistCard__rating"
    />

    <div slot="footer" class="tui-playlistCard__footer">
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
        custom-class="tui-playlistCard__visibilityIcon"
      />
    </div>
  </BaseCard>
</template>

<script>
import BaseCard from 'totara_engage/components/card/BaseCard';
import ImageHeader from 'totara_engage/components/card/ImageHeader';
import CardHeader from 'totara_engage/components/card/CardHeader';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import StatIcon from 'totara_engage/components/icons/StatIcon';
import StarRating from 'totara_engage/components/icons/StarRating';
import ShareIcon from 'tui/components/icons/Share';
import CommentIcon from 'tui/components/icons/Comment';
import MoreIcon from 'tui/components/icons/More';
import { cardMixin, AccessManager } from 'totara_engage/index';
import AccessIcon from 'totara_engage/components/icons/access/computed/AccessIcon';
import BookmarkButton from 'totara_engage/components/buttons/BookmarkButton';

// GraphQL
import updateBookmark from 'totara_engage/graphql/update_bookmark';

export default {
  components: {
    AccessIcon,
    BaseCard,
    BookmarkButton,
    ButtonIcon,
    CardHeader,
    CommentIcon,
    ImageHeader,
    MoreIcon,
    ShareIcon,
    StarRating,
    StatIcon,
  },

  mixins: [cardMixin],

  data() {
    return {
      // Assign to the inner property, so that we don't have to mutate the parent.
      innerBookmarked: this.bookmarked,
      hovered: false,
      extraData: JSON.parse(this.extra),
      statIcons: [],
    };
  },

  computed: {
    showRating() {
      return this.rating;
    },
    starTitle() {
      if (this.extraData.ratingCount <= 1) {
        return this.$str(
          'numberofpersonrating',
          'totara_engage',
          this.extraData.ratingCount
        );
      }

      return this.$str(
        'numberofpeoplerating',
        'totara_engage',
        this.extraData.ratingCount
      );
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
    /**
     * Changing the state of image hovering.
     * @param {boolean} value
     */
    $_handleHovered(value) {
      this.hovered = value;
    },

    $_handleShare() {
      // Todo: update this functionality
    },

    $_handleMoreActions() {
      // Todo: update this functionality
    },

    $_setStatIcons() {
      if (AccessManager.isPrivate(this.access)) {
        return;
      }

      const restrictedStatIcons = [
        {
          type: 'comment',
          icon: CommentIcon,
          title: this.$str(
            'numberofcomments',
            'totara_engage',
            this.totalComments
          ),
          statNumber: this.totalComments,
        },
      ];

      if (AccessManager.isRestricted(this.access)) {
        this.statIcons = restrictedStatIcons;
        return;
      }

      if (AccessManager.isPublic(this.access)) {
        this.statIcons = restrictedStatIcons.concat({
          type: 'share',
          icon: ShareIcon,
          title: this.$str(
            'numberofshares',
            'totara_engage',
            this.sharedbycount
          ),
          statNumber: this.sharedbycount,
        });
        return;
      }
    },

    updateBookmark() {
      this.innerBookmarked = !this.innerBookmarked;
      this.$apollo.mutate({
        mutation: updateBookmark,
        refetchAll: false,
        refetchQueries: [
          'totara_playlist_playlist_links',
          'totara_engage_contribution_cards',
        ],
        variables: {
          itemid: this.instanceId,
          component: 'totara_playlist',
          bookmarked: this.innerBookmarked,
        },
      });
    },
  },
};
</script>

<lang-strings>
  {
    "totara_engage": [
      "numberofcomments",
      "numberofshares",
      "share",
      "more",
      "numberofpeoplerating",
      "numberofpersonrating"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-playlistCard {
  min-height: var(---engage-card-height);

  &__imageHeader {
    position: relative;
    @include card-header-image(
      var(--engage-card-image-width),
      var(--engage-card-image-height)
    );
    background-color: var(--color-primary);
    border-top-left-radius: calc(var(--card-border-radius) - 1px);
    border-top-right-radius: calc(var(--card-border-radius) - 1px);
  }

  &__imageContainer {
    display: flex;
    flex-wrap: wrap;
    align-content: space-between;
    justify-content: space-between;
    width: 100%;
    height: 100%;
  }

  &__link {
    color: var(--color-neutral-7);
    text-decoration: none;

    &:hover,
    &:focus {
      color: var(--color-neutral-7);
    }
  }

  &__numberOfResourcesContainer {
    position: absolute;
    top: 0;
    left: 0;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
  }

  &__numberOfResources {
    width: 60px;
    height: 60px;
    background-color: var(--color-neutral-1);
    border-radius: 50%;

    p {
      margin: 0 auto;
      padding: 0;
      font-weight: 700;
      font-size: var(--font-size-16);
      line-height: 60px;
      text-align: center;
    }
  }

  &__icons {
    display: flex;
    flex-direction: row;
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
    margin-right: calc(var(--gap-3) * -1);
  }

  &__title {
    @include tui-font-heading-x-small();
    margin-right: var(--gap-5);
  }

  &__bookmarkIcon {
    margin: -1px -5px 0 0;
    &--hidden {
      visibility: hidden;
    }
  }

  &__rating {
    align-items: flex-end;
    justify-content: flex-start;
    padding-bottom: 10px;

    .tui-engageStarIcon {
      width: var(--font-size-14);
      height: var(--font-size-14);

      &__filled {
        stop-color: var(--color-chart-background-2);
      }

      &__unfilled {
        stop-color: var(--color-neutral-1);
      }
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
