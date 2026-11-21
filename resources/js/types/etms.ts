import type { PageProps } from '@inertiajs/core';

export interface AppMetadata {
    brandName: string;
    shortName: string;
    displayTimezone: string;
    currency: string;
    year: number;
    logoUrl: string;
}

export interface PortalLink {
    slug: 'customer' | 'instructor' | 'administrator' | string;
    label: string;
    description: string;
    url: string;
}

export interface AuthUser {
    id: number;
    name: string;
    email: string;
    photoUrl: string | null;
    isCustomer: boolean;
    isInstructor: boolean;
    isAdministrator: boolean;
    defaultDashboardUrl: string;
    availablePortals: PortalLink[];
    unreadNotificationsCount?: number;
}

export interface FlashMessages {
    status: string | null;
    error: string | null;
}

export interface SharedRoutes {
    home: string;
    catalogue: string;
    catalogueIndependent: string;
    sessions: string;
    instructors: string;
    login: string;
    register: string;
    logout: string;
    portalDashboard: string;
    portalSearch: string;
    portalModules: string;
    portalBookings: string;
    portalRecordings: string;
    portalCertificates: string;
    portalOrders: string;
    portalNotifications: string;
    portalProfile: string;
    portalSecurity: string;
    instructorDashboard: string;
    instructorSessions: string;
    instructorProfile: string;
    instructorNotifications: string;
    adminDashboard: string;
}

export interface PortalShellData {
    recentNotifications: PortalNotificationItem[];
    markAllReadUrl: string;
}

export interface PortalSearchResultItem {
    id: number;
    title: string;
    subtitle: string;
    meta: string;
    statusValue: string | null;
    statusLabel: string | null;
    url: string;
}

export interface PortalSearchResponse {
    query: string;
    totalCount: number;
    results: {
        bookings: PortalSearchResultItem[];
        orders: PortalSearchResultItem[];
        certificates: PortalSearchResultItem[];
        modules: PortalSearchResultItem[];
        catalogue: PortalSearchResultItem[];
    };
}

export interface EtmsSharedProps extends PageProps {
    app: AppMetadata;
    auth: {
        user: AuthUser | null;
    };
    flash: FlashMessages;
    routes: SharedRoutes;
    portal?: PortalShellData | null;
    instructorShell?: PortalShellData | null;
    currentPath: string;
}

export type SharedPageProps = EtmsSharedProps;

export interface CategorySummary {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    publishedModulesCount: number;
    catalogueUrl: string;
}

export interface UpcomingSessionItem {
    id: number;
    sessionCode: string;
    title: string;
    slug: string;
    startDayLabel: string;
    startYearTimeLabel: string;
    endsDateLabel: string | null;
    dateRangeLabel: string;
    timeLabel: string;
    isThisMonth: boolean;
    durationLabel: string;
    venueName: string;
    venueLocation: string | null;
    capacity: number;
    remainingSeats: number;
    isOpenForBooking: boolean;
    statusLabel: string;
    statusValue: string;
    priceLabel: string;
    moduleCode: string | null;
    moduleTitle?: string | null;
    moduleSlug?: string | null;
    moduleCategory: string | null;
    instructorNames: string | null;
    detailUrl: string;
    bookUrl: string | null;
}

export interface FeaturedModuleItem {
    id: number;
    code: string;
    title: string;
    slug: string;
    summary: string | null;
    categoryName: string;
    categorySlug: string;
    coverImageUrl: string | null;
    isAvailableIndependently: boolean;
    independentPriceLabel: string | null;
    publishedMaterialsCount: number;
    publishedVideosCount: number;
    upcomingSessionsCount: number;
    detailUrl: string;
}

export interface PublicInstructorItem {
    id: number;
    displayName: string;
    professionalTitle: string | null;
    qualifications: string | null;
    shortBio: string | null;
    specialisations: string[];
    photoUrl: string | null;
    profileUrl: string;
    upcomingSessionsCount?: number;
}

export interface HomepageStats {
    publishedModulesCount: number;
    independentModulesCount: number;
    upcomingSessionsCount: number;
    activeCategoriesCount: number;
    publicInstructorsCount: number;
    reservationHoldMinutes: number;
}

export interface HomepageAssets {
    heroPhotoUrl: string;
    heroPhotoWebpUrl: string | null;
    classroomPhotoUrl: string;
    classroomPhotoWebpUrl: string | null;
    logoSvgUrl: string;
}

export interface HomepageProps extends EtmsSharedProps {
    categories: CategorySummary[];
    upcomingSessions: UpcomingSessionItem[];
    featuredModules: FeaturedModuleItem[];
    instructors: PublicInstructorItem[];
    stats: HomepageStats;
    assets: HomepageAssets;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginationMeta {
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
    from: number | null;
    to: number | null;
    prevPageUrl: string | null;
    nextPageUrl: string | null;
    links: PaginationLink[];
}

export interface CatalogueFilters {
    q: string;
    category: string;
    availability: 'all' | 'independent' | 'session_only' | string;
    sort: 'newest' | 'title_asc' | 'price_asc' | string;
}

export interface CatalogueIndexProps extends EtmsSharedProps {
    modules: FeaturedModuleItem[];
    pagination: PaginationMeta;
    categories: CategorySummary[];
    filters: CatalogueFilters;
    totalPublishedCount: number;
}

export interface ModuleMaterialPreview {
    id: number;
    title: string;
    description: string | null;
    typeLabel: string;
    fileSizeLabel: string;
    canDownload: boolean;
    downloadUrl: string | null;
}

export interface ModuleVideoPreview {
    id: number;
    title: string;
    description: string | null;
    durationLabel: string;
    isReady: boolean;
}

export interface ModuleDetailData {
    id: number;
    code: string;
    title: string;
    slug: string;
    summary: string | null;
    description: string;
    learningObjectives: string[];
    categoryName: string;
    categorySlug: string;
    categoryUrl: string;
    coverImageUrl: string | null;
    isAvailableIndependently: boolean;
    independentPriceLabel: string | null;
    checkoutUrl: string | null;
    portalModuleUrl: string;
    hasActiveEntitlement: boolean;
    canPurchaseAsCustomer: boolean;
}

export interface CatalogueShowProps extends EtmsSharedProps {
    module: ModuleDetailData;
    materials: ModuleMaterialPreview[];
    videos: ModuleVideoPreview[];
    upcomingSessions: UpcomingSessionItem[];
}

export interface SessionsFilters {
    q: string;
    category: string;
    availability: 'all' | 'this_month' | 'open_seats' | string;
}

export interface SessionsIndexProps extends EtmsSharedProps {
    sessions: UpcomingSessionItem[];
    pagination: PaginationMeta;
    categories: CategorySummary[];
    filters: SessionsFilters;
    totalUpcomingCount: number;
}

export interface SessionDetailInstructor {
    id: number;
    displayName: string;
    roleLabel: string;
    professionalTitle: string | null;
    qualifications: string | null;
    photoUrl: string | null;
    profileUrl: string | null;
}

export interface SessionDetailModule {
    id: number;
    code: string;
    title: string;
    slug: string;
    summary: string | null;
    description: string;
    learningObjectives: string[];
    categoryName: string;
    categorySlug: string;
    categoryUrl: string;
    detailUrl: string;
    isAvailableIndependently: boolean;
    independentPriceLabel: string | null;
}

export interface SessionDetailData {
    id: number;
    sessionCode: string;
    title: string;
    slug: string;
    description: string | null;
    dateRangeLabel: string;
    timeLabel: string;
    scheduleLabel: string;
    durationLabel: string;
    registrationClosesLabel: string | null;
    venueName: string;
    venueAddressLine: string | null;
    venueLocation: string | null;
    venueMapUrl: string | null;
    capacity: number;
    remainingSeats: number;
    seatsOpenLabel: string;
    isOpenForBooking: boolean;
    isFull: boolean;
    statusLabel: string;
    statusValue: string;
    priceLabel: string;
    recordingPolicyLabel: string;
    bookUrl: string | null;
    module: SessionDetailModule;
    instructors: SessionDetailInstructor[];
}

export interface ExistingBookingSummary {
    bookingReference: string;
    statusLabel: string;
    statusValue: string;
    detailUrl: string;
}

export interface SessionShowProps extends EtmsSharedProps {
    session: SessionDetailData;
    existingBooking: ExistingBookingSummary | null;
    canBookAsCustomer: boolean;
}

export interface InstructorsIndexProps extends EtmsSharedProps {
    instructors: PublicInstructorItem[];
}

export interface InstructorDetailData {
    id: number;
    displayName: string;
    professionalTitle: string | null;
    qualifications: string | null;
    shortBio: string | null;
    biography: string | null;
    specialisations: string[];
    photoUrl: string | null;
}

export interface InstructorShowProps extends EtmsSharedProps {
    instructor: InstructorDetailData;
    upcomingSessions: UpcomingSessionItem[];
}

export interface PortalDashboardStats {
    activeModulesCount: number;
    upcomingBookingsCount: number;
    completedSessionsCount: number;
    certificatesCount: number;
    pendingPaymentsCount: number;
    daysUntilNextSession: number | null;
    nextSessionTitle: string | null;
    summaryLine: string;
}

export interface MonthlyActivityPoint {
    monthKey: string;
    monthLabel: string;
    fullMonthLabel: string;
    bookingsCount: number;
    ordersCount: number;
    paidAmountMyr: number;
    trainingHours: number;
}

export interface PortfolioMixItem {
    key: 'modules' | 'upcoming' | 'completed' | 'certificates' | string;
    label: string;
    count: number;
}

export interface PortalActiveHoldBooking {
    bookingReference: string;
    sessionTitle: string;
    expiresAtLabel: string | null;
    detailUrl: string;
}

export interface PortalUpcomingBooking {
    id: number;
    bookingReference: string;
    sessionTitle: string;
    moduleTitle: string | null;
    dayNumber: string;
    monthShort: string;
    dateRangeLabel: string;
    timeRangeLabel: string;
    venueName: string;
    venueCity: string | null;
    venueFull: string;
    statusValue: string;
    statusLabel: string;
    detailUrl: string;
}

export interface PortalActivityItem {
    id: string;
    type: 'booking' | 'order';
    kindLabel: string;
    reference: string;
    title: string;
    detail: string;
    amountLabel: string;
    dateLabel: string;
    shortDateLabel: string;
    statusValue: string;
    statusLabel: string;
    actionUrl: string;
}

export interface PortalCertificateItem {
    id: number;
    certificateNumber: string;
    title: string;
    recordTypeLabel: string;
    issuedDateLabel: string;
    viewUrl: string;
    downloadUrl: string;
}

export interface PortalMeta {
    userName: string;
    userInitials: string;
    unreadNotificationsCount: number;
    continueLearningUrl: string | null;
    availablePortals: PortalLink[];
}

export interface PortalCalendarEvent {
    id: number;
    bookingReference: string;
    sessionTitle: string;
    startDate: string;
    endDate: string;
    dateLabel: string;
    timeLabel: string;
    venueName: string;
    statusValue: string;
    statusLabel: string;
    detailUrl: string;
}

export interface PortalDashboardProps extends EtmsSharedProps {
    stats: PortalDashboardStats;
    monthlyActivity: MonthlyActivityPoint[];
    portfolioMix: PortfolioMixItem[];
    activeHoldBooking: PortalActiveHoldBooking | null;
    upcomingBookings: PortalUpcomingBooking[];
    recentActivity: PortalActivityItem[];
    calendarEvents: PortalCalendarEvent[];
    calendarInitialDate: string;
    certificates: PortalCertificateItem[];
    portalMeta: PortalMeta;
}

export interface SelectOption {
    value: string;
    label: string;
}

export interface PortalBookingListItem {
    id: number;
    bookingReference: string;
    orderNumber: string | null;
    sessionTitle: string;
    moduleCode: string | null;
    sessionCode: string | null;
    scheduleLabel: string;
    venueLabel: string;
    amountLabel: string;
    statusValue: string;
    statusLabel: string;
    isHoldActive: boolean;
    holdExpiresFullLabel: string | null;
    holdExpiresTimeLabel: string | null;
    detailUrl: string;
}

export interface PortalBookingsIndexProps extends EtmsSharedProps {
    bookings: PortalBookingListItem[];
    pagination: PaginationMeta;
    statusFilter: string;
    statuses: SelectOption[];
}

export interface PortalBookingDetailInstructor {
    name: string;
    title: string | null;
}

export interface PortalBookingDetailSession {
    title: string;
    sessionCode: string | null;
    moduleCode: string | null;
    scheduleLabel: string;
    durationLabel: string;
    venueName: string;
    venueAddressLine: string | null;
    venueNotes: string | null;
    venueMapUrl: string | null;
    isOpenForBooking: boolean;
    bookAgainUrl: string | null;
    modulePortalUrl: string | null;
    moduleCatalogueUrl: string | null;
    recordingsUrl: string | null;
    instructors: PortalBookingDetailInstructor[];
}

export interface PortalBookingAttendance {
    attendanceStatusValue: string;
    attendanceStatusLabel: string;
    reviewStatusValue: string;
    reviewStatusLabel: string;
    remarks: string | null;
}

export interface PortalBookingLinkedOrder {
    orderNumber: string;
    statusValue: string;
    statusLabel: string;
    registrationTypeLabel: string;
    companyName: string | null;
    totalLabel: string;
    detailUrl: string;
    payUrl: string;
}

export interface PortalBookingDetail {
    id: number;
    bookingReference: string;
    statusValue: string;
    statusLabel: string;
    isHoldActive: boolean;
    isConfirmed: boolean;
    isExpired: boolean;
    isCancelled: boolean;
    isRefunded: boolean;
    holdExpiresLabel: string | null;
    cancellationReason: string | null;
    session: PortalBookingDetailSession;
    attendance: PortalBookingAttendance | null;
    order: PortalBookingLinkedOrder | null;
}

export interface PortalBookingShowProps extends EtmsSharedProps {
    booking: PortalBookingDetail;
}

export interface PortalOrderListItem {
    id: number;
    orderNumber: string;
    createdAtLabel: string;
    itemTitle: string;
    itemCode: string | null;
    orderTypeValue: string;
    orderTypeLabel: string;
    gatewayLabel: string | null;
    totalLabel: string;
    statusValue: string;
    statusLabel: string;
    detailUrl: string;
}

export interface PortalOrdersIndexProps extends EtmsSharedProps {
    orders: PortalOrderListItem[];
    pagination: PaginationMeta;
    statusFilter: string;
    typeFilter: string;
    statuses: SelectOption[];
    orderTypes: SelectOption[];
}

export interface PortalOrderLineItem {
    id: number;
    title: string;
    code: string | null;
    quantity: number;
    lineTotalLabel: string;
}

export interface PortalOrderLinkedBooking {
    id: number;
    bookingReference: string;
    statusValue: string;
    statusLabel: string;
    detailUrl: string;
}

export interface PortalOrderLinkedEntitlement {
    id: number;
    moduleTitle: string;
    statusValue: string;
    statusLabel: string;
    isActive: boolean;
    moduleUrl: string | null;
}

export interface PortalOrderPaymentRecord {
    id: number;
    paymentReference: string;
    statusValue: string;
    statusLabel: string;
    gatewayLabel: string;
    timestampPrefix: string | null;
    timestampLabel: string | null;
    amountLabel: string;
}

export interface PortalOrderRefundRecord {
    id: number;
    refundReference: string;
    statusValue: string;
    statusLabel: string;
    methodLabel: string;
    reason: string | null;
    amountLabel: string;
}

export interface PortalOrderBilling {
    customerName: string;
    customerEmail: string;
    customerPhone: string | null;
    customerTypeLabel: string;
    companyName: string | null;
    billingAddress: string | null;
}

export interface PortalOrderDetail {
    id: number;
    orderNumber: string;
    orderTypeValue: string;
    orderTypeLabel: string;
    statusValue: string;
    statusLabel: string;
    isPendingPayment: boolean;
    canPayOnline: boolean;
    createdAtLabel: string;
    expiresAtLabel: string | null;
    totalLabel: string;
    currency: string;
    payUrl: string;
    items: PortalOrderLineItem[];
    bookings: PortalOrderLinkedBooking[];
    entitlements: PortalOrderLinkedEntitlement[];
    payments: PortalOrderPaymentRecord[];
    refunds: PortalOrderRefundRecord[];
    billing: PortalOrderBilling;
}

export interface PortalOrderShowProps extends EtmsSharedProps {
    order: PortalOrderDetail;
}

export interface PortalCertificateCard {
    id: number;
    certificateNumber: string;
    title: string;
    code: string | null;
    recordTypeLabel: string;
    completionDateLabel: string;
    issuedDateLabel: string;
    isIssued: boolean;
    isRevoked: boolean;
    revocationReason: string | null;
    revokedAtLabel: string | null;
    viewUrl: string;
    downloadUrl: string | null;
}

export interface PortalCertificatesIndexProps extends EtmsSharedProps {
    issuedCertificates: PortalCertificateCard[];
    revokedCertificates: PortalCertificateCard[];
}

export interface PortalCertificateDetail extends PortalCertificateCard {
    issuingOrganisation: string;
    traineeName: string;
    signatoryName: string;
    signatoryTitle: string | null;
    fileSizeLabel: string | null;
}

export interface PortalCertificateShowProps extends EtmsSharedProps {
    certificate: PortalCertificateDetail;
    logoDataUri: string | null;
}

export interface PortalModuleEntitlementItem {
    id: number;
    grantSourceLabel: string;
    grantedDateLabel: string;
    accessLabel: string;
    module: {
        id: number;
        code: string;
        title: string;
        slug: string;
        categoryName: string | null;
        summary: string | null;
        materialsCount: number;
        videosCount: number;
        workspaceUrl: string;
    };
}

export interface PortalRevokedEntitlementItem {
    id: number;
    revocationReason: string | null;
    module: {
        code: string;
        title: string;
        catalogueUrl: string;
    } | null;
}

export interface PortalModulesIndexProps extends EtmsSharedProps {
    activeEntitlements: PortalModuleEntitlementItem[];
    revokedEntitlements: PortalRevokedEntitlementItem[];
    sessionDocuments: {
        id: number;
        sessionTitle: string;
        documentTitle: string;
        grantedDateLabel: string;
        downloadUrl: string | null;
    }[];
}

export interface PortalRecordingItem {
    id: number;
    title: string;
    description: string | null;
    durationLabel: string;
    encodingStatusValue: string;
    encodingStatusLabel: string;
    isReady: boolean;
    watchUrl: string | null;
}

export interface PortalRecordingBookingGroup {
    bookingId: number;
    bookingReference: string;
    bookingDetailUrl: string;
    sessionCode: string;
    sessionTitle: string;
    scheduleLabel: string;
    isEligible: boolean;
    denialReason: string | null;
    recordings: PortalRecordingItem[];
}

export interface PortalRecordingsIndexProps extends EtmsSharedProps {
    bookingItems: PortalRecordingBookingGroup[];
}

export interface PortalNotificationDetailPair {
    label: string;
    value: string;
}

export interface PortalNotificationItem {
    id: string;
    title: string;
    message: string;
    reference: string | null;
    details: PortalNotificationDetailPair[];
    detailText: string | null;
    createdAtLabel: string;
    isUnread: boolean;
    actionUrl: string | null;
    actionLabel: string;
    actionIsInertia: boolean;
    markReadUrl: string;
}

export interface PortalNotificationsIndexProps extends EtmsSharedProps {
    filter: 'all' | 'unread';
    unreadCount: number;
    markAllReadUrl: string;
    notifications: PortalNotificationItem[];
    pagination: PaginationMeta;
}

export interface PortalProfileProps extends EtmsSharedProps {
    user: {
        name: string;
        email: string;
        photoUrl: string | null;
        isEmailVerified: boolean;
    };
    profile: {
        phone: string;
        jobTitle: string;
        billingContactName: string;
        billingEmail: string;
        billingPhone: string;
        billingAddressLine1: string;
        billingAddressLine2: string;
        billingCity: string;
        billingState: string;
        billingPostcode: string;
        billingCountry: string;
        isComplete: boolean;
    };
    updateUrl: string;
}

export interface PortalSecurityProps extends EtmsSharedProps {
    user: {
        email: string;
        isEmailVerified: boolean;
        isActive: boolean;
    };
    updatePasswordUrl: string;
}

export interface CheckoutSessionProps extends EtmsSharedProps {
    session: {
        title: string;
        sessionCode: string | null;
        moduleCode: string | null;
        moduleTitle: string | null;
        scheduleLabel: string;
        venueLabel: string;
        remainingSeats: number;
        priceLabel: string;
        detailUrl: string;
    };
    attendee: {
        name: string;
        email: string;
    };
    defaults: {
        phone: string;
        billingAddressLine1: string;
        billingCity: string;
        billingState: string;
        billingPostcode: string;
    };
    existingHold: {
        bookingReference: string;
        expiresAtLabel: string | null;
        detailUrl: string;
    } | null;
    submitUrl: string;
}

export interface CheckoutModuleProps extends EtmsSharedProps {
    purchaseKind?: 'module' | 'session_document';
    module: {
        code: string;
        title: string;
        summary: string | null;
        categoryName: string | null;
        priceLabel: string;
        materialsCount: number;
        videosCount: number;
        detailUrl: string;
    };
    learner: {
        name: string;
        email: string;
    };
    defaults: {
        phone: string;
        billingAddressLine1: string;
        billingCity: string;
        billingState: string;
        billingPostcode: string;
    };
    submitUrl: string;
}

export interface CheckoutReturnProps extends EtmsSharedProps {
    order: {
        orderNumber: string;
        orderTypeValue: string;
        orderTypeLabel: string;
        isModulePurchase: boolean;
        statusValue: string;
        statusLabel: string;
        isPaid: boolean;
        isPendingPayment: boolean;
        totalLabel: string;
        detailUrl: string;
    };
    payment: {
        paymentReference: string;
        statusValue: string;
        statusLabel: string;
    } | null;
    redirectReportedPaid: boolean;
    refreshUrl: string;
}

export interface PortalWorkspaceMaterialItem {
    id: number;
    title: string;
    description: string | null;
    typeLabel: string;
    originalFilename: string;
    fileSizeLabel: string;
    isDownloadable: boolean;
    downloadUrl: string | null;
}

export interface PortalWorkspaceVideoItem {
    id: number;
    title: string;
    durationLabel: string;
    encodingStatusValue: string;
    encodingStatusLabel: string;
    isPlayable: boolean;
    isCurrent: boolean;
    watchUrl: string | null;
}

export interface PortalModuleWorkspaceProps extends EtmsSharedProps {
    module: {
        id: number;
        code: string;
        title: string;
        slug: string;
        categoryName: string | null;
        description: string;
        learningObjectives: string[];
    };
    entitlement: {
        grantedAtLabel: string | null;
    } | null;
    activeVideo: {
        id: number;
        title: string;
        description: string | null;
        durationLabel: string;
    } | null;
    streamManifestUrl: string | null;
    materials: PortalWorkspaceMaterialItem[];
    videos: PortalWorkspaceVideoItem[];
}

export interface PortalRecordingWatchProps extends EtmsSharedProps {
    recording: {
        id: number;
        title: string;
        description: string | null;
        durationLabel: string;
    };
    session: {
        sessionCode: string | null;
        title: string;
    } | null;
    streamManifestUrl: string | null;
}

export interface AuthLoginProps extends EtmsSharedProps {
    submitUrl: string;
    forgotPasswordUrl: string;
    registerUrl: string;
}

export interface AuthRegisterProps extends EtmsSharedProps {
    submitUrl: string;
    loginUrl: string;
}

export interface AuthForgotPasswordProps extends EtmsSharedProps {
    submitUrl: string;
    loginUrl: string;
}

export interface AuthResetPasswordProps extends EtmsSharedProps {
    token: string;
    email: string;
    submitUrl: string;
}

export interface AuthVerifyEmailProps extends EtmsSharedProps {
    email: string;
    resendUrl: string;
    logoutUrl: string;
}

// ---------------------------------------------------------------------------
// Instructor Portal Types (FM-5)
// ---------------------------------------------------------------------------

export interface InstructorSessionItem {
    id: number;
    sessionCode: string;
    slug: string;
    title: string;
    statusValue: string;
    statusLabel: string;
    moduleCode: string | null;
    moduleTitle: string | null;
    categoryName: string | null;
    scheduleLabel: string;
    durationLabel: string;
    venueName: string;
    venueCity: string;
    venueState: string;
    venueAddress: string;
    roleLabel: string;
    confirmedParticipantsCount: number;
    capacity: number;
    recordingsCount: number;
    recordingAccessPolicy: string;
    recordingAccessPolicyLabel: string;
    completionNotes: string | null;
    completedAtLabel: string | null;
    showUrl: string;
    attendanceUrl: string;
    completionUrl: string;
    recordingsUrl: string;
}

export interface InstructorDashboardProps extends EtmsSharedProps {
    instructor: {
        displayName: string;
        email: string;
        isPublic: boolean;
        publicProfileUrl: string | null;
    };
    kpis: {
        assignedSessionsCount: number;
        pendingAttendanceCount: number;
        submittedRecordingsCount: number;
    };
    assignedSessions: InstructorSessionItem[];
}

export interface InstructorSessionsIndexProps extends EtmsSharedProps {
    sessions: InstructorSessionItem[];
    statusFilter: string;
}

export interface InstructorRosterParticipant {
    bookingId: number;
    bookingReference: string;
    participantName: string;
    organisation: string;
    attendanceStatusValue: string;
    attendanceStatusLabel: string;
    reviewStatusValue: string;
    reviewStatusLabel: string;
    remarks: string | null;
}

export interface InstructorSessionShowProps extends EtmsSharedProps {
    session: InstructorSessionItem;
    roster: InstructorRosterParticipant[];
}

export interface InstructorAttendanceRecord {
    id: number;
    bookingId: number;
    bookingReference: string;
    participantName: string;
    organisation: string;
    attendanceStatus: string;
    attendanceStatusLabel: string;
    remarks: string;
    reviewStatus: string;
    reviewStatusLabel: string;
    recordedAtLabel: string | null;
}

export interface InstructorAttendanceProps extends EtmsSharedProps {
    session: InstructorSessionItem;
    attendances: InstructorAttendanceRecord[];
    allMarked: boolean;
    isLockedVerified: boolean;
    updateAttendanceUrl: string;
    submitAttendanceUrl: string;
}

export interface InstructorCompletionProps extends EtmsSharedProps {
    session: InstructorSessionItem;
    completionNotes: string | null;
    submitUrl: string;
}

export interface InstructorRecordingItem {
    id: number;
    title: string;
    description: string | null;
    providerVideoId: string;
    storageDisk: string;
    originalFilename: string | null;
    durationSeconds: number | null;
    durationLabel: string | null;
    encodingStatus: string;
    encodingStatusLabel: string;
    status: string;
    statusLabel: string;
    submittedAtLabel: string | null;
}

export interface InstructorRecordingsProps extends EtmsSharedProps {
    session: InstructorSessionItem;
    recordings: InstructorRecordingItem[];
    submitUrl: string;
}

export interface InstructorProfileProps extends EtmsSharedProps {
    user: {
        name: string;
        email: string;
        photoUrl: string | null;
    };
    profile: {
        displayName: string;
        professionalTitle: string;
        qualifications: string;
        shortBio: string;
        specialisations: string[];
        specialisationsText: string;
        biography: string;
        isPublic: boolean;
        photoUrl: string | null;
        publicProfileUrl: string | null;
    };
    updateUrl: string;
}

export interface InstructorNotificationsProps extends EtmsSharedProps {
    filter: 'all' | 'unread';
    unreadCount: number;
    markAllReadUrl: string;
    notifications: PortalNotificationItem[];
    pagination: PaginationMeta;
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: EtmsSharedProps;
    }
}
