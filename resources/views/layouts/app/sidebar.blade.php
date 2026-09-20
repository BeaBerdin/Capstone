<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')

    <style>
        :root {
            --pw-sidebar-width: 255px;
            --pw-purple: #6d28d9;
            --pw-purple-dark: #5b21b6;
            --pw-purple-light: #f4f0ff;
            --pw-purple-border: #ddd6fe;

            --pw-text: #111827;
            --pw-text-muted: #64748b;
            --pw-text-soft: #94a3b8;

            --pw-border: #eceef3;
            --pw-background: #f7f8fc;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
        }

        body {
            background: var(--pw-background);
            color: var(--pw-text);
        }


        /* =====================================================
           SIDEBAR
        ===================================================== */

        .pw-sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;

            z-index: 50;

            width: var(--pw-sidebar-width);
            height: 100vh;

            display: flex;
            flex-direction: column;

            overflow: hidden;

            background: #ffffff;

            border-right: 1px solid var(--pw-border);

            box-shadow:
                3px 0 20px rgba(15, 23, 42, 0.025);
        }


        /* =====================================================
           BRAND
        ===================================================== */

        .pw-brand {
            display: flex;
            align-items: center;

            gap: 12px;

            min-height: 92px;

            padding: 20px 22px;

            text-decoration: none;

            border-bottom: 1px solid #f1f2f6;
        }

        .pw-brand:hover {
            opacity: .94;
        }

        .pw-brand-icon {
            width: 44px;
            height: 44px;

            min-width: 44px;
            min-height: 44px;

            display: flex;
            align-items: center;
            justify-content: center;

            overflow: hidden;

            border-radius: 13px;

            background:
                linear-gradient(
                    135deg,
                    rgba(124, 58, 237, .12),
                    rgba(147, 51, 234, .08)
                );

            box-shadow:
                0 7px 18px rgba(124, 58, 237, .12);
        }

        .pw-brand-icon img {
            display: block;

            width: 34px;
            height: 34px;

            max-width: 34px;
            max-height: 34px;

            object-fit: contain;
        }

        .pw-brand-copy {
            min-width: 0;
        }

        .pw-brand-title {
            margin: 0;

            color: #171717;

            font-size: 16px;
            font-weight: 800;

            line-height: 1;

            letter-spacing: .06em;
        }

        .pw-brand-subtitle {
            margin-top: 6px;

            color: #7c3aed;

            font-size: 8px;
            font-weight: 700;

            line-height: 1.35;

            letter-spacing: .16em;

            text-transform: uppercase;
        }


        /* =====================================================
           NAVIGATION
        ===================================================== */

        .pw-sidebar-scroll {
            flex: 1 1 auto;

            min-height: 0;

            padding: 17px 14px 20px;

            overflow-x: hidden;
            overflow-y: auto;

            scrollbar-width: thin;
            scrollbar-color: #d8dce5 transparent;
        }

        .pw-sidebar-scroll::-webkit-scrollbar {
            width: 5px;
        }

        .pw-sidebar-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .pw-sidebar-scroll::-webkit-scrollbar-thumb {
            border-radius: 999px;
            background: #d8dce5;
        }

        .pw-nav-section {
            margin-bottom: 24px;
        }

        .pw-nav-section:last-child {
            margin-bottom: 0;
        }

        .pw-nav-heading {
            margin: 0 0 8px;

            padding: 0 10px;

            color: #9ca3af;

            font-size: 10px;
            font-weight: 700;

            line-height: 20px;

            letter-spacing: .08em;

            text-transform: uppercase;
        }


        /* =====================================================
           NAV LINKS
        ===================================================== */

        .pw-nav-link {
            position: relative;

            width: 100%;
            min-height: 42px;

            display: flex;
            align-items: center;

            gap: 12px;

            margin-bottom: 3px;

            padding: 10px 12px;

            overflow: hidden;

            border-radius: 10px;

            color: #475569;

            font-size: 13px;
            font-weight: 500;

            line-height: 1.25;

            text-decoration: none;

            transition:
                background-color .16s ease,
                color .16s ease,
                transform .16s ease;
        }

        .pw-nav-link:hover {
            color: var(--pw-purple);

            background: #f8f6ff;

            transform: translateX(1px);
        }


        /* =====================================================
           ICON FIX
           Explicit dimensions prevent giant SVG icons
        ===================================================== */

        .pw-nav-icon {
            display: block;

            width: 18px !important;
            height: 18px !important;

            min-width: 18px !important;
            min-height: 18px !important;

            max-width: 18px !important;
            max-height: 18px !important;

            flex: 0 0 18px !important;

            color: #64748b;

            object-fit: contain;
        }

        .pw-nav-link:hover .pw-nav-icon {
            color: var(--pw-purple);
        }


        /* =====================================================
           ACTIVE PAGE
        ===================================================== */

        .pw-nav-link.is-active {
            color: var(--pw-purple);

            font-weight: 650;

            background:
                linear-gradient(
                    90deg,
                    #f3e8ff 0%,
                    #f5f3ff 100%
                );

            box-shadow:
                inset 3px 0 0 #7c3aed;
        }

        .pw-nav-link.is-active .pw-nav-icon {
            color: #7c3aed;
        }


        /* =====================================================
           ACCOUNT / BOTTOM
        ===================================================== */

        .pw-sidebar-bottom {
            flex: 0 0 auto;

            padding: 15px 14px 14px;

            background: #ffffff;

            border-top: 1px solid var(--pw-border);
        }

        .pw-account-heading {
            margin-bottom: 7px;

            padding: 0 10px;

            color: #9ca3af;

            font-size: 10px;
            font-weight: 700;

            letter-spacing: .08em;

            text-transform: uppercase;
        }

        .pw-user-menu {
            margin-top: 10px;
        }


        /* =====================================================
           MAIN CONTENT
        ===================================================== */
.pw-main-content {
    width: calc(100% - var(--pw-sidebar-width));
    min-height: 100vh;
    margin-left: var(--pw-sidebar-width);

    background: #f7f8fc;
    color: #18181b;

    transition:
        background-color .2s ease,
        color .2s ease;
}

html.dark .pw-main-content {
    background: #09090b;
    color: #f4f4f5;
}
/* =====================================================
   DARK MODE — SIDEBAR
===================================================== */

html.dark body {
    background: #09090b;
    color: #f4f4f5;
}

html.dark .pw-sidebar {
    background: #111113;
    border-right-color: #27272a;

    box-shadow:
        3px 0 20px rgba(0, 0, 0, 0.18);
}

/* BRAND */

html.dark .pw-brand {
    border-bottom-color: #27272a;
}

html.dark .pw-brand-icon {
    background:
        linear-gradient(
            135deg,
            rgba(124, 58, 237, .22),
            rgba(147, 51, 234, .12)
        );

    box-shadow:
        0 7px 18px rgba(124, 58, 237, .12);
}

html.dark .pw-brand-title {
    color: #fafafa;
}

html.dark .pw-brand-subtitle {
    color: #a78bfa;
}


/* NAVIGATION */

html.dark .pw-sidebar-scroll {
    scrollbar-color: #3f3f46 transparent;
}

html.dark .pw-sidebar-scroll::-webkit-scrollbar-thumb {
    background: #3f3f46;
}

html.dark .pw-nav-heading {
    color: #71717a;
}

html.dark .pw-nav-link {
    color: #d4d4d8;
}

html.dark .pw-nav-icon {
    color: #a1a1aa;
}

html.dark .pw-nav-link:hover {
    color: #c4b5fd;
    background: rgba(124, 58, 237, 0.12);
}

html.dark .pw-nav-link:hover .pw-nav-icon {
    color: #a78bfa;
}


/* ACTIVE NAV ITEM */

html.dark .pw-nav-link.is-active {
    color: #ffffff;

    background:
        linear-gradient(
            90deg,
            rgba(124, 58, 237, 0.30) 0%,
            rgba(99, 102, 241, 0.12) 100%
        );

    box-shadow:
        inset 3px 0 0 #8b5cf6;
}

html.dark .pw-nav-link.is-active .pw-nav-icon {
    color: #c4b5fd;
}


/* BOTTOM ACCOUNT AREA */

html.dark .pw-sidebar-bottom {
    background: #111113;
    border-top-color: #27272a;
}

html.dark .pw-account-heading {
    color: #71717a;
}


/* MOBILE */

html.dark .pw-mobile-toggle {
    background: #18181b;
    border-color: #3f3f46;
    color: #f4f4f5;

    box-shadow:
        0 4px 16px rgba(0, 0, 0, .20);
}


        /* =====================================================
           MOBILE
        ===================================================== */

        .pw-mobile-toggle {
            display: none;
        }

        .pw-mobile-overlay {
            display: none;
        }

        @media (max-width: 1023px) {

            .pw-sidebar {
                transform: translateX(-100%);

                transition: transform .22s ease;
            }

            .pw-sidebar.pw-sidebar-open {
                transform: translateX(0);
            }

            .pw-main-content {
                width: 100%;
                margin-left: 0;
            }

            .pw-mobile-toggle {
                position: fixed;

                top: 14px;
                left: 14px;

                z-index: 70;

                width: 42px;
                height: 42px;

                display: flex;
                align-items: center;
                justify-content: center;

                border: 1px solid #e5e7eb;
                border-radius: 11px;

                background: #ffffff;

                color: #374151;

                cursor: pointer;

                box-shadow:
                    0 4px 16px rgba(15, 23, 42, .08);
            }

            .pw-mobile-toggle svg {
                width: 20px !important;
                height: 20px !important;

                min-width: 20px !important;
                min-height: 20px !important;
            }

            .pw-mobile-overlay.pw-overlay-open {
                position: fixed;

                inset: 0;

                z-index: 40;

                display: block;

                background: rgba(15, 23, 42, .38);

                backdrop-filter: blur(2px);
            }
        }
    
        /* =====================================================
           GLOBAL TOPBAR
           Shared across Teacher / Admin / Super Admin / Student
        ===================================================== */

        .pw-topbar {
            position: sticky;
            top: 0;
            z-index: 35;

            height: 68px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 20px;

            padding: 0 28px;

            background: rgba(255, 255, 255, .96);
            border-bottom: 1px solid #eceef3;

            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);

            box-shadow:
                0 1px 2px rgba(15, 23, 42, .025);
        }

        .pw-topbar-left {
            min-width: 0;
            flex: 1 1 auto;

            display: flex;
            align-items: center;
        }

        .pw-global-search {
            position: relative;

            width: min(360px, 100%);
        }

        .pw-global-search-icon {
            position: absolute;
            top: 50%;
            left: 14px;

            width: 17px;
            height: 17px;

            transform: translateY(-50%);

            color: #94a3b8;

            pointer-events: none;
        }

        .pw-global-search-input {
            width: 100%;
            height: 40px;

            padding: 0 42px 0 40px;

            border: 1px solid #e2e8f0;
            border-radius: 10px;

            outline: none;

            background: #f8fafc;
            color: #1e293b;

            font-size: 12px;
            font-weight: 500;

            transition:
                background-color .16s ease,
                border-color .16s ease,
                box-shadow .16s ease;
        }

        .pw-global-search-input::placeholder {
            color: #94a3b8;
        }

        .pw-global-search-input:focus {
            background: #ffffff;
            border-color: #c4b5fd;

            box-shadow:
                0 0 0 4px rgba(124, 58, 237, .08);
        }

        .pw-global-search-kbd {
            position: absolute;
            top: 50%;
            right: 11px;

            transform: translateY(-50%);

            min-width: 21px;
            height: 21px;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 0 5px;

            border: 1px solid #e2e8f0;
            border-radius: 6px;

            background: #ffffff;
            color: #94a3b8;

            font-size: 9px;
            font-weight: 700;
        }

        .pw-search-results {
            position: absolute;
            top: calc(100% + 9px);
            left: 0;

            z-index: 80;

            width: min(430px, calc(100vw - 32px));

            display: none;

            overflow: hidden;

            border: 1px solid #e5e7eb;
            border-radius: 14px;

            background: #ffffff;

            box-shadow:
                0 18px 50px rgba(15, 23, 42, .14);
        }

        .pw-search-results.is-open {
            display: block;
        }

        .pw-search-result-header {
            padding: 11px 14px;

            border-bottom: 1px solid #f1f5f9;

            color: #94a3b8;

            font-size: 9px;
            font-weight: 800;

            letter-spacing: .1em;
            text-transform: uppercase;
        }

        .pw-search-result-list {
            max-height: 320px;
            overflow-y: auto;

            padding: 6px;
        }

        .pw-search-result-item {
            width: 100%;

            display: flex;
            align-items: center;

            gap: 11px;

            padding: 10px 11px;

            border-radius: 10px;

            color: #475569;

            text-decoration: none;

            transition:
                color .15s ease,
                background-color .15s ease;
        }

        .pw-search-result-item:hover,
        .pw-search-result-item.is-focused {
            color: #6d28d9;
            background: #f8f6ff;
        }

        .pw-search-result-item-icon {
            width: 32px;
            height: 32px;

            display: flex;
            align-items: center;
            justify-content: center;

            flex: 0 0 32px;

            border-radius: 9px;

            background: #f5f3ff;
            color: #7c3aed;
        }

        .pw-search-result-item-icon svg {
            width: 16px;
            height: 16px;
        }

        .pw-search-result-copy {
            min-width: 0;
        }

        .pw-search-result-title {
            color: inherit;

            font-size: 12px;
            font-weight: 700;
        }

        .pw-search-result-meta {
            margin-top: 2px;

            color: #94a3b8;

            font-size: 10px;
            font-weight: 500;
        }

        .pw-search-empty {
            padding: 22px;

            color: #94a3b8;

            font-size: 12px;
            text-align: center;
        }

        .pw-topbar-actions {
            flex: 0 0 auto;

            display: flex;
            align-items: center;

            gap: 8px;
        }

        .pw-topbar-icon-button {
            position: relative;

            width: 39px;
            height: 39px;

            display: flex;
            align-items: center;
            justify-content: center;

            flex: 0 0 39px;

            border: 1px solid transparent;
            border-radius: 10px;

            background: transparent;
            color: #64748b;

            cursor: pointer;

            transition:
                color .15s ease,
                background-color .15s ease,
                border-color .15s ease;
        }

        .pw-topbar-icon-button:hover {
            color: #6d28d9;
            background: #f8f6ff;
            border-color: #eee9ff;
        }

        .pw-topbar-icon-button svg {
            width: 19px;
            height: 19px;
        }

        .pw-notification-badge {
            position: absolute;
            top: 3px;
            right: 2px;

            min-width: 17px;
            height: 17px;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 0 4px;

            border: 2px solid #ffffff;
            border-radius: 999px;

            background: #ef4444;
            color: #ffffff;

            font-size: 8px;
            font-weight: 800;
            line-height: 1;
        }

        .pw-topbar-dropdown-wrap {
            position: relative;
        }

        .pw-topbar-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;

            z-index: 90;

            width: min(390px, calc(100vw - 24px));

            display: none;

            overflow: hidden;

            border: 1px solid #e5e7eb;
            border-radius: 14px;

            background: #ffffff;

            box-shadow:
                0 20px 55px rgba(15, 23, 42, .15);
        }

        .pw-topbar-dropdown.is-open {
            display: block;
        }

        .pw-topbar-dropdown-header {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 12px;

            padding: 15px 16px;

            border-bottom: 1px solid #f1f5f9;
        }

        .pw-topbar-dropdown-title {
            color: #111827;

            font-size: 13px;
            font-weight: 800;
        }

        .pw-topbar-dropdown-subtitle {
            color: #94a3b8;

            font-size: 10px;
            font-weight: 600;
        }

        .pw-notification-list {
            max-height: 430px;
            overflow-y: auto;
            padding: 6px;
            background: #ffffff;
        }

        .pw-notification-list::-webkit-scrollbar {
            width: 5px;
        }

        .pw-notification-list::-webkit-scrollbar-track {
            background: transparent;
        }

        .pw-notification-list::-webkit-scrollbar-thumb {
            border-radius: 999px;
            background: #d8dce5;
        }

        .pw-notification-form {
            margin: 0;
        }

        .pw-notification-item-button {
            width: 100%;

            display: flex;
            align-items: flex-start;
            gap: 12px;

            margin: 0;
            padding: 13px 12px;

            border: 0;
            border-radius: 12px;

            background: transparent;

            font: inherit;
            text-align: left;

            cursor: pointer;

            transition:
                background-color .16s ease,
                transform .16s ease;
        }

        .pw-notification-item-button:hover {
            background: #faf7ff;
        }

        .pw-notification-item-button.is-unread {
            background:
                linear-gradient(
                    90deg,
                    rgba(124, 58, 237, .075),
                    rgba(99, 102, 241, .035)
                );
        }

        .pw-notification-item-button.is-unread:hover {
            background:
                linear-gradient(
                    90deg,
                    rgba(124, 58, 237, .11),
                    rgba(99, 102, 241, .06)
                );
        }

        .pw-notification-icon {
            width: 36px;
            height: 36px;

            display: flex;
            align-items: center;
            justify-content: center;

            flex: 0 0 36px;

            border-radius: 11px;

            background: #f5f3ff;
            color: #7c3aed;
        }

        .pw-notification-icon svg {
            width: 17px;
            height: 17px;
        }

        .pw-notification-copy {
            min-width: 0;
            flex: 1 1 auto;
        }

        .pw-notification-title-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;

            gap: 10px;
        }

        .pw-notification-title {
            color: #1e293b;

            font-size: 11px;
            font-weight: 800;
            line-height: 1.35;
        }

        .pw-notification-new {
            flex: 0 0 auto;

            padding: 3px 6px;

            border-radius: 999px;

            background: #ede9fe;
            color: #6d28d9;

            font-size: 8px;
            font-weight: 800;
            line-height: 1;

            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .pw-notification-message {
            margin-top: 3px;

            color: #64748b;

            font-size: 10px;
            font-weight: 550;
            line-height: 1.5;
        }

        .pw-notification-time {
            margin-top: 7px;

            display: flex;
            align-items: center;
            gap: 5px;

            color: #94a3b8;

            font-size: 9px;
            font-weight: 600;
        }

        .pw-notification-time svg {
            width: 11px;
            height: 11px;
        }

        .pw-notification-mark-all {
            min-height: 30px;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            padding: 0 10px;

            border: 1px solid #ddd6fe;
            border-radius: 9px;

            background: #f5f3ff;
            color: #6d28d9;

            font-size: 9px;
            font-weight: 800;

            cursor: pointer;

            transition:
                background-color .15s ease,
                border-color .15s ease,
                color .15s ease;
        }

        .pw-notification-mark-all:hover {
            border-color: #c4b5fd;
            background: #ede9fe;
            color: #5b21b6;
        }

        .pw-notification-empty {
            padding: 38px 22px 42px;

            text-align: center;
        }

        .pw-notification-empty-icon {
            width: 48px;
            height: 48px;

            margin: 0 auto 12px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 15px;

            background: #f5f3ff;
            color: #7c3aed;
        }

        .pw-notification-empty-icon svg {
            width: 21px;
            height: 21px;
        }

        .pw-notification-empty-title {
            color: #334155;

            font-size: 11px;
            font-weight: 800;
        }

        .pw-notification-empty-copy {
            margin-top: 4px;

            color: #94a3b8;

            font-size: 9px;
            font-weight: 550;
            line-height: 1.5;
        }

        .pw-profile-trigger {
            min-height: 44px;

            display: flex;
            align-items: center;

            gap: 10px;

            padding: 4px 7px 4px 5px;

            border: 1px solid transparent;
            border-radius: 12px;

            background: transparent;
            color: #1f2937;

            cursor: pointer;

            transition:
                background-color .15s ease,
                border-color .15s ease;
        }

        .pw-profile-trigger:hover {
            background: #f8fafc;
            border-color: #eef2f7;
        }

        .pw-profile-avatar {
            width: 34px;
            height: 34px;

            overflow: hidden;

            display: flex;
            align-items: center;
            justify-content: center;

            flex: 0 0 34px;

            border-radius: 999px;

            background:
                linear-gradient(
                    135deg,
                    #ede9fe,
                    #ddd6fe
                );

            color: #6d28d9;

            font-size: 11px;
            font-weight: 800;
        }

        .pw-profile-avatar img {
            display: block;

            width: 100%;
            height: 100%;

            object-fit: cover;
            border-radius: inherit;
        }

        .pw-profile-copy {
            min-width: 0;

            text-align: left;
        }

        .pw-profile-name {
            max-width: 135px;

            overflow: hidden;

            color: #1f2937;

            font-size: 11px;
            font-weight: 800;

            line-height: 1.15;

            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .pw-profile-role {
            margin-top: 3px;

            color: #94a3b8;

            font-size: 9px;
            font-weight: 600;
        }

        .pw-profile-chevron {
            width: 14px;
            height: 14px;

            color: #94a3b8;
        }

        .pw-profile-menu {
            width: 245px;
        }

        .pw-profile-menu-user {
            padding: 15px 16px;

            border-bottom: 1px solid #f1f5f9;
        }

        .pw-profile-menu-name {
            color: #111827;

            font-size: 12px;
            font-weight: 800;
        }

        .pw-profile-menu-email {
            margin-top: 3px;

            overflow: hidden;

            color: #94a3b8;

            font-size: 10px;

            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .pw-profile-menu-links {
            padding: 6px;
        }

        .pw-profile-menu-link {
            width: 100%;

            display: flex;
            align-items: center;

            gap: 10px;

            padding: 10px;

            border: 0;
            border-radius: 9px;

            background: transparent;
            color: #475569;

            font: inherit;
            font-size: 11px;
            font-weight: 650;

            text-align: left;
            text-decoration: none;

            cursor: pointer;

            transition:
                background-color .15s ease,
                color .15s ease;
        }

        .pw-profile-menu-link:hover {
            color: #6d28d9;
            background: #f8f6ff;
        }

        .pw-profile-menu-link.is-danger:hover {
            color: #dc2626;
            background: #fef2f2;
        }

        .pw-profile-menu-link svg {
            width: 16px;
            height: 16px;

            flex: 0 0 16px;
        }

        html.dark .pw-topbar {
            background: rgba(17, 17, 19, .96);
            border-bottom-color: #27272a;
        }

        html.dark .pw-global-search-input {
            background: #18181b;
            border-color: #3f3f46;
            color: #f4f4f5;
        }

        html.dark .pw-global-search-input:focus {
            background: #18181b;
            border-color: #7c3aed;
        }

        html.dark .pw-global-search-kbd,
        html.dark .pw-search-results,
        html.dark .pw-topbar-dropdown {
            background: #18181b;
            border-color: #3f3f46;
        }

        html.dark .pw-search-result-header,
        html.dark .pw-topbar-dropdown-header,
        html.dark .pw-profile-menu-user {
            border-color: #27272a;
        }

        html.dark .pw-search-result-item {
            color: #d4d4d8;
        }

        html.dark .pw-search-result-item:hover,
        html.dark .pw-search-result-item.is-focused,
        html.dark .pw-profile-menu-link:hover {
            background: rgba(124, 58, 237, .13);
        }

        html.dark .pw-topbar-icon-button {
            color: #a1a1aa;
        }

        html.dark .pw-topbar-icon-button:hover {
            color: #c4b5fd;
            background: rgba(124, 58, 237, .12);
            border-color: rgba(124, 58, 237, .20);
        }

        html.dark .pw-notification-badge {
            border-color: #111113;
        }

        html.dark .pw-topbar-dropdown-title,
        html.dark .pw-profile-name,
        html.dark .pw-profile-menu-name {
            color: #fafafa;
        }

        html.dark .pw-notification-message,
        html.dark .pw-profile-menu-link {
            color: #d4d4d8;
        }

        html.dark .pw-notification-item {
            border-bottom-color: #27272a;
        }

        html.dark .pw-profile-trigger {
            color: #f4f4f5;
        }

        html.dark .pw-profile-trigger:hover {
            background: #18181b;
            border-color: #27272a;
        }

        html.dark .pw-notification-list {
            background: #18181b;
        }

        html.dark .pw-notification-item-button:hover {
            background: rgba(124, 58, 237, .11);
        }

        html.dark .pw-notification-item-button.is-unread {
            background:
                linear-gradient(
                    90deg,
                    rgba(124, 58, 237, .18),
                    rgba(99, 102, 241, .08)
                );
        }

        html.dark .pw-notification-icon {
            background: rgba(124, 58, 237, .16);
            color: #c4b5fd;
        }

        html.dark .pw-notification-title,
        html.dark .pw-notification-empty-title {
            color: #f4f4f5;
        }

        html.dark .pw-notification-message {
            color: #a1a1aa;
        }

        html.dark .pw-notification-new {
            background: rgba(124, 58, 237, .20);
            color: #c4b5fd;
        }

        html.dark .pw-notification-mark-all {
            border-color: rgba(124, 58, 237, .28);
            background: rgba(124, 58, 237, .14);
            color: #c4b5fd;
        }

        html.dark .pw-notification-mark-all:hover {
            background: rgba(124, 58, 237, .22);
            color: #ddd6fe;
        }

        @media (max-width: 1023px) {
            .pw-topbar {
                padding:
                    0 16px
                    0 70px;
            }

            .pw-profile-copy {
                display: none;
            }
        }

        @media (max-width: 700px) {
            .pw-topbar {
                gap: 8px;
            }

            .pw-global-search {
                max-width: none;
            }

            .pw-global-search-kbd {
                display: none;
            }

            .pw-global-search-input {
                padding-right: 14px;
            }

            .pw-topbar-actions {
                gap: 2px;
            }

            .pw-profile-trigger {
                padding-right: 2px;
            }

            .pw-profile-chevron {
                display: none;
            }
        }

        @media (max-width: 540px) {
            .pw-global-search-input::placeholder {
                color: transparent;
            }

            .pw-topbar {
                height: 64px;
            }

            .pw-topbar-icon-button {
                width: 36px;
                height: 36px;

                flex-basis: 36px;
            }

            .pw-profile-avatar {
                width: 32px;
                height: 32px;

                flex-basis: 32px;
            }

            .pw-topbar-dropdown {
                position: fixed;
                top: 70px;
                right: 12px;
                left: 12px;

                width: auto;
            }
        }

</style>
</head>


<body>


@php
    $pwUser = auth()->user();

    $pwRoleLabel = match (true) {
        $pwUser->hasRole('super_admin') => 'Super Administrator',
        $pwUser->hasRole('admin') => 'Administrator',
        $pwUser->hasRole('teacher') => 'Teacher',
        $pwUser->hasRole('student') => 'Student',
        default => 'User',
    };

    $pwInitials = collect(preg_split('/\s+/', trim($pwUser->name ?? 'User')))
        ->filter()
        ->take(2)
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->implode('');

    if ($pwInitials === '') {
        $pwInitials = 'U';
    }

    $pwSearchItems = collect();

    if ($pwUser->hasRole('super_admin')) {
        $pwSearchItems = collect([
            ['label' => 'Dashboard', 'meta' => 'Overview', 'url' => route('super_admin.dashboard')],
            ['label' => 'User Management', 'meta' => 'Manage users and roles', 'url' => route('users.index')],
            ['label' => 'Transactions', 'meta' => 'Review transactions', 'url' => route('super_admin.transactions.index')],
            ['label' => 'Settings', 'meta' => 'Profile and security', 'url' => route('profile.edit')],
        ]);
    } elseif ($pwUser->hasRole('admin')) {
        $pwSearchItems = collect([
            ['label' => 'Dashboard', 'meta' => 'Overview', 'url' => route('admin.dashboard')],
            ['label' => 'Courses', 'meta' => 'Course management', 'url' => route('courses.index')],
            ['label' => 'Categories', 'meta' => 'Course categories', 'url' => route('course-categories.index')],
            ['label' => 'Lessons', 'meta' => 'Learning content', 'url' => route('lessons.index')],
            ['label' => 'Quizzes', 'meta' => 'Assessment management', 'url' => route('quizzes.index')],
            ['label' => 'AI Recommendations', 'meta' => 'Recommendation tools', 'url' => route('ai-recommendations.index')],
            ['label' => 'Student Progress', 'meta' => 'Learner performance', 'url' => route('student-progress.index')],
            ['label' => 'Certificates', 'meta' => 'Certificate management', 'url' => route('certificate-management.index')],
            ['label' => 'Settings', 'meta' => 'Profile and security', 'url' => route('profile.edit')],
        ]);
    } elseif ($pwUser->hasRole('teacher')) {
        $pwSearchItems = collect([
            ['label' => 'Dashboard', 'meta' => 'Teaching overview', 'url' => route('teacher.dashboard')],
            ['label' => 'My Courses', 'meta' => 'Manage your courses', 'url' => route('teacher.my-courses')],
            ['label' => 'Lessons', 'meta' => 'Manage learning content', 'url' => route('teacher.lessons.index')],
            ['label' => 'Assignments', 'meta' => 'Create and review graded activities', 'url' => route('teacher.assignments.index')],
            ['label' => 'Quiz Results', 'meta' => 'Review quiz performance', 'url' => route('teacher.quiz-results.index')],
            ['label' => 'Student Progress', 'meta' => 'Track learners', 'url' => route('teacher.student-progress.index')],
            ['label' => 'Student Invitations', 'meta' => 'Invite learners via unique links', 'url' => route('course-invitations.index')],
            ['label' => 'Performance Analytics', 'meta' => 'Reports and learner performance', 'url' => route('teacher.analytics')],
            ['label' => 'Settings', 'meta' => 'Profile and security', 'url' => route('profile.edit')],
        ]);
    } elseif ($pwUser->hasRole('student')) {
        $pwSearchItems = collect([
            ['label' => 'Dashboard', 'meta' => 'Learning overview', 'url' => route('student.dashboard')],
            ['label' => 'Marketplace', 'meta' => 'Browse courses', 'url' => route('student.marketplace')],
            ['label' => 'My Courses', 'meta' => 'Your enrolled courses', 'url' => route('student.my-courses')],
            ['label' => 'Assignments', 'meta' => 'View and submit course assignments', 'url' => route('student.assignments.index')],
            ['label' => 'Learning Paths', 'meta' => 'Personalized learning paths', 'url' => route('student.learning-paths')],
            ['label' => 'AI Recommendations', 'meta' => 'Recommended courses', 'url' => route('student.recommendations')],
            ['label' => 'Transactions', 'meta' => 'Payment records', 'url' => route('student.transactions')],
            ['label' => 'Certificates', 'meta' => 'Your certificates', 'url' => route('student.certificates')],
            ['label' => 'Settings', 'meta' => 'Profile and security', 'url' => route('profile.edit')],
        ]);
    }

    $pwNotifications = collect();
    $pwUnreadCount = 0;

    try {
        if (method_exists($pwUser, 'notifications')) {
            $pwNotifications = $pwUser->notifications()
                ->latest()
                ->take(5)
                ->get();

            if (method_exists($pwUser, 'unreadNotifications')) {
                $pwUnreadCount = $pwUser->unreadNotifications()->count();
            }
        }
    } catch (\Throwable $e) {
        $pwNotifications = collect();
        $pwUnreadCount = 0;
    }
@endphp


    {{-- =====================================================
         MOBILE MENU BUTTON
    ====================================================== --}}

    <button
        type="button"
        class="pw-mobile-toggle"
        onclick="pwToggleSidebar()"
        aria-label="Open sidebar"
    >
        <svg
            width="20"
            height="20"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
        >
            <path
                stroke-linecap="round"
                d="M4 6h16M4 12h16M4 18h16"
            />
        </svg>
    </button>


    <div
        id="pwSidebarOverlay"
        class="pw-mobile-overlay"
        onclick="pwCloseSidebar()"
    ></div>


    {{-- =====================================================
         SIDEBAR
    ====================================================== --}}

    <aside
        id="pwSidebar"
        class="pw-sidebar"
    >

        {{-- =================================================
             BRAND
        ================================================== --}}

        <a
            href="{{ route('dashboard') }}"
            class="pw-brand"
            wire:navigate
        >

            <div class="pw-brand-icon">

                <img
                    src="{{ asset('images/pathwise-icon.png') }}"
                    alt="PathWise"
                >

            </div>


            <div class="pw-brand-copy">

                <div class="pw-brand-title">
                    PATHWISE
                </div>

                <div class="pw-brand-subtitle">
                    An AI Learning<br>
                    Platform
                </div>

            </div>

        </a>



        {{-- =================================================
             SCROLLABLE NAVIGATION
        ================================================== --}}

        <nav class="pw-sidebar-scroll">


            {{-- =================================================
                 MAIN
            ================================================== --}}

            <section class="pw-nav-section">

                <div class="pw-nav-heading">
                    Main
                </div>


                <a
                    href="{{ route('dashboard') }}"
                    wire:navigate
                    class="pw-nav-link {{ request()->routeIs('dashboard', 'teacher.dashboard', 'admin.dashboard', 'super_admin.dashboard', 'student.dashboard') ? 'is-active' : '' }}"
                >

                    <svg
                        class="pw-nav-icon"
                        width="18"
                        height="18"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M3 10.5 12 3l9 7.5M5.25 9.75V21h13.5V9.75M9 21v-6h6v6"
                        />
                    </svg>

                    <span>
                        Dashboard
                    </span>

                </a>

            </section>



            {{-- =================================================
                 SUPER ADMIN
            ================================================== --}}

            @if(auth()->user()->hasRole('super_admin'))

                <section class="pw-nav-section">

                    <div class="pw-nav-heading">
                        Management
                    </div>


                    <a
                        href="{{ route('users.index') }}"
                        wire:navigate
                        class="pw-nav-link {{ request()->routeIs('users.*') ? 'is-active' : '' }}"
                    >

                        <svg
                            class="pw-nav-icon"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"
                            />

                            <circle
                                cx="9"
                                cy="7"
                                r="4"
                            />

                            <path
                                stroke-linecap="round"
                                d="M19 8v6M22 11h-6"
                            />
                        </svg>

                        <span>
                            User Management
                        </span>

                    </a>

                     {{-- Department Management --}}
        <a
            href="{{ route('departments.index') }}"
            wire:navigate
            class="pw-nav-link {{ request()->routeIs('departments.*') ? 'is-active' : '' }}"
        >
            <svg
                class="pw-nav-icon"
                width="18"
                height="18"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M3 21h18"
                />

                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M5 21V8l7-5 7 5v13"
                />

                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M9 21v-6h6v6"
                />

                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M8 10h.01M12 10h.01M16 10h.01"
                />
            </svg>

            <span>
                Department Management
            </span>
        </a>

    </section>

                  {{-- Transactions --}}
                    <a
                        href="{{ route('super_admin.transactions.index') }}"
                        wire:navigate
                        class="pw-nav-link {{ request()->routeIs('super_admin.transactions.*') ? 'is-active' : '' }}"
                    >

                        <svg
                            class="pw-nav-icon"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <rect
                                x="3"
                                y="5"
                                width="18"
                                height="14"
                                rx="2"
                            />

                            <path d="M3 10h18" />
                        </svg>

                        <span>
                            Transactions
                        </span>

                    </a>

                </section>

                    {{-- Reports --}}
        <a
            href="{{ route('reports.index') }}"
            wire:navigate
            class="pw-nav-link {{ request()->routeIs('reports.*') ? 'is-active' : '' }}"
        >
            <svg
                class="pw-nav-icon"
                width="18"
                height="18"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M4 19V5"
                />

                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M4 19h16"
                />

                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M8 16v-5"
                />

                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 16V7"
                />

                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M16 16v-9"
                />

                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M20 16V4"
                />
            </svg>

            <span>
                Reports
            </span>
        </a>

    </section>


            {{-- =================================================
                 ADMIN
            ================================================== --}}

            @elseif(auth()->user()->hasRole('admin'))

                <section class="pw-nav-section">

                    <div class="pw-nav-heading">
                        Management
                    </div>


                    {{-- Courses --}}
                    <a
                        href="{{ route('courses.index') }}"
                        wire:navigate
                        class="pw-nav-link {{ request()->routeIs('courses.*') ? 'is-active' : '' }}"
                    >

                        <svg
                            class="pw-nav-icon"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"
                            />
                        </svg>

                        <span>
                            Courses
                        </span>

                    </a>


                    {{-- Categories --}}
                    <a
                        href="{{ route('course-categories.index') }}"
                        wire:navigate
                        class="pw-nav-link {{ request()->routeIs('course-categories.*') ? 'is-active' : '' }}"
                    >

                        <svg
                            class="pw-nav-icon"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M3 6h7l2 2h9v11H3V6Z"
                            />
                        </svg>

                        <span>
                            Categories
                        </span>

                    </a>


                    {{-- Lessons --}}
                    <a
                        href="{{ route('lessons.index') }}"
                        wire:navigate
                        class="pw-nav-link {{ request()->routeIs('lessons.*') ? 'is-active' : '' }}"
                    >

                        <svg
                            class="pw-nav-icon"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"
                            />

                            <path d="M14 2v6h6M8 13h8M8 17h6" />
                        </svg>

                        <span>
                            Lessons
                        </span>

                    </a>


                    {{-- Quizzes --}}
                    <a
                        href="{{ route('quizzes.index') }}"
                        wire:navigate
                        class="pw-nav-link {{ request()->routeIs('quizzes.*') ? 'is-active' : '' }}"
                    >

                        <svg
                            class="pw-nav-icon"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <rect
                                x="5"
                                y="3"
                                width="14"
                                height="18"
                                rx="2"
                            />

                            <path d="M9 7h6M9 11h6M9 15h3" />
                        </svg>

                        <span>
                            Quizzes
                        </span>

                    </a>

                </section>


                <section class="pw-nav-section">

                    <div class="pw-nav-heading">
                        Analytics
                    </div>


                    {{-- AI --}}
                    <a
                        href="{{ route('ai-recommendations.index') }}"
                        wire:navigate
                        class="pw-nav-link {{ request()->routeIs('ai-recommendations.*') ? 'is-active' : '' }}"
                    >

                        <svg
                            class="pw-nav-icon"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m12 3 1.4 4.1L17.5 8.5l-4.1 1.4L12 14l-1.4-4.1-4.1-1.4 4.1-1.4L12 3Z"
                            />

                            <path
                                stroke-linecap="round"
                                d="m18 14 .8 2.2L21 17l-2.2.8L18 20l-.8-2.2L15 17l2.2-.8L18 14Z"
                            />
                        </svg>

                        <span>
                            AI Recommendations
                        </span>

                    </a>


                    {{-- Student Progress --}}
                    <a
                        href="{{ route('student-progress.index') }}"
                        wire:navigate
                        class="pw-nav-link {{ request()->routeIs('student-progress.*') ? 'is-active' : '' }}"
                    >

                        <svg
                            class="pw-nav-icon"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path d="M4 19V9M9 19V5M14 19v-7M19 19V3" />
                        </svg>

                        <span>
                            Student Progress
                        </span>

                    </a>

                </section>


                <section class="pw-nav-section">

                    <div class="pw-nav-heading">
                        Resources
                    </div>


                    <a
                        href="{{ route('certificate-management.index') }}"
                        wire:navigate
                        class="pw-nav-link {{ request()->routeIs('certificate-management.*') ? 'is-active' : '' }}"
                    >

                        <svg
                            class="pw-nav-icon"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <circle
                                cx="12"
                                cy="8"
                                r="5"
                            />

                            <path
                                stroke-linejoin="round"
                                d="m8.5 12-1 9L12 18l4.5 3-1-9"
                            />
                        </svg>

                        <span>
                            Certificates
                        </span>

                    </a>

                </section>



            {{-- =================================================
                 TEACHER
            ================================================== --}}

            @elseif(auth()->user()->hasRole('teacher'))


                {{-- TEACHING --}}
                <section class="pw-nav-section">

                    <div class="pw-nav-heading">
                        Teaching
                    </div>


                    <a
                        href="{{ route('teacher.my-courses') }}"
                        wire:navigate
                        class="pw-nav-link {{
                            request()->routeIs(
                                'teacher.my-courses',
                                'teacher.courses.*'
                            )
                                ? 'is-active'
                                : ''
                        }}"
                    >

                        <svg
                            class="pw-nav-icon"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"
                            />
                        </svg>

                        <span>
                            My Courses
                        </span>

                    </a>


                    <a
                        href="{{ route('teacher.lessons.index') }}"
                        wire:navigate
                        class="pw-nav-link {{
                            request()->routeIs('teacher.lessons.*')
                                ? 'is-active'
                                : ''
                        }}"
                    >

                        <svg
                            class="pw-nav-icon"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"
                            />
                            <path d="M14 2v6h6M8 13h8M8 17h6" />
                        </svg>

                        <span>
                            Lessons
                        </span>

                    </a>

                </section>



                {{-- ASSESSMENT --}}
                <section class="pw-nav-section">

                    <div class="pw-nav-heading">
                        Assessment
                    </div>


                    {{-- ASSIGNMENTS --}}
                    <a
                        href="{{ route('teacher.assignments.index') }}"
                        wire:navigate
                        class="pw-nav-link {{
                            request()->routeIs(
                                'teacher.assignments.*',
                                'teacher.submissions.*'
                            )
                                ? 'is-active'
                                : ''
                        }}"
                    >
                        <svg
                            class="pw-nav-icon"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M7 3h10a2 2 0 0 1 2 2v16H5V5a2 2 0 0 1 2-2Z"
                            />
                            <path
                                stroke-linecap="round"
                                d="M9 8h6M9 12h6M9 16h4"
                            />
                        </svg>

                        <span>
                            Assignments
                        </span>
                    </a>


                    <a
                        href="{{ route('teacher.quiz-results.index') }}"
                        wire:navigate
                        class="pw-nav-link {{
                            request()->routeIs('teacher.quiz-results.*')
                                ? 'is-active'
                                : ''
                        }}"
                    >

                        <svg
                            class="pw-nav-icon"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <rect
                                x="5"
                                y="3"
                                width="14"
                                height="18"
                                rx="2"
                            />
                            <path d="M9 7h6M9 11h6M9 15h3" />
                        </svg>

                        <span>
                            Quiz Results
                        </span>

                    </a>

                </section>



                {{-- LEARNERS --}}
                <section class="pw-nav-section">

                    <div class="pw-nav-heading">
                        Learners
                    </div>


                    <a
                        href="{{ route('teacher.student-progress.index') }}"
                        wire:navigate
                        class="pw-nav-link {{
                            request()->routeIs('teacher.student-progress.*')
                                ? 'is-active'
                                : ''
                        }}"
                    >

                        <svg
                            class="pw-nav-icon"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"
                            />
                            <circle cx="9" cy="7" r="4" />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m17 11 2 2 4-4"
                            />
                        </svg>

                        <span>
                            Student Progress
                        </span>

                    </a>

                </section>



                {{-- STUDENT INVITATIONS --}}
                <section class="pw-nav-section">

                    <div class="pw-nav-heading">
                        Enrollment
                    </div>


                    <a
                        href="{{ route('course-invitations.index') }}"
                        wire:navigate
                        class="pw-nav-link {{
                            request()->routeIs('course-invitations.*')
                                ? 'is-active'
                                : ''
                        }}"
                    >

                        <svg
                            class="pw-nav-icon"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"
                            />
                            <circle
                                cx="9"
                                cy="7"
                                r="4"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M19 8v6M22 11h-6"
                            />
                        </svg>

                        <span>
                            Student Invitations
                        </span>

                    </a>

                </section>



                {{-- ANALYTICS --}}
                <section class="pw-nav-section">

                    <div class="pw-nav-heading">
                        Analytics
                    </div>


                    <a
                        href="{{ route('teacher.analytics') }}"
                        wire:navigate
                        class="pw-nav-link {{
                            request()->routeIs('teacher.analytics')
                                ? 'is-active'
                                : ''
                        }}"
                    >

                        <svg
                            class="pw-nav-icon"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M4 19V9M9 19V5M14 19v-7M19 19V3"
                            />
                        </svg>

                        <span>
                            Performance Analytics
                        </span>

                    </a>

                </section>



            {{-- =================================================
                 STUDENT
            ================================================== --}}

            @elseif(auth()->user()->hasRole('student'))

                <section class="pw-nav-section">

                    <div class="pw-nav-heading">
                        Learning
                    </div>


                    {{-- MARKETPLACE --}}
                    <a
                        href="{{ route('student.marketplace') }}"
                        wire:navigate
                        class="pw-nav-link {{ request()->routeIs('student.marketplace') ? 'is-active' : '' }}"
                    >

                        <svg
                            class="pw-nav-icon"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 8V6a6 6 0 0 1 12 0v2M4 8h16l-1 13H5L4 8Z"
                            />
                        </svg>

                        <span>
                            Marketplace
                        </span>

                    </a>


                    {{-- MY COURSES --}}
                    <a
                        href="{{ route('student.my-courses') }}"
                        wire:navigate
                        class="pw-nav-link {{ request()->routeIs('student.my-courses') ? 'is-active' : '' }}"
                    >

                        <svg
                            class="pw-nav-icon"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"
                            />
                        </svg>

                        <span>
                            My Courses
                        </span>

                    </a>


                    {{-- ASSIGNMENTS --}}
                    <a
                        href="{{ route('student.assignments.index') }}"
                        wire:navigate
                        class="pw-nav-link {{
                            request()->routeIs('student.assignments.*')
                                ? 'is-active'
                                : ''
                        }}"
                    >
                        <svg
                            class="pw-nav-icon"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M7 3h10a2 2 0 0 1 2 2v16H5V5a2 2 0 0 1 2-2Z"
                            />
                            <path
                                stroke-linecap="round"
                                d="M9 8h6M9 12h6M9 16h4"
                            />
                        </svg>

                        <span>
                            Assignments
                        </span>
                    </a>


                    {{-- LEARNING PATHS --}}
                    <a
                        href="{{ route('student.learning-paths') }}"
                        wire:navigate
                        class="pw-nav-link {{ request()->routeIs('student.learning-paths*') ? 'is-active' : '' }}"
                    >

                        <svg
                            class="pw-nav-icon"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M3 6l6-3 6 3 6-3v15l-6 3-6-3-6 3V6Z"
                            />

                            <path d="M9 3v15M15 6v15" />
                        </svg>

                        <span>
                            Learning Paths
                        </span>

                    </a>


                    {{-- AI RECOMMENDATIONS --}}
                    <a
                        href="{{ route('student.recommendations') }}"
                        wire:navigate
                        class="pw-nav-link {{ request()->routeIs('student.recommendations') ? 'is-active' : '' }}"
                    >

                        <svg
                            class="pw-nav-icon"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m12 3 1.4 4.1L17.5 8.5l-4.1 1.4L12 14l-1.4-4.1-4.1-1.4 4.1-1.4L12 3Z"
                            />

                            <path
                                stroke-linecap="round"
                                d="m18 14 .8 2.2L21 17l-2.2.8L18 20l-.8-2.2L15 17l2.2-.8L18 14Z"
                            />
                        </svg>

                        <span>
                            AI Recommendations
                        </span>

                    </a>

                </section>


                <section class="pw-nav-section">

                    <div class="pw-nav-heading">
                        Records
                    </div>


                    {{-- TRANSACTIONS --}}
                    <a
                        href="{{ route('student.transactions') }}"
                        wire:navigate
                        class="pw-nav-link {{ request()->routeIs('student.transactions*') ? 'is-active' : '' }}"
                    >

                        <svg
                            class="pw-nav-icon"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <rect
                                x="3"
                                y="5"
                                width="18"
                                height="14"
                                rx="2"
                            />

                            <path d="M3 10h18" />
                        </svg>

                        <span>
                            Transactions
                        </span>

                    </a>


                    {{-- CERTIFICATES --}}
                    <a
                        href="{{ route('student.certificates') }}"
                        wire:navigate
                        class="pw-nav-link {{ request()->routeIs('student.certificates') ? 'is-active' : '' }}"
                    >

                        <svg
                            class="pw-nav-icon"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <circle
                                cx="12"
                                cy="8"
                                r="5"
                            />

                            <path
                                stroke-linejoin="round"
                                d="m8.5 12-1 9L12 18l4.5 3-1-9"
                            />
                        </svg>

                        <span>
                            Certificates
                        </span>

                    </a>

                </section>

            @endif

        </nav>



        {{-- =================================================
             ACCOUNT SECTION
        ================================================== --}}

        <div class="pw-sidebar-bottom">

            <div class="pw-account-heading">
                Account
            </div>


            <a
                href="{{ route('profile.edit') }}"
                wire:navigate
                class="pw-nav-link {{ request()->routeIs('profile.edit', 'security.edit', 'appearance.edit') ? 'is-active' : '' }}"
            >

                <svg
                    class="pw-nav-icon"
                    width="18"
                    height="18"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                >
                    <circle
                        cx="12"
                        cy="12"
                        r="3"
                    />

                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06-2.83 2.83-.06-.06A1.65 1.65 0 0 0 15 19.4a1.65 1.65 0 0 0-1 .6 1.65 1.65 0 0 0-.4 1V21h-4v-.09a1.65 1.65 0 0 0-.4-1 1.65 1.65 0 0 0-1-.6 1.65 1.65 0 0 0-1.82.33l-.06.06-2.83-2.83.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-.6-1 1.65 1.65 0 0 0-1-.4H3v-4h.09a1.65 1.65 0 0 0 1-.4 1.65 1.65 0 0 0 .6-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06L7.13 3.5l.06.06A1.65 1.65 0 0 0 9 3.6a1.65 1.65 0 0 0 1-.6 1.65 1.65 0 0 0 .4-1V2h4v.09a1.65 1.65 0 0 0 .4 1 1.65 1.65 0 0 0 1 .6 1.65 1.65 0 0 0 1.82-.33l.06-.06 2.83 2.83-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 .6 1 1.65 1.65 0 0 0 1 .4H21v4h-.09a1.65 1.65 0 0 0-1 .4 1.65 1.65 0 0 0-.51.2Z"
                    />
                </svg>

                <span>
                    Settings
                </span>

            </a>


                    </div>

    </aside>



    {{-- =====================================================
         MAIN PAGE CONTENT
    ====================================================== --}}

    <div class="pw-main-content">

        {{-- =====================================================
             GLOBAL TOPBAR
             Search + Notifications + Profile
        ====================================================== --}}

        <header class="pw-topbar">

            {{-- Search --}}
            <div class="pw-topbar-left">

                <div
                    class="pw-global-search"
                    id="pwGlobalSearchWrap"
                >

                    <svg
                        class="pw-global-search-icon"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <circle cx="11" cy="11" r="7"></circle>
                        <path
                            stroke-linecap="round"
                            d="m20 20-3.6-3.6"
                        ></path>
                    </svg>


                    <input
                        id="pwGlobalSearchInput"
                        class="pw-global-search-input"
                        type="search"
                        placeholder="Search anything..."
                        autocomplete="off"
                        aria-label="Search PathWise"
                    >


                    <div class="pw-global-search-kbd">
                        /
                    </div>


                    <div
                        id="pwSearchResults"
                        class="pw-search-results"
                    >

                        <div class="pw-search-result-header">
                            Quick navigation
                        </div>


                        <div
                            id="pwSearchResultList"
                            class="pw-search-result-list"
                        >

                            @foreach($pwSearchItems as $pwSearchItem)

                                <a
                                    href="{{ $pwSearchItem['url'] }}"
                                    class="pw-search-result-item"
                                    data-pw-search-item
                                    data-pw-search-text="{{ strtolower($pwSearchItem['label'] . ' ' . $pwSearchItem['meta']) }}"
                                >

                                    <div class="pw-search-result-item-icon">

                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M9 18l6-6-6-6"
                                            />
                                        </svg>

                                    </div>


                                    <div class="pw-search-result-copy">

                                        <div class="pw-search-result-title">
                                            {{ $pwSearchItem['label'] }}
                                        </div>

                                        <div class="pw-search-result-meta">
                                            {{ $pwSearchItem['meta'] }}
                                        </div>

                                    </div>

                                </a>

                            @endforeach


                            <div
                                id="pwSearchEmpty"
                                class="pw-search-empty"
                                style="display:none;"
                            >
                                No matching page found.
                            </div>

                        </div>

                    </div>

                </div>

            </div>



            {{-- Right actions --}}
            <div class="pw-topbar-actions">


                {{-- Notifications --}}
                <div class="pw-topbar-dropdown-wrap">

                    <button
                        type="button"
                        class="pw-topbar-icon-button"
                        onclick="pwToggleDropdown('pwNotificationsDropdown')"
                        aria-label="Notifications"
                        aria-haspopup="true"
                    >

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M14.857 17.082a23.848 23.848 0 01-5.714 0M18 8a6 6 0 10-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"
                            />
                        </svg>

                        @if($pwUnreadCount > 0)
                            <span class="pw-notification-badge">
                                {{ $pwUnreadCount > 9 ? '9+' : $pwUnreadCount }}
                            </span>
                        @endif

                    </button>


                    <div
                        id="pwNotificationsDropdown"
                        class="pw-topbar-dropdown"
                    >

                        <div class="pw-topbar-dropdown-header">

                            <div>
                                <div class="pw-topbar-dropdown-title">
                                    Notifications
                                </div>

                                <div class="pw-topbar-dropdown-subtitle">
                                    @if($pwUnreadCount > 0)
                                        {{ $pwUnreadCount }} unread
                                    @else
                                        You're all caught up
                                    @endif
                                </div>
                            </div>


                            @if($pwUnreadCount > 0)
                                <form
                                    action="{{ route('notifications.read-all') }}"
                                    method="POST"
                                >
                                    @csrf

                                    <button
                                        type="submit"
                                        class="pw-notification-mark-all"
                                    >
                                        Mark all read
                                    </button>
                                </form>
                            @endif

                        </div>


                        <div class="pw-notification-list">

                            @forelse($pwNotifications as $pwNotification)

                                @php
                                    $pwNotificationData = is_array($pwNotification->data ?? null)
                                        ? $pwNotification->data
                                        : [];

                                    $pwNotificationCourseId =
                                        $pwNotificationData['course_id']
                                        ?? null;

                                    $pwNotificationTitle =
                                        $pwNotificationData['title']
                                        ?? (
                                            $pwNotificationCourseId
                                                ? 'Course update'
                                                : 'PathWise notification'
                                        );

                                    $pwNotificationMessage =
                                        $pwNotificationData['message']
                                        ?? (
                                            empty($pwNotificationData['title'])
                                                ? 'You have a new update in PathWise.'
                                                : null
                                        );

                                    $pwNotificationUnread =
                                        is_null($pwNotification->read_at);
                                @endphp


                                <form
                                    action="{{ route('notifications.read', $pwNotification->id) }}"
                                    method="POST"
                                    class="pw-notification-form"
                                >
                                    @csrf

                                    <button
                                        type="submit"
                                        class="pw-notification-item-button {{
                                            $pwNotificationUnread
                                                ? 'is-unread'
                                                : ''
                                        }}"
                                    >

                                        <span class="pw-notification-icon">

                                            @if($pwNotificationCourseId)

                                                <svg
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="1.8"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"
                                                    />
                                                </svg>

                                            @else

                                                <svg
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="1.8"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M14.857 17.082a23.848 23.848 0 01-5.714 0M18 8a6 6 0 10-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"
                                                    />
                                                </svg>

                                            @endif

                                        </span>


                                        <span class="pw-notification-copy">

                                            <span class="pw-notification-title-row">

                                                <span class="pw-notification-title">
                                                    {{ $pwNotificationTitle }}
                                                </span>

                                                @if($pwNotificationUnread)
                                                    <span class="pw-notification-new">
                                                        New
                                                    </span>
                                                @endif

                                            </span>


                                            @if($pwNotificationMessage)
                                                <span class="pw-notification-message">
                                                    {{ $pwNotificationMessage }}
                                                </span>
                                            @endif


                                            <span class="pw-notification-time">

                                                <svg
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="1.8"
                                                >
                                                    <circle cx="12" cy="12" r="9" />
                                                    <path
                                                        stroke-linecap="round"
                                                        d="M12 7v5l3 2"
                                                    />
                                                </svg>

                                                {!!
                                                    optional(
                                                        $pwNotification->created_at
                                                    )->diffForHumans()
                                                !!}

                                            </span>

                                        </span>

                                    </button>

                                </form>


                            @empty

                                <div class="pw-notification-empty">

                                    <div class="pw-notification-empty-icon">
                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M14.857 17.082a23.848 23.848 0 01-5.714 0M18 8a6 6 0 10-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"
                                            />
                                        </svg>
                                    </div>

                                    <div class="pw-notification-empty-title">
                                        No notifications yet
                                    </div>

                                    <div class="pw-notification-empty-copy">
                                        Course approvals, updates, and other PathWise activity will appear here.
                                    </div>

                                </div>

                            @endforelse

                        </div>

                    </div>

                </div>


                {{-- Profile --}}
                <div class="pw-topbar-dropdown-wrap">

                    <button
                        type="button"
                        class="pw-profile-trigger"
                        onclick="pwToggleDropdown('pwProfileDropdown')"
                        aria-label="Open profile menu"
                        aria-haspopup="true"
                    >

                        <div class="pw-profile-avatar">

                            @if($pwUser->profile_photo_path)

                                <img
                                    src="{{ asset('storage/' . $pwUser->profile_photo_path) }}?v={{ $pwUser->updated_at?->timestamp }}"
                                    alt="{{ $pwUser->name }}"
                                >

                            @else

                                {{ $pwInitials }}

                            @endif

                        </div>


                        <div class="pw-profile-copy">

                            <div class="pw-profile-name">
                                {{ $pwUser->name }}
                            </div>

                            <div class="pw-profile-role">
                                {{ $pwRoleLabel }}
                            </div>

                        </div>


                        <svg
                            class="pw-profile-chevron"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m7 10 5 5 5-5"
                            />
                        </svg>

                    </button>


                    <div
                        id="pwProfileDropdown"
                        class="pw-topbar-dropdown pw-profile-menu"
                    >

                        <div class="pw-profile-menu-user">

                            <div class="pw-profile-menu-name">
                                {{ $pwUser->name }}
                            </div>

                            <div class="pw-profile-menu-email">
                                {{ $pwUser->email }}
                            </div>

                        </div>


                        <div class="pw-profile-menu-links">

                            <a
                                href="{{ route('profile.edit') }}"
                                wire:navigate
                                class="pw-profile-menu-link"
                            >

                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >
                                    <circle cx="12" cy="8" r="4"></circle>
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M4 21a8 8 0 0116 0"
                                    />
                                </svg>

                                Profile & Settings

                            </a>


                            <form
                                method="POST"
                                action="{{ route('logout') }}"
                            >
                                @csrf

                                <button
                                    type="submit"
                                    class="pw-profile-menu-link is-danger"
                                >

                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M9 12h12M17 8l4 4-4 4M12 21H5a2 2 0 01-2-2V5a2 2 0 012-2h7"
                                        />
                                    </svg>

                                    Sign out

                                </button>

                            </form>

                        </div>

                    </div>

                </div>

            </div>

        </header>



        {{-- =====================================================
             MAIN PAGE CONTENT
        ====================================================== --}}

        {{ $slot }}

    </div>



    @fluxScripts


    {{-- =====================================================
         MOBILE SIDEBAR SCRIPT
    ====================================================== --}}

    <script>
        function pwToggleSidebar() {
            const sidebar = document.getElementById('pwSidebar');
            const overlay = document.getElementById('pwSidebarOverlay');

            sidebar.classList.toggle('pw-sidebar-open');
            overlay.classList.toggle('pw-overlay-open');
        }

        function pwCloseSidebar() {
            const sidebar = document.getElementById('pwSidebar');
            const overlay = document.getElementById('pwSidebarOverlay');

            sidebar.classList.remove('pw-sidebar-open');
            overlay.classList.remove('pw-overlay-open');
        }

        document.addEventListener('livewire:navigated', function () {
            pwCloseSidebar();
            pwCloseTopbarDropdowns();
            pwInitGlobalSearch();
        });
    

        /* =====================================================
           GLOBAL TOPBAR
        ===================================================== */

        function pwCloseTopbarDropdowns(exceptId = null) {
            document
                .querySelectorAll('.pw-topbar-dropdown.is-open')
                .forEach(function (dropdown) {
                    if (!exceptId || dropdown.id !== exceptId) {
                        dropdown.classList.remove('is-open');
                    }
                });
        }

        function pwToggleDropdown(id) {
            const dropdown = document.getElementById(id);

            if (!dropdown) {
                return;
            }

            const willOpen = !dropdown.classList.contains('is-open');

            pwCloseTopbarDropdowns(id);

            if (willOpen) {
                dropdown.classList.add('is-open');
            } else {
                dropdown.classList.remove('is-open');
            }

            const searchResults = document.getElementById('pwSearchResults');

            if (searchResults) {
                searchResults.classList.remove('is-open');
            }
        }

        function pwInitGlobalSearch() {
            const input = document.getElementById('pwGlobalSearchInput');
            const results = document.getElementById('pwSearchResults');
            const empty = document.getElementById('pwSearchEmpty');

            if (!input || !results || !empty) {
                return;
            }

            if (input.dataset.pwSearchInitialized === 'true') {
                return;
            }

            input.dataset.pwSearchInitialized = 'true';

            const items = Array.from(
                results.querySelectorAll('[data-pw-search-item]')
            );

            const filterItems = function () {
                const query = input.value.trim().toLowerCase();
                let visibleCount = 0;

                items.forEach(function (item) {
                    const haystack =
                        (item.dataset.pwSearchText || '').toLowerCase();

                    const visible =
                        query === '' || haystack.includes(query);

                    item.style.display = visible ? 'flex' : 'none';

                    if (visible) {
                        visibleCount++;
                    }
                });

                empty.style.display =
                    visibleCount === 0 ? 'block' : 'none';

                results.classList.add('is-open');
                pwCloseTopbarDropdowns();
            };

            input.addEventListener('focus', filterItems);
            input.addEventListener('input', filterItems);

            input.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    results.classList.remove('is-open');
                    input.blur();
                }

                if (event.key === 'Enter') {
                    const firstVisible = items.find(function (item) {
                        return item.style.display !== 'none';
                    });

                    if (firstVisible) {
                        event.preventDefault();
                        window.location.href = firstVisible.href;
                    }
                }
            });

            document.addEventListener('keydown', function (event) {
                const activeTag =
                    document.activeElement?.tagName?.toLowerCase();

                const typing =
                    activeTag === 'input'
                    || activeTag === 'textarea'
                    || document.activeElement?.isContentEditable;

                if (
                    event.key === '/'
                    && !typing
                ) {
                    event.preventDefault();
                    input.focus();
                }
            });
        }

        document.addEventListener('click', function (event) {
            const searchWrap =
                document.getElementById('pwGlobalSearchWrap');

            const searchResults =
                document.getElementById('pwSearchResults');

            if (
                searchWrap
                && searchResults
                && !searchWrap.contains(event.target)
            ) {
                searchResults.classList.remove('is-open');
            }

            if (
                !event.target.closest('.pw-topbar-dropdown-wrap')
            ) {
                pwCloseTopbarDropdowns();
            }
        });

        pwInitGlobalSearch();

</script>

</body>

</html>