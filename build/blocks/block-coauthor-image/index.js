!function(){"use strict";var e,t={62:function(){var e=window.wp.blocks,t=window.wp.element,l=window.wp.primitives,a=(0,t.createElement)(l.SVG,{viewBox:"0 0 24 24",xmlns:"http://www.w3.org/2000/svg"},(0,t.createElement)(l.Path,{d:"M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM5 4.5h14c.3 0 .5.2.5.5v8.4l-3-2.9c-.3-.3-.8-.3-1 0L11.9 14 9 12c-.3-.2-.6-.2-.8 0l-3.6 2.6V5c-.1-.3.1-.5.4-.5zm14 15H5c-.3 0-.5-.2-.5-.5v-2.4l4.1-3 3 1.9c.3.2.7.2.9-.1L16 12l3.5 3.4V19c0 .3-.2.5-.5.5z"})),o=window.wp.i18n,i=window.wp.blockEditor,n=window.wp.components,r=window.wp.data,s=window.wp.coreData;const c=(0,t.createElement)(t.Fragment,null,(0,t.createElement)(n.__experimentalToggleGroupControlOption,{value:"cover",label:(0,o._x)("Cover","Scale option for Image dimension control")}),(0,t.createElement)(n.__experimentalToggleGroupControlOption,{value:"contain",label:(0,o._x)("Contain","Scale option for Image dimension control")}),(0,t.createElement)(n.__experimentalToggleGroupControlOption,{value:"fill",label:(0,o._x)("Fill","Scale option for Image dimension control")})),u="cover",h={cover:(0,o.__)("Image is scaled and cropped to fill the entire space without being distorted."),contain:(0,o.__)("Image is scaled to fill the space without clipping nor distorting."),fill:(0,o.__)("Image will be stretched and distorted to completely fill the space.")};var p=e=>{let{clientId:l,attributes:{aspectRatio:a,width:r,height:s,scale:p,sizeSlug:m},setAttributes:g,imageSizeOptions:d=[]}=e;const _=(0,n.__experimentalUseCustomUnits)({availableUnits:(0,i.useSetting)("spacing.units")||["px","%","vw","em","rem"]}),v=(e,t)=>{const l=parseFloat(t);isNaN(l)&&t||g({[e]:l<0?"0":t})},w=(0,o._x)("Scale","Image scaling options"),b=s||a&&"auto"!==a;return(0,t.createElement)(i.InspectorControls,{group:"dimensions"},(0,t.createElement)(n.__experimentalToolsPanelItem,{hasValue:()=>!!a,label:(0,o.__)("Aspect ratio"),onDeselect:()=>g({aspectRatio:void 0}),resetAllFilter:()=>({aspectRatio:void 0}),isShownByDefault:!0,panelId:l},(0,t.createElement)(n.SelectControl,{__nextHasNoMarginBottom:!0,label:(0,o.__)("Aspect ratio"),value:a,options:[{label:(0,o.__)("Original"),value:"auto"},{label:(0,o.__)("Square"),value:"1"},{label:(0,o.__)("16:9"),value:"16/9"},{label:(0,o.__)("4:3"),value:"4/3"},{label:(0,o.__)("3:2"),value:"3/2"},{label:(0,o.__)("9:16"),value:"9/16"},{label:(0,o.__)("3:4"),value:"3/4"},{label:(0,o.__)("2:3"),value:"2/3"}],onChange:e=>g({aspectRatio:e})})),(0,t.createElement)(n.__experimentalToolsPanelItem,{className:"single-column",hasValue:()=>!!s,label:(0,o.__)("Height"),onDeselect:()=>g({height:void 0}),resetAllFilter:()=>({height:void 0}),isShownByDefault:!0,panelId:l},(0,t.createElement)(n.__experimentalUnitControl,{label:(0,o.__)("Height"),labelPosition:"top",value:s||"",min:0,onChange:e=>v("height",e),units:_})),(0,t.createElement)(n.__experimentalToolsPanelItem,{className:"single-column",hasValue:()=>!!r,label:(0,o.__)("Width"),onDeselect:()=>g({width:void 0}),resetAllFilter:()=>({width:void 0}),isShownByDefault:!0,panelId:l},(0,t.createElement)(n.__experimentalUnitControl,{label:(0,o.__)("Width"),labelPosition:"top",value:r||"",min:0,onChange:e=>v("width",e),units:_})),b&&(0,t.createElement)(n.__experimentalToolsPanelItem,{hasValue:()=>!!p&&p!==u,label:w,onDeselect:()=>g({scale:u}),resetAllFilter:()=>({scale:u}),isShownByDefault:!0,panelId:l},(0,t.createElement)(n.__experimentalToggleGroupControl,{__nextHasNoMarginBottom:!0,label:w,value:p,help:h[p],onChange:e=>g({scale:e}),isBlock:!0},c)),!!d.length&&(0,t.createElement)(n.__experimentalToolsPanelItem,{hasValue:()=>!!m,label:(0,o.__)("Resolution"),onDeselect:()=>g({sizeSlug:void 0}),resetAllFilter:()=>({sizeSlug:void 0}),isShownByDefault:!1,panelId:l},(0,t.createElement)(n.SelectControl,{__nextHasNoMarginBottom:!0,label:(0,o.__)("Resolution"),value:m||"full",options:d,onChange:e=>g({sizeSlug:e}),help:(0,o.__)("Select the size of the source image.")})))};function m(e){let{dimensions:l,style:a,className:i}=e;const n=(0,t.useMemo)((()=>function(e){let{width:t,height:l}=e;return`data:image/svg+xml;charset=UTF-8,${encodeURIComponent(`<svg width="${t}" height="${l}" viewBox="0 0 ${t} ${t}" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">\n\t\t\t<rect width="${t}" height="${t}" fill="#eeeeee"></rect>\n\t\t\t<path stroke="black" vector-effect="non-scaling-stroke" d="M ${t} ${t} 0 0" />\n\t\t</svg>`.replace(/[\t\n\r]/gim,"").replace(/\s\s+/g," ")).replace(/\(/g,"%28").replace(/\)/g,"%29")}`}(l)),[l]);return(0,t.createElement)("img",{alt:(0,o.__)("Placeholder image"),className:i,src:n,style:a,width:l.width,height:l.height})}function g(e,t){var l,a;return null==e||null===(l=e.media_details)||void 0===l||null===(a=l.sizes[t])||void 0===a?void 0:a.source_url}var d=JSON.parse('{"u2":"co-authors-plus/image"}');(0,e.registerBlockType)(d.u2,{edit:function(e){let{attributes:l,setAttributes:a,context:c,clientId:u}=e;const{aspectRatio:h,height:d,isLink:_,rel:v,scale:w,sizeSlug:b,verticalAlign:f,width:x}=l,S=(0,r.useSelect)((e=>e("co-authors-plus/blocks").getAuthorPlaceholder()),[]),E=c["co-authors-plus/author"]||S,C=(0,r.useSelect)((e=>0!==E.featured_media&&e(s.store).getMedia(E.featured_media,{context:"view"})),[E.featured_media]),{imageSizes:y,imageDimensions:k}=(0,r.useSelect)((e=>e(i.store).getSettings()),[]),I=y.map((e=>{let{name:t,slug:l}=e;return{value:l,label:t}})),B=function(e,t,l){if(e&&"full"===l)return l;const a=function(e,t){if(!e)return Object.keys(t);const l=Object.keys(e.media_details.sizes),a=Object.keys(t);return Array.from(new Set([...l.filter((e=>a.includes(e)))]))}(e,t);return l&&a.includes(l)?l:a[Math.max(0,a.length-1)]}(C,k,b),O=function(e,t,l){if(!e)return{};const a=e.media_details.sizes[l];if("full"===l)return{width:a.width,height:a.height};const o=t[l];if(!0===o.crop||o.width===o.height)return{width:o.width,height:o.height};const i=a.width/a.height;return o.width>o.height?{width:o.width,height:o.width/i}:{width:o.height*i,height:o.height}}(C,k,B),A=C?{}:function(e,t){const l=e[t];return!0===l.crop||l.width===l.height?{width:l.width,height:l.height}:l.width>l.height?{width:l.width,height:l.width}:{width:l.height,height:l.height}}(k,B),T=(0,i.__experimentalUseBorderProps)(l),N=0!==E.id&&!1===C;return(0,t.createElement)(t.Fragment,null,(0,t.createElement)(p,{clientId:u,attributes:l,setAttributes:a,imageSizeOptions:I}),N?null:(0,t.createElement)("figure",(0,i.useBlockProps)(),C?(0,t.createElement)("img",{alt:(0,o.__)("Author featured image","co-authors-plus"),className:T.className,src:g(C,B),style:{width:!x&&d?"auto":x,height:!d&&x?"auto":d,aspectRatio:h,objectFit:w,verticalAlign:f,...T.style},width:O.width,height:O.height}):(0,t.createElement)(m,{className:T.className,dimensions:A,style:{width:!x&&d?"auto":x,height:!d&&x?"auto":d,aspectRatio:h,objectFit:w,verticalAlign:f,...T.style}})),(0,t.createElement)(i.InspectorControls,null,(0,t.createElement)(n.PanelBody,{title:(0,o.__)("Image Settings","co-authors-plus")},(0,t.createElement)(n.ToggleControl,{__nextHasNoMarginBottom:!0,label:(0,o.__)("Make featured image a link to author archive.","co-authors-plus"),onChange:()=>a({isLink:!_}),checked:_}),_&&(0,t.createElement)(n.TextControl,{__nextHasNoMarginBottom:!0,label:(0,o.__)("Link rel","co-authors-plus"),value:v,onChange:e=>a({rel:e})})),(0,t.createElement)(n.PanelBody,{initialOpen:!1,title:(0,o.__)("Co-Authors Layout","co-authors-plus")},(0,t.createElement)(n.SelectControl,{label:(0,o.__)("Vertical align","co-authors-plus"),value:f,options:[{value:"",label:(0,o.__)("Default","co-authors-plus")},{value:"baseline",label:(0,o.__)("Baseline","co-authors-plus")},{value:"bottom",label:(0,o.__)("Bottom","co-authors-plus")},{value:"middle",label:(0,o.__)("Middle","co-authors-plus")},{value:"sub",label:(0,o.__)("Sub","co-authors-plus")},{value:"super",label:(0,o.__)("Super","co-authors-plus")},{value:"text-bottom",label:(0,o.__)("Text Bottom","co-authors-plus")},{value:"text-top",label:(0,o.__)("Text Top","co-authors-plus")},{value:"top",label:(0,o.__)("Top","co-authors-plus")}],onChange:e=>{a({verticalAlign:""===e?void 0:e})},help:(0,o.__)("Vertical alignment defaults to bottom in the block layout and middle in the inline layout.","co-authors-plus")}))))},icon:a})}},l={};function a(e){var o=l[e];if(void 0!==o)return o.exports;var i=l[e]={exports:{}};return t[e](i,i.exports,a),i.exports}a.m=t,e=[],a.O=function(t,l,o,i){if(!l){var n=1/0;for(u=0;u<e.length;u++){l=e[u][0],o=e[u][1],i=e[u][2];for(var r=!0,s=0;s<l.length;s++)(!1&i||n>=i)&&Object.keys(a.O).every((function(e){return a.O[e](l[s])}))?l.splice(s--,1):(r=!1,i<n&&(n=i));if(r){e.splice(u--,1);var c=o();void 0!==c&&(t=c)}}return t}i=i||0;for(var u=e.length;u>0&&e[u-1][2]>i;u--)e[u]=e[u-1];e[u]=[l,o,i]},a.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},function(){var e={461:0,286:0};a.O.j=function(t){return 0===e[t]};var t=function(t,l){var o,i,n=l[0],r=l[1],s=l[2],c=0;if(n.some((function(t){return 0!==e[t]}))){for(o in r)a.o(r,o)&&(a.m[o]=r[o]);if(s)var u=s(a)}for(t&&t(l);c<n.length;c++)i=n[c],a.o(e,i)&&e[i]&&e[i][0](),e[i]=0;return a.O(u)},l=self.webpackChunkco_authors_plus=self.webpackChunkco_authors_plus||[];l.forEach(t.bind(null,0)),l.push=t.bind(null,l.push.bind(l))}();var o=a.O(void 0,[286],(function(){return a(62)}));o=a.O(o)}();