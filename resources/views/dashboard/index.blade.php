@extends('layouts.tabler')

@section('title', 'Home')

@push('styles')
<style>
    .home-dashboard {
        display: flex;
        flex-direction: column;
        gap: 24px;
        padding: 8px clamp(10px, 1.6vw, 22px) 32px;
    }

    .home-hero {
        display: grid;
        grid-template-columns: minmax(0, 1.7fr) minmax(300px, 0.9fr);
        gap: 20px;
        padding: 28px;
        border-radius: 32px;
        background:
            radial-gradient(circle at top left, rgba(66, 133, 244, 0.16), transparent 42%),
            linear-gradient(180deg, #ffffff 0%, #f7faff 100%);
        border: 1px solid rgba(66, 133, 244, 0.14);
        box-shadow: 0 22px 60px rgba(60, 64, 67, 0.08);
        overflow: hidden;
        position: relative;
    }

    .home-hero::after {
        content: "";
        position: absolute;
        inset: auto -120px -120px auto;
        width: 260px;
        height: 260px;
        border-radius: 50%;
        background: rgba(232, 240, 254, 0.9);
        filter: blur(4px);
        pointer-events: none;
    }

    .home-hero__content,
    .home-hero__rail {
        position: relative;
        z-index: 1;
    }

    .home-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        border-radius: 999px;
        background: rgba(232, 240, 254, 0.96);
        color: #1967d2;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .home-hero__title {
        margin: 18px 0 10px;
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: clamp(2rem, 4vw, 3.45rem);
        line-height: 1.02;
        font-weight: 500;
        letter-spacing: -0.04em;
        color: #202124;
        max-width: 11ch;
    }

    .home-hero__summary {
        max-width: 560px;
        margin: 0;
        color: #5f6368;
        font-size: 1rem;
        line-height: 1.65;
    }

    .home-hero__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 22px;
    }

    .home-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        min-height: 46px;
        padding: 0 18px;
        border-radius: 999px;
        border: 1px solid #dadce0;
        background: #fff;
        color: #3c4043;
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 0.95rem;
        font-weight: 500;
        text-decoration: none;
        transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease, background-color 180ms ease;
    }

    .home-action:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 24px rgba(60, 64, 67, 0.12);
        border-color: #c6dafc;
        color: #1f1f1f;
        text-decoration: none;
    }

    .home-action--primary {
        background: #1a73e8;
        border-color: #1a73e8;
        color: #fff;
    }

    .home-action--primary,
    .home-action--primary:visited,
    .home-action--primary i,
    .home-action--primary span {
        color: #fff !important;
    }

    .home-action--primary:hover,
    .home-action--primary:focus,
    .home-action--primary:active {
        background: #1967d2;
        border-color: #1967d2;
        color: #fff !important;
    }

    .home-hero__meta {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 22px;
    }

    .home-meta-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 38px;
        padding: 0 14px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.92);
        border: 1px solid rgba(218, 220, 224, 0.9);
        color: #5f6368;
        font-size: 0.875rem;
    }

    .home-meta-pill strong {
        color: #202124;
        font-weight: 500;
    }

    .home-hero__rail {
        display: grid;
        grid-template-rows: auto 1fr;
        gap: 14px;
    }

    .home-brief,
    .home-signal {
        border-radius: 26px;
        background: rgba(255, 255, 255, 0.88);
        border: 1px solid rgba(218, 220, 224, 0.92);
        padding: 18px 18px 20px;
        backdrop-filter: blur(14px);
        transition: transform 180ms ease, box-shadow 180ms ease;
    }

    .home-brief:hover,
    .home-signal:hover {
        transform: translateY(-1px);
        box-shadow: 0 16px 40px rgba(60, 64, 67, 0.1);
    }

    .home-brief__label,
    .home-signal__label,
    .home-section__eyebrow {
        margin: 0 0 12px;
        color: #5f6368;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .home-brief__list {
        display: grid;
        gap: 12px;
        margin: 0;
    }

    .home-brief__row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        padding-bottom: 12px;
        border-bottom: 1px solid #edf0f2;
    }

    .home-brief__row:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .home-brief__title {
        margin: 0 0 4px;
        color: #202124;
        font-size: 0.95rem;
        font-weight: 500;
    }

    .home-brief__meta {
        margin: 0;
        color: #5f6368;
        font-size: 0.8rem;
    }

    .home-brief__value {
        color: #202124;
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 1.25rem;
        font-weight: 500;
        white-space: nowrap;
    }

    .home-signal {
        display: grid;
        gap: 14px;
        align-content: start;
    }

    .home-signal__headline {
        margin: 0;
        color: #202124;
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 1.4rem;
        line-height: 1.15;
        font-weight: 500;
    }

    .home-signal__body {
        margin: 0;
        color: #5f6368;
        line-height: 1.6;
    }

    .home-signal__footer {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #1967d2;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .home-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
    }

    .home-kpi {
        padding: 18px 18px 20px;
        border-radius: 24px;
        background: #fff;
        border: 1px solid rgba(218, 220, 224, 0.92);
        box-shadow: 0 10px 30px rgba(60, 64, 67, 0.05);
        transition: transform 180ms ease, box-shadow 180ms ease;
    }

    .home-kpi:hover {
        transform: translateY(-2px);
        box-shadow: 0 18px 34px rgba(60, 64, 67, 0.09);
    }

    .home-kpi__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 22px;
    }

    .home-kpi__icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 46px;
        height: 46px;
        border-radius: 16px;
        font-size: 1.15rem;
    }

    .home-kpi__icon--blue { background: #e8f0fe; color: #1a73e8; }
    .home-kpi__icon--green { background: #e6f4ea; color: #188038; }
    .home-kpi__icon--amber { background: #fef7e0; color: #b06000; }
    .home-kpi__icon--red { background: #fce8e6; color: #c5221f; }

    .home-kpi__trend {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 600;
    }

    .is-positive {
        background: #e6f4ea;
        color: #188038;
    }

    .is-negative {
        background: #fce8e6;
        color: #c5221f;
    }

    .is-neutral {
        background: #f1f3f4;
        color: #5f6368;
    }

    .home-kpi__value {
        margin: 0;
        color: #202124;
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: clamp(1.8rem, 3vw, 2.4rem);
        line-height: 1;
        font-weight: 500;
    }

    .home-kpi__title {
        margin: 10px 0 6px;
        color: #202124;
        font-size: 0.95rem;
        font-weight: 500;
    }

    .home-kpi__meta {
        margin: 0;
        color: #5f6368;
        font-size: 0.85rem;
    }

    .home-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.65fr) minmax(300px, 0.85fr);
        gap: 20px;
        align-items: start;
    }

    .home-main,
    .home-side {
        display: grid;
        gap: 20px;
    }

    .home-panel {
        background: #fff;
        border: 1px solid rgba(218, 220, 224, 0.9);
        border-radius: 28px;
        box-shadow: 0 10px 28px rgba(60, 64, 67, 0.05);
        overflow: hidden;
    }

    .home-panel__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        padding: 22px 24px 0;
    }

    .home-panel__title {
        margin: 0;
        color: #202124;
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 1.28rem;
        line-height: 1.2;
        font-weight: 500;
    }

    .home-panel__subtitle {
        margin: 6px 0 0;
        color: #5f6368;
        font-size: 0.92rem;
        line-height: 1.55;
    }

    .home-panel__body {
        padding: 20px 24px 24px;
    }

    .home-panel__link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #1967d2;
        font-size: 0.9rem;
        font-weight: 500;
        text-decoration: none;
        white-space: nowrap;
    }

    .home-panel__link:hover {
        color: #174ea6;
        text-decoration: none;
    }

    .home-analytics {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 260px;
        gap: 18px;
        align-items: stretch;
    }

    .home-chart {
        min-height: 320px;
        padding: 10px 14px 0 0;
    }

    .home-chart canvas {
        width: 100% !important;
        height: 320px !important;
    }

    .home-chart--compact canvas {
        height: 260px !important;
    }

    .home-aside-card {
        display: grid;
        gap: 12px;
    }

    .home-metric-stack {
        padding: 16px 18px;
        border-radius: 22px;
        background: #f8f9fa;
    }

    .home-metric-stack__label {
        margin: 0 0 8px;
        color: #5f6368;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        font-weight: 600;
    }

    .home-metric-stack__value {
        margin: 0;
        color: #202124;
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 1.55rem;
        font-weight: 500;
        line-height: 1.1;
    }

    .home-metric-stack__meta {
        margin: 6px 0 0;
        color: #5f6368;
        font-size: 0.84rem;
        line-height: 1.55;
    }

    .home-split {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(220px, 0.75fr);
        gap: 20px;
        align-items: start;
    }

    .home-comparison-grid,
    .home-mini-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .home-comparison-card,
    .home-mini-card {
        padding: 18px;
        border-radius: 22px;
        background: #f8f9fa;
        border: 1px solid #edf0f2;
    }

    .home-comparison-card__label,
    .home-mini-card__label {
        margin: 0 0 10px;
        color: #5f6368;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .home-comparison-card__value,
    .home-mini-card__value {
        margin: 0;
        color: #202124;
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 1.55rem;
        line-height: 1.1;
        font-weight: 500;
    }

    .home-comparison-card__footer,
    .home-mini-card__footer {
        margin-top: 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        color: #5f6368;
        font-size: 0.84rem;
    }

    .home-inline-trend {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.82rem;
        font-weight: 600;
    }

    .home-table {
        width: 100%;
        border-collapse: collapse;
    }

    .home-table th,
    .home-table td {
        padding: 14px 0;
        border-bottom: 1px solid #edf0f2;
        font-size: 0.92rem;
        text-align: left;
    }

    .home-table th {
        color: #5f6368;
        font-size: 0.78rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .home-table tr:last-child td {
        border-bottom: 0;
    }

    .home-table td:last-child,
    .home-table th:last-child {
        text-align: right;
    }

    .home-store-name {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
        color: #202124;
    }

    .home-store-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .home-list {
        display: grid;
        gap: 12px;
    }

    .home-list-item {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        padding: 16px 18px;
        border-radius: 22px;
        background: #f8f9fa;
        border: 1px solid #edf0f2;
    }

    .home-list-item__title {
        margin: 0 0 4px;
        color: #202124;
        font-weight: 500;
    }

    .home-list-item__body,
    .home-list-item__meta {
        margin: 0;
        color: #5f6368;
        font-size: 0.86rem;
        line-height: 1.55;
    }

    .home-list-item__value {
        text-align: right;
        color: #202124;
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 1.15rem;
        font-weight: 500;
        white-space: nowrap;
    }

    .home-list-item__icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border-radius: 14px;
        flex-shrink: 0;
        font-size: 1rem;
    }

    .home-list-item--success .home-list-item__icon,
    .home-list-item--success .home-badge {
        background: #e6f4ea;
        color: #188038;
    }

    .home-list-item--warning .home-list-item__icon,
    .home-list-item--warning .home-badge {
        background: #fef7e0;
        color: #b06000;
    }

    .home-list-item--critical .home-list-item__icon,
    .home-list-item--critical .home-badge {
        background: #fce8e6;
        color: #c5221f;
    }

    .home-list-item--info .home-list-item__icon,
    .home-list-item--info .home-badge {
        background: #e8f0fe;
        color: #1967d2;
    }

    .home-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 30px;
        padding: 0 12px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    .home-empty {
        display: grid;
        place-items: center;
        gap: 10px;
        min-height: 220px;
        padding: 26px;
        text-align: center;
        color: #5f6368;
    }

    .home-empty i {
        font-size: 2rem;
        color: #9aa0a6;
    }

    .home-admin-list {
        display: grid;
        gap: 12px;
    }

    .home-admin-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 14px 16px;
        border-radius: 22px;
        background: #f8f9fa;
        border: 1px solid #edf0f2;
    }

    .home-admin-card__identity {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .home-admin-card__avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
        border: 1px solid #dadce0;
    }

    .home-admin-card__name {
        margin: 0;
        color: #202124;
        font-size: 0.92rem;
        font-weight: 500;
        line-height: 1.3;
    }

    .home-admin-card__meta {
        margin: 2px 0 0;
        color: #5f6368;
        font-size: 0.8rem;
        line-height: 1.45;
        word-break: break-word;
    }

    .home-admin-card__button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 38px;
        padding: 0 14px;
        border-radius: 999px;
        border: 1px solid #c6dafc;
        background: #fff;
        color: #1967d2;
        font-size: 0.84rem;
        font-weight: 500;
        white-space: nowrap;
    }

    .home-admin-card__button:hover {
        background: #e8f0fe;
        color: #174ea6;
    }

    .home-actions-stack {
        display: grid;
        gap: 12px;
    }

    .home-actions-stack .home-action {
        justify-content: flex-start;
        width: 100%;
    }

    .home-modal .modal-content {
        border-radius: 28px;
        border: 1px solid rgba(218, 220, 224, 0.9);
        box-shadow: 0 26px 70px rgba(60, 64, 67, 0.18);
        overflow: hidden;
    }

    .home-modal .modal-header,
    .home-modal .modal-footer {
        border-color: #edf0f2;
        padding: 20px 24px;
    }

    .home-modal .modal-body {
        padding: 22px 24px 24px;
    }

    .home-modal__search {
        min-height: 48px;
        border-radius: 16px;
        border: 1px solid #dadce0;
        padding: 0 16px;
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    .home-modal__tabs {
        gap: 10px;
        border-bottom: 0;
    }

    .home-modal__tabs .nav-link {
        border: 0;
        border-radius: 999px;
        background: #f1f3f4;
        color: #5f6368;
        padding: 10px 16px !important;
        font-weight: 500;
    }

    .home-modal__tabs .nav-link.active {
        background: #e8f0fe !important;
        color: #1967d2 !important;
        box-shadow: none !important;
    }

    .home-modal__list {
        display: grid;
        gap: 12px;
        max-height: 340px;
        overflow-y: auto;
        padding-right: 4px;
    }

    .home-modal__user {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 14px 16px;
        border-radius: 20px;
        background: #f8f9fa;
        border: 1px solid #edf0f2;
    }

    .home-modal__user-main {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .home-modal__user img {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        object-fit: cover;
        border: 1px solid #dadce0;
    }

    .home-modal__user-name {
        margin: 0;
        color: #202124;
        font-weight: 500;
    }

    .home-modal__user-meta {
        margin: 2px 0 0;
        color: #5f6368;
        font-size: 0.82rem;
        line-height: 1.5;
    }

    .home-link-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 40px;
        padding: 0 14px;
        border-radius: 999px;
        border: 1px solid #dadce0;
        background: #fff;
        color: #3c4043;
        text-decoration: none;
        font-weight: 500;
    }

    .home-link-button:hover {
        background: #f8f9fa;
        color: #202124;
        text-decoration: none;
    }

    @media (max-width: 1199.98px) {
        .home-grid,
        .home-hero,
        .home-analytics,
        .home-split {
            grid-template-columns: 1fr;
        }

        .home-kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767.98px) {
        .home-dashboard {
            gap: 18px;
            padding-bottom: 24px;
        }

        .home-hero,
        .home-panel__header,
        .home-panel__body {
            padding-left: 18px;
            padding-right: 18px;
        }

        .home-hero {
            padding-top: 22px;
            padding-bottom: 22px;
            border-radius: 24px;
        }

        .home-panel {
            border-radius: 24px;
        }

        .home-hero__title {
            max-width: 12ch;
        }

        .home-kpi-grid,
        .home-comparison-grid,
        .home-mini-grid {
            grid-template-columns: 1fr;
        }

        .home-chart canvas,
        .home-chart--compact canvas {
            height: 240px !important;
        }

        .home-admin-card,
        .home-modal__user,
        .home-list-item {
            flex-direction: column;
            align-items: stretch;
        }

        .home-admin-card__button,
        .home-modal .btn,
        .home-link-button {
            width: 100%;
            justify-content: center;
        }
    }
</style>
@endpush

@section('content')
@php
    $user = Auth::user();
    $roleLabel = $user->isAdmin() ? 'Admin' : ($user->isOwner() ? 'Owner' : 'Manager');
    $growthChange = $analytics['monthlyComparison']['changes']['gross_sales'] ?? null;
    $netSalesChange = $analytics['monthlyComparison']['changes']['net_sales'] ?? null;
    $reportsChange = $analytics['monthlyComparison']['changes']['reports'] ?? null;
    $customerChange = $analytics['monthlyComparison']['changes']['avg_customers'] ?? null;
    $topStore = $analytics['storePerformance']->first();
    $primaryInsight = $analytics['insights']->first();
    $bestDay = $analytics['topDays']->first();
    $storeColors = ['#4285f4', '#34a853', '#fbbc04', '#ea4335', '#7baaf7'];
    $defaultAvatar = asset('images/default-owner.png');
@endphp

<div class="home-dashboard">
    @includeWhen(isset($circularMetrics), 'dashboard.partials.circular-metrics')

    <section class="home-grid">
        <div class="home-main">
            <article class="home-panel">
                <div class="home-panel__header">
                    <div>
                        <p class="home-section__eyebrow">Primary View</p>
                        <h2 class="home-panel__title">Daily sales flow</h2>
                        <p class="home-panel__subtitle">Ninety days of gross and net sales with supporting context in a single read.</p>
                    </div>
                </div>
                <div class="home-panel__body">
                    @if($analytics['dailyTrends']->count() > 0)
                        <div class="home-analytics">
                            <div class="home-chart">
                                <canvas id="dailyTrendsChart"></canvas>
                            </div>
                            <div class="home-aside-card">
                                <div class="home-metric-stack">
                                    <p class="home-metric-stack__label">Average daily sales</p>
                                    <p class="home-metric-stack__value">${{ !empty($analytics['financialAnalysis']) ? number_format($analytics['financialAnalysis']['avgDailySales'], 0) : '0' }}</p>
                                    <p class="home-metric-stack__meta">Based on the current reporting window.</p>
                                </div>
                                <div class="home-metric-stack">
                                    <p class="home-metric-stack__label">Current month reports</p>
                                    <p class="home-metric-stack__value">{{ $analytics['monthlyComparison']['current']->reports ?? 0 }}</p>
                                    <p class="home-metric-stack__meta">Compared against {{ $analytics['monthlyComparison']['previous']->reports ?? 0 }} last month.</p>
                                </div>
                                <div class="home-metric-stack">
                                    <p class="home-metric-stack__label">Recent best day</p>
                                    <p class="home-metric-stack__value">
                                        @if($bestDay)
                                            ${{ number_format($bestDay->gross_sales, 0) }}
                                        @else
                                            --
                                        @endif
                                    </p>
                                    <p class="home-metric-stack__meta">
                                        @if($bestDay)
                                            {{ $bestDay->report_date->format(config('dates.display')) }}
                                        @else
                                            Add more reports to unlock rankings.
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="home-empty">
                            <i class="bi bi-graph-up"></i>
                            <p class="mb-0">Create daily reports to unlock the sales timeline.</p>
                        </div>
                    @endif
                </div>
            </article>

            <article class="home-panel">
                <div class="home-panel__header">
                    <div>
                        <p class="home-section__eyebrow">Comparison</p>
                        <h2 class="home-panel__title">Month-over-month performance</h2>
                        <p class="home-panel__subtitle">The key changes that usually matter first: sales, reports, and customer count.</p>
                    </div>
                </div>
                <div class="home-panel__body">
                    @if($analytics['monthlyComparison']['changes'])
                        <div class="home-comparison-grid">
                            <div class="home-comparison-card">
                                <p class="home-comparison-card__label">Gross Sales</p>
                                <p class="home-comparison-card__value">${{ number_format($analytics['monthlyComparison']['current']->gross_sales ?? 0, 0) }}</p>
                                <div class="home-comparison-card__footer">
                                    <span>Previous: ${{ number_format($analytics['monthlyComparison']['previous']->gross_sales ?? 0, 0) }}</span>
                                    <span class="home-inline-trend {{ is_numeric($growthChange) ? ($growthChange >= 0 ? 'is-positive' : 'is-negative') : 'is-neutral' }}">
                                        <i class="bi {{ is_numeric($growthChange) ? ($growthChange >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right') : 'bi-dash-lg' }}"></i>
                                        {{ is_numeric($growthChange) ? (($growthChange >= 0 ? '+' : '') . $growthChange . '%') : 'No change' }}
                                    </span>
                                </div>
                            </div>
                            <div class="home-comparison-card">
                                <p class="home-comparison-card__label">Net Sales</p>
                                <p class="home-comparison-card__value">${{ number_format($analytics['monthlyComparison']['current']->net_sales ?? 0, 0) }}</p>
                                <div class="home-comparison-card__footer">
                                    <span>Previous: ${{ number_format($analytics['monthlyComparison']['previous']->net_sales ?? 0, 0) }}</span>
                                    <span class="home-inline-trend {{ is_numeric($netSalesChange) ? ($netSalesChange >= 0 ? 'is-positive' : 'is-negative') : 'is-neutral' }}">
                                        <i class="bi {{ is_numeric($netSalesChange) ? ($netSalesChange >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right') : 'bi-dash-lg' }}"></i>
                                        {{ is_numeric($netSalesChange) ? (($netSalesChange >= 0 ? '+' : '') . $netSalesChange . '%') : 'No change' }}
                                    </span>
                                </div>
                            </div>
                            <div class="home-comparison-card">
                                <p class="home-comparison-card__label">Reports</p>
                                <p class="home-comparison-card__value">{{ $analytics['monthlyComparison']['current']->reports ?? 0 }}</p>
                                <div class="home-comparison-card__footer">
                                    <span>Previous: {{ $analytics['monthlyComparison']['previous']->reports ?? 0 }}</span>
                                    <span class="home-inline-trend {{ is_numeric($reportsChange) ? ($reportsChange >= 0 ? 'is-positive' : 'is-negative') : 'is-neutral' }}">
                                        <i class="bi {{ is_numeric($reportsChange) ? ($reportsChange >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right') : 'bi-dash-lg' }}"></i>
                                        {{ is_numeric($reportsChange) ? (($reportsChange >= 0 ? '+' : '') . $reportsChange . '%') : 'No change' }}
                                    </span>
                                </div>
                            </div>
                            <div class="home-comparison-card">
                                <p class="home-comparison-card__label">Avg Customers</p>
                                <p class="home-comparison-card__value">{{ number_format($analytics['monthlyComparison']['current']->avg_customers ?? 0, 0) }}</p>
                                <div class="home-comparison-card__footer">
                                    <span>Previous: {{ number_format($analytics['monthlyComparison']['previous']->avg_customers ?? 0, 0) }}</span>
                                    <span class="home-inline-trend {{ is_numeric($customerChange) ? ($customerChange >= 0 ? 'is-positive' : 'is-negative') : 'is-neutral' }}">
                                        <i class="bi {{ is_numeric($customerChange) ? ($customerChange >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right') : 'bi-dash-lg' }}"></i>
                                        {{ is_numeric($customerChange) ? (($customerChange >= 0 ? '+' : '') . $customerChange . '%') : 'No change' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="home-empty">
                            <i class="bi bi-bar-chart"></i>
                            <p class="mb-0">Not enough reporting history yet for month-over-month comparison.</p>
                        </div>
                    @endif
                </div>
            </article>

            @if($analytics['storePerformance']->count() > 0)
                <article class="home-panel">
                    <div class="home-panel__header">
                        <div>
                            <p class="home-section__eyebrow">Distribution</p>
                            <h2 class="home-panel__title">Store performance mix</h2>
                            <p class="home-panel__subtitle">Revenue distribution across locations with the strongest stores surfaced first.</p>
                        </div>
                    </div>
                    <div class="home-panel__body">
                        <div class="home-split">
                            <div class="home-chart home-chart--compact">
                                <canvas id="storePerformanceChart"></canvas>
                            </div>
                            <div>
                                <table class="home-table">
                                    <thead>
                                        <tr>
                                            <th>Store</th>
                                            <th>Sales</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($analytics['storePerformance']->take(5) as $index => $store)
                                            <tr>
                                                <td>
                                                    <span class="home-store-name">
                                                        <span class="home-store-dot" style="background: {{ $storeColors[$index % count($storeColors)] }}"></span>
                                                        {{ \Illuminate\Support\Str::limit($store->store_info, 28) }}
                                                    </span>
                                                </td>
                                                <td>${{ number_format($store->total_gross, 0) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </article>
            @endif

            @if(!empty($analytics['financialAnalysis']))
                <article class="home-panel">
                    <div class="home-panel__header">
                        <div>
                            <p class="home-section__eyebrow">Finance</p>
                            <h2 class="home-panel__title">Financial mix</h2>
                            <p class="home-panel__subtitle">Margin, tax, card usage, and operational exception rates from the recent reporting window.</p>
                        </div>
                    </div>
                    <div class="home-panel__body">
                        <div class="home-split">
                            <div class="home-chart home-chart--compact">
                                <canvas id="financialBreakdownChart"></canvas>
                            </div>
                            <div class="home-mini-grid">
                                <div class="home-mini-card">
                                    <p class="home-mini-card__label">Profit Margin</p>
                                    <p class="home-mini-card__value">{{ $analytics['financialAnalysis']['profitMargin'] }}%</p>
                                    <div class="home-mini-card__footer"><span>Recent reports</span></div>
                                </div>
                                <div class="home-mini-card">
                                    <p class="home-mini-card__label">Tax Rate</p>
                                    <p class="home-mini-card__value">{{ $analytics['financialAnalysis']['taxRate'] }}%</p>
                                    <div class="home-mini-card__footer"><span>Current average</span></div>
                                </div>
                                <div class="home-mini-card">
                                    <p class="home-mini-card__label">Card Usage</p>
                                    <p class="home-mini-card__value">{{ $analytics['financialAnalysis']['creditCardRatio'] }}%</p>
                                    <div class="home-mini-card__footer"><span>Share of transactions</span></div>
                                </div>
                                <div class="home-mini-card">
                                    <p class="home-mini-card__label">Avg Daily Sales</p>
                                    <p class="home-mini-card__value">${{ number_format($analytics['financialAnalysis']['avgDailySales'], 0) }}</p>
                                    <div class="home-mini-card__footer"><span>Across {{ $analytics['financialAnalysis']['reportCount'] }} reports</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            @endif

            @if(!empty($analytics['customerAnalytics']))
                <article class="home-panel">
                    <div class="home-panel__header">
                        <div>
                            <p class="home-section__eyebrow">Customers</p>
                            <h2 class="home-panel__title">Customer movement</h2>
                            <p class="home-panel__subtitle">Track footfall and average ticket value together to spot demand shifts faster.</p>
                        </div>
                    </div>
                    <div class="home-panel__body">
                        <div class="home-split">
                            <div class="home-chart home-chart--compact">
                                <canvas id="customerTrendsChart"></canvas>
                            </div>
                            <div class="home-mini-grid">
                                <div class="home-mini-card">
                                    <p class="home-mini-card__label">Total Customers</p>
                                    <p class="home-mini-card__value">{{ number_format($analytics['customerAnalytics']['totalCustomers']) }}</p>
                                    <div class="home-mini-card__footer"><span>Across recent reports</span></div>
                                </div>
                                <div class="home-mini-card">
                                    <p class="home-mini-card__label">Avg Ticket</p>
                                    <p class="home-mini-card__value">${{ $analytics['customerAnalytics']['avgTicketAmount'] }}</p>
                                    <div class="home-mini-card__footer"><span>Revenue per customer</span></div>
                                </div>
                                <div class="home-mini-card">
                                    <p class="home-mini-card__label">Avg Daily Customers</p>
                                    <p class="home-mini-card__value">{{ $analytics['customerAnalytics']['avgDailyCustomers'] }}</p>
                                    <div class="home-mini-card__footer"><span>Typical daily traffic</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            @endif
        </div>

        <aside class="home-side">
            @if($analytics['thirdParty']['statement_count'] > 0)
                <article class="home-panel">
                    <div class="home-panel__header">
                        <div>
                            <p class="home-section__eyebrow">Fees</p>
                            <h2 class="home-panel__title">Third-party platforms</h2>
                            <p class="home-panel__subtitle">{{ $analytics['thirdParty']['statement_count'] }} statements loaded across delivery platforms.</p>
                        </div>
                        @if($user->isAdmin() || $user->isOwner())
                            <a href="{{ route('admin.merchant-fees.third-party') }}" class="home-panel__link">
                                View all
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        @endif
                    </div>
                    <div class="home-panel__body">
                        <div class="home-mini-grid">
                            <div class="home-mini-card">
                                <p class="home-mini-card__label">Gross Sales</p>
                                <p class="home-mini-card__value">${{ number_format($analytics['thirdParty']['total_gross_sales'], 0) }}</p>
                                <div class="home-mini-card__footer"><span>All statements</span></div>
                            </div>
                            <div class="home-mini-card">
                                <p class="home-mini-card__label">Total Fees</p>
                                <p class="home-mini-card__value">${{ number_format($analytics['thirdParty']['total_fees'], 0) }}</p>
                                <div class="home-mini-card__footer"><span>{{ $analytics['thirdParty']['avg_fee_percentage'] }}% average fee</span></div>
                            </div>
                            <div class="home-mini-card">
                                <p class="home-mini-card__label">Net Deposits</p>
                                <p class="home-mini-card__value">${{ number_format($analytics['thirdParty']['total_net_deposit'], 0) }}</p>
                                <div class="home-mini-card__footer"><span>After platform charges</span></div>
                            </div>
                        </div>
                    </div>
                </article>
            @endif

            <article class="home-panel">
                <div class="home-panel__header">
                    <div>
                        <p class="home-section__eyebrow">Signals</p>
                        <h2 class="home-panel__title">Insights and alerts</h2>
                        <p class="home-panel__subtitle">The latest variances and high-signal operating notes.</p>
                    </div>
                </div>
                <div class="home-panel__body">
                    @if($analytics['insights']->count() > 0)
                        <div class="home-list">
                            @foreach($analytics['insights']->take(4) as $insight)
                                @php
                                    $insightVariant = match($insight['type']) {
                                        'success' => 'success',
                                        'warning' => 'warning',
                                        'alert' => 'critical',
                                        default => 'info',
                                    };
                                    $insightIcon = match($insight['type']) {
                                        'success' => 'bi-check-circle',
                                        'warning' => 'bi-exclamation-triangle',
                                        'alert' => 'bi-exclamation-octagon',
                                        default => 'bi-info-circle',
                                    };
                                @endphp
                                <div class="home-list-item home-list-item--{{ $insightVariant }}">
                                    <div style="display:flex; gap:12px;">
                                        <span class="home-list-item__icon"><i class="bi {{ $insightIcon }}"></i></span>
                                        <div>
                                            <p class="home-list-item__title">{{ $insight['title'] }}</p>
                                            <p class="home-list-item__body">{{ $insight['message'] }}</p>
                                        </div>
                                    </div>
                                    <span class="home-badge">{{ $insight['type'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="home-empty">
                            <i class="bi bi-check-circle"></i>
                            <p class="mb-0">No urgent alerts right now.</p>
                        </div>
                    @endif
                </div>
            </article>

        </aside>
    </section>
</div>

<script>
    function loadChartJS() {
        if (window.Chart) {
            initializeCharts();
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
        script.async = true;
        script.onload = initializeCharts;
        document.head.appendChild(script);
    }

    const hasData = @json(
        $analytics['dailyTrends']->count() > 0 ||
        $analytics['storePerformance']->count() > 0 ||
        !empty($analytics['financialAnalysis']) ||
        !empty($analytics['customerAnalytics'])
    );

    if (hasData) {
        loadChartJS();
    }
</script>

<script>
function initializeCharts() {
    const dailyTrends = @json($analytics['dailyTrends']);
    const storePerformance = @json($analytics['storePerformance']);
    const financialAnalysis = @json($analytics['financialAnalysis'] ?? []);
    const customerAnalytics = @json($analytics['customerAnalytics'] ?? []);

    const colors = {
        primary: '#4285f4',
        primarySoft: 'rgba(66, 133, 244, 0.14)',
        success: '#34a853',
        successSoft: 'rgba(52, 168, 83, 0.14)',
        warning: '#fbbc04',
        warningSoft: 'rgba(251, 188, 4, 0.24)',
        danger: '#ea4335',
        dangerSoft: 'rgba(234, 67, 53, 0.16)',
        ink: '#202124',
        muted: '#5f6368',
        line: '#edf0f2'
    };

    const sharedScales = {
        x: {
            grid: { display: false },
            ticks: { color: colors.muted, maxTicksLimit: 8 }
        },
        y: {
            beginAtZero: true,
            grid: { color: colors.line },
            ticks: { color: colors.muted }
        }
    };

    if (document.getElementById('dailyTrendsChart') && dailyTrends.length > 0) {
        const context = document.getElementById('dailyTrendsChart').getContext('2d');

        new Chart(context, {
            type: 'line',
            data: {
                labels: dailyTrends.map((item) => {
                    const date = new Date(item.date);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }),
                datasets: [
                    {
                        label: 'Gross Sales',
                        data: dailyTrends.map((item) => parseFloat(item.total_gross || 0)),
                        borderColor: colors.primary,
                        backgroundColor: colors.primarySoft,
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2
                    },
                    {
                        label: 'Net Sales',
                        data: dailyTrends.map((item) => parseFloat(item.total_net || 0)),
                        borderColor: colors.success,
                        backgroundColor: colors.successSoft,
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'start',
                        labels: {
                            color: colors.ink,
                            usePointStyle: true,
                            boxWidth: 10
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': $' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    ...sharedScales,
                    y: {
                        ...sharedScales.y,
                        ticks: {
                            color: colors.muted,
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }

    if (document.getElementById('storePerformanceChart') && storePerformance.length > 0) {
        const context = document.getElementById('storePerformanceChart').getContext('2d');
        const chartColors = ['#4285f4', '#34a853', '#fbbc04', '#ea4335', '#7baaf7', '#7cb342', '#ff7043', '#9aa0a6'];

        new Chart(context, {
            type: 'doughnut',
            data: {
                labels: storePerformance.map((store) => {
                    return store.store_info.length > 18 ? store.store_info.slice(0, 18) + '...' : store.store_info;
                }),
                datasets: [{
                    data: storePerformance.map((store) => parseFloat(store.total_gross || 0)),
                    backgroundColor: chartColors.slice(0, storePerformance.length),
                    borderWidth: 3,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '66%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 18,
                            color: colors.ink,
                            usePointStyle: true,
                            boxWidth: 10
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((sum, value) => sum + value, 0);
                                const percentage = total ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                return context.label + ': $' + context.parsed.toLocaleString() + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    if (document.getElementById('financialBreakdownChart') && Object.keys(financialAnalysis).length > 0) {
        const context = document.getElementById('financialBreakdownChart').getContext('2d');

        new Chart(context, {
            type: 'bar',
            data: {
                labels: ['Profit', 'Tax', 'Cancel', 'Void', 'Coupons'],
                datasets: [{
                    label: 'Rate',
                    data: [
                        financialAnalysis.profitMargin || 0,
                        financialAnalysis.taxRate || 0,
                        financialAnalysis.cancelRate || 0,
                        financialAnalysis.voidRate || 0,
                        financialAnalysis.couponUsage || 0
                    ],
                    backgroundColor: [
                        'rgba(52, 168, 83, 0.72)',
                        'rgba(66, 133, 244, 0.72)',
                        'rgba(251, 188, 4, 0.72)',
                        'rgba(234, 67, 53, 0.72)',
                        'rgba(95, 99, 104, 0.72)'
                    ],
                    borderRadius: 12,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed.y.toFixed(2) + '%';
                            }
                        }
                    }
                },
                scales: {
                    ...sharedScales,
                    y: {
                        ...sharedScales.y,
                        ticks: {
                            color: colors.muted,
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
    }

    if (document.getElementById('customerTrendsChart') && customerAnalytics.customerTrends) {
        const context = document.getElementById('customerTrendsChart').getContext('2d');

        new Chart(context, {
            type: 'line',
            data: {
                labels: customerAnalytics.customerTrends.map((item) => {
                    const date = new Date(item.date);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }),
                datasets: [
                    {
                        label: 'Customers',
                        data: customerAnalytics.customerTrends.map((item) => parseInt(item.customers || 0, 10)),
                        borderColor: colors.primary,
                        backgroundColor: colors.primarySoft,
                        tension: 0.35,
                        yAxisID: 'y',
                        borderWidth: 2
                    },
                    {
                        label: 'Avg Ticket ($)',
                        data: customerAnalytics.customerTrends.map((item) => parseFloat(item.avg_ticket || 0)),
                        borderColor: colors.warning,
                        backgroundColor: colors.warningSoft,
                        tension: 0.35,
                        yAxisID: 'y1',
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'start',
                        labels: {
                            color: colors.ink,
                            usePointStyle: true,
                            boxWidth: 10
                        }
                    }
                },
                scales: {
                    x: sharedScales.x,
                    y: {
                        type: 'linear',
                        position: 'left',
                        beginAtZero: true,
                        grid: { color: colors.line },
                        ticks: { color: colors.muted },
                        title: { display: true, text: 'Customers', color: colors.muted }
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        beginAtZero: true,
                        grid: { drawOnChartArea: false },
                        ticks: {
                            color: colors.muted,
                            callback: function(value) {
                                return '$' + value;
                            }
                        },
                        title: { display: true, text: 'Avg Ticket', color: colors.muted }
                    }
                }
            }
        });
    }
}

function refreshDashboard() {
    const refreshButtons = document.querySelectorAll('[data-refresh-dashboard]');

    refreshButtons.forEach((button) => {
        button.dataset.originalText = button.innerHTML;
        button.innerHTML = '<i class="bi bi-arrow-clockwise"></i>Refreshing...';
        button.disabled = true;
    });

    setTimeout(() => {
        window.location.reload();
    }, 700);
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-refresh-dashboard]').forEach((button) => {
        button.addEventListener('click', refreshDashboard);
    });

    document.querySelectorAll('.dropdown-item[href*="export"]').forEach((link) => {
        link.addEventListener('click', function() {
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Exporting...';

            setTimeout(() => {
                this.innerHTML = originalText;
            }, 2000);
        });
    });
});
</script>

@if($user->isAdmin())
    <div class="modal fade home-modal" id="userSelectionModal" tabindex="-1" aria-labelledby="userSelectionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <p class="home-section__eyebrow mb-2">Admin Tool</p>
                        <h5 class="modal-title" id="userSelectionModalLabel" style="font-family: 'Google Sans', sans-serif; font-weight: 500; color: #202124;">
                            Search and impersonate a user
                        </h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="text" id="userSearch" class="form-control home-modal__search" placeholder="Search by name, email, or store">
                    </div>

                    <ul class="nav home-modal__tabs mb-3" id="userTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="owners-tab" data-bs-toggle="tab" data-bs-target="#owners" type="button" role="tab">
                                <i class="bi bi-person-badge me-1"></i>Owners (<span id="ownersCount">0</span>)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="managers-tab" data-bs-toggle="tab" data-bs-target="#managers" type="button" role="tab">
                                <i class="bi bi-person-gear me-1"></i>Managers (<span id="managersCount">0</span>)
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="userTabContent">
                        <div class="tab-pane fade show active" id="owners" role="tabpanel" aria-labelledby="owners-tab">
                            <div id="ownersList" class="home-modal__list"></div>
                        </div>
                        <div class="tab-pane fade" id="managers" role="tabpanel" aria-labelledby="managers-tab">
                            <div id="managersList" class="home-modal__list"></div>
                        </div>
                    </div>

                    <div id="loadingState" class="text-center py-4" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted mb-0">Loading users...</p>
                    </div>

                    <div id="noResults" class="text-center py-4" style="display: none;">
                        <i class="bi bi-search" style="font-size: 2rem; color: #9aa0a6;"></i>
                        <p class="text-muted mb-0 mt-2">No users found for that search.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="home-link-button" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const userSelectionModal = document.getElementById('userSelectionModal');
        const userSearch = document.getElementById('userSearch');
        const ownersList = document.getElementById('ownersList');
        const managersList = document.getElementById('managersList');
        const ownersCount = document.getElementById('ownersCount');
        const managersCount = document.getElementById('managersCount');
        const loadingState = document.getElementById('loadingState');
        const noResults = document.getElementById('noResults');
        const defaultAvatarUrl = @json($defaultAvatar);
        const csrfToken = '{{ csrf_token() }}';
        const impersonateBaseUrl = '{{ rtrim(url('/'), '/') }}/impersonate';

        let allUsers = { owners: [], managers: [] };

        userSelectionModal.addEventListener('shown.bs.modal', function() {
            loadUsers();
        });

        userSearch.addEventListener('input', function() {
            renderUsers(this.value.toLowerCase());
        });

        function loadUsers() {
            loadingState.style.display = 'block';
            noResults.style.display = 'none';
            ownersList.innerHTML = '';
            managersList.innerHTML = '';

            setTimeout(() => {
                allUsers.owners = @json($modalOwnersData ?? []);
                allUsers.managers = @json($modalManagersData ?? []);
                loadingState.style.display = 'none';
                renderUsers(userSearch.value.toLowerCase());
            }, 250);
        }

        function renderUsers(query) {
            const filteredOwners = allUsers.owners.filter((user) => {
                return user.name.toLowerCase().includes(query) || user.email.toLowerCase().includes(query);
            });

            const filteredManagers = allUsers.managers.filter((user) => {
                return user.name.toLowerCase().includes(query) ||
                    user.email.toLowerCase().includes(query) ||
                    (user.store_name && user.store_name.toLowerCase().includes(query));
            });

            ownersCount.textContent = filteredOwners.length;
            managersCount.textContent = filteredManagers.length;

            ownersList.innerHTML = filteredOwners.length
                ? filteredOwners.map((user) => createUserCard(user)).join('')
                : '<div class="text-center py-3 text-muted">No owners found.</div>';

            managersList.innerHTML = filteredManagers.length
                ? filteredManagers.map((user) => createUserCard(user)).join('')
                : '<div class="text-center py-3 text-muted">No managers found.</div>';

            noResults.style.display = filteredOwners.length || filteredManagers.length ? 'none' : 'block';
        }

        function createUserCard(user) {
            const storeInfo = user.store_name
                ? `<p class="home-modal__user-meta" style="margin-top:4px;"><i class="bi bi-geo-alt me-1"></i>${user.store_name}</p>`
                : '';

            return `
                <div class="home-modal__user">
                    <div class="home-modal__user-main">
                        <img src="${user.avatar_url || defaultAvatarUrl}" alt="${user.name}">
                        <div>
                            <p class="home-modal__user-name">${user.name}</p>
                            <p class="home-modal__user-meta">${user.email}</p>
                            ${storeInfo}
                        </div>
                    </div>
                    <form method="POST" action="${impersonateBaseUrl}/${user.id}">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <button type="submit" class="home-admin-card__button">
                            <i class="bi bi-box-arrow-in-right"></i>
                            Login
                        </button>
                    </form>
                </div>
            `;
        }
    });
    </script>
@endif

@endsection
