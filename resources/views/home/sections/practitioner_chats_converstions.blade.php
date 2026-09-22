<section class="chats" id="practitioner-chats" aria-label="Practitioner chats">
    <div class="container">
        <div class="shell">
            <div class="intro">
                <div>
                    <p class="eyebrow">Practitioner stories</p>
                    <h2>Practitioner Chats</h2>
                    <p class="copy">Go beyond listings. Read interviews, watch conversations and hear directly from the people behind the therapies.</p>
                    <div class="actions">
                        <a class="btn primary" href="https://times.weofferwellness.co.uk/category/interviews" target="_blank" rel="noopener">Read interviews</a>
                        <a class="btn ghost" href="https://www.youtube.com/@WeOfferWellness/videos" target="_blank" rel="noopener">Watch videos</a>
                    </div>
                </div>
            </div>

            <div class="media-grid" data-practitioner-chat-cards>
                <div class="media media-placeholder"><div class="media-content"><span class="tag">OUR VIBE</span><h3>Loading practitioner stories</h3></div></div>
                <div class="media media-placeholder"><div class="media-content"><span class="tag">OUR VIBE</span><h3>Loading article</h3></div></div>
                <div class="media media-placeholder"><div class="media-content"><span class="tag">OUR VIBE</span><h3>Loading article</h3></div></div>
            </div>
        </div>
    </div>
</section>

<style>
    .chats { padding: 68px 0; }
    .shell { display: grid; grid-template-columns: .9fr 1.1fr; min-height: 430px; overflow: hidden; border-radius: 4px; background: #0e1726; color: #fff; }
    .intro { display: flex; flex-direction: column; justify-content: space-between; padding: 42px; }
    .eyebrow { margin: 0 0 12px; color: #8fbfaf; font-size: 11px; font-weight: 700; letter-spacing: .18em; text-transform: uppercase; }
    .chats h2 { margin: 0; font: 500 clamp(46px, 5vw, 70px)/.95 "Playfair Display", serif; letter-spacing: -.05em; }
    .copy { max-width: 520px; margin: 18px 0 0; color: rgba(255,255,255,.7); font-size: 14px; line-height: 1.65; }
    .actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 28px; }
    .btn { display: inline-flex; align-items: center; gap: 7px; height: 42px; padding: 0 16px; border-radius: 999px; text-decoration: none; font-size: 13px; font-weight: 600; }
    .btn.primary { background: #4f9482; color: #fff; }
    .btn.ghost { background: #fff; color: #17201d; }
    .btn::after { display: grid; width: 21px; height: 21px; place-items: center; border-radius: 50%; background: rgba(255,255,255,.15); content: "→"; }
    .btn.ghost::after { background: #f3f7f5; color: #4f9482; }
    .media-grid { display: grid; grid-template: 1fr 1fr / 1.15fr .85fr; gap: 1px; background: rgba(255,255,255,.12); }
    .media { position: relative; min-height: 210px; overflow: hidden; color: #fff; text-decoration: none; background: #1b2638; }
    .media:first-child { grid-row: 1 / 3; }
    .media img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; filter: brightness(.72); transition: transform .4s ease; }
    .media:hover img { transform: scale(1.04); }
    .shade { position: absolute; inset: 0; background: linear-gradient(180deg, transparent 30%, rgba(9,18,31,.92) 100%); }
    .media-content { position: absolute; right: 18px; bottom: 17px; left: 18px; z-index: 2; }
    .tag { display: inline-flex; margin-bottom: 8px; padding: 5px 8px; border-radius: 999px; background: rgba(255,255,255,.9); color: #16202d; font-size: 9px; font-weight: 700; text-transform: uppercase; }
    .media h3 { margin: 0; font-size: 15px; line-height: 1.25; }
    .media p { margin: 5px 0 0; color: rgba(255,255,255,.7); font-size: 10px; line-height: 1.4; }
    .media-placeholder { background: linear-gradient(120deg, #1b2638, #26394c); }
    @media (max-width: 900px) { .shell { grid-template-columns: 1fr; } .media-grid { min-height: 480px; } }
    @media (max-width: 575px) { .chats { padding: 42px 0; } .intro { padding: 28px 20px; } .media-grid { grid-template: 220px 160px 160px / 1fr; } .media:first-child { grid-row: auto; } }

    /* Keep this component independent from global button/card typography. */
    #practitioner-chats.chats,
    #practitioner-chats.chats * { box-sizing: border-box; font-family: "Instrument Sans", sans-serif; }
    #practitioner-chats .shell { background: #0e1726; color: #fff; }
    #practitioner-chats .intro { padding: 42px; }
    #practitioner-chats .eyebrow { margin: 0 0 12px; color: #8fbfaf; font-size: 11px; font-weight: 700; letter-spacing: .18em; line-height: 1.2; text-transform: uppercase; }
    #practitioner-chats h2 { margin: 0; color: #fff; font-family: "Playfair Display", serif; font-size: clamp(46px, 5vw, 70px); font-weight: 500; line-height: .95; letter-spacing: -.05em; }
    #practitioner-chats .copy { max-width: 520px; margin: 18px 0 0; color: rgba(255,255,255,.7); font-size: 14px; line-height: 1.65; }
    #practitioner-chats .actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 28px; }
    #practitioner-chats .btn { display: inline-flex; align-items: center; gap: 7px; height: 42px; padding: 0 16px; border: 0; border-radius: 999px; font-family: "Instrument Sans", sans-serif; font-size: 13px; font-weight: 600; line-height: 1; text-decoration: none; }
    #practitioner-chats .btn.primary { background: #4f9482; color: #fff; }
    #practitioner-chats .btn.ghost { background: #fff; color: #17201d; }
    #practitioner-chats .btn::after { display: grid; width: 21px; height: 21px; place-items: center; border-radius: 50%; background: rgba(255,255,255,.15); color: #fff; content: "→"; }
    #practitioner-chats .btn.ghost::after { background: #f3f7f5; color: #4f9482; }
    #practitioner-chats .media-grid { background: rgba(255,255,255,.12); }
    #practitioner-chats .media { color: #fff; background: #1b2638; }
    #practitioner-chats .media:hover { color: #fff; }
    #practitioner-chats .media-content { color: #fff; }
    #practitioner-chats .media h3 { margin: 0; color: #fff; font-family: "Instrument Sans", sans-serif; font-size: 15px; font-weight: 600; line-height: 1.25; }
    #practitioner-chats .media p { margin: 5px 0 0; color: rgba(255,255,255,.7); font-family: "Instrument Sans", sans-serif; font-size: 10px; line-height: 1.4; }
    #practitioner-chats .tag { color: #16202d; font-family: "Instrument Sans", sans-serif; font-size: 9px; font-weight: 700; line-height: 1; text-transform: uppercase; }
    @media (max-width: 575px) { #practitioner-chats .intro { padding: 28px 20px; } }
</style>

<script>
    (function () {
        const grid = document.querySelector('[data-practitioner-chat-cards]');
        if (!grid) return;

        fetch('/api/articles?limit=100', { headers: { Accept: 'application/json' } })
            .then((response) => response.ok ? response.json() : [])
            .then((articles) => {
                const escapeHtml = (value) => String(value || '').replace(/[&<>'"]/g, (character) => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
                }[character]));
                const vibe = (Array.isArray(articles) ? articles : [])
                    .filter((article) => String(article?.title || '').trim().toUpperCase().startsWith('OUR VIBE'))
                    .filter((article) => article?.img && article?.href)
                    .slice(0, 3);

                if (!vibe.length) {
                    grid.innerHTML = '<div class="media media-placeholder"><div class="media-content"><span class="tag">OUR VIBE</span><h3>No practitioner stories available yet</h3></div></div>';
                    return;
                }

                grid.innerHTML = vibe.map((article, index) => `
                    <a class="media" href="${escapeHtml(article.href)}" target="_blank" rel="noopener">
                        <img src="${escapeHtml(article.img)}" alt="${escapeHtml(article.title)}" loading="lazy">
                        <span class="shade"></span>
                        <div class="media-content">
                            <span class="tag">${index === 0 ? 'Featured interview' : 'OUR VIBE'}</span>
                            <h3>${escapeHtml(article.title)}</h3>
                            ${index === 0 && article.excerpt ? `<p>${escapeHtml(article.excerpt)}</p>` : ''}
                        </div>
                    </a>
                `).join('');
            })
            .catch(() => {
                grid.innerHTML = '<div class="media media-placeholder"><div class="media-content"><span class="tag">OUR VIBE</span><h3>Stories are being refreshed</h3></div></div>';
            });
    })();
</script>
