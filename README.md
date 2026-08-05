# PNS Search Routing

Site-owned WordPress search routing for Protests and Suffragettes.

## Behaviour

- Keeps native WordPress search focused on published editorial posts and pages.
- Redirects legacy Core Search form submissions from `/?s=term` to WordPress's
  canonical pretty route, currently `/search/term/`.
- Repairs the accidental `/search/?s=term` landing-page collision with the same
  canonical redirect.
- Preserves native pretty-search pagination, for example
  `/?s=term&paged=2` becomes `/search/term/page/2/`.

The plugin does not own search form markup or search result presentation. Core
Search blocks and theme-level search forms can continue to submit their normal
`GET s` request. WordPress owns the existing pretty-search rewrite rule, while
the active theme owns the search landing page and result template.

Themes can retain an editorial "enable search" control with the
`pns_search_routing_enabled` filter. In the absence of that filter, the plugin
keeps canonical routing enabled.

## Extending editorial search

Additional editorial post types can opt in with the
`pns_search_routing_editorial_post_types` filter. The prior
`pns_theme_editorial_search_post_types` filter is retained as a temporary
compatibility bridge.
