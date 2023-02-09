!function(){var e={dASd:function(e,t,n){var r={"./nodes/Placeholder":"pUB6","./nodes/Placeholder.vue":"pUB6","./suggestion/Placeholder":"wONr","./suggestion/Placeholder.vue":"wONr"};function o(e){var t=i(e);return n(t)}function i(e){if(!n.o(r,e)){var t=new Error("Cannot find module '"+e+"'");throw t.code="MODULE_NOT_FOUND",t}return r[e]}o.keys=function(){return Object.keys(r)},o.resolve=i,e.exports=o,o.id="dASd"},rbU4:function(e,t,n){var r={"./extension":"oV0U","./extension.js":"oV0U","./plugin":"vyAX","./plugin.js":"vyAX"};function o(e){var t=i(e);return n(t)}function i(e){if(!n.o(r,e)){var t=new Error("Cannot find module '"+e+"'");throw t.code="MODULE_NOT_FOUND",t}return r[e]}o.keys=function(){return Object.keys(r)},o.resolve=i,e.exports=o,o.id="rbU4"},pUB6:function(e,t,n){"use strict";n.r(t),n.d(t,{default:function(){return u}});var r=tui.require("editor_weka/components/nodes/BaseNode"),o=n.n(r),i=tui.require("totara_notification/components/json_editor/nodes/Placeholder"),a={components:{Placeholder:n.n(i)()},extends:o(),computed:{placeholderKey:function(){var e=this.attrs;return e.key?e.key:""},displayName:function(){var e=this.attrs;return e.label?e.label:""}}},l=(0,n("wWJ2").Z)(a,(function(){var e=this,t=e.$createElement;return(e._self._c||t)("Placeholder",{attrs:{"placeholder-key":e.placeholderKey,"display-name":e.displayName}})}),[],!1,null,null,null);l.options.__hasBlocks={script:!0,template:!0};var u=l.exports},wONr:function(e,t,n){"use strict";n.r(t),n.d(t,{default:function(){return p}});var r=tui.require("tui/components/dropdown/Dropdown"),o=n.n(r),i=tui.require("tui/components/dropdown/DropdownItem"),a=n.n(i),l=tui.require("tui/components/loading/Loader"),u=n.n(l),s={components:{Dropdown:o(),DropdownItem:a(),Loader:u()},props:{contextId:{type:[Number,String],required:!0},resolverClassName:{type:String,required:!0},location:{required:!0,type:Object},pattern:{required:!0,type:String}},apollo:{placeholders:{query:{kind:"Document",definitions:[{kind:"OperationDefinition",operation:"query",name:{kind:"Name",value:"weka_notification_placeholder_placeholders"},variableDefinitions:[{kind:"VariableDefinition",variable:{kind:"Variable",name:{kind:"Name",value:"context_id"}},type:{kind:"NonNullType",type:{kind:"NamedType",name:{kind:"Name",value:"param_integer"}}},directives:[]},{kind:"VariableDefinition",variable:{kind:"Variable",name:{kind:"Name",value:"pattern"}},type:{kind:"NonNullType",type:{kind:"NamedType",name:{kind:"Name",value:"param_text"}}},directives:[]},{kind:"VariableDefinition",variable:{kind:"Variable",name:{kind:"Name",value:"resolver_class_name"}},type:{kind:"NonNullType",type:{kind:"NamedType",name:{kind:"Name",value:"param_text"}}},directives:[]}],directives:[],selectionSet:{kind:"SelectionSet",selections:[{kind:"Field",alias:{kind:"Name",value:"placeholders"},name:{kind:"Name",value:"weka_notification_placeholder_placeholders"},arguments:[{kind:"Argument",name:{kind:"Name",value:"context_id"},value:{kind:"Variable",name:{kind:"Name",value:"context_id"}}},{kind:"Argument",name:{kind:"Name",value:"pattern"},value:{kind:"Variable",name:{kind:"Name",value:"pattern"}}},{kind:"Argument",name:{kind:"Name",value:"resolver_class_name"},value:{kind:"Variable",name:{kind:"Name",value:"resolver_class_name"}}}],directives:[],selectionSet:{kind:"SelectionSet",selections:[{kind:"Field",name:{kind:"Name",value:"__typename"},arguments:[],directives:[]},{kind:"Field",name:{kind:"Name",value:"label"},arguments:[],directives:[]},{kind:"Field",name:{kind:"Name",value:"key"},arguments:[],directives:[]}]}}]}}]},fetchPolicy:"network-only",variables:function(){return{pattern:this.pattern,context_id:this.contextId,resolver_class_name:this.resolverClassName}}}},data:function(){return{placeholders:[]}},computed:{showSuggestions:function(){return this.$apollo.loading||this.placeholders.length>0},positionStyle:function(){return{left:"".concat(this.location.x,"px"),top:"".concat(this.location.y,"px")}}},watch:{showSuggestions:function(e){e||this.$emit("dismiss")}},methods:{pickPlaceholder:function(e){var t=e.key,n=e.label;this.$emit("item-selected",{id:t,text:n})}}},c=function(e){e.options.__langStrings={editor_weka:["matching_placeholders"]}},d=(0,n("wWJ2").Z)(s,(function(){var e=this,t=e.$createElement,n=e._self._c||t;return n("div",{staticClass:"tui-wekaPlaceholderSuggestion",style:e.positionStyle},[n("Dropdown",{attrs:{separator:!0,open:e.showSuggestions,"inline-menu":!0},on:{dismiss:function(t){return e.$emit("dismiss")}}},[n("span",{staticClass:"sr-only"},[e._v("\n      "+e._s(e.$str("matching_placeholders","editor_weka"))+":\n    ")]),e._v(" "),e.$apollo.loading?[n("DropdownItem",{attrs:{disabled:!0}},[n("Loader",{attrs:{loading:!0}})],1)]:e._e(),e._v(" "),e._l(e.placeholders,(function(t,r){return n("DropdownItem",{key:r,attrs:{"no-padding":!0},on:{click:function(n){return e.pickPlaceholder(t)}}},[n("span",{staticClass:"tui-wekaPlaceholderSuggestion__label"},[e._v("\n        "+e._s(t.label)+"\n      ")])])}))],2)],1)}),[],!1,null,null,null);c(d),d.options.__hasBlocks={script:!0,template:!0};var p=d.exports},oV0U:function(e,t,n){"use strict";function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){for(var n=0;n<t.length;n++){var r=t[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}function i(e,t){return i=Object.setPrototypeOf?Object.setPrototypeOf.bind():function(e,t){return e.__proto__=t,e},i(e,t)}function a(e){return a="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},a(e)}function l(e,t){if(t&&("object"===a(t)||"function"==typeof t))return t;if(void 0!==t)throw new TypeError("Derived constructors may only return object or undefined");return function(e){if(void 0===e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return e}(e)}function u(e){return u=Object.setPrototypeOf?Object.getPrototypeOf.bind():function(e){return e.__proto__||Object.getPrototypeOf(e)},u(e)}n.r(t),n.d(t,{default:function(){return y}});var s=tui.require("weka_notification_placeholder/components/nodes/Placeholder"),c=n.n(s),d=tui.require("editor_weka/extensions/Base"),p=n.n(d),f=n("vyAX");var m=function(e){!function(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function");e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,writable:!0,configurable:!0}}),Object.defineProperty(e,"prototype",{writable:!1}),t&&i(e,t)}(p,e);var t,n,a,s,d=(a=p,s=function(){if("undefined"==typeof Reflect||!Reflect.construct)return!1;if(Reflect.construct.sham)return!1;if("function"==typeof Proxy)return!0;try{return Boolean.prototype.valueOf.call(Reflect.construct(Boolean,[],(function(){}))),!0}catch(e){return!1}}(),function(){var e,t=u(a);if(s){var n=u(this).constructor;e=Reflect.construct(t,arguments,n)}else e=t.apply(this,arguments);return l(this,e)});function p(){return r(this,p),d.apply(this,arguments)}return t=p,(n=[{key:"nodes",value:function(){return{totara_notification_placeholder:{schema:{group:"inline",inline:!0,attrs:{key:{default:void 0},label:{default:void 0}},parseDOM:[{tag:"span.tui-placeholder__text",getAttrs:function(e){try{return{key:e.getAttribute("data-key"),label:e.getAttribute("data-label")}}catch(e){return{}}}}],toDOM:function(e){return["span",{class:"tui-placeholder__text","data-key":e.attrs.key,"data-label":e.attrs.label},"["+e.attrs.label+"]"]}},component:c()}}}},{key:"plugins",value:function(){return[(0,f.default)(this.editor,this.options.resolver_class_name)]}}])&&o(t.prototype,n),Object.defineProperty(t,"prototype",{writable:!1}),p}(p()),y=function(e){return new m(e)}},vyAX:function(e,t,n){"use strict";n.r(t),n.d(t,{REGEX:function(){return s},default:function(){return c}});var r=tui.require("tui/util"),o=tui.require("ext_prosemirror/state"),i=tui.require("editor_weka/helpers/suggestion"),a=n.n(i),l=tui.require("weka_notification_placeholder/components/suggestion/Placeholder"),u=n.n(l),s=new RegExp("\\[([a-z_:]+]?)?$","ig");function c(e,t){var n=new o.PluginKey("placeholders"),i=new(a())(e);return new o.Plugin({key:n,view:function(){var n=this;return{update:(0,r.debounce)((function(r){var o=n.key.getState(r.state),a=o.text,l=o.active,s=o.range;if(i.destroyInstance(),a&&l&&r.editable){var c=a.slice(1);i.showList({view:r,component:{name:"totara_notification_placeholder",component:u(),attrs:function(e,t){return{key:e,label:t}},props:{resolverClassName:t,contextId:e.identifier.contextId,pattern:c}},state:{text:c,active:l,range:s}})}}),250)}},state:{init:function(){return{active:!1,range:{},text:null}},apply:function(e,t){return s.lastIndex=0,i.apply(e,t,s)}},props:{handleKeyDown:function(e,t){if("Escape"===t.key||"Esc"===t.key)return!!this.getState(e.state).active&&(i.destroyInstance(),e.focus(),t.stopPropagation(),!0)}}})}},wWJ2:function(e,t,n){"use strict";function r(e,t,n,r,o,i,a,l){var u,s="function"==typeof e?e.options:e;if(t&&(s.render=t,s.staticRenderFns=n,s._compiled=!0),r&&(s.functional=!0),i&&(s._scopeId="data-v-"+i),a?(u=function(e){(e=e||this.$vnode&&this.$vnode.ssrContext||this.parent&&this.parent.$vnode&&this.parent.$vnode.ssrContext)||"undefined"==typeof __VUE_SSR_CONTEXT__||(e=__VUE_SSR_CONTEXT__),o&&o.call(this,e),e&&e._registeredComponents&&e._registeredComponents.add(a)},s._ssrRegister=u):o&&(u=l?function(){o.call(this,(s.functional?this.parent:this).$root.$options.shadowRoot)}:o),u)if(s.functional){s._injectStyles=u;var c=s.render;s.render=function(e,t){return u.call(t),c(e,t)}}else{var d=s.beforeCreate;s.beforeCreate=d?[].concat(d,u):[u]}return{exports:e,options:s}}n.d(t,{Z:function(){return r}})}},t={};function n(r){var o=t[r];if(void 0!==o)return o.exports;var i=t[r]={exports:{}};return e[r](i,i.exports,n),i.exports}n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,{a:t}),t},n.d=function(e,t){for(var r in t)n.o(t,r)&&!n.o(e,r)&&Object.defineProperty(e,r,{enumerable:!0,get:t[r]})},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},function(){"use strict";"undefined"!=typeof tui&&tui._bundle.isLoaded("weka_notification_placeholder")?console.warn('[tui bundle] The bundle "weka_notification_placeholder" is already loaded, skipping initialisation.'):(tui._bundle.register("weka_notification_placeholder"),tui._bundle.addModulesFromContext("weka_notification_placeholder",n("rbU4")),tui._bundle.addModulesFromContext("weka_notification_placeholder/components",n("dASd")))}()}();