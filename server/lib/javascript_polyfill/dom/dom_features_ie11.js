(function() {

    /*
    *  ES6 custom events polyfill required for IE11 when not using jQuery
    *  https://developer.mozilla.org/en-US/docs/Web/API/CustomEvent/CustomEvent
    */

    (function() {

        if (typeof window.CustomEvent === "function") {
            return false;
        }
        function CustomEvent(event, params) {
            params = params || {bubbles: false, cancelable: false, detail: undefined};
            var evt = document.createEvent('CustomEvent');
            evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail);
            return evt;
        }

        CustomEvent.prototype = window.Event.prototype;

        window.CustomEvent = CustomEvent;
    })();


    /*
    *  ES6 Element.matches() polyfill required for IE11
    *  https://developer.mozilla.org/en-US/docs/Web/API/Element/matches
    */

    if (!Element.prototype.matches) {
        Element.prototype.matches = Element.prototype.msMatchesSelector;
    }

    /*
    *  ES6 Element.closest() polyfill required for IE11
    *  https://developer.mozilla.org/en-US/docs/Web/API/Element/closest
    */

    if (!Element.prototype.closest) {
        Element.prototype.closest = function(s) {
            var el = this;
            if (!document.documentElement.contains(el)) {
                return null;
            }
            do {
                if (el.matches(s)) {
                    return el;
                }
                el = el.parentElement || el.parentNode;
            } while (el !== null && el.nodeType === 1);
            return null;
        };
    }


    /*
    *  ES6 ChildNode.remove() polyfill required for IE11
    *  https://developer.mozilla.org/en-US/docs/Web/API/ChildNode/remove
    */

    (function(arr) {
        arr.forEach(function(item) {
            if (item.hasOwnProperty('remove')) {
                return;
            }
            Object.defineProperty(item, 'remove', {
                configurable: true,
                enumerable: true,
                writable: true,
                value: function remove() {
                    if (this.parentNode !== null) {
                        this.parentNode.removeChild(this);
                    }
                }
            });
        });
    })([Element.prototype, CharacterData.prototype, DocumentType.prototype]);

})();
