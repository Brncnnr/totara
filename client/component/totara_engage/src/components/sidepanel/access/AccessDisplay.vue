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
  @module totara_engage
-->

<template>
  <div class="tui-engageAccessDisplay">
    <div class="tui-engageAccessDisplay__accessIcon">
      <div class="tui-engageAccessDisplay__accessIcon-icons">
        <AccessIcon :access="accessValue" size="300" />
        <span>{{ viewLabel }}</span>
      </div>
      <div
        v-if="showButton"
        class="tui-engageAccessDisplay__accessIcon-shareButton"
      >
        <Button
          :text="$str('share', 'totara_engage')"
          :styleclass="{ primary: true, small: true }"
          @click="$emit('request-open')"
        />
      </div>
    </div>

    <div v-if="timeView" class="tui-engageAccessDisplay__timeView">
      <TimeIcon
        size="300"
        :alt="$str('time', 'totara_engage')"
        custom-class="tui-icon--dimmed"
      />
      {{ getTimeView }}
    </div>

    <div v-if="topics.length" class="tui-engageAccessDisplay__topics">
      <TagIcon
        size="300"
        :alt="$str('tags', 'totara_engage')"
        custom-class="tui-icon--dimmed"
      />

      <template v-for="(topic, index) in topics">
        <Tag
          :key="index"
          :text="topic.value"
          :href="$url('/totara/catalog/index.php?' + topic.catalog)"
          class="tui-engageAccessDisplay__topic"
        />
      </template>
    </div>

    <div v-if="showButton" class="tui-engageAccessDisplay__editSettings">
      <Button
        :text="$str('editsettings', 'totara_engage')"
        :styleclass="{ transparent: true, small: true }"
        @click="$emit('request-open')"
      />
    </div>
  </div>
</template>

<script>
import TagIcon from 'tui/components/icons/Tags';
import Tag from 'tui/components/tag/Tag';
import TimeIcon from 'tui/components/icons/Time';
import { AccessManager, TimeViewType } from 'totara_engage/index';
import AccessIcon from 'totara_engage/components/icons/access/computed/AccessIcon';
import Button from 'tui/components/buttons/Button';

export default {
  components: {
    AccessIcon,
    TagIcon,
    TimeIcon,
    Tag,
    Button,
  },

  props: {
    accessValue: {
      type: String,
      required: true,
      validator(prop) {
        return AccessManager.isValid(prop);
      },
    },

    topics: {
      type: [Array, Object],
      default() {
        return [];
      },

      validator(topics) {
        topics = Array.prototype.slice.call(topics);
        for (let i in topics) {
          if (!Object.prototype.hasOwnProperty.call(topics, i)) {
            continue;
          }

          if (!topics[i].value) {
            return false;
          }
        }

        return true;
      },
    },

    timeView: {
      type: String,
      default: null,
    },
    showButton: {
      type: Boolean,
      default: true,
    },
  },
  computed: {
    viewLabel() {
      if (AccessManager.isPublic(this.accessValue)) {
        return this.$str('viewpublic', 'totara_engage');
      } else if (AccessManager.isRestricted(this.accessValue)) {
        return this.$str('viewrestricted', 'totara_engage');
      }

      return this.$str('viewprivate', 'totara_engage');
    },

    getTimeView() {
      if (TimeViewType.isLessThanFive(this.timeView)) {
        return this.$str('timelessthanfive', 'engage_article');
      } else if (TimeViewType.isFiveToTen(this.timeView)) {
        return this.$str('timefivetoten', 'engage_article');
      } else if (TimeViewType.isMoreThanTen(this.timeView)) {
        return this.$str('timemorethanten', 'engage_article');
      }

      return null;
    },
  },
};
</script>

<lang-strings>
  {
    "totara_engage": [
      "viewprivate",
      "viewpublic",
      "viewrestricted",
      "tags",
      "time",
      "share",
      "editsettings"
    ],
    "engage_article": [
      "timelessthanfive",
      "timefivetoten",
      "timemorethanten"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-engageAccessDisplay {
  &__accessIcon {
    display: flex;
    &-icons {
      display: flex;
      align-items: center;
      > :first-child {
        margin-right: var(--gap-2);
      }
    }
    &-shareButton {
      margin-left: var(--gap-4);

      @media (max-width: $tui-screen-md) {
        margin-left: var(--gap-2);
      }
    }
  }

  &__timeView {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    margin-top: var(--gap-2);
    > :first-child {
      margin-right: var(--gap-2);
    }
  }

  &__topics {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    margin-top: var(--gap-2);
    line-height: normal;

    > :first-child {
      margin-right: var(--gap-2);
      color: var(--color-neutral-6);
    }
  }

  &__topic {
    @include tui-font-body-small();
    margin-top: var(--gap-1);
    margin-right: var(--gap-1);
  }

  &__editSettings {
    margin-top: var(--gap-2);
  }
}
</style>
