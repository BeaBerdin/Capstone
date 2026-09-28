<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">

    <style>
        @page {
            size: A4 landscape;
            margin: 32px 35px 38px 35px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #252525;
            font-size: 9px;
            line-height: 1.4;
        }

        h1, h2, h3, p {
            margin: 0;
            padding: 0;
        }

        .page {
            width: 100%;
        }

        .page-break {
            page-break-before: always;
        }

        /* ---------------------------------
           HEADER
        --------------------------------- */

        .header {
            width: 100%;
            border-bottom: 3px solid #5b3cc4;
            padding-bottom: 13px;
            margin-bottom: 18px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .brand {
            width: 65%;
            vertical-align: middle;
        }

        .brand-name {
            color: #5b3cc4;
            font-size: 23px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .brand-subtitle {
            color: #6b7280;
            font-size: 9px;
            margin-top: 3px;
        }

        .report-meta {
            width: 35%;
            text-align: right;
            vertical-align: middle;
        }

        .report-title {
            font-size: 14px;
            font-weight: bold;
            color: #222222;
        }

        .report-period {
            color: #5b3cc4;
            font-size: 9px;
            font-weight: bold;
            margin-top: 3px;
        }

        /* ---------------------------------
           SECTION HEADINGS
        --------------------------------- */

        .section {
            margin-bottom: 17px;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #292929;
            border-left: 4px solid #5b3cc4;
            padding-left: 8px;
            margin-bottom: 9px;
        }

        .section-description {
            color: #777777;
            font-size: 8px;
            margin-bottom: 8px;
        }

        /* ---------------------------------
           KPI CARDS
        --------------------------------- */

        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 7px;
            margin-left: -7px;
            margin-right: -7px;
        }

        .kpi-cell {
            width: 25%;
            vertical-align: top;
        }

        .kpi {
            border: 1px solid #dedede;
            border-top: 3px solid #5b3cc4;
            background: #ffffff;
            padding: 11px 12px;
        }

        .kpi-label {
            color: #777777;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .kpi-value {
            color: #242424;
            font-size: 18px;
            font-weight: bold;
            margin-top: 5px;
        }

        .kpi-note {
            color: #888888;
            font-size: 7px;
            margin-top: 2px;
        }

        /* ---------------------------------
           SUMMARY TABLE
        --------------------------------- */

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #dddddd;
        }

        .summary-table td {
            border-bottom: 1px solid #eeeeee;
            padding: 7px 9px;
        }

        .summary-table tr:last-child td {
            border-bottom: none;
        }

        .summary-label {
            color: #666666;
            width: 70%;
        }

        .summary-value {
            text-align: right;
            font-weight: bold;
            color: #292929;
            width: 30%;
        }

        /* ---------------------------------
           TWO COLUMN CONTENT
        --------------------------------- */

        .two-column {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 0;
            margin-left: -10px;
            margin-right: -10px;
        }

        .column {
            width: 50%;
            vertical-align: top;
        }

        .panel {
            border: 1px solid #dddddd;
            background: #ffffff;
            padding: 10px;
        }

        .panel-title {
            font-size: 10px;
            font-weight: bold;
            color: #333333;
            border-bottom: 1px solid #e5e5e5;
            padding-bottom: 7px;
            margin-bottom: 7px;
        }

        /* ---------------------------------
           DATA TABLES
        --------------------------------- */

        .data-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #dddddd;
        }

        .data-table th {
            background: #f3f1fb;
            color: #4c339f;
            text-align: left;
            font-size: 8px;
            font-weight: bold;
            padding: 7px 7px;
            border-bottom: 1px solid #d9d4ef;
        }

        .data-table td {
            padding: 6px 7px;
            border-bottom: 1px solid #eeeeee;
            vertical-align: top;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .number {
            text-align: right;
        }

        .muted {
            color: #777777;
        }

        .small {
            font-size: 7px;
        }

        /* ---------------------------------
           STATUS
        --------------------------------- */

        .status-passed {
            color: #237a45;
            font-weight: bold;
        }

        .status-failed {
            color: #b33a3a;
            font-weight: bold;
        }

        .status-approved {
            color: #237a45;
            font-weight: bold;
        }

        .status-pending {
            color: #a66a00;
            font-weight: bold;
        }

        .status-rejected {
            color: #b33a3a;
            font-weight: bold;
        }

        /* ---------------------------------
           HIGHLIGHT BOX
        --------------------------------- */

        .highlight {
            background: #f5f2fc;
            border: 1px solid #ddd5f2;
            padding: 10px 12px;
            margin-bottom: 12px;
        }

        .highlight-title {
            color: #5b3cc4;
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 3px;
        }

        .highlight-text {
            color: #555555;
            font-size: 8px;
        }

        /* ---------------------------------
           FOOTER
        --------------------------------- */

        .footer {
            margin-top: 17px;
            border-top: 1px solid #dddddd;
            padding-top: 7px;
            color: #888888;
            font-size: 7px;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer-left {
            text-align: left;
        }

        .footer-right {
            text-align: right;
        }

        /* ---------------------------------
           PAGE 3 ACTIVITY
        --------------------------------- */

        .activity-table th:nth-child(1) {
            width: 24%;
        }

        .activity-table th:nth-child(2) {
            width: 28%;
        }

        .activity-table th:nth-child(3) {
            width: 17%;
        }

        .activity-table th:nth-child(4) {
            width: 16%;
        }

        .activity-table th:nth-child(5) {
            width: 15%;
        }

        .empty {
            text-align: center;
            color: #999999;
            padding: 14px !important;
        }
    </style>
</head>

<body>

    {{-- =========================================================
         PAGE 1 — EXECUTIVE OVERVIEW
    ========================================================== --}}

    <div class="page">

        <div class="header">
            <table class="header-table">
                <tr>
                    <td class="brand">
                        <div class="brand-name">PATHWISE</div>
                        <div class="brand-subtitle">
                            Learning Management System
                        </div>
                    </td>

                    <td class="report-meta">
                        <div class="report-title">
                            System Report
                        </div>

                        <div class="report-period">
                            {{ $reportPeriod }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="highlight">
            <div class="highlight-title">
                Executive Overview
            </div>

            <div class="highlight-text">
                This report summarizes user activity, course participation,
                learning performance, assignments, certificates, and
                transaction activity recorded in the PATHWISE system.
            </div>
        </div>

        <div class="section">

            <div class="section-title">
                Platform Overview
            </div>

            <table class="kpi-table">
                <tr>

                    <td class="kpi-cell">
                        <div class="kpi">
                            <div class="kpi-label">Students</div>
                            <div class="kpi-value">
                                {{ number_format($users['students']) }}
                            </div>
                            <div class="kpi-note">
                                Registered learners
                            </div>
                        </div>
                    </td>

                    <td class="kpi-cell">
                        <div class="kpi">
                            <div class="kpi-label">Teachers</div>
                            <div class="kpi-value">
                                {{ number_format($users['teachers']) }}
                            </div>
                            <div class="kpi-note">
                                Teaching users
                            </div>
                        </div>
                    </td>

                    <td class="kpi-cell">
                        <div class="kpi">
                            <div class="kpi-label">Admins</div>
                            <div class="kpi-value">
                                {{ number_format($users['admins']) }}
                            </div>
                            <div class="kpi-note">
                                Administrative users
                            </div>
                        </div>
                    </td>

                    <td class="kpi-cell">
                        <div class="kpi">
                            <div class="kpi-label">Courses</div>
                            <div class="kpi-value">
                                {{ number_format($courses) }}
                            </div>
                            <div class="kpi-note">
                                Courses in selected period
                            </div>
                        </div>
                    </td>

                </tr>
            </table>

        </div>

        <div class="section">

            <div class="section-title">
                Enrollment & Completion
            </div>

            <table class="kpi-table">
                <tr>

                    <td class="kpi-cell">
                        <div class="kpi">
                            <div class="kpi-label">Enrollments</div>
                            <div class="kpi-value">
                                {{ number_format($totalEnrollments) }}
                            </div>
                            <div class="kpi-note">
                                Total enrollments
                            </div>
                        </div>
                    </td>

                    <td class="kpi-cell">
                        <div class="kpi">
                            <div class="kpi-label">Completed</div>
                            <div class="kpi-value">
                                {{ number_format($completedEnrollments) }}
                            </div>
                            <div class="kpi-note">
                                Completed enrollments
                            </div>
                        </div>
                    </td>

                    <td class="kpi-cell">
                        <div class="kpi">
                            <div class="kpi-label">Active</div>
                            <div class="kpi-value">
                                {{ number_format($activeEnrollments) }}
                            </div>
                            <div class="kpi-note">
                                Active enrollments
                            </div>
                        </div>
                    </td>

                    <td class="kpi-cell">
                        <div class="kpi">
                            <div class="kpi-label">Completion Rate</div>
                            <div class="kpi-value">
                                {{ number_format($completionRate, 1) }}%
                            </div>
                            <div class="kpi-note">
                                Completed / total enrollments
                            </div>
                        </div>
                    </td>

                </tr>
            </table>

        </div>

        <table class="two-column">
            <tr>

                <td class="column">
                    <div class="panel">

                        <div class="panel-title">
                            Assessment Summary
                        </div>

                        <table class="summary-table">
                            <tr>
                                <td class="summary-label">
                                    Quiz Attempts
                                </td>
                                <td class="summary-value">
                                    {{ number_format($quizAttempts) }}
                                </td>
                            </tr>

                            <tr>
                                <td class="summary-label">
                                    Average Quiz Score
                                </td>
                                <td class="summary-value">
                                    {{ number_format($averageQuizScore, 1) }}
                                </td>
                            </tr>

                            <tr>
                                <td class="summary-label">
                                    Passed Quizzes
                                </td>
                                <td class="summary-value">
                                    {{ number_format($passedQuizzes) }}
                                </td>
                            </tr>

                            <tr>
                                <td class="summary-label">
                                    Failed Quizzes
                                </td>
                                <td class="summary-value">
                                    {{ number_format($failedQuizzes) }}
                                </td>
                            </tr>
                        </table>

                    </div>
                </td>

                <td class="column">
                    <div class="panel">

                        <div class="panel-title">
                            Assignment Summary
                        </div>

                        <table class="summary-table">
                            <tr>
                                <td class="summary-label">
                                    Assignments
                                </td>
                                <td class="summary-value">
                                    {{ number_format($assignments) }}
                                </td>
                            </tr>

                            <tr>
                                <td class="summary-label">
                                    Submissions
                                </td>
                                <td class="summary-value">
                                    {{ number_format($submissions) }}
                                </td>
                            </tr>

                            <tr>
                                <td class="summary-label">
                                    Graded
                                </td>
                                <td class="summary-value">
                                    {{ number_format($gradedSubmissions) }}
                                </td>
                            </tr>

                            <tr>
                                <td class="summary-label">
                                    Pending Grading
                                </td>
                                <td class="summary-value">
                                    {{ number_format($pendingSubmissions) }}
                                </td>
                            </tr>
                        </table>

                    </div>
                </td>

            </tr>
        </table>

        <div class="section" style="margin-top: 15px;">

            <div class="section-title">
                Certificates & Revenue
            </div>

            <table class="kpi-table">
                <tr>

                    <td class="kpi-cell">
                        <div class="kpi">
                            <div class="kpi-label">Certificates</div>
                            <div class="kpi-value">
                                {{ number_format($certificates) }}
                            </div>
                            <div class="kpi-note">
                                Certificates issued
                            </div>
                        </div>
                    </td>

                    <td class="kpi-cell">
                        <div class="kpi">
                            <div class="kpi-label">Transactions</div>
                            <div class="kpi-value">
                                {{ number_format($transactions) }}
                            </div>
                            <div class="kpi-note">
                                Total transactions
                            </div>
                        </div>
                    </td>

                    <td class="kpi-cell">
                        <div class="kpi">
                            <div class="kpi-label">Approved</div>
                            <div class="kpi-value">
                                {{ number_format($approvedTransactions) }}
                            </div>
                            <div class="kpi-note">
                                Approved transactions
                            </div>
                        </div>
                    </td>

                    <td class="kpi-cell">
                        <div class="kpi">
                            <div class="kpi-label">Revenue</div>
                            <div class="kpi-value">
                                ₱{{ number_format($totalRevenue, 2) }}
                            </div>
                            <div class="kpi-note">
                                Approved transaction amount
                            </div>
                        </div>
                    </td>

                </tr>
            </table>

        </div>

        <div class="footer">
            <table class="footer-table">
                <tr>
                    <td class="footer-left">
                        PATHWISE • System Report
                    </td>

                    <td class="footer-right">
                        Generated {{ now()->format('F d, Y h:i A') }}
                    </td>
                </tr>
            </table>
        </div>

    </div>


    {{-- =========================================================
         PAGE 2 — PERFORMANCE & TRANSACTIONS
    ========================================================== --}}

    <div class="page page-break">

        <div class="header">
            <table class="header-table">
                <tr>
                    <td class="brand">
                        <div class="brand-name">PATHWISE</div>
                        <div class="brand-subtitle">
                            Learning Performance & Operations
                        </div>
                    </td>

                    <td class="report-meta">
                        <div class="report-title">
                            Performance Report
                        </div>

                        <div class="report-period">
                            {{ $reportPeriod }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">

            <div class="section-title">
                Transaction Overview
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Transaction Status</th>
                        <th class="number">Count</th>
                        <th>Share of Transactions</th>
                    </tr>
                </thead>

                <tbody>

                    @php
                        $transactionTotal = max((int) $transactions, 1);

                        $approvedPercentage =
                            ($approvedTransactions / $transactionTotal) * 100;

                        $pendingPercentage =
                            ($pendingTransactions / $transactionTotal) * 100;

                        $rejectedPercentage =
                            ($rejectedTransactions / $transactionTotal) * 100;
                    @endphp

                    <tr>
                        <td class="status-approved">
                            Approved
                        </td>

                        <td class="number">
                            {{ number_format($approvedTransactions) }}
                        </td>

                        <td>
                            {{ number_format($approvedPercentage, 1) }}%
                        </td>
                    </tr>

                    <tr>
                        <td class="status-pending">
                            Pending
                        </td>

                        <td class="number">
                            {{ number_format($pendingTransactions) }}
                        </td>

                        <td>
                            {{ number_format($pendingPercentage, 1) }}%
                        </td>
                    </tr>

                    <tr>
                        <td class="status-rejected">
                            Rejected
                        </td>

                        <td class="number">
                            {{ number_format($rejectedTransactions) }}
                        </td>

                        <td>
                            {{ number_format($rejectedPercentage, 1) }}%
                        </td>
                    </tr>

                </tbody>
            </table>

        </div>

        <div class="section">

            <div class="section-title">
                Most Popular Courses
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 8%;">Rank</th>
                        <th style="width: 62%;">Course</th>
                        <th class="number" style="width: 30%;">
                            Enrollments
                        </th>
                    </tr>
                </thead>

                <tbody>

                    @forelse ($popularCourses as $index => $course)

                        <tr>
                            <td>
                                {{ $index + 1 }}
                            </td>

                            <td>
                                {{ $course->title ?? 'Untitled Course' }}
                            </td>

                            <td class="number">
                                {{ number_format($course->enrollments_count ?? 0) }}
                            </td>
                        </tr>

                    @empty

                        <tr>
                            <td colspan="3" class="empty">
                                No course enrollment data available.
                            </td>
                        </tr>

                    @endforelse

                </tbody>
            </table>

        </div>

        <table class="two-column">

            <tr>

                <td class="column">

                    <div class="panel">

                        <div class="panel-title">
                            Learning Outcomes
                        </div>

                        <table class="summary-table">

                            <tr>
                                <td class="summary-label">
                                    Total Enrollments
                                </td>

                                <td class="summary-value">
                                    {{ number_format($totalEnrollments) }}
                                </td>
                            </tr>

                            <tr>
                                <td class="summary-label">
                                    Completed
                                </td>

                                <td class="summary-value">
                                    {{ number_format($completedEnrollments) }}
                                </td>
                            </tr>

                            <tr>
                                <td class="summary-label">
                                    Active
                                </td>

                                <td class="summary-value">
                                    {{ number_format($activeEnrollments) }}
                                </td>
                            </tr>

                            <tr>
                                <td class="summary-label">
                                    Completion Rate
                                </td>

                                <td class="summary-value">
                                    {{ number_format($completionRate, 1) }}%
                                </td>
                            </tr>

                            <tr>
                                <td class="summary-label">
                                    Certificates Issued
                                </td>

                                <td class="summary-value">
                                    {{ number_format($certificates) }}
                                </td>
                            </tr>

                        </table>

                    </div>

                </td>

                <td class="column">

                    <div class="panel">

                        <div class="panel-title">
                            Assessment Outcomes
                        </div>

                        <table class="summary-table">

                            <tr>
                                <td class="summary-label">
                                    Quiz Attempts
                                </td>

                                <td class="summary-value">
                                    {{ number_format($quizAttempts) }}
                                </td>
                            </tr>

                            <tr>
                                <td class="summary-label">
                                    Average Score
                                </td>

                                <td class="summary-value">
                                    {{ number_format($averageQuizScore, 1) }}
                                </td>
                            </tr>

                            <tr>
                                <td class="summary-label">
                                    Passed
                                </td>

                                <td class="summary-value">
                                    {{ number_format($passedQuizzes) }}
                                </td>
                            </tr>

                            <tr>
                                <td class="summary-label">
                                    Failed
                                </td>

                                <td class="summary-value">
                                    {{ number_format($failedQuizzes) }}
                                </td>
                            </tr>

                        </table>

                    </div>

                </td>

            </tr>

        </table>

        <div class="section" style="margin-top: 15px;">

            <div class="section-title">
                Assignment Activity
            </div>

            <table class="data-table">

                <thead>
                    <tr>
                        <th>Metric</th>
                        <th class="number">Count</th>
                    </tr>
                </thead>

                <tbody>

                    <tr>
                        <td>Assignments Created</td>
                        <td class="number">
                            {{ number_format($assignments) }}
                        </td>
                    </tr>

                    <tr>
                        <td>Student Submissions</td>
                        <td class="number">
                            {{ number_format($submissions) }}
                        </td>
                    </tr>

                    <tr>
                        <td>Graded Submissions</td>
                        <td class="number">
                            {{ number_format($gradedSubmissions) }}
                        </td>
                    </tr>

                    <tr>
                        <td>Pending Grading</td>
                        <td class="number">
                            {{ number_format($pendingSubmissions) }}
                        </td>
                    </tr>

                </tbody>

            </table>

        </div>

        <div class="footer">
            <table class="footer-table">
                <tr>
                    <td class="footer-left">
                        PATHWISE • Performance & Operations
                    </td>

                    <td class="footer-right">
                        {{ $reportPeriod }}
                    </td>
                </tr>
            </table>
        </div>

    </div>


    {{-- =========================================================
         PAGE 3 — RECENT ACTIVITY
    ========================================================== --}}

    <div class="page page-break">

        <div class="header">
            <table class="header-table">
                <tr>
                    <td class="brand">
                        <div class="brand-name">PATHWISE</div>
                        <div class="brand-subtitle">
                            Recent Learning Activity
                        </div>
                    </td>

                    <td class="report-meta">
                        <div class="report-title">
                            Activity Report
                        </div>

                        <div class="report-period">
                            {{ $reportPeriod }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">

            <div class="section-title">
                Recent Quiz Results
            </div>

            <table class="data-table activity-table">

                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Quiz</th>
                        <th>Score</th>
                        <th>Result</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse ($recentQuizResults as $result)

                        <tr>

                            <td>
                                {{ $result->student->name ?? 'Unknown Student' }}
                            </td>

                            <td>
                                {{ $result->quiz->title ?? 'Untitled Quiz' }}
                            </td>

                            <td>
                                {{ $result->score ?? 0 }}
                                /
                                {{ $result->total_items ?? 0 }}
                            </td>

                            <td>
                                @if (($result->remarks ?? '') === 'passed')
                                    <span class="status-passed">
                                        Passed
                                    </span>
                                @else
                                    <span class="status-failed">
                                        Failed
                                    </span>
                                @endif
                            </td>

                            <td class="muted">
                                {{ optional($result->created_at)->format('M d, Y') }}
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="5" class="empty">
                                No quiz activity found for this period.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="section">

            <div class="section-title">
                Recent Certificates
            </div>

            <table class="data-table">

                <thead>
                    <tr>
                        <th style="width: 30%;">Student</th>
                        <th style="width: 45%;">Course</th>
                        <th style="width: 25%;">Issued</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse ($recentCertificates as $certificate)

                        <tr>

                            <td>
                                {{ $certificate->student->name ?? 'Unknown Student' }}
                            </td>

                            <td>
                                {{ $certificate->course->title ?? 'Untitled Course' }}
                            </td>

                            <td class="muted">
                                {{ optional($certificate->created_at)->format('M d, Y') }}
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="3" class="empty">
                                No certificates found for this period.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="highlight">

            <div class="highlight-title">
                Report Summary
            </div>

            <div class="highlight-text">

                PATHWISE recorded
                <strong>{{ number_format($totalEnrollments) }}</strong>
                enrollments and
                <strong>{{ number_format($completedEnrollments) }}</strong>
                completed enrollments during the selected reporting period.

                The system recorded
                <strong>{{ number_format($quizAttempts) }}</strong>
                quiz attempts and
                <strong>{{ number_format($certificates) }}</strong>
                certificates issued.

            </div>

        </div>

        <div class="footer">

            <table class="footer-table">
                <tr>

                    <td class="footer-left">
                        PATHWISE • Generated System Report
                    </td>

                    <td class="footer-right">
                        Generated {{ now()->format('F d, Y h:i A') }}
                    </td>

                </tr>
            </table>

        </div>

    </div>

</body>
</html>