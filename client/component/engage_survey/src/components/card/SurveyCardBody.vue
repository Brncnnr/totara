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

  @author Qingyang Liu <Qingyang.liu@totaralearning.com>
  @module engage_survey
-->

<template>
  <div class="tui-engageSurveyCardBody">
    <h3 :id="labelId" class="tui-engageSurveyCardBody__title">
      <span aria-hidden="true" :title="name">{{ displayName }}</span>
      <span class="sr-only">{{ name }}</span>
    </h3>
    <div class="tui-engageSurveyCardBody__footer">
      <p v-if="showEdit" class="tui-engageSurveyCardBody__text">
        {{ $str('noresult', 'engage_survey') }}
      </p>
      <div class="tui-engageSurveyCardBody__container">
        <ActionLink
          v-if="!voted"
          :href="
            $url(url, {
              page: 'vote',
            })
          "
          :text="$str('votenow', 'engage_survey')"
          :styleclass="{ primary: true }"
        />
        <ActionLink
          v-else-if="showEdit"
          :href="
            $url(url, {
              page: 'edit',
            })
          "
          :styleclass="{ primary: true, small: true }"
          :text="$str('editsurvey', 'engage_survey')"
          :aria-label="$str('editsurveyaccessiblename', 'engage_survey', name)"
        />
        <div class="tui-engageSurveyCardBody__icon">
          <AccessIcon :access="access" size="300" />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import AccessIcon from 'totara_engage/components/icons/access/computed/AccessIcon';
import ActionLink from 'tui/components/links/ActionLink';

export default {
  components: {
    AccessIcon,
    ActionLink,
  },

  inheritAttrs: false,

  props: {
    resourceId: {
      type: [Number, String],
      required: true,
    },

    name: {
      required: true,
      type: String,
      default: '',
    },

    access: {
      required: true,
      type: String,
    },

    voted: {
      required: true,
      type: Boolean,
    },

    owned: {
      required: true,
      type: Boolean,
    },

    editAble: {
      required: true,
      type: Boolean,
    },

    bookmarked: {
      type: Boolean,
      default: false,
    },

    labelId: {
      type: String,
      default: '',
    },

    url: {
      type: String,
      default: '/totara/engage/resources/survey/index.php',
    },
  },
  computed: {
    showEdit() {
      return this.owned && this.editAble;
    },

    /**
     * Gets what a visual user will see
     */
    displayName() {
      if (!this.voted || this.editAble || this.name.length < 40) {
        return this.name;
      } else {
        return this.name.substring(0, 37) + String.fromCharCode(8230);
      }
    },
  },
};
</script>

<lang-strings>
  {
    "engage_survey": [
      "votenow",
      "editsurvey",
      "editsurveyaccessiblename",
      "noresult"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-engageSurveyCardBody {
  display: flex;
  flex: 1;
  flex-direction: column;
  width: 100%;
  padding: var(--gap-2) var(--gap-4) var(--gap-2) var(--gap-4);
  overflow: hidden;

  &__title {
    @include tui-font-heading-small-regular();
    flex-grow: 1;
    height: 100%;
    margin: 0;
    overflow: hidden;
    @include tui-wordbreak--hyphens;
  }

  &__footer {
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    justify-content: flex-end;
  }

  &__text {
    @include tui-font-heading-label-small();
    margin-top: 0;
    margin-bottom: var(--gap-4);
  }

  &__container {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    justify-content: space-between;
  }

  &__icon {
    align-self: flex-end;
    margin-right: -3px;
  }
}
</style>
