!function(){"use strict";var e=window.wp.blocks,t=window.wp.element,o=window.wp.blockEditor,n=JSON.parse('{"u2":"cap/coauthor-display-name"}');(0,e.registerBlockType)(n.u2,{edit:function(e){let{context:n}=e;const{displayName:c}=n;return(0,t.createElement)("p",(0,o.useBlockProps)(),c)}})}();