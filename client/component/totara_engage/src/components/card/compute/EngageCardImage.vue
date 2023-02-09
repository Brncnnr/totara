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
  @module totara_engage
-->

<template>
  <!--
    A compute component for rendering different card images within engage. This is a wrapper for the image.
   -->
  <component :is="component" v-if="loaded" v-bind="propAttributes" />
</template>

<script>
import tui from 'tui/tui';

// Caching all the type of cards here.
const has = Object.prototype.hasOwnProperty,
  components = {};

export default {
  props: {
    /**
     * We can only use this prop as generic object to catch all the type of card. Then within this component
     * we can actually computing the actual props that is needed for the actual card.
     */
    cardAttribute: {
      type: Object,
      required: true,
      validator(prop) {
        return 'imagetuicomponent' in prop && 'component' in prop;
      },
    },
  },

  data() {
    return {
      loaded: false,
    };
  },

  computed: {
    /**
     * @return {Object}
     */
    propAttributes() {
      const image = Object.assign({}, this.cardAttribute);
      return {
        key: `${image.component}-${image.instanceid}`,
        instanceId: image.instanceid || 0,
        name: image.name,
        image: image.image,
      };
    },

    component() {
      return this.cardAttribute.component;
    },
  },

  watch: {
    cardAttribute: {
      deep: true,
      immediate: true,
      handler(value) {
        this.loaded = false;

        let promise = new Promise(resolve => {
          const { component, imagetuicomponent } = value;

          if (!has.call(components, component)) {
            components[component] = tui.asyncComponent(imagetuicomponent);
          }

          this.$options.components[component] = components[component];
          resolve('done');
        });

        promise.then(() => {
          this.loaded = true;
        });
      },
    },
  },
};
</script>
