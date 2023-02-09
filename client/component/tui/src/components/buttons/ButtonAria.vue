<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2022 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Simon Chester <simon.chester@totaralearning.com>
  @module tui
-->

<script>
import {
  cloneVNode,
  mergeListeners,
  normalizeListenerValue,
} from '../../js/internal/vnode';

const KEY_ENTER = 13;
const KEY_SPACE = 32;

export default {
  methods: {
    getDisabled() {
      return this.$el.getAttribute('disabled');
    },
    handleClick(event) {
      if (this.getDisabled()) {
        event.preventDefault();
        // Replicate native behavior: no click events on disabled buttons
        // (including bubbling)
        event.stopPropagation();
      }
    },
    handleKeyDown(event) {
      if (event.keyCode === KEY_SPACE) {
        event.preventDefault();
      } else if (event.keyCode === KEY_ENTER) {
        event.preventDefault();
        this.$el.click();
      }
    },
    handleKeyUp(event) {
      if (event.keyCode === KEY_SPACE) {
        event.preventDefault();
        this.$el.click();
      }
    },
    wrapClickListener(fn) {
      return event => {
        if (!this.getDisabled()) {
          fn(event);
        }
      };
    },
  },

  render() {
    const content = this.$scopedSlots.default && this.$scopedSlots.default();
    if (!content || content.length == 0) {
      return null;
    }
    if (content.length > 1) {
      console.error('ButtonAria may only contain a single child');
      return null;
    }

    let vnode = content[0];

    if (vnode.componentOptions) {
      console.error(
        'The content of ButtonAria must be a plain HTML element, not a component'
      );
      return null;
    }

    vnode = cloneVNode(vnode);

    const isDisabled = vnode.data.attrs && Boolean(vnode.data.attrs.disabled);

    vnode.data.attrs = {
      role: 'button',
      tabindex: isDisabled ? null : '0',
      'aria-disabled': isDisabled ? 'true' : null,
      ...vnode.data.attrs,
    };

    const onListeners = { ...vnode.data.on };

    // Replicate native behavior by suppressing click handlers
    const clickListener =
      onListeners.click && normalizeListenerValue(onListeners.click);
    if (clickListener) {
      onListeners.click = clickListener.map(this.wrapClickListener);
    }

    vnode.data.on = mergeListeners(
      {
        click: this.handleClick,
        keydown: this.handleKeyDown,
        keyup: this.handleKeyUp,
      },
      onListeners
    );

    return vnode;
  },
};
</script>
