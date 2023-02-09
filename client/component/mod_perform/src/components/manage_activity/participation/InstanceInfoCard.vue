<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD’s customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Jaron Steenson <jaron.steenson@totaralearning.com>
  @module mod_perform
-->

<template>
  <ActionCard class="tui-instanceInfoCard" :has-shadow="true">
    <template v-slot:card-body>
      <div>
        <h3 class="tui-instanceInfoCard__title">
          {{ title }}
        </h3>
        <div class="tui-instanceInfoCard__info-section">
          <p
            v-for="infoLine in infoLines"
            :key="infoLine.label"
            class="tui-instanceInfoCard__info-row"
          >
            <span class="tui-instanceInfoCard__info-label">
              <strong>{{ infoLine.label }}</strong>
            </span>
            <span class="tui-instanceInfoCard__info-value">
              {{ infoLine.text }}
            </span>
          </p>
        </div>
      </div>
    </template>
    <template v-slot:card-action>
      <ActionLink
        :href="showAllLink"
        :text="$str('instance_info_card_show_all_button', 'mod_perform')"
        :styleclass="{
          small: true,
        }"
      />
    </template>
  </ActionCard>
</template>

<script>
import ActionCard from 'tui/components/card/ActionCard';
import ActionLink from 'tui/components/links/ActionLink';

export default {
  components: {
    ActionLink,
    ActionCard,
  },
  props: {
    title: {
      type: String,
      required: true,
    },
    infoLines: {
      type: Array,
      required: true,
    },
    showAllLink: {
      type: String,
      required: true,
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "instance_info_card_show_all_button"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-instanceInfoCard {
  &__title {
    @include tui-font-heading-x-small();
    margin-top: 0;
  }

  &__info-section {
    @include tui-font-body();
    display: table;
  }

  &__info-row {
    display: table-row;
  }

  &__info-label {
    display: table-cell;
    padding-right: var(--gap-8);
    padding-bottom: var(--gap-3);
  }

  &__info-value {
    display: table-cell;
    padding-bottom: var(--gap-3);
  }
}
</style>
