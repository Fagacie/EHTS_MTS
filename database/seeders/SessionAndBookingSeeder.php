<?php

namespace Database\Seeders;

use App\Enums\AttendanceReviewStatus;
use App\Enums\AttendanceStatus;
use App\Enums\BookingStatus;
use App\Enums\EncodingStatus;
use App\Enums\OrderItemType;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Enums\RecordingAccessPolicy;
use App\Enums\RecordingStatus;
use App\Enums\SessionStatus;
use App\Models\Attendance;
use App\Models\Booking; 
use App\Models\InstructorProfile;
use App\Models\Module;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\SessionInstructor;
use App\Models\SessionRecording;
use App\Models\SystemSetting;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SessionAndBookingSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->first();
        $instructorSiti = User::query()->where('email', 'instructor@ecohydrotech.com.my')->first();
        $instructorAmirul = User::query()->where('email', 'staff.trainee@ecohydrotech.com.my')->first();
        $trainee = User::query()->where('email', 'trainee@ecohydrotech.com.my')->first();

        if (! $admin || ! $instructorSiti || ! $instructorAmirul) {
            return;
        }

        // 1. Seed Stage 5 System Settings
        SystemSetting::setValue(
            key: 'booking.reservation_hold_minutes',
            value: 15,
            group: 'booking',
            type: 'integer',
            description: 'Duration in minutes that a physical session seat is held during pending payment checkout (D-019).',
            isPublic: true,
            updatedBy: $admin->id,
        );

        SystemSetting::setValue(
            key: 'recordings.default_access_policy',
            value: RecordingAccessPolicy::AttendeesOnly->value,
            group: 'recordings',
            type: 'string',
            description: 'Default post-session recording access policy for physical training sessions (D-017).',
            isPublic: false,
            updatedBy: $admin->id,
        );

        // 2. Seed Public Instructor Profiles (FR-15)
        InstructorProfile::query()->updateOrCreate(
            ['user_id' => $instructorSiti->id],
            [
                'display_name' => 'Ir. Siti Norzahra, P.Eng',
                'professional_title' => 'Principal Hydraulic & Flood Modelling Specialist',
                'qualifications' => 'M.Eng (Hydrology & Water Resources), B.Eng (Civil), BEM P.Eng, MIEM',
                'short_bio' => '16+ years of river basin masterplan and MSMA urban stormwater engineering across Malaysia.',
                'specialisations' => [
                    'MSMA 2nd Edition Urban Drainage',
                    '1D/2D River Floodplain Modelling',
                    'Detention Basin & OSD Hydraulics',
                ],
                'biography' => 'Ir. Siti Norzahra has over 16 years of consulting and technical training experience in Malaysian river basin masterplans, urban stormwater management (MSMA), and hydrodynamic flood hazard mapping.',
                'photo_storage_disk' => 'public',
                'is_public' => true,
                'published_at' => now(),
                'updated_by' => $admin->id,
            ],
        );

        InstructorProfile::query()->updateOrCreate(
            ['user_id' => $instructorAmirul->id],
            [
                'display_name' => 'Dr. Amirul Hakim',
                'professional_title' => 'Senior Ecohydrologist & Water Quality Specialist',
                'qualifications' => 'PhD (Ecohydrology), M.Sc (Environmental Engineering)',
                'short_bio' => 'Specialist in constructed treatment wetlands, bio-retention systems, and tropical catchment hydrology.',
                'specialisations' => [
                    'Constructed Treatment Wetlands',
                    'Bio-Retention & WSUD',
                    'Catchment Water Quality Modelling',
                ],
                'biography' => 'Dr. Amirul Hakim leads nature-based stormwater treatment research and practical engineering workshops on bio-retention systems, constructed wetlands, and tropical catchment hydrology.',
                'photo_storage_disk' => 'public',
                'is_public' => true,
                'published_at' => now(),
                'updated_by' => $admin->id,
            ],
        );

        // 3. Seed Physical Training Sessions (FR-03, D-029)
        $modMsma = Module::query()->where('code', 'EHT-HYD-101')->first();
        $modFlood = Module::query()->where('code', 'EHT-FLD-201')->first();
        $modWetland = Module::query()->where('code', 'EHT-WQ-105')->first();

        if ($modMsma) {
            $session1 = TrainingSession::query()->updateOrCreate(
                ['session_code' => 'SES-2026-001'],
                [
                    'module_id' => $modMsma->id,
                    'title' => 'MSMA 2nd Edition Practical Stormwater Design Workshop — Cohort A',
                    'slug' => 'msma-2nd-edition-practical-stormwater-design-workshop-cohort-a',
                    'description' => 'Full-day hands-on engineering workshop covering IDF derivation, OSD sizing, and detention basin routing for JPS submission compliance.',
                    'starts_at' => now()->addDays(14)->setTime(9, 0),
                    'ends_at' => now()->addDays(14)->setTime(17, 0),
                    'duration_minutes' => 480,
                    'timezone' => 'Asia/Kuala_Lumpur',
                    'venue_name' => 'Ecohydrotech Training Centre, Hall A',
                    'venue_address' => 'Level 5, Menara Ecohydrotech, Persiaran Perbandaran, Seksyen 14',
                    'venue_city' => 'Shah Alam',
                    'venue_state' => 'Selangor',
                    'venue_postcode' => '40000',
                    'venue_map_url' => 'https://maps.google.com/?q=Shah+Alam+Selangor',
                    'capacity' => 25,
                    'reserved_seats_count' => 0,
                    'confirmed_seats_count' => 0,
                    'price' => '890.00',
                    'currency' => 'MYR',
                    'registration_opens_at' => now()->subDays(7),
                    'registration_closes_at' => now()->addDays(12)->setTime(17, 0),
                    'recording_access_policy' => RecordingAccessPolicy::AttendeesOnly->value,
                    'status' => SessionStatus::Published->value,
                    'published_at' => now()->subDays(7),
                    'created_by' => $admin->id,
                    'updated_by' => $admin->id,
                ],
            );

            SessionInstructor::query()->updateOrCreate(
                [
                    'training_session_id' => $session1->id,
                    'instructor_id' => $instructorSiti->id,
                ],
                [
                    'role_label' => 'Lead Instructor',
                    'assigned_by' => $admin->id,
                    'assigned_at' => now()->subDays(7),
                ],
            );

            SessionInstructor::query()->updateOrCreate(
                [
                    'training_session_id' => $session1->id,
                    'instructor_id' => $instructorAmirul->id,
                ],
                [
                    'role_label' => 'Co-Instructor',
                    'assigned_by' => $admin->id,
                    'assigned_at' => now()->subDays(7),
                ],
            );
        }

        if ($modFlood) {
            $session2 = TrainingSession::query()->updateOrCreate(
                ['session_code' => 'SES-2026-002'],
                [
                    'module_id' => $modFlood->id,
                    'title' => '1D/2D Hydrodynamic River Floodplain Modelling Masterclass',
                    'slug' => '1d-2d-hydrodynamic-river-floodplain-modelling-masterclass',
                    'description' => 'Intensive physical computer-lab cohort on LiDAR mesh preparation, unsteady hydrograph routing, and flood hazard map generation.',
                    'starts_at' => now()->addDays(21)->setTime(9, 0),
                    'ends_at' => now()->addDays(21)->setTime(17, 30),
                    'duration_minutes' => 510,
                    'timezone' => 'Asia/Kuala_Lumpur',
                    'venue_name' => 'Cyberjaya Hydrological Simulation Lab',
                    'venue_address' => 'Block C, TechPark Cyberjaya, Persiaran Multimedia',
                    'venue_city' => 'Cyberjaya',
                    'venue_state' => 'Selangor',
                    'venue_postcode' => '63000',
                    'capacity' => 20,
                    'reserved_seats_count' => 0,
                    'confirmed_seats_count' => 0,
                    'price' => '1250.00',
                    'currency' => 'MYR',
                    'registration_opens_at' => now()->subDays(5),
                    'registration_closes_at' => now()->addDays(19)->setTime(17, 0),
                    'recording_access_policy' => RecordingAccessPolicy::AttendeesOnly->value,
                    'status' => SessionStatus::Published->value,
                    'published_at' => now()->subDays(5),
                    'created_by' => $admin->id,
                    'updated_by' => $admin->id,
                ],
            );

            SessionInstructor::query()->updateOrCreate(
                [
                    'training_session_id' => $session2->id,
                    'instructor_id' => $instructorSiti->id,
                ],
                [
                    'role_label' => 'Lead Instructor',
                    'assigned_by' => $admin->id,
                    'assigned_at' => now()->subDays(5),
                ],
            );
        }

        if ($modWetland && $trainee) {
            $session3 = TrainingSession::query()->updateOrCreate(
                ['session_code' => 'SES-2026-003'],
                [
                    'module_id' => $modWetland->id,
                    'title' => 'Constructed Wetland & Bio-Retention Field Engineering Clinic',
                    'slug' => 'constructed-wetland-bio-retention-field-engineering-clinic',
                    'description' => 'Physical clinic covering filter media specification, macrophyte selection, and pollutant removal calculations.',
                    'starts_at' => now()->addDays(7)->setTime(9, 0),
                    'ends_at' => now()->addDays(7)->setTime(17, 0),
                    'duration_minutes' => 480,
                    'timezone' => 'Asia/Kuala_Lumpur',
                    'venue_name' => 'Putrajaya Wetland Engineering Resource Hall',
                    'venue_address' => 'Precinct 13, Taman Wetland Putrajaya',
                    'venue_city' => 'Putrajaya',
                    'venue_state' => 'Wilayah Persekutuan Putrajaya',
                    'venue_postcode' => '62000',
                    'capacity' => 15,
                    'reserved_seats_count' => 0,
                    'confirmed_seats_count' => 1,
                    'price' => '780.00',
                    'currency' => 'MYR',
                    'registration_opens_at' => now()->subDays(10),
                    'registration_closes_at' => now()->addDays(5)->setTime(17, 0),
                    'recording_access_policy' => RecordingAccessPolicy::AttendeesOnly->value,
                    'status' => SessionStatus::Published->value,
                    'published_at' => now()->subDays(10),
                    'created_by' => $admin->id,
                    'updated_by' => $admin->id,
                ],
            );

            SessionInstructor::query()->updateOrCreate(
                [
                    'training_session_id' => $session3->id,
                    'instructor_id' => $instructorAmirul->id,
                ],
                [
                    'role_label' => 'Lead Instructor',
                    'assigned_by' => $admin->id,
                    'assigned_at' => now()->subDays(10),
                ],
            );

            SessionInstructor::query()->updateOrCreate(
                [
                    'training_session_id' => $session3->id,
                    'instructor_id' => $instructorSiti->id,
                ],
                [
                    'role_label' => 'Co-Instructor',
                    'assigned_by' => $admin->id,
                    'assigned_at' => now()->subDays(10),
                ],
            );

            // Seed a confirmed booking for trainee@ecohydrotech.com.my on SES-2026-003
            $order = Order::query()->updateOrCreate(
                ['order_number' => 'ORD-2026-DEMO01'],
                [
                    'user_id' => $trainee->id,
                    'order_type' => OrderType::SessionBooking->value,
                    'status' => OrderStatus::Paid->value,
                    'subtotal_amount' => '780.00',
                    'total_amount' => '780.00',
                    'currency' => 'MYR',
                    'customer_type' => 'company',
                    'customer_name' => $trainee->name,
                    'customer_email' => $trainee->email,
                    'customer_phone' => '+6019-245 7710',
                    'company_name' => 'LembagaPerunding Hidro Sdn. Bhd.',
                    'company_registration_no' => '201801024899 (1286919-W)',
                    'billing_contact_name' => 'Puan Farah Nadia (Accounts)',
                    'billing_email' => 'accounts@perundinghidro.com.my',
                    'billing_phone' => '+603-8925 4400',
                    'billing_address_line_1' => 'No. 14, Jalan Medan Pusat 2D, Seksyen 9',
                    'billing_city' => 'Bandar Baru Bangi',
                    'billing_state' => 'Selangor',
                    'billing_postcode' => '43650',
                    'billing_country' => 'MY',
                    'paid_at' => now()->subDays(2),
                ],
            );

            $orderItem = OrderItem::query()->updateOrCreate(
                [
                    'order_id' => $order->id,
                    'training_session_id' => $session3->id,
                ],
                [
                    'order_type' => OrderType::SessionBooking->value,
                    'item_type' => OrderItemType::TrainingSession->value,
                    'module_id' => null,
                    'item_code_snapshot' => $session3->session_code,
                    'item_title_snapshot' => $session3->title,
                    'unit_price' => '780.00',
                    'quantity' => 1,
                    'line_total' => '780.00',
                    'currency' => 'MYR',
                ],
            );

            if (Schema::hasTable('payments')) {
                Payment::query()->updateOrCreate(
                    ['payment_reference' => 'PAY-2026-DEMO01'],
                    [
                        'order_id' => $order->id,
                        'user_id' => $trainee->id,
                        'gateway' => 'billplz',
                        'payment_method' => 'fpx',
                        'amount' => '780.00',
                        'refunded_amount' => '0.00',
                        'currency' => 'MYR',
                        'status' => PaymentStatus::Successful->value,
                        'gateway_bill_id' => 'bpz_demo01',
                        'gateway_transaction_id' => 'fpx_demo01',
                        'gateway_reference_1' => 'PAY-2026-DEMO01',
                        'gateway_state' => 'paid',
                        'paid_at' => now()->subDays(2),
                    ],
                );
            }

            $booking = Booking::query()->updateOrCreate(
                ['booking_reference' => 'BKG-2026-DEMO01'],
                [
                    'user_id' => $trainee->id,
                    'training_session_id' => $session3->id,
                    'order_id' => $order->id,
                    'order_item_id' => $orderItem->id,
                    'status' => BookingStatus::Confirmed->value,
                    'reserved_at' => now()->subDays(2),
                    'reservation_expires_at' => now()->subDays(2)->addMinutes(15),
                    'confirmed_at' => now()->subDays(2),
                ],
            );

            Attendance::query()->updateOrCreate(
                [
                    'training_session_id' => $session3->id,
                    'booking_id' => $booking->id,
                ],
                [
                    'user_id' => $trainee->id,
                    'attendance_status' => AttendanceStatus::Pending->value,
                    'review_status' => AttendanceReviewStatus::Draft->value,
                ],
            );

            if (Schema::hasTable('session_recordings')) {
                $recHlsDir = 'hls/sessions/ses-2026-003/rec-1';
                $recHlsManifest = "{$recHlsDir}/stream.m3u8";
                Storage::disk('recordings')->put($recHlsManifest, "#EXTM3U\n#EXT-X-VERSION:3\n#EXT-X-TARGETDURATION:6\n#EXT-X-MEDIA-SEQUENCE:0\n#EXTINF:6.000,\nseg_0000.ts\n#EXT-X-ENDLIST\n");
                Storage::disk('recordings')->put("{$recHlsDir}/seg_0000.ts", str_repeat('TS', 512));

                SessionRecording::query()->updateOrCreate(
                    [
                        'provider' => 'local_hls',
                        'provider_library_id' => 'local',
                        'provider_video_id' => 'vid-ses-2026-003-rec1',
                    ],
                    [
                        'training_session_id' => $session3->id,
                        'title' => 'SES-2026-003 Wetland Hydraulic Sizing & Media Specification Recording',
                        'description' => 'Full session recording for verified present attendees.',
                        'storage_disk' => 'recordings',
                        'hls_manifest_key' => $recHlsManifest,
                        'mime_type' => 'application/vnd.apple.mpegurl',
                        'duration_seconds' => 3600,
                        'encoding_status' => EncodingStatus::Ready->value,
                        'status' => RecordingStatus::Published->value,
                        'sort_order' => 1,
                        'submitted_by' => $instructorAmirul->id,
                        'submitted_at' => now()->subDay(),
                        'reviewed_by' => $admin->id,
                        'reviewed_at' => now()->subHours(12),
                        'published_at' => now()->subHours(12),
                    ],
                );
            }
        }
    }
}
