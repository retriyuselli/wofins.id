{{-- Shared Admin Tools theme styles (include once per page via @include) --}}
@push('styles')
<style>
    .wf-admin-input,
    .wf-admin-select {
        width: 100%;
        border: 1.5px solid var(--wf-line);
        border-radius: 0.85rem;
        padding: 0.65rem 1rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--wf-navy);
        background: #fff;
        outline: none;
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .wf-admin-select {
        font-weight: 600;
        border-radius: 999px;
    }

    .wf-admin-input:focus,
    .wf-admin-select:focus {
        border-color: rgba(201, 162, 39, 0.75);
        box-shadow: 0 0 0 3px rgba(201, 162, 39, 0.18);
    }

    .wf-admin-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.7rem;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 700;
        border: 1px solid var(--wf-line);
        background: var(--wf-cream);
        color: var(--wf-navy);
        text-transform: capitalize;
    }

    .wf-admin-badge--ok {
        background: rgba(16, 185, 129, 0.12);
        border-color: rgba(16, 185, 129, 0.25);
        color: #047857;
    }

    .wf-admin-badge--warn {
        background: rgba(201, 162, 39, 0.15);
        border-color: rgba(201, 162, 39, 0.35);
        color: #8a6d12;
    }

    .wf-admin-badge--danger {
        background: rgba(244, 63, 94, 0.1);
        border-color: rgba(244, 63, 94, 0.25);
        color: #be123c;
    }

    .wf-admin-badge--muted {
        background: rgba(11, 31, 58, 0.06);
        border-color: var(--wf-line);
        color: var(--wf-muted);
    }

    .wf-admin-stat {
        position: relative;
        overflow: hidden;
        border: 1px solid var(--wf-line);
        border-radius: 1rem;
        padding: 1rem 1.1rem;
        background: #fff;
        height: 100%;
    }

    .wf-admin-stat::before {
        content: '';
        position: absolute;
        width: 4.5rem;
        height: 4.5rem;
        top: -1.4rem;
        right: -1.2rem;
        border-radius: 40% 60% 55% 45% / 50% 40% 60% 50%;
        background: radial-gradient(circle at 30% 30%, rgba(201, 162, 39, 0.18), transparent 70%);
        pointer-events: none;
    }

    .wf-admin-stat__label {
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--wf-muted);
    }

    .wf-admin-stat__value {
        margin-top: 0.35rem;
        font-size: 1rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        font-variant-numeric: tabular-nums;
        color: var(--wf-navy);
    }

    .wf-admin-table-wrap {
        overflow-x: auto;
        border-radius: 1rem;
        border: 1px solid var(--wf-line);
    }

    .wf-admin-table thead tr {
        text-align: left;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--wf-muted);
        background: color-mix(in srgb, var(--wf-cream) 70%, white);
        border-bottom: 1px solid var(--wf-line);
    }

    .wf-admin-table th {
        padding: 0.75rem 1rem;
        font-weight: 600;
    }

    .wf-admin-table tbody {
        border-color: var(--wf-line);
    }

    .wf-admin-table tbody tr {
        color: var(--wf-ink);
        transition: background .15s ease;
    }

    .wf-admin-table tbody tr:hover {
        background: color-mix(in srgb, var(--wf-cream) 40%, transparent);
    }

    .wf-admin-table td {
        padding: 0.875rem 1rem;
        vertical-align: top;
    }

    .wf-admin-link-chip {
        display: inline-flex;
        align-items: center;
        padding: 0.4rem 0.9rem;
        border-radius: 999px;
        border: 1px solid var(--wf-line);
        background: #fff;
        color: var(--wf-navy);
        font-size: 0.75rem;
        font-weight: 700;
        transition: border-color .15s ease, color .15s ease, background .15s ease;
    }

    .wf-admin-link-chip:hover {
        border-color: var(--wf-gold);
        color: var(--wf-gold);
    }
</style>
@endpush
