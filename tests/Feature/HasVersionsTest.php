<?php

declare(strict_types=1);

namespace Thumbrise\LaravelVersionedModel\Tests\Feature;

use Thumbrise\LaravelVersionedModel\Tests\Fixtures\TestModel;
use Thumbrise\LaravelVersionedModel\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HasVersionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->defineDatabaseMigrations();
        TestModel::createTable();
    }

    protected function tearDown(): void
    {
        TestModel::dropTable();
        
        parent::tearDown();
    }

    public function test_it_creates_version_on_model_update(): void
    {
        $model = TestModel::create([
            'name'  => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertDatabaseCount('model_versions', 0);

        $model->updateVersioned(['name' => 'Jane Doe']);

        $this->assertDatabaseCount('model_versions', 1);
        $this->assertDatabaseHas('model_versions', [
            'model_type' => TestModel::class,
            'model_id'   => $model->id,
            'version'    => 1,
        ]);
    }

    public function test_it_increments_version_number(): void
    {
        $model = TestModel::create(['name' => 'John']);

        $model->updateVersioned(['name' => 'Jane']);
        $model->updateVersioned(['name' => 'Jack']);
        $model->updateVersioned(['name' => 'Jim']);

        $this->assertDatabaseCount('model_versions', 3);
        
        // Use fresh query, not cached relationship
        $this->assertEquals(3, \Thumbrise\LaravelVersionedModel\Models\ModelVersion::where('model_id', $model->id)->count());
        $this->assertEquals(3, $model->getLatestVersion()->version);
    }

    public function test_it_stores_full_snapshot(): void
    {
        $model = TestModel::create([
            'name'   => 'John',
            'email'  => 'john@example.com',
            'status' => 'active',
            'count'  => 5,
        ]);

        $model->updateVersioned(['name' => 'Jane']);

        $version = $model->getVersion(1);
        
        $this->assertNotNull($version);
        $this->assertEquals('Jane', $version->snapshot['name']);
        $this->assertEquals('john@example.com', $version->snapshot['email']);
        $this->assertEquals('active', $version->snapshot['status']);
        $this->assertEquals(5, $version->snapshot['count']);
    }

    public function test_it_does_not_create_version_when_only_timestamps_change(): void
    {
        $model = TestModel::create(['name' => 'John']);
        
        // Touch updates only timestamps
        $model->touch();

        $this->assertDatabaseCount('model_versions', 0);
    }

    public function test_get_version_returns_specific_version(): void
    {
        $model = TestModel::create(['name' => 'v0']);

        $model->updateVersioned(['name' => 'v1']);
        $model->updateVersioned(['name' => 'v2']);
        $model->updateVersioned(['name' => 'v3']);

        $version2 = $model->getVersion(2);
        
        $this->assertNotNull($version2);
        $this->assertEquals(2, $version2->version);
        $this->assertEquals('v2', $version2->snapshot['name']);
    }

    public function test_get_latest_version_returns_latest(): void
    {
        $model = TestModel::create(['name' => 'v0']);

        $model->updateVersioned(['name' => 'v1']);
        $model->updateVersioned(['name' => 'v2']);

        $latest = $model->getLatestVersion();
        
        $this->assertNotNull($latest);
        $this->assertEquals(2, $latest->version);
        $this->assertEquals('v2', $latest->snapshot['name']);
    }

    public function test_get_diff_between_versions(): void
    {
        $model = TestModel::create([
            'name'   => 'John',
            'email'  => 'john@example.com',
            'status' => 'active',
        ]);

        $model->updateVersioned(['name' => 'Jane', 'email' => 'jane@example.com']);
        $model->updateVersioned(['status' => 'inactive']);

        $diff = $model->getDiff(1, 2);

        $this->assertArrayHasKey('status', $diff);
        $this->assertEquals('active', $diff['status']['old']);
        $this->assertEquals('inactive', $diff['status']['new']);
        $this->assertArrayNotHasKey('name', $diff);
        $this->assertArrayNotHasKey('email', $diff);
    }

    public function test_get_diff_from_null_uses_empty_state(): void
    {
        $model = TestModel::create(['name' => 'John', 'email' => 'john@example.com']);
        
        $model->updateVersioned(['name' => 'Jane']);

        $diff = $model->getDiff(null, 1);

        $this->assertArrayHasKey('name', $diff);
        $this->assertNull($diff['name']['old']);
        $this->assertEquals('Jane', $diff['name']['new']);
    }

    public function test_revert_to_version(): void
    {
        $model = TestModel::create(['name' => 'v0', 'email' => 'v0@example.com']);
        
        $model->updateVersioned(['name' => 'v1', 'email' => 'v1@example.com']);
        $model->updateVersioned(['name' => 'v2', 'email' => 'v2@example.com']);

        $this->assertEquals('v2', $model->name);
        $this->assertEquals('v2@example.com', $model->email);

        $result = $model->revertToVersion(1);

        $this->assertTrue($result);
        $model->refresh();
        
        $this->assertEquals('v1', $model->name);
        $this->assertEquals('v1@example.com', $model->email);
    }

    public function test_revert_to_non_existent_version_returns_false(): void
    {
        $model = TestModel::create(['name' => 'John']);
        
        $model->updateVersioned(['name' => 'Jane']);

        $result = $model->revertToVersion(999);

        $this->assertFalse($result);
    }

    public function test_get_field_history(): void
    {
        $model = TestModel::create(['name' => 'v0']);

        $model->updateVersioned(['name' => 'v1']);
        $model->updateVersioned(['name' => 'v2']);
        $model->updateVersioned(['name' => 'v3']);

        $history = $model->getFieldHistory('name');

        $this->assertCount(3, $history);
        $this->assertEquals('v1', $history[0]['value']);
        $this->assertEquals('v2', $history[1]['value']);
        $this->assertEquals('v3', $history[2]['value']);
    }

    public function test_get_fields_history(): void
    {
        $model = TestModel::create(['name' => 'John', 'email' => 'john@example.com']);

        $model->updateVersioned(['name' => 'Jane', 'email' => 'jane@example.com']);
        $model->updateVersioned(['name' => 'Jack']);

        $history = $model->getFieldsHistory(['name', 'email']);

        $this->assertArrayHasKey('name', $history);
        $this->assertArrayHasKey('email', $history);
        $this->assertCount(2, $history['name']);
        $this->assertCount(2, $history['email']);
    }

    public function test_versions_relation(): void
    {
        $model = TestModel::create(['name' => 'John']);

        $model->updateVersioned(['name' => 'Jane']);
        $model->updateVersioned(['name' => 'Jack']);

        $versions = $model->versions;

        $this->assertCount(2, $versions);
        $this->assertEquals(1, $versions[0]->version);
        $this->assertEquals(2, $versions[1]->version);
    }
}
