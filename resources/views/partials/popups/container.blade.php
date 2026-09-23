@include('partials.popups.newsletter-utility')
<div class="wow-popup-layer" data-wow-popup-layer hidden aria-hidden="true">
    <div class="wow-popup-backdrop" data-wow-popup-backdrop aria-hidden="true"></div>
    @include('partials.popups.location-banner')
    @include('partials.popups.cookie-banner')
    @include('partials.popups.newsletter-modal')
    @include('partials.popups.live-chat')
</div>
<style>
  .wow-popup-layer{position:fixed;inset:0;z-index:1200;display:grid;place-items:center;width:100%;padding:20px;background:rgba(11,48,40,.28);backdrop-filter:blur(4px)}
  .wow-popup-layer[hidden]{display:none!important}
  .wow-popup-backdrop{position:absolute;inset:0;background:transparent;pointer-events:auto}
  .wow-popup-layer.is-backdropless{background:transparent;backdrop-filter:none}
  .wow-popup-layer>[data-wow-popup]{position:relative;inset:auto;z-index:1;width:100%;background:transparent;backdrop-filter:none}
  .wow-popup-layer .wow-location-banner,.wow-popup-layer .wow-cookie-banner,.wow-popup-layer .wow-newsletter-modal{display:grid;place-items:center;min-height:100%;padding:0}
  .wow-popup-layer .wow-location-banner[hidden],.wow-popup-layer .wow-cookie-banner[hidden],.wow-popup-layer .wow-newsletter-modal[hidden]{display:none!important}
  .wow-popup-layer .wow-live-chat-modal[hidden]{display:none!important}
  .wow-popup-layer .wow-newsletter-modal{font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#101828}
  @media(max-width:767px){.wow-popup-layer{align-items:end;padding:0}.wow-popup-layer .wow-location-banner,.wow-popup-layer .wow-cookie-banner{padding:16px;min-height:100%}.wow-popup-layer .wow-newsletter-modal{align-items:end}}
</style>
