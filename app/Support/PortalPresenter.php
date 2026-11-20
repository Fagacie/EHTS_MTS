<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Enums\CustomerType;
use App\Enums\EncodingStatus;
use App\Enums\EntitlementStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Booking;
use App\Models\Certificate;
use App\Models\CustomerProfile;
use App\Models\Module;
use App\Models\ModuleEntitlement;
use App\Models\ModuleMaterial;
use App\Models\ModuleVideo;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SessionRecording;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class PortalPresenter
{
    public static function displayTimezone(): string
    {
        return (string) config('etms.display_timezone', 'Asia/Kuala_Lumpur');
    }

    /**
     * Strip internal decision/architecture/policy references before exposing text to UI.
     */
    public static function sanitizeReason(?string $reason): ?string
    {
        if ($reason === null) {
            return null;
        }

        $cleaned = trim((string) preg_replace(
            '/\s*\((?:[A-Z]+-\d+|recording_access_policy\s*=\s*[^)]+)\)/',
            '',
            $reason,
        ));

        return $cleaned !== '' ? $cleaned : null;
    }

    /**
     * Determine whether an internal action path targets a Vue/Inertia-migrated route.
     */
    public static function isInertiaPath(?string $path): bool
    {
        if (! is_string($path) || $path === '' || ! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return false;
        }

        $parsedPath = parse_url($path, PHP_URL_PATH);
        if (! is_string($parsedPath)) {
            return false;
        }

        $normalized = $parsedPath === '/' ? '/' : rtrim($parsedPath, '/');

        if (str_contains($normalized, '/stream/') || str_ends_with($normalized, '/download')) {
            return false;
        }

        if (in_array($normalized, [
            '/',
            '/training',
            '/sessions',
            '/instructors',
            '/login',
            '/register',
            '/forgot-password',
            '/email/verify',
            '/two-factor-setup',
            '/two-factor-challenge',
            '/checkout/return',
            '/portal',
            '/portal/bookings',
            '/portal/orders',
            '/portal/certificates',
            '/portal/modules',
            '/portal/recordings',
            '/portal/notifications',
            '/portal/profile',
            '/portal/security',
            '/instructor',
            '/instructor/sessions',
            '/instructor/profile',
            '/instructor/notifications',
        ], true)) {
            return true;
        }

        if (str_starts_with($normalized, '/portal/recordings/') && str_ends_with($normalized, '/watch')) {
            return true;
        }

        foreach ([
            '/training/',
            '/sessions/',
            '/instructors/',
            '/reset-password/',
            '/checkout/session/',
            '/checkout/module/',
            '/portal/bookings/',
            '/portal/orders/',
            '/portal/certificates/',
            '/portal/modules/',
            '/instructor/sessions/',
        ] as $prefix) {
            if (str_starts_with($normalized, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert redirects pointing to external origins or unmigrated Blade/Filament routes into Inertia::location()
     * for Inertia XHR requests while leaving standard HTTP redirects untouched.
     */
    public static function inertiaSafeRedirect(
        Request $request,
        RedirectResponse $redirect,
    ): RedirectResponse|SymfonyResponse {
        if (! $request->header('X-Inertia')) {
            return $redirect;
        }

        $targetUrl = $redirect->getTargetUrl();
        $targetHost = parse_url($targetUrl, PHP_URL_HOST);

        if (is_string($targetHost) && $targetHost !== '' && strcasecmp($targetHost, $request->getHost()) !== 0) {
            return Inertia::location($targetUrl);
        }

        $targetPath = parse_url($targetUrl, PHP_URL_PATH);
        if (! self::isInertiaPath(is_string($targetPath) ? $targetPath : '/')) {
            return Inertia::location($targetUrl);
        }

        return $redirect;
    }

    /**
     * @return array<string, mixed>
     */
    public static function bookingListItem(Booking $booking): array
    {
        $tz = self::displayTimezone();
        $session = $booking->trainingSession;
        $order = $booking->order;
        $isHoldActive = $booking->isHoldActive();

        $venueParts = array_filter([
            $session?->venue_name ?: 'TBA',
            $session?->venue_city,
        ]);

        return [
            'id' => $booking->id,
            'bookingReference' => $booking->booking_reference,
            'orderNumber' => $order?->order_number,
            'sessionTitle' => $session?->title ?? 'Training Session',
            'moduleCode' => $session?->module?->code,
            'sessionCode' => $session?->session_code,
            'scheduleLabel' => $session?->formattedSchedule() ?? 'Schedule TBA',
            'venueLabel' => implode(', ', $venueParts),
            'amountLabel' => $order?->formattedTotal() ?? '—',
            'statusValue' => $booking->status->value,
            'statusLabel' => $booking->status->label(),
            'isHoldActive' => $isHoldActive,
            'holdExpiresFullLabel' => $isHoldActive && $booking->reservation_expires_at
                ? $booking->reservation_expires_at->copy()->timezone($tz)->format('d M Y, h:i A').' MYT'
                : null,
            'holdExpiresTimeLabel' => $isHoldActive && $booking->reservation_expires_at
                ? $booking->reservation_expires_at->copy()->timezone($tz)->format('h:i A')
                : null,
            'detailUrl' => route('portal.bookings.show', $booking),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function bookingDetail(Booking $booking, bool $hasModuleEntitlement = false): array
    {
        $tz = self::displayTimezone();
        $session = $booking->trainingSession;
        $order = $booking->order;
        $attendance = $booking->attendance;
        $isHoldActive = $booking->isHoldActive();

        $venueAddressParts = array_filter([
            $session?->venue_address,
            trim(($session?->venue_postcode ?? '').' '.($session?->venue_city ?? '')),
            $session?->venue_state,
        ]);

        $instructors = $session
            ? $session->instructors->map(static fn ($instructor): array => [
                'name' => $instructor->instructorProfile?->resolvedDisplayName() ?: $instructor->name,
                'title' => $instructor->instructorProfile?->professional_title,
            ])->values()->all()
            : [];

        $isOpenForBooking = $session?->isOpenForBooking() ?? false;

        return [
            'id' => $booking->id,
            'bookingReference' => $booking->booking_reference,
            'statusValue' => $booking->status->value,
            'statusLabel' => $booking->status->label(),
            'isHoldActive' => $isHoldActive,
            'isConfirmed' => $booking->status === BookingStatus::Confirmed,
            'isExpired' => $booking->status === BookingStatus::Expired,
            'isCancelled' => $booking->status === BookingStatus::Cancelled,
            'isRefunded' => $booking->status === BookingStatus::Refunded,
            'holdExpiresLabel' => $isHoldActive && $booking->reservation_expires_at
                ? $booking->reservation_expires_at->copy()->timezone($tz)->format('d M Y, h:i A').' MYT'
                : null,
            'cancellationReason' => $booking->cancellation_reason,
            'session' => [
                'title' => $session?->title ?? 'Training Session',
                'sessionCode' => $session?->session_code,
                'moduleCode' => $session?->module?->code,
                'scheduleLabel' => $session?->formattedSchedule() ?? 'Schedule TBA',
                'durationLabel' => $session?->formattedDuration() ?? '—',
                'venueName' => $session?->venue_name ?: 'To be confirmed',
                'venueAddressLine' => $venueAddressParts !== [] ? implode(', ', $venueAddressParts) : null,
                'venueNotes' => $session?->venue_notes,
                'venueMapUrl' => $session?->venue_map_url,
                'isOpenForBooking' => $isOpenForBooking,
                'bookAgainUrl' => $session && $isOpenForBooking ? route('checkout.session.show', $session) : null,
                'modulePortalUrl' => $booking->status === BookingStatus::Confirmed && $session?->module && $hasModuleEntitlement
                    ? route('portal.modules.show', $session->module)
                    : null,
                'moduleCatalogueUrl' => $session?->module ? route('catalogue.modules.show', $session->module) : null,
                'recordingsUrl' => $booking->status === BookingStatus::Confirmed ? route('portal.recordings.index') : null,
                'instructors' => $instructors,
            ],
            'attendance' => $attendance ? [
                'attendanceStatusValue' => $attendance->attendance_status->value,
                'attendanceStatusLabel' => $attendance->attendance_status->label(),
                'reviewStatusValue' => $attendance->review_status->value,
                'reviewStatusLabel' => $attendance->review_status->label(),
                'remarks' => $attendance->remarks,
            ] : null,
            'order' => $order ? [
                'orderNumber' => $order->order_number,
                'statusValue' => $order->status->value,
                'statusLabel' => $order->status->label(),
                'registrationTypeLabel' => ucwords(str_replace('_', ' ', (string) $order->customer_type)),
                'companyName' => $order->company_name,
                'totalLabel' => $order->formattedTotal(),
                'detailUrl' => route('portal.orders.show', $order),
                'payUrl' => route('portal.orders.pay', $order),
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function orderListItem(Order $order): array
    {
        $tz = self::displayTimezone();
        $primaryItem = $order->items->first();
        $latestPayment = $order->payments->first();

        return [
            'id' => $order->id,
            'orderNumber' => $order->order_number,
            'createdAtLabel' => $order->created_at?->copy()->timezone($tz)->format('d M Y, h:i A') ?? '—',
            'itemTitle' => $primaryItem?->item_title_snapshot ?? $order->order_type->label(),
            'itemCode' => $primaryItem?->item_code_snapshot,
            'orderTypeValue' => $order->order_type->value,
            'orderTypeLabel' => $order->order_type->label(),
            'gatewayLabel' => $latestPayment?->gateway ? ucfirst((string) $latestPayment->gateway) : null,
            'totalLabel' => $order->formattedTotal(),
            'statusValue' => $order->status->value,
            'statusLabel' => $order->status->label(),
            'detailUrl' => route('portal.orders.show', $order),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function orderDetail(Order $order): array
    {
        $tz = self::displayTimezone();
        $canPayOnline = $order->status === OrderStatus::PendingPayment
            && (! $order->expires_at || $order->expires_at->isFuture());

        $billingAddress = collect([
            $order->billing_address_line_1,
            $order->billing_address_line_2,
            trim(($order->billing_postcode ?? '').' '.($order->billing_city ?? '')),
            $order->billing_state,
            $order->billing_country,
        ])->filter()->implode(', ');

        $currencyPrefix = $order->currency === 'MYR' ? 'RM' : $order->currency;

        return [
            'id' => $order->id,
            'orderNumber' => $order->order_number,
            'orderTypeValue' => $order->order_type->value,
            'orderTypeLabel' => $order->order_type->label(),
            'statusValue' => $order->status->value,
            'statusLabel' => $order->status->label(),
            'isPendingPayment' => $order->status === OrderStatus::PendingPayment,
            'canPayOnline' => $canPayOnline,
            'createdAtLabel' => $order->created_at
                ? $order->created_at->copy()->timezone($tz)->format('d M Y, h:i A').' MYT'
                : '—',
            'expiresAtLabel' => $order->expires_at
                ? $order->expires_at->copy()->timezone($tz)->format('d M Y, h:i A').' MYT'
                : null,
            'totalLabel' => $order->formattedTotal(),
            'currency' => $order->currency,
            'payUrl' => route('portal.orders.pay', $order),
            'items' => $order->items->map(static fn ($item): array => [
                'id' => $item->id,
                'title' => $item->item_title_snapshot,
                'code' => $item->item_code_snapshot,
                'quantity' => (int) $item->quantity,
                'lineTotalLabel' => "{$currencyPrefix} ".number_format((float) $item->line_total, 2),
            ])->values()->all(),
            'bookings' => $order->bookings->map(static fn (Booking $linkedBooking): array => [
                'id' => $linkedBooking->id,
                'bookingReference' => $linkedBooking->booking_reference,
                'statusValue' => $linkedBooking->status->value,
                'statusLabel' => $linkedBooking->status->label(),
                'detailUrl' => route('portal.bookings.show', $linkedBooking),
            ])->values()->all(),
            'entitlements' => $order->moduleEntitlements->map(static fn (ModuleEntitlement $entitlement): array => [
                'id' => $entitlement->id,
                'moduleTitle' => $entitlement->module?->title ?? 'Training Module Access',
                'statusValue' => $entitlement->status->value,
                'statusLabel' => $entitlement->status->label(),
                'isActive' => $entitlement->status === EntitlementStatus::Active,
                'moduleUrl' => $entitlement->status === EntitlementStatus::Active && $entitlement->module
                    ? route('portal.modules.show', $entitlement->module)
                    : null,
            ])->values()->all(),
            'payments' => $order->payments->map(static function ($payment) use ($tz): array {
                $timestampPrefix = null;
                $timestampLabel = null;

                if ($payment->paid_at) {
                    $timestampPrefix = 'Paid';
                    $timestampLabel = $payment->paid_at->copy()->timezone($tz)->format('d M Y, h:i A').' MYT';
                } elseif ($payment->created_at) {
                    $timestampPrefix = 'Initiated';
                    $timestampLabel = $payment->created_at->copy()->timezone($tz)->format('d M Y, h:i A').' MYT';
                }

                return [
                    'id' => $payment->id,
                    'paymentReference' => $payment->payment_reference,
                    'statusValue' => $payment->status->value,
                    'statusLabel' => $payment->status->label(),
                    'gatewayLabel' => ucfirst((string) $payment->gateway),
                    'timestampPrefix' => $timestampPrefix,
                    'timestampLabel' => $timestampLabel,
                    'amountLabel' => $payment->formattedAmount(),
                ];
            })->values()->all(),
            'refunds' => $order->refunds->map(static fn ($refund): array => [
                'id' => $refund->id,
                'refundReference' => $refund->refund_reference,
                'statusValue' => $refund->status->value,
                'statusLabel' => $refund->status->label(),
                'methodLabel' => $refund->refund_method->label(),
                'reason' => $refund->reason,
                'amountLabel' => $refund->formattedAmount(),
            ])->values()->all(),
            'billing' => [
                'customerName' => $order->customer_name,
                'customerEmail' => $order->customer_email,
                'customerPhone' => $order->customer_phone,
                'customerTypeLabel' => ucwords(str_replace('_', ' ', (string) $order->customer_type)),
                'companyName' => $order->company_name,
                'billingAddress' => $billingAddress !== '' ? $billingAddress : null,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function certificateListItem(Certificate $certificate): array
    {
        $tz = self::displayTimezone();
        $isIssued = $certificate->isIssued();

        return [
            'id' => $certificate->id,
            'certificateNumber' => $certificate->certificate_number,
            'title' => $certificate->training_title_snapshot,
            'code' => $certificate->training_code_snapshot,
            'recordTypeLabel' => $certificate->recordTypeLabel(),
            'completionDateLabel' => $certificate->completion_date?->format('d M Y') ?? '—',
            'issuedDateLabel' => $certificate->issued_at?->copy()->timezone($tz)->format('d M Y') ?? '—',
            'isIssued' => $isIssued,
            'isRevoked' => $certificate->isRevoked(),
            'revocationReason' => $certificate->revocation_reason,
            'revokedAtLabel' => $certificate->revoked_at
                ? $certificate->revoked_at->copy()->timezone($tz)->format('d M Y, h:i A').' MYT'
                : null,
            'viewUrl' => route('portal.certificates.show', $certificate),
            'downloadUrl' => $isIssued ? route('certificates.download', $certificate) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function certificateDetail(Certificate $certificate): array
    {
        return [
            ...self::certificateListItem($certificate),
            'issuingOrganisation' => $certificate->issuing_organisation_snapshot,
            'traineeName' => $certificate->trainee_name_snapshot,
            'signatoryName' => $certificate->signatory_name_snapshot,
            'signatoryTitle' => $certificate->signatory_title_snapshot,
            'fileSizeLabel' => $certificate->isIssued() && $certificate->pdf_file_size_bytes
                ? $certificate->formattedFileSize()
                : null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function activeModuleEntitlement(ModuleEntitlement $entitlement): ?array
    {
        $module = $entitlement->module;

        if (! $module) {
            return null;
        }

        $tz = self::displayTimezone();
        $summary = $module->summary ?: Str::limit(strip_tags((string) $module->description), 140);

        return [
            'id' => $entitlement->id,
            'grantSourceLabel' => $entitlement->grant_source === 'admin_grant' ? 'Administrator Grant' : 'Purchased',
            'grantedDateLabel' => $entitlement->granted_at
                ? $entitlement->granted_at->copy()->timezone($tz)->format('d M Y').' MYT'
                : '—',
            'accessLabel' => 'Lifetime Access',
            'module' => [
                'id' => $module->id,
                'code' => $module->code,
                'title' => $module->title,
                'slug' => $module->slug,
                'categoryName' => $module->category?->name,
                'summary' => $summary !== '' ? $summary : null,
                'materialsCount' => $module->publishedMaterials->count(),
                'videosCount' => $module->publishedVideos->count(),
                'workspaceUrl' => route('portal.modules.show', $module),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function revokedModuleEntitlement(ModuleEntitlement $entitlement): array
    {
        $module = $entitlement->module;

        return [
            'id' => $entitlement->id,
            'revocationReason' => $entitlement->revocation_reason,
            'module' => $module ? [
                'code' => $module->code,
                'title' => $module->title,
                'catalogueUrl' => route('catalogue.modules.show', $module),
            ] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public static function recordingBookingGroup(array $item): array
    {
        /** @var Booking $booking */
        $booking = $item['booking'];
        $session = $item['session'];
        $recordings = $item['recordings'];
        $isEligible = (bool) $item['isEligible'];

        return [
            'bookingId' => $booking->id,
            'bookingReference' => $booking->booking_reference,
            'bookingDetailUrl' => route('portal.bookings.show', $booking),
            'sessionCode' => $session?->session_code ?? 'Session',
            'sessionTitle' => $session?->title ?? 'Physical Training Session',
            'scheduleLabel' => $session?->formattedSchedule() ?? 'Schedule TBA',
            'isEligible' => $isEligible,
            'denialReason' => self::sanitizeReason($item['denialReason'] ?? null),
            'recordings' => $recordings->map(static function (SessionRecording $recording) use ($isEligible): array {
                $isReady = $recording->encoding_status === EncodingStatus::Ready;

                return [
                    'id' => $recording->id,
                    'title' => $recording->title,
                    'description' => $recording->description,
                    'durationLabel' => $recording->formattedDuration(),
                    'encodingStatusValue' => $recording->encoding_status->value,
                    'encodingStatusLabel' => $recording->encoding_status->label(),
                    'isReady' => $isReady,
                    'watchUrl' => $isEligible && $isReady ? $recording->temporaryWatchUrl(30) : null,
                ];
            })->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function notificationItem(DatabaseNotification $notification): array
    {
        $tz = self::displayTimezone();
        $data = is_array($notification->data) ? $notification->data : [];
        $rawDetails = $data['details'] ?? null;
        $actionUrl = is_string($data['action_url'] ?? null) ? $data['action_url'] : null;

        $details = [];
        $detailText = null;

        if (is_array($rawDetails)) {
            foreach ($rawDetails as $label => $value) {
                $details[] = [
                    'label' => (string) $label,
                    'value' => is_scalar($value) ? (string) $value : (string) json_encode($value),
                ];
            }
        } elseif (is_string($rawDetails) && trim($rawDetails) !== '') {
            $detailText = trim($rawDetails);
        }

        return [
            'id' => $notification->id,
            'title' => (string) ($data['title'] ?? 'Account Update'),
            'message' => (string) ($data['message'] ?? ''),
            'reference' => is_string($data['reference'] ?? null) ? $data['reference'] : null,
            'details' => $details,
            'detailText' => $detailText,
            'createdAtLabel' => $notification->created_at
                ? $notification->created_at->copy()->timezone($tz)->format('d M Y, h:i A').' MYT'
                : '—',
            'isUnread' => $notification->read_at === null,
            'actionUrl' => $actionUrl,
            'actionLabel' => (string) ($data['action_label'] ?? 'View Details'),
            'actionIsInertia' => self::isInertiaPath($actionUrl),
            'markReadUrl' => route('portal.notifications.read', $notification),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function profileData(User $user, CustomerProfile $profile): array
    {
        return [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'isEmailVerified' => $user->email_verified_at !== null,
            ],
            'profile' => [
                'customerType' => $profile->customer_type?->value ?? CustomerType::Individual->value,
                'phone' => $profile->phone ?? '',
                'jobTitle' => $profile->job_title ?? '',
                'companyName' => $profile->company_name ?? '',
                'companyRegistrationNo' => $profile->company_registration_no ?? '',
                'billingContactName' => $profile->billing_contact_name ?? '',
                'billingEmail' => $profile->billing_email ?? '',
                'billingPhone' => $profile->billing_phone ?? '',
                'billingAddressLine1' => $profile->billing_address_line_1 ?? '',
                'billingAddressLine2' => $profile->billing_address_line_2 ?? '',
                'billingCity' => $profile->billing_city ?? '',
                'billingState' => $profile->billing_state ?? '',
                'billingPostcode' => $profile->billing_postcode ?? '',
                'billingCountry' => $profile->billing_country ?: 'MY',
                'isComplete' => $profile->isComplete(),
            ],
            'updateUrl' => route('portal.profile.update'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function securityData(User $user): array
    {
        return [
            'user' => [
                'email' => $user->email,
                'isEmailVerified' => $user->email_verified_at !== null,
                'isActive' => $user->isActive(),
            ],
            'updatePasswordUrl' => route('portal.security.password.update'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function checkoutSessionData(
        TrainingSession $session,
        User $user,
        ?CustomerProfile $profile,
        ?Booking $existingHold,
    ): array {
        $tz = self::displayTimezone();
        $venueParts = array_filter([
            $session->venue_name ?: 'TBA',
            $session->venue_city,
        ]);

        return [
            'session' => [
                'title' => $session->title,
                'sessionCode' => $session->session_code,
                'moduleCode' => $session->module?->code,
                'moduleTitle' => $session->module?->title,
                'scheduleLabel' => $session->formattedSchedule(),
                'venueLabel' => implode(', ', $venueParts),
                'remainingSeats' => $session->remainingSeats(),
                'priceLabel' => $session->formattedPrice(),
                'detailUrl' => route('sessions.show', $session),
            ],
            'attendee' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'defaults' => [
                'customerType' => $profile?->customer_type === CustomerType::Company ? 'company_sponsored' : 'individual',
                'phone' => $profile?->phone ?? '',
                'companyName' => $profile?->company_name ?? '',
                'billingAddressLine1' => $profile?->billing_address_line_1 ?? '',
                'billingCity' => $profile?->billing_city ?? '',
                'billingState' => $profile?->billing_state ?? '',
                'billingPostcode' => $profile?->billing_postcode ?? '',
            ],
            'existingHold' => $existingHold ? [
                'bookingReference' => $existingHold->booking_reference,
                'expiresAtLabel' => $existingHold->reservation_expires_at
                    ? $existingHold->reservation_expires_at->copy()->timezone($tz)->format('d M Y, h:i A').' MYT'
                    : null,
                'detailUrl' => route('portal.bookings.show', $existingHold),
            ] : null,
            'submitUrl' => route('checkout.session.store', $session),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function checkoutModuleData(
        Module $module,
        User $user,
        ?CustomerProfile $profile,
    ): array {
        return [
            'module' => [
                'code' => $module->code,
                'title' => $module->title,
                'summary' => $module->summary,
                'categoryName' => $module->category?->name,
                'priceLabel' => $module->formattedIndependentPrice() ?? '—',
                'materialsCount' => $module->publishedMaterials->count(),
                'videosCount' => $module->publishedVideos->count(),
                'detailUrl' => route('catalogue.modules.show', $module),
            ],
            'learner' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'defaults' => [
                'customerType' => $profile?->customer_type?->value ?? CustomerType::Individual->value,
                'phone' => $profile?->phone ?? '',
                'companyName' => $profile?->company_name ?? '',
                'billingAddressLine1' => $profile?->billing_address_line_1 ?? '',
                'billingCity' => $profile?->billing_city ?? '',
                'billingState' => $profile?->billing_state ?? '',
                'billingPostcode' => $profile?->billing_postcode ?? '',
            ],
            'submitUrl' => route('checkout.module.store', $module),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function checkoutReturnData(
        Order $order,
        ?Payment $payment,
        bool $redirectReportedPaid,
        string $refreshUrl,
    ): array {
        return [
            'order' => [
                'orderNumber' => $order->order_number,
                'orderTypeValue' => $order->order_type->value,
                'orderTypeLabel' => $order->order_type->label(),
                'isModulePurchase' => $order->order_type === OrderType::ModulePurchase,
                'statusValue' => $order->status->value,
                'statusLabel' => $order->status->label(),
                'isPaid' => $order->status === OrderStatus::Paid,
                'isPendingPayment' => $order->status === OrderStatus::PendingPayment,
                'totalLabel' => $order->formattedTotal(),
                'detailUrl' => route('portal.orders.show', $order),
            ],
            'payment' => $payment ? [
                'paymentReference' => $payment->payment_reference,
                'statusValue' => $payment->status->value,
                'statusLabel' => $payment->status->label(),
            ] : null,
            'redirectReportedPaid' => $redirectReportedPaid,
            'refreshUrl' => $refreshUrl,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function moduleWorkspaceData(
        Module $module,
        ?ModuleEntitlement $entitlement,
        ?ModuleVideo $activeVideo,
        ?string $streamManifestUrl,
    ): array {
        $tz = self::displayTimezone();

        $objectives = is_array($module->learning_objectives)
            ? array_values(array_filter(array_map(
                static fn (mixed $item): string => trim((string) $item),
                $module->learning_objectives,
            )))
            : [];

        return [
            'module' => [
                'id' => $module->id,
                'code' => $module->code,
                'title' => $module->title,
                'slug' => $module->slug,
                'categoryName' => $module->category?->name,
                'description' => trim(strip_tags((string) $module->description)),
                'learningObjectives' => $objectives,
            ],
            'entitlement' => $entitlement ? [
                'grantedAtLabel' => $entitlement->granted_at
                    ? $entitlement->granted_at->copy()->timezone($tz)->format('d M Y, h:i A').' MYT'
                    : null,
            ] : null,
            'activeVideo' => $activeVideo ? [
                'id' => $activeVideo->id,
                'title' => $activeVideo->title,
                'description' => $activeVideo->description,
                'durationLabel' => $activeVideo->formattedDuration(),
            ] : null,
            'streamManifestUrl' => $streamManifestUrl,
            'materials' => $module->publishedMaterials->map(static fn (ModuleMaterial $material): array => [
                'id' => $material->id,
                'title' => $material->title,
                'description' => $material->description,
                'typeLabel' => $material->material_type->label(),
                'originalFilename' => $material->original_filename,
                'fileSizeLabel' => $material->formattedFileSize(),
                'isDownloadable' => (bool) $material->is_downloadable,
                'downloadUrl' => $material->is_downloadable ? $material->temporaryDownloadUrl(15) : null,
            ])->values()->all(),
            'videos' => $module->publishedVideos->map(static function (ModuleVideo $video) use ($module, $activeVideo): array {
                $video->setRelation('module', $module);
                $isPlayable = $video->isPlayable();

                return [
                    'id' => $video->id,
                    'title' => $video->title,
                    'durationLabel' => $video->formattedDuration(),
                    'encodingStatusValue' => $video->encoding_status->value,
                    'encodingStatusLabel' => $video->encoding_status->label(),
                    'isPlayable' => $isPlayable,
                    'isCurrent' => $activeVideo !== null && $activeVideo->id === $video->id,
                    'watchUrl' => $isPlayable ? $video->temporaryWatchUrl(30) : null,
                ];
            })->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function recordingWatchData(
        SessionRecording $recording,
        ?TrainingSession $session,
        ?string $streamManifestUrl,
    ): array {
        return [
            'recording' => [
                'id' => $recording->id,
                'title' => $recording->title,
                'description' => $recording->description,
                'durationLabel' => $recording->formattedDuration(),
            ],
            'session' => $session ? [
                'sessionCode' => $session->session_code,
                'title' => $session->title,
            ] : null,
            'streamManifestUrl' => $streamManifestUrl,
        ];
    }
}
