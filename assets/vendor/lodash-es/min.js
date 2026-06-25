/**
 * Bundled by jsDelivr using Rollup v2.79.2 and Terser v5.39.0.
 * Original file: /npm/lodash-es@4.17.21/min.js
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
var t="undefined"!=typeof global?global:"undefined"!=typeof self?self:"undefined"!=typeof window?window:{},e="object"==typeof t&&t&&t.Object===Object&&t,n="object"==typeof self&&self&&self.Object===Object&&self,o=(e||n||Function("return this")()).Symbol,r=Object.prototype,l=r.hasOwnProperty,f=r.toString,i=o?o.toStringTag:void 0;var c=Object.prototype.toString;var u=o?o.toStringTag:void 0;function a(t){return null==t?void 0===t?"[object Undefined]":"[object Null]":u&&u in Object(t)?function(t){var e=l.call(t,i),n=t[i];try{t[i]=void 0;var o=!0}catch(t){}var r=f.call(t);return o&&(e?t[i]=n:delete t[i]),r}(t):function(t){return c.call(t)}(t)}function b(t){return"symbol"==typeof t||function(t){return null!=t&&"object"==typeof t}(t)&&"[object Symbol]"==a(t)}function d(t,e){return t<e}function v(t){return t}function y(t){return t&&t.length?function(t,e,n){for(var o=-1,r=t.length;++o<r;){var l=t[o],f=e(l);if(null!=f&&(void 0===i?f==f&&!b(f):n(f,i)))var i=f,c=l}return c}(t,v,d):void 0}export{y as default};
