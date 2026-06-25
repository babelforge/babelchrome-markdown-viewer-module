import hljs from 'highlight.js';
import mermaid from 'mermaid';

type MermaidApi = {
  initialize: (configuration: Record<string, unknown>) => void;
  run: (configuration: { nodes: HTMLElement[] }) => Promise<void>;
};

const sourceElement = document.querySelector<HTMLScriptElement>('#markdown-source');
const viewerElement = document.querySelector<HTMLElement>('[data-markdown-viewer]');

if (null !== sourceElement && null !== viewerElement) {
  viewerElement.dataset.frontendMarkdown = (sourceElement.textContent ?? '').length > 0 ? 'ready' : 'empty';
}

if (null !== viewerElement) {
  document.body.dataset.markdownTheme = viewerElement.dataset.markdownTheme ?? 'github-light';
}

for (const block of document.querySelectorAll<HTMLElement>('pre > code')) {
  if (block.classList.contains('language-mermaid') || block.classList.contains('language-mmd')) {
    continue;
  }

  hljs.highlightElement(block);
}

const mermaidBlocks = Array.from(
  document.querySelectorAll<HTMLElement>('pre > code.language-mermaid, pre > code.language-mmd'),
);

if (0 < mermaidBlocks.length) {
  for (const block of mermaidBlocks) {
    const container = document.createElement('div');
    container.className = 'mermaid';
    container.textContent = block.textContent ?? '';
    block.closest('pre')?.replaceWith(container);
  }

  try {
    const mermaidApi = mermaid as MermaidApi;
    mermaidApi.initialize({
      startOnLoad: false,
      securityLevel: 'strict',
      theme: 'default',
    });
    await mermaidApi.run({
      nodes: Array.from(document.querySelectorAll<HTMLElement>('.mermaid')),
    });
  } catch (error) {
    for (const diagram of document.querySelectorAll<HTMLElement>('.mermaid')) {
      diagram.dataset.rendering = 'failed';
    }
  }
}

const sourceId = viewerElement?.dataset.sourceId ?? '';
const autoRefreshEnabled = '1' === (viewerElement?.dataset.autoRefresh ?? '');
const initialLastModified = Number.parseInt(viewerElement?.dataset.lastModified ?? '', 10);

if (autoRefreshEnabled && '' !== sourceId && Number.isFinite(initialLastModified)) {
  let knownLastModified = initialLastModified;
  window.setInterval(() => {
    void (async () => {
      try {
        const response = await fetch(`/source-status/${encodeURIComponent(sourceId)}${window.location.search}`, {
          cache: 'no-store',
        });
        if (!response.ok) {
          return;
        }

        const payload = (await response.json()) as { lastModified?: number };
        if ('number' === typeof payload.lastModified && payload.lastModified > knownLastModified) {
          knownLastModified = payload.lastModified;
          window.location.reload();
        }
      } catch {
        // Auto-refresh is best-effort and must never disturb reading.
      }
    })();
  }, 1600);
}
