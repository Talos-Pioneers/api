<?php

namespace Database\Seeders;

use App\Enums\GameVersion;
use App\Enums\Locale;
use App\Enums\Region;
use App\Enums\Status;
use App\Models\Blueprint;
use App\Models\BlueprintCollection;
use App\Models\Facility;
use App\Models\Item;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Tags\Tag;

class TestSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating test users...');
        $users = $this->createUsers();

        $this->command->info('Creating facilities and items...');
        $facilities = $this->createFacilities();
        $items = $this->createItems();

        $this->command->info('Creating blueprints...');
        $blueprints = $this->createBlueprints($users, $facilities, $items);

        $this->command->info('Creating blueprint collections...');
        $this->createCollections($users, $blueprints);

        $this->command->info('Creating comments...');
        $this->createComments($users, $blueprints);

        $this->command->info('Creating reports...');
        $this->createReports($users, $blueprints);

        $this->command->info('Test data created successfully!');
    }

    /**
     * Create test users with different roles.
     */
    private function createUsers(): array
    {
        $admin = User::factory()->admin()->create([
            'username' => 'admin',
            'email' => 'admin@test.com',
            'locale' => Locale::ENGLISH,
        ]);

        $moderator = User::factory()->moderator()->create([
            'username' => 'moderator',
            'email' => 'moderator@test.com',
            'locale' => Locale::ENGLISH,
        ]);

        $regularUsers = User::factory()
            ->regularUser()
            ->count(15)
            ->create();

        return [
            'admin' => $admin,
            'moderator' => $moderator,
            'regular' => $regularUsers,
        ];
    }

    /**
     * Create facilities if they don't exist.
     */
    private function createFacilities(): array
    {
        $facilities = Facility::all();

        if ($facilities->isEmpty()) {
            $facilities = Facility::factory()->count(20)->create();
        }

        return $facilities->toArray();
    }

    /**
     * Create items if they don't exist.
     */
    private function createItems(): array
    {
        $items = Item::all();

        if ($items->isEmpty()) {
            $items = Item::factory()->count(30)->create();
        }

        return $items->toArray();
    }

    /**
     * Create blueprints with relationships.
     */
    private function createBlueprints(array $users, array $facilities, array $items): array
    {
        $blueprints = [];
        $allUsers = array_merge([$users['admin'], $users['moderator']], $users['regular']->all());

        // Create published blueprints
        for ($i = 0; $i < 25; $i++) {
            $creator = fake()->randomElement($allUsers);
            $blueprint = Blueprint::factory()->create([
                'creator_id' => $creator->id,
                'status' => Status::PUBLISHED,
                'region' => fake()->randomElement([null, Region::VALLEY_IV, Region::WULING]),
                'version' => GameVersion::CBT_3,
                'is_anonymous' => fake()->boolean(10), // 10% chance of being anonymous
            ]);

            // Attach facilities
            $selectedFacilities = fake()->randomElements($facilities, fake()->numberBetween(1, 5));
            $facilityData = [];
            foreach ($selectedFacilities as $facility) {
                $facilityData[$facility['id']] = ['quantity' => fake()->numberBetween(1, 10)];
            }
            $blueprint->facilities()->sync($facilityData);

            // Attach item inputs
            $selectedInputs = fake()->randomElements($items, fake()->numberBetween(1, 8));
            $inputData = [];
            foreach ($selectedInputs as $item) {
                $inputData[$item['id']] = ['quantity' => fake()->numberBetween(1, 100)];
            }
            $blueprint->itemInputs()->sync($inputData);

            // Attach item outputs
            $selectedOutputs = fake()->randomElements($items, fake()->numberBetween(1, 5));
            $outputData = [];
            foreach ($selectedOutputs as $item) {
                $outputData[$item['id']] = ['quantity' => fake()->numberBetween(1, 100)];
            }
            $blueprint->itemOutputs()->sync($outputData);

            // Attach random tags
            $tags = Tag::where('type', 'blueprint_tags')->inRandomOrder()->limit(fake()->numberBetween(2, 8))->get();
            $blueprint->syncTags($tags);

            // Add likes from random users
            $likingUsers = fake()->randomElements($allUsers, fake()->numberBetween(0, 12));
            $blueprint->likes()->sync(array_map(fn ($user) => $user->id, $likingUsers));

            $blueprints[] = $blueprint;
        }

        // Create draft blueprints
        for ($i = 0; $i < 8; $i++) {
            $creator = fake()->randomElement($allUsers);
            $blueprint = Blueprint::factory()->create([
                'creator_id' => $creator->id,
                'status' => Status::DRAFT,
                'region' => null,
                'version' => GameVersion::CBT_3,
                'is_anonymous' => false,
            ]);

            // Some drafts might have partial data
            if (fake()->boolean(60)) {
                $selectedFacilities = fake()->randomElements($facilities, fake()->numberBetween(1, 3));
                $facilityData = [];
                foreach ($selectedFacilities as $facility) {
                    $facilityData[$facility['id']] = ['quantity' => fake()->numberBetween(1, 5)];
                }
                $blueprint->facilities()->sync($facilityData);
            }

            $blueprints[] = $blueprint;
        }

        // Create archived blueprints
        for ($i = 0; $i < 5; $i++) {
            $creator = fake()->randomElement($allUsers);
            $blueprint = Blueprint::factory()->create([
                'creator_id' => $creator->id,
                'status' => Status::ARCHIVED,
                'region' => fake()->randomElement([null, Region::VALLEY_IV, Region::WULING]),
                'version' => GameVersion::CBT_3,
                'is_anonymous' => false,
            ]);

            $blueprints[] = $blueprint;
        }

        return $blueprints;
    }

    /**
     * Create blueprint collections.
     */
    private function createCollections(array $users, array $blueprints): void
    {
        $allUsers = array_merge([$users['admin'], $users['moderator']], $users['regular']->all());
        $publishedBlueprints = array_filter($blueprints, fn ($bp) => $bp->status === Status::PUBLISHED);

        // Create collections with blueprints
        for ($i = 0; $i < 10; $i++) {
            $creator = fake()->randomElement($allUsers);
            $collection = BlueprintCollection::factory()->create([
                'creator_id' => $creator->id,
                'status' => fake()->randomElement([Status::DRAFT, Status::PUBLISHED]),
                'is_anonymous' => fake()->boolean(10),
            ]);

            // Attach random blueprints to collection
            $selectedBlueprints = fake()->randomElements($publishedBlueprints, fake()->numberBetween(3, 12));
            $collection->blueprints()->sync(array_map(fn ($bp) => $bp->id, $selectedBlueprints));
        }

        // Create empty collections
        for ($i = 0; $i < 3; $i++) {
            $creator = fake()->randomElement($allUsers);
            BlueprintCollection::factory()->create([
                'creator_id' => $creator->id,
                'status' => Status::DRAFT,
                'is_anonymous' => false,
            ]);
        }
    }

    /**
     * Create comments on blueprints.
     */
    private function createComments(array $users, array $blueprints): void
    {
        $allUsers = array_merge([$users['admin'], $users['moderator']], $users['regular']->all());
        $publishedBlueprints = array_filter($blueprints, fn ($bp) => $bp->status === Status::PUBLISHED);

        foreach ($publishedBlueprints as $blueprint) {
            // Each blueprint gets 0-8 comments
            $commentCount = fake()->numberBetween(0, 8);

            for ($i = 0; $i < $commentCount; $i++) {
                $commenter = fake()->randomElement($allUsers);
                $comment = $blueprint->commentAsUser($commenter, fake()->paragraph());

                // Set approval and edited status
                if (fake()->boolean(90)) {
                    $comment->approve();
                }

                if (fake()->boolean(15)) {
                    $comment->update(['is_edited' => true]);
                }
            }
        }
    }

    /**
     * Create reports.
     */
    private function createReports(array $users, array $blueprints): void
    {
        $allUsers = array_merge([$users['admin'], $users['moderator']], $users['regular']->all());
        $publishedBlueprints = array_filter($blueprints, fn ($bp) => $bp->status === Status::PUBLISHED);

        // Create reports on blueprints
        for ($i = 0; $i < 5; $i++) {
            $reporter = fake()->randomElement($allUsers);
            $blueprint = fake()->randomElement($publishedBlueprints);

            Report::factory()->create([
                'user_id' => $reporter->id,
                'reportable_type' => Blueprint::class,
                'reportable_id' => $blueprint->id,
                'reason' => fake()->sentence(),
            ]);
        }

        // Create reports on collections
        $collections = BlueprintCollection::where('status', Status::PUBLISHED)->get();
        if ($collections->isNotEmpty()) {
            for ($i = 0; $i < 2; $i++) {
                $reporter = fake()->randomElement($allUsers);
                $collection = $collections->random();

                Report::factory()->create([
                    'user_id' => $reporter->id,
                    'reportable_type' => BlueprintCollection::class,
                    'reportable_id' => $collection->id,
                    'reason' => fake()->sentence(),
                ]);
            }
        }
    }
}
