@php
    $guidePanel = $guidePanel ?? null;

    if ($guidePanel === null) {
        $guidePanel = app(\App\Services\GuideRegistryService::class)->modalityGuidePanel(
            (string) ($guidePanelModality ?? ''),
            isset($guidePanelFormat) ? (string) $guidePanelFormat : null,
        );
    }
@endphp

@include('partials.guides-section', ['guideSection' => $guidePanel])
