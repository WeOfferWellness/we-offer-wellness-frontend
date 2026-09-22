# Agent Rules

- Proceed carefully.
- No DB work ever.
- No unit tests ever.
- After changes, run `npm install` (if dependencies changed) and `npm run build`.
- Do not use Laravel Mix; use the Vite-based build (`npm run build`) only.
- After changes that affect runtime behavior, run `php artisan optimize:clear`.
- Every landing-page hero must render through the shared `resources/views/partials/landing-hero.blade.php` component/partial. Use its general two-column treatment for category/modality and other non-location pages; when a landing page has a resolved location, pass `heroImage` and `heroLocationLabel` so it uses the location-image treatment. Do not create a page-specific landing hero without updating this shared partial and this rule.
- Landing-page hero metadata must render through the shared `resources/views/partials/hero-meta.blade.php` partial immediately below the hero, never as chips to the right of breadcrumbs. Use the supplied dot/strong treatment for the first item and preserve responsive horizontal scrolling for additional metadata.
- Keep the landing stack continuous: do not add parent top padding or margins between breadcrumbs, the shared hero, and `hero-meta`; page-specific wrappers must start at the hero edge.
- In user-facing wellness copy, use “Modality” and “Modalities” instead of “Category” and “Categories.” Preserve technical field names, API keys, database columns, route parameters, and internal code identifiers unless a separate migration explicitly requires changing them.
