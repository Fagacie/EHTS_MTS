<?php

namespace Database\Seeders;

use App\Enums\EncodingStatus;
use App\Enums\MaterialStatus;
use App\Enums\MaterialType;
use App\Enums\ModuleStatus;
use App\Models\Category;
use App\Models\Module;
use App\Models\ModuleMaterial;
use App\Models\ModuleVideo;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class CatalogueSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->first()
            ?? User::query()->first();

        if ($admin === null) {
            return;
        }

        $categories = [
            [
                'name' => 'Urban Stormwater & MSMA Design',
                'slug' => 'urban-stormwater-msma-design',
                'description' => 'Practical hydrological engineering and urban stormwater management aligned with Malaysia MSMA 2nd Edition guidelines.',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'River Basin & Flood Modelling',
                'slug' => 'river-basin-flood-modelling',
                'description' => '1D/2D hydrodynamic river modelling, flood hazard mapping, and catchment runoff simulation.',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Water Quality & Ecohydrology',
                'slug' => 'water-quality-ecohydrology',
                'description' => 'Constructed wetlands, bio-retention systems, river water quality indices, and nature-based solutions.',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Archived Legacy Programmes',
                'slug' => 'archived-legacy-programmes',
                'description' => 'Historical training programmes retained for reference.',
                'sort_order' => 4,
                'is_active' => false,
            ],
        ];

        $categoryBySlug = [];
        foreach ($categories as $catData) {
            $categoryBySlug[$catData['slug']] = Category::query()->updateOrCreate(
                ['slug' => $catData['slug']],
                $catData,
            );
        }

        $modules = [
            [
                'category_slug' => 'urban-stormwater-msma-design',
                'code' => 'EHT-HYD-101',
                'title' => 'Applied Urban Stormwater Management (MSMA 2nd Edition)',
                'slug' => 'applied-urban-stormwater-management-msma',
                'summary' => 'Comprehensive design of detention basins, OSD systems, swales, and stormwater routing using Malaysian IDF curves.',
                'description' => "This module equips civil and hydrological engineers with complete calculation workflows for Urban Stormwater Management Manual for Malaysia (MSMA 2nd Edition).\n\nTopics include design storm derivation, time of concentration, Rational Method and hydrograph routing, On-Site Detention (OSD) sizing, dry/wet detention pond design, and authority submission checklists.",
                'learning_objectives' => [
                    'Derive local Intensity-Duration-Frequency (IDF) curves for Peninsular Malaysia, Sabah, and Sarawak',
                    'Size On-Site Detention (OSD) and community detention basins to meet pre- vs post-development peak flow criteria',
                    'Design engineered vegetated swales and infiltration trenches',
                    'Prepare compliant hydrological calculation reports for JPS submission',
                ],
                'is_available_independently' => true,
                'independent_price' => '680.00',
                'status' => ModuleStatus::Published->value,
                'published_at' => now()->subDays(10),
            ],
            [
                'category_slug' => 'river-basin-flood-modelling',
                'code' => 'EHT-FLD-201',
                'title' => '1D/2D Hydrodynamic Flood Plain Modelling & Hazard Mapping',
                'slug' => '1d-2d-hydrodynamic-flood-plain-modelling',
                'summary' => 'Hands-on river cross-section setup, DEM mesh conditioning, boundary hydrographs, calibration, and flood risk mapping.',
                'description' => "Master coupled 1D channel and 2D floodplain hydrodynamic modelling for tropical river basins.\n\nParticipants work through LiDAR terrain preparation, bridge/culvert hydraulic structures, tidal downstream boundaries, Manning's roughness calibration against gauge telemetry, and flood depth-velocity hazard classification.",
                'learning_objectives' => [
                    'Build coupled 1D/2D river and floodplain meshes from LiDAR DEMs',
                    'Configure unsteady inflow hydrographs and tidal boundary conditions',
                    'Calibrate model water levels against historical flood event telemetry',
                    'Export flood extent, depth, and velocity hazard maps for engineering mitigation design',
                ],
                'is_available_independently' => true,
                'independent_price' => '950.00',
                'status' => ModuleStatus::Published->value,
                'published_at' => now()->subDays(5),
            ],
            [
                'category_slug' => 'water-quality-ecohydrology',
                'code' => 'EHT-WQ-105',
                'title' => 'Ecohydrological Bio-Retention & Constructed Wetland Engineering',
                'slug' => 'ecohydrological-bio-retention-constructed-wetland-engineering',
                'summary' => 'Field-focused design of urban bio-retention cells, gross pollutant traps, and constructed treatment wetlands.',
                'description' => "Focused on nature-based stormwater treatment systems, filter media hydraulic conductivity, pollutant removal kinetics (TSS, TN, TP), and tropical macrophyte selection.",
                'learning_objectives' => [
                    'Specify bio-retention filter media layers and underdrain hydraulics',
                    'Estimate TSS, total nitrogen, and total phosphorus load reduction',
                    'Establish inspection and maintenance protocols for municipal handover',
                ],
                'is_available_independently' => false,
                'independent_price' => null,
                'status' => ModuleStatus::Published->value,
                'published_at' => now()->subDays(2),
            ],
            [
                'category_slug' => 'urban-stormwater-msma-design',
                'code' => 'EHT-HYD-399',
                'title' => 'Advanced Dam Break & Emergency Action Plan Simulation (Draft)',
                'slug' => 'advanced-dam-break-emergency-action-plan-simulation',
                'summary' => 'Internal draft syllabus under technical review.',
                'description' => 'Draft module syllabus not yet visible in the public training catalogue.',
                'learning_objectives' => [
                    'Evaluate dam breach parameters and downstream flood wave propagation',
                ],
                'is_available_independently' => true,
                'independent_price' => '1200.00',
                'status' => ModuleStatus::Draft->value,
                'published_at' => null,
            ],
        ];

        foreach ($modules as $modData) {
            $category = $categoryBySlug[$modData['category_slug']];
            unset($modData['category_slug']);

            $module = Module::query()->updateOrCreate(
                ['code' => $modData['code']],
                array_merge($modData, [
                    'category_id' => $category->id,
                    'currency' => config('etms.currency'),
                    'created_by' => $admin->id,
                    'updated_by' => $admin->id,
                ]),
            );

            if ($module->code === 'EHT-HYD-101') {
                $storageKey1 = 'modules/eht-hyd-101-msma-workbook.pdf';
                $content1 = '%PDF-1.4 Ecohydrotech MSMA 2nd Edition Engineering Workbook (Private Server Storage)';
                Storage::disk('materials')->put($storageKey1, $content1);

                ModuleMaterial::query()->updateOrCreate(
                    [
                        'module_id' => $module->id,
                        'storage_key' => $storageKey1,
                    ],
                    [
                        'title' => 'MSMA 2nd Edition Design Calculation Workbook',
                        'description' => 'Complete worked examples for OSD, detention basin routing, and swale sizing.',
                        'material_type' => MaterialType::Pdf->value,
                        'storage_disk' => 'materials',
                        'storage_bucket' => 'etms-server-storage',
                        'original_filename' => 'EHT-HYD-101-MSMA-Workbook.pdf',
                        'mime_type' => 'application/pdf',
                        'file_size_bytes' => strlen($content1),
                        'checksum_sha256' => hash('sha256', $content1),
                        'is_downloadable' => true,
                        'sort_order' => 1,
                        'status' => MaterialStatus::Published->value,
                        'published_at' => now(),
                        'uploaded_by' => $admin->id,
                    ],
                );

                if (Schema::hasTable('module_videos')) {
                    $hlsDir = 'hls/modules/eht-hyd-101/part-1';
                    $hlsManifest = "{$hlsDir}/stream.m3u8";
                    Storage::disk('videos')->put($hlsManifest, "#EXTM3U\n#EXT-X-VERSION:3\n#EXT-X-TARGETDURATION:6\n#EXT-X-MEDIA-SEQUENCE:0\n#EXTINF:6.000,\nseg_0000.ts\n#EXT-X-ENDLIST\n");
                    Storage::disk('videos')->put("{$hlsDir}/seg_0000.ts", str_repeat('TS', 512));

                    ModuleVideo::query()->updateOrCreate(
                        [
                            'provider' => 'local_hls',
                            'provider_library_id' => 'local',
                            'provider_video_id' => 'vid-eht-hyd-101-p1',
                        ],
                        [
                            'module_id' => $module->id,
                            'title' => 'Part 1: MSMA 2nd Edition IDF & Rational Method Walkthrough',
                            'description' => 'Step-by-step worked example of design storm derivation and OSD sizing.',
                            'storage_disk' => 'videos',
                            'hls_manifest_key' => $hlsManifest,
                            'mime_type' => 'application/vnd.apple.mpegurl',
                            'duration_seconds' => 2700,
                            'encoding_status' => EncodingStatus::Ready->value,
                            'sort_order' => 1,
                            'status' => MaterialStatus::Published->value,
                            'published_at' => now(),
                            'uploaded_by' => $admin->id,
                        ],
                    );
                }
            }
        }
    }
}
