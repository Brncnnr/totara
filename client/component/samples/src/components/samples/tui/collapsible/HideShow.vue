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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module samples
-->

<template>
  <div>
    Hide show component for collapsing additional content

    <SamplesExample>
      <HideShow
        :aria-hide-content-text="'Hide this extra content'"
        :aria-region-label="'content region description'"
        :aria-show-content-text="'Show this extra content'"
        :content-before-toggle="contentBeforeToggle"
        :hide-content-text="'Hide content'"
        :mobile-only="forMobile"
        :show-content-text="'Show content'"
        :sticky="sticky"
      >
        <template v-slot:trigger="{ controls, text, toggleContent }">
          <Button
            :aria-controls="controls"
            :styleclass="{ primary: true, small: true, transparent: true }"
            :text="text"
            @click="toggleContent"
          />
        </template>

        <template v-slot:content>
          <div>
            Collapsible content on mobile
            <ol>
              <li>1</li>
              <li>2</li>
              <li>3</li>
              <li>4</li>
              <li>5</li>
              <li>6</li>
              <li>7</li>
              <li>8</li>
              <li>9</li>
              <li>10</li>
              <li>11</li>
              <li>12</li>
              <li>13</li>
              <li>14</li>
              <li>15</li>
              <li>16</li>
              <li>17</li>
              <li>18</li>
              <li>19</li>
              <li>20</li>
            </ol>
          </div>
        </template>
      </HideShow>
    </SamplesExample>

    <SamplesPropCtl>
      <FormRow label="Collapsible only for mobile">
        <RadioGroup v-model="forMobile" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>
      <FormRow label="Display content before toggle">
        <RadioGroup v-model="contentBeforeToggle" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>
      <FormRow label="Toggle sticky (No IE11 support)">
        <RadioGroup v-model="sticky" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>
    </SamplesPropCtl>
  </div>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import FormRow from 'tui/components/form/FormRow';
import HideShow from 'tui/components/collapsible/HideShow';
import Radio from 'tui/components/form/Radio';
import RadioGroup from 'tui/components/form/RadioGroup';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import SamplesPropCtl from 'samples/components/sample_parts/misc/SamplesPropCtl';

export default {
  components: {
    Button,
    FormRow,
    HideShow,
    Radio,
    RadioGroup,
    SamplesExample,
    SamplesPropCtl,
  },

  data() {
    return {
      contentBeforeToggle: false,
      forMobile: false,
      sticky: false,
    };
  },
};
</script>
