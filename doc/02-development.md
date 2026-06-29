# Development

Navigation: [Previous: Usage](01-usage.md) | [README](README.md)

The module is a Symfony-based PHP project. Source frontend assets live in `assets/`, compiled runtime assets live in `public/assets/`, and templates live in `templates/`.

It depends on `babelforge/babel-chrome-viewer-kit` for the shared viewer header and `Open with` controls.

Run quality checks with:

```bash
composer qa
```

Build the production zip from the meta workspace root:

```bash
./tools/dev2prod.sh markdown-viewer-module
```

Navigation: [Previous: Usage](01-usage.md) | [README](README.md)
