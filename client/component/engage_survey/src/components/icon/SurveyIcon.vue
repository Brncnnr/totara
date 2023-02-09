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

  @author Cody Finegan <cody.finegan@totaralearning.com>
  @module engage_survey
-->

<template>
  <div class="tui-engageSurveyIcon">
    <svg
      class="tui-engageSurveyIcon__img"
      preserveAspectRatio="xMidYMid slice"
      xmlns="http://www.w3.org/2000/svg"
      role="img"
    >
      <title>{{ imageAlt }}</title>
      <desc>{{ imageAlt }}</desc>
      <g fill="none" fill-rule="evenodd">
        <circle
          cx="20"
          cy="20"
          r="19"
          fill="#fff"
          stroke="currentColor"
          stroke-width="2"
        />
        <g transform="translate(11 9)">
          <polygon
            points="1 19.372 17.978 19.372 17.978 2.3947 1 2.3947"
            fill="#fff"
          />
          <path
            d="m0 20.372h18.977v-18.977h-18.977v18.977zm2-2h14.978v-14.977h-14.978v14.977z"
            fill="currentColor"
          />
          <polygon
            points="5.344 3.6587 13.632 3.6587 13.632 1.0007 5.344 1.0007"
            fill="#fff"
          />
          <path
            d="m4.344 4.6577h10.288v-4.658h-10.288v4.658zm2-2h6.288v-0.658h-6.288v0.658z"
            fill="currentColor"
          />
          <line
            x1="4.3444"
            x2="14.632"
            y1="8.2087"
            y2="8.2087"
            stroke="currentColor"
          />
          <line
            x1="4.3444"
            x2="14.632"
            y1="11.209"
            y2="11.209"
            stroke="currentColor"
          />
          <line
            x1="4.3444"
            x2="12.051"
            y1="14.209"
            y2="14.209"
            stroke="currentColor"
          />
        </g>
      </g>
    </svg>
  </div>
</template>

<script>
export default {
  props: {
    imageAlt: {
      type: String,
      required: false,
      default() {
        return this.$str('survey', 'engage_survey');
      },
    },
  },
};
</script>

<lang-strings>
  {
    "engage_survey": [
      "survey"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-engageSurveyIcon {
  text-align: center;

  &__img {
    width: 40px;
    height: 40px;
    color: var(--color-primary);
  }
}
</style>
