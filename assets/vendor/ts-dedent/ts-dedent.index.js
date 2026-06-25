/**
 * Bundled by jsDelivr using Rollup v2.79.2 and Terser v5.39.0.
 * Original file: /npm/ts-dedent@2.2.0/esm/index.js
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
function n(n){for(var t=[],r=1;r<arguments.length;r++)t[r-1]=arguments[r];var a=Array.from("string"==typeof n?[n]:n);a[a.length-1]=a[a.length-1].replace(/\r?\n([\t ]*)$/,"");var e=a.reduce((function(n,t){var r=t.match(/\n([\t ]+|(?!\s).)/g);return r?n.concat(r.map((function(n){var t,r;return null!==(r=null===(t=n.match(/[\t ]/g))||void 0===t?void 0:t.length)&&void 0!==r?r:0}))):n}),[]);if(e.length){var c=new RegExp("\n[\t ]{"+Math.min.apply(Math,e)+"}","g");a=a.map((function(n){return n.replace(c,"\n")}))}a[0]=a[0].replace(/^\r?\n/,"");var i=a[0];return t.forEach((function(n,t){var r=i.match(/(?:^|\n)( *)$/),e=r?r[1]:"",c=n;"string"==typeof n&&n.includes("\n")&&(c=String(n).split("\n").map((function(n,t){return 0===t?n:""+e+n})).join("\n")),i+=c+a[t+1]})),i}export{n as dedent,n as default};
