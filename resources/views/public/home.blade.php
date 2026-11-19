@php
    $tz = config('etms.display_timezone', 'Asia/Kuala_Lumpur');
    $categories = $categories ?? collect();
    $upcomingSessions = $upcomingSessions ?? collect();
    $featuredModules = $featuredModules ?? collect();

    $homeCssVersion = file_exists(public_path('css/home.css')) ? filemtime(public_path('css/home.css')) : null;
@endphp

<x-layouts.public title="Technical Training in Water, Environment & Engineering">
    <link rel="stylesheet" href="{{ asset('css/home.css') }}{{ $homeCssVersion ? '?v='.$homeCssVersion : '' }}">

    <div class="hp">

        {{-- Hero --}}
        <section class="hp-hero" aria-labelledby="hp-hero-title">
            <div class="hp-container hp-hero__grid">
                <div class="hp-hero__copy">
                    <h1 id="hp-hero-title" class="hp-hero__title">
                        Building Expertise in Water, Environment &amp; Engineering.
                    </h1>

                    <p class="hp-hero__lede">
                        Practical technical training in hydrology, environmental science, and engineering for professionals and individuals in Malaysia &mdash; delivered through scheduled in-person sessions and independent study modules.
                    </p>

                    <div class="hp-actions">
                        <a href="{{ route('catalogue.index') }}" class="hp-btn hp-btn--primary">
                            <span>Explore Training Programmes</span>
                            <svg class="hp-arrow" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.65" aria-hidden="true"><path d="M4 10h12M11 5l5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                        <a href="{{ route('sessions.index') }}" class="hp-btn hp-btn--secondary">
                            Browse Upcoming Sessions
                        </a>
                    </div>

                    <p class="hp-hero__account">
                        @auth
                            Signed in as {{ auth()->user()->name }}.
                            <a href="{{ auth()->user()->defaultDashboardUrl() }}" class="hp-link">Open My Portal</a>
                        @else
                            <a href="{{ route('register') }}" class="hp-link">Create a customer account</a>
                            to book sessions or manage purchased modules, or
                            <a href="{{ route('login') }}" class="hp-link">log in</a>.
                        @endauth
                    </p>
                </div>

                <div class="hp-hero__media">
                    <figure class="hp-hero__frame">
                        <img
                            src="{{ asset('images/home/hero-field-hydrology.jpg') }}"
                            alt="Environmental technicians taking river flow velocity measurements in a Malaysian river"
                            width="1264"
                            height="848"
                            fetchpriority="high"
                        >
                    </figure>
                </div>
            </div>
        </section>

        {{-- Training pathways --}}
        <section id="delivery-models" class="hp-section hp-section--aubergine" aria-labelledby="hp-pathways-title">
            <div class="hp-container">
                <header class="hp-head">
                    <h2 id="hp-pathways-title" class="hp-h2">Two ways to train with Ecohydrotech</h2>
                    <p class="hp-head__sub">
                        Reserve a place in a scheduled physical session or purchase individual training modules for self-directed study under one customer account.
                    </p>
                </header>

                <div class="hp-pathways">
                    <figure class="hp-pathways__photo">
                        <img
                            src="{{ asset('images/home/classroom-session.jpg') }}"
                            alt="Instructor guiding professionals through a hydrological catchment diagram in a training room"
                            width="900"
                            height="740"
                            loading="lazy"
                        >
                        <figcaption class="hp-pathways__caption">
                            Instructor-led technical workshop in Malaysia
                        </figcaption>
                    </figure>

                    <div class="hp-pathways__list">
                        <article id="upcoming-preview" class="hp-path-panel">
                            <div class="hp-path-panel__head">
                                <h3 class="hp-h3">Physical Training</h3>
                                <span class="hp-path-panel__note">Instructor-led sessions</span>
                            </div>
                            <p class="hp-path-panel__desc">
                                In-person workshops and technical classes held at specified training venues across Malaysia.
                            </p>
                            <ul class="hp-plain-list">
                                <li>Select an available session date and reserve your seat online.</li>
                                <li>Attend training in person at the designated venue.</li>
                                <li>Attendance and completion are recorded and managed on the platform.</li>
                            </ul>
                            <a href="{{ route('sessions.index') }}" class="hp-text-link">
                                <span>Browse Physical Training Sessions</span>
                                <svg class="hp-arrow" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.65" aria-hidden="true"><path d="M4 10h12M11 5l5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </a>
                        </article>

                        <article id="self-paced-preview" class="hp-path-panel hp-path-panel--soft">
                            <div class="hp-path-panel__head">
                                <h3 class="hp-h3">Independent Training Modules</h3>
                                <span class="hp-path-panel__note">Self-directed study</span>
                            </div>
                            <p class="hp-path-panel__desc">
                                Purchase individual modules directly without booking or attending a scheduled physical session.
                            </p>
                            <ul class="hp-plain-list">
                                <li>Access the learning documents and materials included with each purchased module.</li>
                                <li>Study through your customer portal at your own pace.</li>
                                <li>Independent Module Purchases do not require session registration or physical attendance.</li>
                            </ul>
                            <a href="{{ route('catalogue.index') }}" class="hp-text-link">
                                <span>Explore Training Catalogue</span>
                                <svg class="hp-arrow" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.65" aria-hidden="true"><path d="M4 10h12M11 5l5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </a>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        {{-- Training categories --}}
        @if ($categories->isNotEmpty())
            <section id="categories-section" class="hp-section hp-section--white" aria-labelledby="hp-categories-title">
                <div class="hp-container">
                    <header class="hp-head hp-head--row">
                        <div>
                            <h2 id="hp-categories-title" class="hp-h2">Training categories</h2>
                            <p class="hp-head__sub">Browse published modules grouped by technical discipline.</p>
                        </div>
                        <a href="{{ route('catalogue.index') }}" class="hp-text-link">
                            <span>View full catalogue</span>
                            <svg class="hp-arrow" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.65" aria-hidden="true"><path d="M4 10h12M11 5l5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                    </header>

                    <div class="hp-cat-grid {{ $categories->count() === 2 ? 'hp-cat-grid--two' : '' }}">
                        @foreach ($categories as $cat)
                            <a href="{{ route('catalogue.index', ['category' => $cat->slug]) }}" class="hp-cat-item">
                                <div>
                                    <div class="hp-cat-item__top">
                                        <span class="hp-cat-item__title">{{ $cat->name }}</span>
                                        <span class="hp-cat-item__count">
                                            {{ $cat->published_modules_count }} {{ \Illuminate\Support\Str::plural('module', $cat->published_modules_count) }}
                                        </span>
                                    </div>
                                    @if ($cat->description)
                                        <p class="hp-cat-item__desc">{{ $cat->description }}</p>
                                    @endif
                                </div>

                                <span class="hp-cat-item__link">
                                    <span>Browse discipline</span>
                                    <svg class="hp-arrow" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.65" aria-hidden="true"><path d="M4 10h12M11 5l5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- Upcoming physical sessions --}}
        @if ($upcomingSessions->isNotEmpty())
            <section id="upcoming-sessions" class="hp-section hp-section--stone" aria-labelledby="hp-sessions-title">
                <div class="hp-container">
                    <header class="hp-head hp-head--row">
                        <div>
                            <h2 id="hp-sessions-title" class="hp-h2">Upcoming physical sessions</h2>
                            <p class="hp-head__sub">Scheduled in-person sessions open for booking (times in Malaysia Time).</p>
                        </div>
                        <a href="{{ route('sessions.index') }}" class="hp-text-link">
                            <span>View all sessions</span>
                            <svg class="hp-arrow" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.65" aria-hidden="true"><path d="M4 10h12M11 5l5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                    </header>

                    <div class="hp-sessions" role="list">
                        @foreach ($upcomingSessions as $session)
                            @php
                                $sessionTz = $session->timezone ?: $tz;
                                $start = $session->starts_at?->copy()->timezone($sessionTz);
                                $end = $session->ends_at?->copy()->timezone($sessionTz);
                                $multiDay = $start && $end && ! $start->isSameDay($end);
                                $capacity = (int) $session->capacity;
                                $remaining = $session->remainingSeats();
                                $instructorNames = $session->relationLoaded('instructors')
                                    ? $session->instructors->map(fn ($i) => $i->instructorProfile?->display_name ?? $i->name)->filter()->join(', ')
                                    : '';
                                $venueLocation = collect([$session->venue_city, $session->venue_state])->filter()->join(', ');
                            @endphp

                            <article class="hp-session" role="listitem">
                                <div class="hp-session__date">
                                    @if ($start)
                                        <span class="hp-session__day">{{ $start->format('j M') }}</span>
                                        <span class="hp-session__time">
                                            {{ $start->format('Y') }} &middot; {{ $start->format('g:i A') }}
                                        </span>
                                        @if ($multiDay)
                                            <span class="hp-session__time">Ends {{ $end->format('j M Y') }}</span>
                                        @endif
                                    @else
                                        <span class="hp-session__day">TBA</span>
                                    @endif
                                </div>

                                <div class="hp-session__main">
                                    @if ($session->module)
                                        <p class="hp-session__meta">
                                            {{ $session->module->code }}
                                            @if ($session->module->category)
                                                &middot; {{ $session->module->category->name }}
                                            @endif
                                        </p>
                                    @endif
                                    <h3 class="hp-session__title">
                                        <a href="{{ route('sessions.show', $session) }}">{{ $session->title }}</a>
                                    </h3>
                                    <p class="hp-session__sub">
                                        {{ $session->formattedDuration() }}
                                        @if ($instructorNames !== '')
                                            &middot; {{ $instructorNames }}
                                        @endif
                                    </p>
                                </div>

                                <div class="hp-session__venue">
                                    <span class="hp-session__venue-name">{{ $session->venue_name }}</span>
                                    @if ($venueLocation !== '')
                                        <span class="hp-session__venue-loc">{{ $venueLocation }}</span>
                                    @endif
                                </div>

                                <div class="hp-session__status">
                                    <span class="hp-session__price">
                                        {{ $session->formattedPrice() }}
                                    </span>
                                    <span class="hp-session__seats">
                                        @if ($remaining === 0)
                                            Fully booked
                                        @else
                                            {{ $remaining }} of {{ $capacity }} seats left
                                        @endif
                                        @unless ($session->isOpenForBooking())
                                            &middot; {{ $session->status->label() }}
                                        @endunless
                                    </span>
                                </div>

                                <div class="hp-session__actions">
                                    <a href="{{ route('sessions.show', $session) }}" class="hp-btn hp-btn--secondary hp-btn--sm">
                                        Details
                                    </a>
                                    @if ($session->isOpenForBooking())
                                        <a href="{{ route('checkout.session.show', $session) }}" class="hp-btn hp-btn--primary hp-btn--sm">
                                            Book Seat
                                        </a>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- Featured training programmes --}}
        @if ($featuredModules->isNotEmpty())
            <section id="featured-programmes" class="hp-section hp-section--lavender" aria-labelledby="hp-featured-title">
                <div class="hp-container">
                    <header class="hp-head hp-head--row">
                        <div>
                            <h2 id="hp-featured-title" class="hp-h2">Featured training programmes</h2>
                            <p class="hp-head__sub">Published technical training modules across urban stormwater, flood modelling, and water quality.</p>
                        </div>
                        <a href="{{ route('catalogue.index') }}" class="hp-text-link">
                            <span>Explore all programmes</span>
                            <svg class="hp-arrow" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.65" aria-hidden="true"><path d="M4 10h12M11 5l5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                    </header>

                    <div class="hp-modules {{ $featuredModules->count() === 2 ? 'hp-modules--two' : ($featuredModules->count() === 1 ? 'hp-modules--one' : '') }}">
                        @foreach ($featuredModules as $module)
                            @php
                                $cover = $module->coverImageUrl();
                                $summary = $module->summary ?: \Illuminate\Support\Str::limit(strip_tags((string) $module->description), 160);
                                $price = $module->formattedIndependentPrice();
                                $independent = $module->is_available_independently && $price !== null;
                            @endphp

                            <article class="hp-module">
                                @if ($cover)
                                    <div class="hp-module__cover">
                                        <img src="{{ $cover }}" alt="{{ $module->title }}" loading="lazy">
                                    </div>
                                @endif

                                <div class="hp-module__body">
                                    <p class="hp-module__meta">
                                        <span class="hp-module__category">{{ $module->category?->name ?? 'Training Programme' }}</span>
                                        <span class="hp-module__code">{{ $module->code }}</span>
                                    </p>

                                    <h3 class="hp-module__title">
                                        <a href="{{ route('catalogue.modules.show', $module->slug) }}" class="hp-stretched">{{ $module->title }}</a>
                                    </h3>

                                    @if ($summary)
                                        <p class="hp-module__summary">{{ $summary }}</p>
                                    @endif
                                </div>

                                <div class="hp-module__foot">
                                    <div>
                                        @if ($independent)
                                            <span class="hp-module__price">{{ $price }}</span>
                                            <span class="hp-module__mode">Independent module</span>
                                        @else
                                            <span class="hp-module__price">Physical sessions</span>
                                            <span class="hp-module__mode">Instructor-led cohort</span>
                                        @endif
                                    </div>

                                    <span class="hp-module__link" aria-hidden="true">
                                        View
                                        <svg class="hp-arrow" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.65"><path d="M4 10h12M11 5l5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </span>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- Certificate verification --}}
        <section id="verify-preview" class="hp-cert-band" aria-labelledby="hp-certificates-title">
            <div class="hp-container">
                <div class="hp-cert-band__inner">
                    <div>
                        <h2 id="hp-certificates-title" class="hp-cert-band__title">Certificate verification</h2>
                        <p class="hp-cert-band__desc">
                            Every certificate issued upon approved physical session attendance carries a unique certificate number recorded in our system so its authenticity can be checked against our official training records.
                        </p>
                    </div>

                    <div class="hp-cert-band__action">
                        @auth
                            @if (auth()->user()->isCustomer())
                                <a href="{{ route('portal.certificates.index') }}" class="hp-btn hp-btn--on-dark">View My Certificates</a>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="hp-btn hp-btn--on-dark">Log In to Access Certificates</a>
                        @endauth
                    </div>
                </div>
            </div>
        </section>

    </div>
</x-layouts.public>
