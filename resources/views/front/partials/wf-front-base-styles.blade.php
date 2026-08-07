<style>
    :root {
        --wf-navy: #0b1f3a;
        --wf-navy-deep: #071526;
        --wf-gold: #c9a227;
        --wf-gold-soft: #e8d48b;
        --wf-cream: #f7f4ee;
        --wf-ink: #1a2332;
        --wf-muted: #5c6675;
        --wf-line: #e6e2d9;
        --wf-white: #ffffff;
    }

    .wf-page {
        font-family: 'Poppins', system-ui, sans-serif;
        color: var(--wf-ink);
        background: var(--wf-white);
    }

    .wf-page h1,
    .wf-page h2,
    .wf-page h3 {
        letter-spacing: -0.02em;
    }

    .wf-nav {
        position: sticky;
        top: 0;
        z-index: 50;
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(12px);
        border-bottom: 1px solid var(--wf-line);
    }

    .wf-btn-navy {
        background: var(--wf-navy);
        color: #fff;
        border-radius: 999px;
        font-weight: 700;
        transition: background .2s ease, transform .2s ease;
    }

    .wf-btn-navy:hover {
        background: var(--wf-navy-deep);
        transform: translateY(-1px);
    }

    .wf-btn-ghost {
        border: 1.5px solid var(--wf-navy);
        color: var(--wf-navy);
        border-radius: 999px;
        font-weight: 700;
        background: #fff;
        transition: background .2s ease;
    }

    .wf-btn-ghost:hover {
        background: var(--wf-cream);
    }

    .wf-btn-gold {
        background: var(--wf-gold);
        color: var(--wf-navy-deep);
        border-radius: 999px;
        font-weight: 800;
        transition: filter .2s ease, transform .2s ease;
    }

    .wf-btn-gold:hover {
        filter: brightness(1.05);
        transform: translateY(-1px);
    }

    .wf-info-card {
        background: #fff;
        border: 1px solid var(--wf-line);
        border-radius: 1.25rem;
        padding: 1.5rem;
        height: 100%;
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .wf-info-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 36px -24px rgba(11, 31, 58, 0.35);
    }

    .wf-info-icon {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.75rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(201, 162, 39, 0.14);
        color: var(--wf-gold);
        font-size: 1rem;
    }

    [x-cloak] {
        display: none !important;
    }
</style>
