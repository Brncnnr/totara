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
  @module tui
-->

<script>
import { cloneVNode } from '../../js/internal/vnode';

const triggerEvents = {
  hover: { in: 'mouseenter', out: 'mouseleave' },
  focus: { in: 'focusin', out: 'focusout' },
  click: { toggle: 'click', forceOut: true },
  'click-toggle': { toggle: 'click', forceOut: true },
};

/**
 * Helper class implementing trigger interactions for popover
 *
 * @private
 * @class
 * @param {*} triggers
 */
class PopoverTriggerHelper {
  constructor(triggers, options) {
    this.open = false;
    this._triggers = triggers;
    this._handlers = {
      click: {},
    };
    this._in = {};
    this._finalHandlers = this._getHandlers();
    this.onOpenChange = options.onOpenChange;
    this.onTriggerChange = options.onTriggerChange;
    this.getExcludedElements = options.getExcludedElements;
  }

  set(key, value) {
    this._in[key] = value;

    if (!value && triggerEvents[key].forceOut) {
      this._allOut();
    }

    if (key == 'click') {
      this._onClickSet(value);
    }

    this._update();
  }

  toggle(key) {
    this.set(key, !this._in[key]);
  }

  close() {
    this._allOut();
    this._update();
  }

  getHandlers() {
    return this._finalHandlers;
  }

  destroy() {
    document.removeEventListener('click', this._handlers.click.docClick);
  }

  _allOut() {
    for (const key in this._in) {
      this._in[key] = false;
    }
  }

  _handleSet(key, value) {
    this.set(key, value);
  }

  _handleToggle(key) {
    this.toggle(key);
  }

  _onClickSet(value) {
    document.removeEventListener('click', this._handlers.click.docClick);
    if (value) {
      this._handlers.click.docClick = e => {
        if (this.getExcludedElements().some(x => x && x.contains(e.target))) {
          return;
        }
        this.set('click', false);
      };
      document.addEventListener('click', this._handlers.click.docClick);
    }
  }

  _getHandlers() {
    const handlers = {};
    this._triggers.forEach(trigger => {
      const events = triggerEvents[trigger];
      if (!this._handlers[trigger]) {
        this._handlers[trigger] = {};
      }
      if (events) {
        for (const key in events) {
          if (!this._handlers[trigger][key]) {
            let handler;
            if (key == 'in' || key == 'out') {
              handler = this._handleSet.bind(this, trigger, key == 'in');
            } else if (key == 'toggle') {
              handler = this._handleToggle.bind(this, trigger);
            } else {
              return;
            }
            this._handlers[trigger][key] = handler;
          }
          handlers[events[key]] = this._handlers[trigger][key];
        }
      }
    });
    return handlers;
  }

  _calcOpen() {
    for (const method in this._in) {
      if (this._in[method]) {
        return true;
      }
    }
    return false;
  }

  _update() {
    const isOpen = this._calcOpen();
    this.onTriggerChange(this._in);
    if (this.open === isOpen) {
      return;
    }
    this.open = isOpen;
    this.onOpenChange(this.open);
  }
}

export default {
  props: {
    triggers: Array,
    /* eslint-disable-next-line vue/require-prop-types */
    uiElement: {},
  },

  data() {
    return {
      open: false,
    };
  },

  watch: {
    open(val) {
      this.$emit('open-changed', val);
    },
  },

  created() {
    this.helper = new PopoverTriggerHelper(this.triggers, {
      getExcludedElements: () => [
        this.$el,
        (this.uiElement && this.uiElement.$el) || this.uiElement,
      ],
      onOpenChange: isOpen => {
        this.open = isOpen;
      },
      onTriggerChange: trigger => {
        this.$emit('trigger-type-changed', trigger);
      },
    });
  },

  destroyed() {
    this.helper.destroy();
  },

  methods: {
    close() {
      this.helper.close();
    },
  },

  render: function() {
    const slot = this.$scopedSlots.default && this.$scopedSlots.default();
    let trigger = slot && slot[0];
    if (!trigger) {
      return null;
    }
    trigger = cloneVNode(trigger);
    trigger.data.on = Object.assign(
      {},
      trigger.data.on,
      this.helper.getHandlers()
    );
    return trigger;
  },
};
</script>
