@php
    use App\Models\UtilityBillTransaction;

    if ($transaction->status === UtilityBillTransaction::PENDING) {
        $color = '#EE7541';
    } elseif ($transaction->status === UtilityBillTransaction::APPROVED) {
        $color = '#2F949A';
    } elseif ($transaction->status === UtilityBillTransaction::FAILED) {
        $color = '#FF3B30';
    } elseif ($transaction->status === UtilityBillTransaction::CANCELLED) {
        $color = '#4A36C2';
    } elseif ($transaction->status === UtilityBillTransaction::PROCESSING) {
        $color = '#0160E1';
    }else{
        $color = '#0160E1';
    }
@endphp

<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml">

<head>
    <meta charset="utf-8">
    <meta name="x-apple-disable-message-reformatting">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
    <!--[if mso]>
  <noscript>
    <xml>
      <o:OfficeDocumentSettings xmlns:o="urn:schemas-microsoft-com:office:office">
        <o:PixelsPerInch>96</o:PixelsPerInch>
      </o:OfficeDocumentSettings>
    </xml>
  </noscript>
  <style>
    td,th,div,p,a,h1,h2,h3,h4,h5,h6 {font-family: "Segoe UI", sans-serif; mso-line-height-rule: exactly;}
  </style>
<![endif]-->
    <title>
        {{ $transaction->type }}
        @if ($transaction->status === UtilityBillTransaction::PENDING)
            Pending
        @elseif($transaction->status === UtilityBillTransaction::APPROVED)
            Completed
        @elseif($transaction->status === UtilityBillTransaction::FAILED)
            Failed
        @elseif($transaction->status === UtilityBillTransaction::CANCELLED)
            Cancelled
        @else
            Status
        @endif
    </title>

    <style>
        @media (max-width: 600px) {
            .sm-w-full {
                width: 100% !important;
            }

            .sm-px-24 {
                padding-left: 24px !important;
                padding-right: 24px !important;
            }
        }
    </style>
</head>

<body
    style="margin: 0; width: 100%; padding: 0; word-break: break-word; -webkit-font-smoothing: antialiased; background-color: #f3f4f6;">
    <div style="display: none;">
        @if ($transaction->status === UtilityBillTransaction::PENDING)
            Your {{ $transaction->type }} is currently pending.
        @elseif($transaction->status === UtilityBillTransaction::APPROVED)
            Your {{ $transaction->type }} has been successfully processed.
        @elseif($transaction->status === UtilityBillTransaction::FAILED)
            Your {{ $transaction->type }} has failed. Please review the details.
        @elseif($transaction->status === UtilityBillTransaction::CANCELLED)
            Your {{ $transaction->type }} has been cancelled.
        @else
            Your {{ $transaction->type }} is being processed.
        @endif
    </div>

    <div role="article" aria-roledescription="email" aria-label="Payment Confirmation" lang="en">
        <table style="width: 100%; font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;"
            cellpadding="0" cellspacing="0" role="presentation">
            <tr>
                <td align="center" style="background-color: #f3f4f6;">
                    <table class="sm-w-full" style="width: 600px;" cellpadding="0" cellspacing="0" role="presentation">
                        <tr>
                            <td align="center" class="sm-px-24" style="padding-top: 24px; padding-bottom: 24px;">
                                <table style="width: 100%;" cellpadding="0" cellspacing="0" role="presentation">
                                    <tr>
                                        <td class="sm-px-24"
                                            style="border-radius: 4px; background-color: #ffffff; padding: 32px; text-align: left; font-size: 16px; line-height: 24px; color: #1f2937;">
                                            <a href="{{ config('app.url') }}">
                                                <img src="{{ config('app.mail_url') }}/images/mail/logo.png"
                                                    width="145" alt="{{ config('app.name') }}"
                                                    style="max-width: 100%; vertical-align: middle; line-height: 100%; border: 0;">
                                            </a>
                                            <p>Hello {{ $user->first_name }},</p>
                                            <div style="margin-top: 24px; line-height: 100%;">
                                                <p>
                                                    @if ($transaction->status === UtilityBillTransaction::PENDING)
                                                        Your {{ $transaction->type }} transaction is currently
                                                        <strong>pending</strong>. We are processing it, and you will
                                                        receive an update shortly.
                                                    @elseif($transaction->status === UtilityBillTransaction::APPROVED)
                                                        Great news! Your {{ $transaction->type }} transaction has been
                                                        <strong>approved</strong>
                                                    @elseif($transaction->status === UtilityBillTransaction::FAILED)
                                                        Unfortunately, your {{ $transaction->type }} transaction was
                                                        <strong>rejected</strong>. Please review the reason below or
                                                        contact support for further assistance.
                                                    @elseif($transaction->status === UtilityBillTransaction::CANCELLED)
                                                        Your {{ $transaction->type }} transaction has been
                                                        <strong>cancelled</strong>. If you didn’t initiate this, please
                                                        contact support immediately.
                                                    @else
                                                        Your {{ $transaction->type }} transaction is currently being
                                                        <strong>processed</strong>. We will notify you once the process
                                                        is complete.
                                                    @endif
                                                </p>
                                                <div
                                                    style="margin-top: 16px; width: 100%; border-radius: 6px; background-color: #f3f4f6; padding: 10px;">
                                                    <table style="width: 100%;" cellpadding="0" cellspacing="0"
                                                        role="presentation">
                                                        <tr>
                                                            <td
                                                                style="padding: 2px; text-align: left; font-size: 14px; line-height: 24px; color: #1f2937;">
                                                                <p><strong>Transaction ID:</strong> <span
                                                                        style="color: #4b5563;">{{ $transaction->id }}</span>
                                                                </p>
                                                                <p><strong>Amount:</strong> <span
                                                                        style="color: #4b5563;">₦{{ $transaction->amount }}</span>
                                                                </p>
                                                                <p><strong>Service:</strong> <span
                                                                        style="color: #4b5563;">{{ $transaction->service_name }}</span>
                                                                </p>
                                                                <p><strong>Variation:</strong> <span
                                                                        style="color: #4b5563;">{{ $transaction->package }}</span>
                                                                </p>
                                                                <p><strong>Account ID:</strong> <span
                                                                        style="color: #4b5563;">{{ $transaction->number }}</span>
                                                                </p>
                                                                <p><strong>Time:</strong> <span
                                                                        style="color: #4b5563;">{{ $transaction->updated_at ? $transaction->updated_at->format('F j, Y, g:i a') : $transaction->created_at->format('F j, Y, g:i a') }}</span>
                                                                </p>
                                                                <p><strong>Status:</strong> <span
                                                                        style="border-radius: 9999px; background-color: {{ $color }}; padding-left: 8px; padding-right: 8px; padding-top: 2px; padding-bottom: 2px; font-weight: 500; color: #ffffff;">
                                                                        @if ($transaction->status === UtilityBillTransaction::PENDING)
                                                                            Pending
                                                                        @elseif($transaction->status === UtilityBillTransaction::APPROVED)
                                                                            Completed
                                                                        @elseif($transaction->status === UtilityBillTransaction::FAILED)
                                                                            Rejected
                                                                        @elseif($transaction->status === UtilityBillTransaction::CANCELLED)
                                                                            Cancelled
                                                                        @else
                                                                            Processing
                                                                        @endif
                                                                    </span>
                                                                </p>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>
                                                <p style="font-size: 14px;">If you have any questions or need
                                                    further assistance, please reach out to our support team at <a
                                                        href="mailto:{{ config('app.support_email') }}">{{ config('app.support_email') }}</a>
                                                    or call us at {{ config('app.support_phone') }}.</p>
                                            </div>
                                            <div style="margin-top: 24px; line-height: 100%;">
                                                <p style="margin-top: 40px; font-size: 14px; color: #374151;">Send
                                                    RMB and manage your funds seamlessly across
                                                    borders. Download our app for the best experience:</p>
                                                <div style="display: flex; align-items: center; gap: 5px;">
                                                    <a href="{{ config('app.playstore_link') }}">
                                                        <img src="{{ config('app.mail_url') }}/images/mail/playstore.png"
                                                            width="100" alt="Download on Google Play"
                                                            style="max-width: 100%; vertical-align: middle; border: 0;">
                                                    </a>
                                                    <a href="{{ config('app.appstore_link') }}">
                                                        <img src="{{ config('app.mail_url') }}/images/mail/appstore.png"
                                                            width="100" alt="Download on the App Store"
                                                            style="max-width: 100%; vertical-align: middle; border: 0; margin-left: 10px;">
                                                    </a>
                                                </div>
                                            </div>
                                            <table style="width: 100%;" cellpadding="0" cellspacing="0"
                                                role="presentation">
                                                <tr>
                                                    <td style="padding-top: 32px; padding-bottom: 32px;">
                                                        <div
                                                            style="height: 1px; background-color: #e5e7eb; line-height: 1px;">
                                                            &zwnj;</div>
                                                    </td>
                                                </tr>
                                            </table>
                                            <p style="font-size: 12px; font-weight: 500; color: #6b7280;">For any
                                                questions or support, contact us at <a
                                                    href="mailto:{{ config('app.support_email') }}">{{ config('app.support_email') }}</a>
                                                or call {{ config('app.support_phone') }}.</p>
                                            <p style="font-size: 12px; font-weight: 500; color: #6b7280;">Stay
                                                updated with the latest news by following us on <a
                                                    href="{{ config('app.twitter_link') ?? '' }}">Twitter</a>, <a
                                                    href="{{ config('app.facebook_link') ?? '' }}">Facebook</a>, and
                                                <a href="{{ config('app.instagram_link') ?? '' }}">Instagram</a>.
                                            </p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="height: 48px;"></td>
                                    </tr>
                                    <tr>
                                        <td
                                            style="padding-left: 24px; padding-right: 24px; text-align: center; font-size: 12px; color: #4b5563;">
                                            <p style="margin-bottom: 4px; text-transform: uppercase;">
                                                {{ config('app.name') }}
                                            </p>
                                            <p style="font-style: italic;">&copy; {{ date('Y') }} All Rights
                                                Reserved</p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
