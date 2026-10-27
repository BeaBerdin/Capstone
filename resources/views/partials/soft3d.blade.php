{{--
    Soft-3D style (sad- prefix), used by the Super Admin pages and the
    Student dashboard: white cards with a soft violet-tinted lift, gradient
    icon chips, the lavender banner, gradient buttons and the pop-up list.
    Only pages that @include it change. These rules are unlayered, so they
    win over a page's Tailwind utilities on the same element.
--}}
<style>
    .sad-page {
        min-height: 100vh;
        background: #f6f5fb;
    }

    /* White card with a hairline border and a soft, violet-tinted lift. */
    .sad-card {
        background: #ffffff;
        border: 1px solid #eceaf5;
        border-radius: 20px;
        box-shadow:
            0 1px 2px rgba(30, 20, 70, 0.04),
            0 10px 28px -16px rgba(76, 29, 149, 0.18);
    }

    .sad-lift {
        transition: transform 160ms ease, box-shadow 160ms ease, border-color 160ms ease, background-color 160ms ease;
    }

    .sad-lift:hover {
        transform: translateY(-2px);
        border-color: #ddd6fe;
        box-shadow:
            0 1px 2px rgba(30, 20, 70, 0.04),
            0 16px 32px -16px rgba(76, 29, 149, 0.26);
    }

    /* Soft-3D icon chip: a light gradient with a top highlight and a coloured glow. */
    .sad-chip {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        width: 44px;
        height: 44px;
        border-radius: 14px;
        background: linear-gradient(150deg, #ffffff 0%, var(--chip-bg) 100%);
        color: var(--chip-fg);
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.9),
            0 8px 16px -8px var(--chip-glow);
    }

    .sad-chip svg {
        width: 21px;
        height: 21px;
    }

    .sad-chip-sm {
        width: 36px;
        height: 36px;
        border-radius: 12px;
    }

    .sad-chip-sm svg {
        width: 17px;
        height: 17px;
    }

    .sad-violet  { --chip-bg: #ede9fe; --chip-fg: #7c3aed; --chip-glow: rgba(124, 58, 237, 0.5); }
    .sad-amber   { --chip-bg: #fef3c7; --chip-fg: #d97706; --chip-glow: rgba(217, 119, 6, 0.45); }
    .sad-emerald { --chip-bg: #d1fae5; --chip-fg: #059669; --chip-glow: rgba(5, 150, 105, 0.42); }
    .sad-sky     { --chip-bg: #e0f2fe; --chip-fg: #0284c7; --chip-glow: rgba(2, 132, 199, 0.42); }
    .sad-rose    { --chip-bg: #ffe4e6; --chip-fg: #e11d48; --chip-glow: rgba(225, 29, 72, 0.38); }

    /* Lavender welcome banner, as in the reference. */
    .sad-banner {
        position: relative;
        overflow: hidden;
        border: 1px solid #e4dcfc;
        border-radius: 22px;
        background: linear-gradient(110deg, #f1edff 0%, #ebe5ff 55%, #e2d9ff 100%);
        box-shadow: 0 14px 34px -22px rgba(76, 29, 149, 0.35);
    }

    .sad-banner-art {
        position: absolute;
        right: 10px;
        bottom: 0;
        height: 100%;
        max-height: 220px;
        width: auto;
        pointer-events: none;
    }

    @media (max-width: 767px) {
        .sad-banner-art {
            display: none;
        }
    }

    .sad-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        height: 42px;
        padding: 0 16px;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 600;
        white-space: nowrap;
        transition: background-color 160ms ease, border-color 160ms ease, color 160ms ease;
    }

    .sad-btn-primary {
        background: linear-gradient(180deg, #8b5cf6 0%, #7c3aed 100%);
        color: #ffffff;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.25),
            0 10px 20px -10px rgba(124, 58, 237, 0.7);
    }

    .sad-btn-primary:hover {
        background: linear-gradient(180deg, #7c3aed 0%, #6d28d9 100%);
    }

    .sad-btn-light {
        background: rgba(255, 255, 255, 0.85);
        color: #4c1d95;
        box-shadow: 0 1px 2px rgba(30, 20, 70, 0.05);
    }

    .sad-btn-light:hover {
        background: #ffffff;
    }

    /* Pop-up list (native <dialog>), centred over a dimmed page. */
    /* Form inputs (x-pw.field): text inputs, selects and textareas. */
    .sad-field {
        display: block;
        width: 100%;
        margin-top: .5rem;
        border: 1px solid #e2e8f0;
        border-radius: .75rem;
        background: #ffffff;
        padding: .7rem 1rem;
        font-size: .875rem;
        line-height: 1.4;
        color: #0f172a;
        outline: none;
        transition: border-color .15s, box-shadow .15s;
    }

    input.sad-field,
    select.sad-field {
        height: 2.75rem;
        padding-top: 0;
        padding-bottom: 0;
    }

    .sad-field:focus {
        border-color: #c4b5fd;
        box-shadow: 0 0 0 4px #ede9fe;
    }

    .sad-field::placeholder {
        color: #94a3b8;
    }

    input[type=file].sad-field {
        height: auto;
        padding: .6rem;
        border-style: dashed;
        border-color: #ddd6fe;
        background: rgba(245, 243, 255, .5);
    }

    /* Form section icon (x-pw.form-section, form side panels). */
    .sad-section-icon {
        display: inline-flex;
        flex: none;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 10px;
        background: linear-gradient(150deg, #ffffff 0%, #ede9fe 100%);
        color: #7c3aed;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .9), 0 6px 14px -8px rgba(124, 58, 237, .55);
    }

    .sad-section-icon svg {
        width: 17px;
        height: 17px;
    }

    .sad-dialog {
        width: min(640px, calc(100vw - 32px));
        max-width: none;
        margin: auto;
        padding: 0;
        border: 1px solid #ece9f7;
        border-radius: 22px;
        background: #ffffff;
        color: inherit;
        box-shadow: 0 30px 60px -20px rgba(46, 16, 101, 0.35);
    }

    .sad-dialog::backdrop {
        background: rgba(30, 20, 70, 0.35);
        backdrop-filter: blur(2px);
    }
</style>
