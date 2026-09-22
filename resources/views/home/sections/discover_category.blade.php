@php
    $sectionKicker = $sectionKicker ?? 'Discover';
    $sectionTitle = $sectionTitle ?? 'Shop wellness by modality';
    $sectionDescription = $sectionDescription ?? 'Explore therapies, classes, workshops and experiences by the kind of support you are looking for.';
    $browseUrl = $browseUrl ?? url('/therapies');
    $fallback = collect([
        ['name' => 'Breathwork', 'slug' => 'breathwork', 'description' => 'Guided breathing sessions for calm, clarity, nervous-system support and deeper connection with yourself.', 'image_url' => 'https://images.unsplash.com/photo-1506126613408-eca07ce68773?auto=format&fit=crop&w=700&q=80', 'url' => url('/breathwork')],
        ['name' => 'Sound Healing', 'slug' => 'sound-healing', 'description' => 'Immersive sound, vibration and restorative sessions.', 'image_url' => 'https://images.pexels.com/photos/6997998/pexels-photo-6997998.jpeg', 'url' => url('/sound-healing')],
        ['name' => 'Massage', 'slug' => 'massage', 'description' => 'Hands-on support for tension, recovery and rest.', 'image_url' => 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?auto=format&fit=crop&w=700&q=80', 'url' => url('/massage')],
        ['name' => 'Yoga', 'slug' => 'yoga', 'description' => 'Classes and sessions for mobility, strength and calm.', 'image_url' => 'https://images.unsplash.com/photo-1599901860904-17e6ed7083a0?auto=format&fit=crop&w=700&q=80', 'url' => url('/yoga')],
        ['name' => 'Coaching', 'slug' => 'coaching', 'description' => 'Guidance for confidence, clarity and personal growth.', 'image_url' => 'https://images.unsplash.com/photo-1551836022-d5d88e9218df?auto=format&fit=crop&w=700&q=80', 'url' => url('/coaching')],
    ]);
    $items = collect($discoveryCategories ?? [])->filter(fn ($item) => is_array($item))->take(5);
    if ($items->count() < 5) {
        $items = $items->concat($fallback->reject(fn ($fallbackItem) => $items->contains('slug', $fallbackItem['slug'])))->take(5);
    }
@endphp

@include('partials.modality-board', [
    'items' => $items,
    'eyebrow' => $sectionKicker,
    'heading' => $sectionTitle,
    'intro' => $sectionDescription,
    'browseHref' => $browseUrl,
])
