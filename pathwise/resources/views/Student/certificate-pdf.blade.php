<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificate</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 15px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            background: #ffffff;
            color: #111827;
            margin: 0;
            padding: 0;
        }

        .certificate {
            border: 8px solid #7e22ce;
            padding: 18px;
            text-align: center;
        }

        .inner {
            border: 2px solid #e9d5ff;
            padding: 20px 40px;
        }

        .logo {
            width: 90px;
            margin-bottom: 6px;
        }

        h1 {
            font-size: 34px;
            letter-spacing: 8px;
            color: #6b21a8;
            margin: 5px 0 0;
        }

        h2 {
            font-size: 18px;
            letter-spacing: 2px;
            color: #9333ea;
            margin: 4px 0 12px;
        }

        .decor {
            font-size: 18px;
            color: #9333ea;
            margin-bottom: 12px;
        }

        .label {
            font-size: 13px;
            color: #4b5563;
        }

        .student {
            display: inline-block;
            font-size: 28px;
            font-weight: bold;
            color: #581c87;
            border-bottom: 2px solid #d8b4fe;
            padding: 5px 50px;
            margin: 8px 0 18px;
        }

        .course {
            font-size: 22px;
            font-weight: bold;
            color: #6b21a8;
            margin: 8px 0 14px;
        }

        .description {
            font-size: 12px;
            line-height: 1.5;
            width: 720px;
            margin: 0 auto;
            color: #4b5563;
        }

        .details {
            width: 100%;
            margin-top: 22px;
        }

        .details td {
            width: 50%;
            text-align: center;
            font-size: 12px;
        }

        .details .heading {
            color: #6b7280;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 1px;
        }

        .details .value {
            font-weight: bold;
            font-size: 14px;
            color: #581c87;
            margin-top: 4px;
        }

        .details .verify {
            color: #9ca3af;
            font-size: 9px;
            margin-top: 3px;
        }

        .signature {
            margin-top: 22px;
        }

        .line {
            width: 220px;
            border-top: 2px solid #7e22ce;
            margin: 0 auto 6px;
        }

        .platform {
            font-weight: bold;
            font-size: 14px;
            letter-spacing: 1px;
            color: #581c87;
        }

        .platform-sub {
            color: #6b7280;
            font-size: 11px;
        }
    </style>
</head>

<body>

    <div class="certificate">
        <div class="inner">

            <img src="{{ public_path('images/pathwise-icon.png') }}"
                 class="logo"
                 alt="PathWise">

            <h1>CERTIFICATE</h1>
            <h2>OF COMPLETION</h2>

            <div class="decor">&#10086;</div>

            <div class="label">This certificate is proudly awarded to</div>

            <div class="student">
                {{ $certificate->student->name }}
            </div>

            <div class="label">
                for successfully completing the course
            </div>

            <div class="course">
                {{ $certificate->course->title }}
            </div>

            <p class="description">
                This certificate is proudly awarded in recognition of the learner&rsquo;s
                dedication, commitment, and successful completion of all course
                requirements, assessments, and learning activities under the
                academic standards of the PathWise AI-Powered E-Learning Platform.
            </p>

            <table class="details">
                <tr>
                    <td>
                        <div class="heading">Certificate Number</div>
                        <div class="value">{{ $certificate->certificate_number }}</div>
                        <div class="verify">Verify at: pathwise.test</div>
                    </td>

                    <td>
                        <div class="heading">Date Issued</div>
                        <div class="value">
                            {{ \Carbon\Carbon::parse($certificate->issued_date)->format('F d, Y') }}
                        </div>
                    </td>
                </tr>
            </table>

            <div class="signature">
                <div class="line"></div>
                <div class="platform">PATHWISE</div>
                <div class="platform-sub">AI-Powered E-Learning Platform</div>
            </div>

        </div>
    </div>

</body>
</html>