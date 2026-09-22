<section class="section" aria-labelledby="featured-experiences-title">
    <div class="container">
        <div class="kicker">Popular now</div>
        <h2 id="featured-experiences-title">Featured therapies</h2>
        <div class="row g-3 mt-1">
            @for ($i=0; $i<6; $i++)
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="card p-3 h-100">
                        <div class="fw-semibold">Therapy title</div>
                        <div class="text-muted">Outcome benefit • 60–90 mins</div>
                        <div class="small mt-1 text-muted">Best for: stress / sleep</div>
                        <div class="mt-2 d-flex justify-content-between align-items-center">
                            <div class="fw-semibold">£ —</div>
                            <a href="#" class="btn btn-primary btn-sm" data-analytics="home_book_click">Book now</a>
                        </div>
                    </div>
                </div>
            @endfor
        </div>
    </div>
</section>
