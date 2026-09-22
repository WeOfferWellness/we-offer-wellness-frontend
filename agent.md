# Agent Rules

- Proceed carefully.
- No DB work ever.
- No unit tests ever.
- After changes, run `npm install` (if dependencies changed) and `npm run build`.
- Do not use Laravel Mix; use the Vite-based build (`npm run build`) only.
- After changes that affect runtime behavior, run `php artisan optimize:clear`.
- Every landing-page build or update must include all three shared layers in this order: `resources/views/partials/breadcrumbs.blade.php`, `resources/views/partials/landing-hero.blade.php`, then `resources/views/partials/hero-meta.blade.php`. This applies to home, offering/category/modality pages, location pages, and SEO landing variants.
- Use the shared landing hero for every landing page. Use its general two-column treatment for category/modality and other non-location pages; when a landing page has a resolved location, pass `heroImage` and `heroLocationLabel` and `heroIsLocation => true` so it uses the location-image treatment. Do not create a page-specific landing hero without updating this shared partial and this rule.
- Landing-page hero metadata must render through the shared `resources/views/partials/hero-meta.blade.php` partial immediately below the hero, never as chips to the right of breadcrumbs. Use truthful metadata such as `Local listings`, `Nearby options`, and `Online sessions`; use the supplied dot/strong treatment for the first item and preserve responsive horizontal scrolling for additional metadata.
- Keep the landing stack continuous: do not add parent top padding or margins between breadcrumbs, the shared hero, and `hero-meta`; page-specific wrappers must start at the hero edge.
- Location landing modality/category sections must use the shared `resources/views/partials/modality-board.blade.php` component, the same image-led modality board used by the homepage. Pass the location-specific modality URLs, counts/descriptions, and available category images into that component; do not recreate the old plain modality tile grid.
- In user-facing wellness copy, use “Modality” and “Modalities” instead of “Category” and “Categories.” Preserve technical field names, API keys, database columns, route parameters, and internal code identifiers unless a separate migration explicitly requires changing them.
