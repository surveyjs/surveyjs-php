// Demo only: highlights the code panel on the catalog and the step pages.
import hljs from 'highlight.js/lib/core';
import javascript from 'highlight.js/lib/languages/javascript';
import json from 'highlight.js/lib/languages/json';
import php from 'highlight.js/lib/languages/php';
import 'highlight.js/styles/github-dark.css';

hljs.registerLanguage('javascript', javascript);
hljs.registerLanguage('json', json);
hljs.registerLanguage('php', php);

for (const block of document.querySelectorAll('pre code[class*="language-"]')) {
    hljs.highlightElement(block);
}
