<?php

namespace Database\Seeders;

use App\Enums\TagType;
use Illuminate\Database\Seeder;
use Spatie\Tags\Tag;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $blueprint_tags = [
            'factory_type' => [
                'mining',
                'refining',
                'manufacturing',
                'assembly',
                'energy',
                'power-grid',
                'water-supply',
                'farming',
                'defense-base',
                'combat-outpost',
                'logistics',
                'storage',
                'research',
                'automation',
                'support-station',
            ],

            'functional_purpose' => [
                'ore-processing',
                'metal-production',
                'cryston-production',
                'alloy-refining',
                'component-fabrication',
                'equipment-crafting',
                'bio-materials',
                'energy-generation',
                'chemical-refinement',
                'building-materials',
                'consumables',
                'ammunition',
                'supply-chain',
            ],

            'design_philosophy' => [
                'compact',
                'aesthetic',
                'balanced',
                'efficient',
                'throughput-optimized',
                'modular',
                'scalable',
                'starter-blueprint',
                'late-game',
                'experimental',
                'resource-saver',
                'overproducing',
                'underpowered',
                'low-maintenance',
                'AFK-friendly',
            ],

            'power_and_infrastructure' => [
                'low-power',
                'high-power',
                'distributed-power',
                'centralized-grid',
                'renewable',
                'power-overflow',
                'power-balanced',
                'belt-optimized',
                'beltless',
                'drone-logistics',
                'rail-logistics',
                'zipline-integrated',
                'networked-outposts',
            ],

            'region_or_terrain' => [
                'mountain-region',
                'desert',
                'swamp',
                'frozen-zone',
                'urban',
                'underground',
                'remote-outpost',
                'coastal',
                'wasteland',
                'volcanic',
            ],

            'progression_level' => [
                'early-game',
                'mid-game',
                'late-game',
                'post-campaign',
                'beta-only',
                'limited-tech',
                'advanced-tech',
                'research-unlocked',
            ],

            'blueprint_metadata' => [
                'verified',
                'community-favorite',
                'updated-for-beta2',
                'deprecated',
                'work-in-progress',
                'collaboration',
                'minimal-resources',
                'max-efficiency',
                'blueprint-pack',
                'themed-build',
            ],

            'creator_style' => [
                'industrial',
                'organic',
                'futuristic',
                'minimalist',
                'functional',
                'symmetrical',
                'asymmetrical',
                'decorative',
                'realistic',
            ],

            'special_tags' => [
                'featured',
                'new',
                'updated',
                'meta',
                'challenge-build',
                'tutorial',
                'showcase',
                'blueprint-set',
                'compatibility-checked',
                'multiplayer-compatible',
            ],

        ];

        foreach ($blueprint_tags as $type => $tags) {
            foreach ($tags as $tag) {
                Tag::create([
                    'name' => $tag,
                    'slug' => str($tag)->slug(),
                    'type' => TagType::BLUEPRINT_TAGS,
                ]);
            }
        }
    }
}
