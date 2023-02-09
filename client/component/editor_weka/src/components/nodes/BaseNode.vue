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

  @author Simon Chester <simon.chester@totaralearning.com>
  @module editor_weka
-->

<script>
import { wekaNodeUI } from '../directives';
import ExtrasNodeTracker from '../../js/helpers/ExtrasNodeTracker';

export default {
  directives: {
    'weka-node-ui': wekaNodeUI,
  },

  props: {
    nodeInfo: Object,
    contextInfo: Object,
    updateAttrs: Function,
    replaceWith: Function,
    getPos: Function,
    getRange: Function,
    editorDisabled: Boolean,
    selected: Boolean,
    cursorInside: Boolean,
  },

  computed: {
    node() {
      return this.nodeInfo.node;
    },

    attrs() {
      return this.nodeInfo.node.attrs;
    },

    context() {
      return this.contextInfo.context;
    },
  },

  updated() {
    this.$emit('updated');
  },

  methods: {
    /**
     * @param {HTMLElement} el
     * @param {ConstructorParameters<typeof ExtrasNodeTracker>[1]} opt
     */
    trackInExtras(el, opt) {
      return new ExtrasNodeTracker(el, {
        trackedElAnchor: 'top-left',
        positionedElAnchor: 'top-right',
        ...opt,
        trackedEl: opt.trackedEl,
        viewExtrasEl: this.nodeInfo.editor.viewExtrasEl,
      });
    },
  },
};
</script>
