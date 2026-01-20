<?php

declare(strict_types=1);

namespace Thumbrise\LaravelVersionedModel\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Thumbrise\LaravelVersionedModel\Models\ModelVersion;
use Thumbrise\LaravelVersionedModel\Tests\Fixtures\TestModel;
use Thumbrise\LaravelVersionedModel\Tests\TestCase;

/**
 * @internal
 */
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

    public function testItCreatesVersionOnModelUpdate(): void
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

    public function testItIncrementsVersionNumber(): void
    {
        $model = TestModel::create(['name' => 'John']);

        $model->updateVersioned(['name' => 'Jane']);
        $model->updateVersioned(['name' => 'Jack']);
        $model->updateVersioned(['name' => 'Jim']);

        $this->assertDatabaseCount('model_versions', 3);

        // Use fresh query, not cached relationship
        $this->assertEquals(3, ModelVersion::where('model_id', $model->id)->count());
        $latestVersion = $model->getLatestVersion();
        $this->assertNotNull($latestVersion);
        $this->assertEquals(3, $latestVersion->version);
    }

    public function testItStoresFullSnapshot(): void
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

    public function testItDoesNotCreateVersionWhenOnlyTimestampsChange(): void
    {
        $model = TestModel::create(['name' => 'John']);

        // Touch updates only timestamps
        $model->touch();

        $this->assertDatabaseCount('model_versions', 0);
    }

    public function testGetVersionReturnsSpecificVersion(): void
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

    public function testGetLatestVersionReturnsLatest(): void
    {
        $model = TestModel::create(['name' => 'v0']);

        $model->updateVersioned(['name' => 'v1']);
        $model->updateVersioned(['name' => 'v2']);

        $latest = $model->getLatestVersion();

        $this->assertNotNull($latest);
        $this->assertEquals(2, $latest->version);
        $this->assertEquals('v2', $latest->snapshot['name']);
    }

    public function testGetDiffBetweenVersions(): void
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

    public function testGetDiffFromNullUsesEmptyState(): void
    {
        $model = TestModel::create(['name' => 'John', 'email' => 'john@example.com']);

        $model->updateVersioned(['name' => 'Jane']);

        $diff = $model->getDiff(null, 1);

        $this->assertArrayHasKey('name', $diff);
        $this->assertNull($diff['name']['old']);
        $this->assertEquals('Jane', $diff['name']['new']);
    }

    public function testRevertToVersion(): void
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

    public function testRevertToNonExistentVersionReturnsFalse(): void
    {
        $model = TestModel::create(['name' => 'John']);

        $model->updateVersioned(['name' => 'Jane']);

        $result = $model->revertToVersion(999);

        $this->assertFalse($result);
    }

    public function testGetFieldHistory(): void
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

    public function testGetFieldsHistory(): void
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

    public function testVersionsRelation(): void
    {
        $model = TestModel::create(['name' => 'John']);

        $model->updateVersioned(['name' => 'Jane']);
        $model->updateVersioned(['name' => 'Jack']);

        $versions = $model->versions;

        $this->assertCount(2, $versions);
        $this->assertNotNull($versions[0]);
        $this->assertNotNull($versions[1]);
        $this->assertEquals(1, $versions[0]->version);
        $this->assertEquals(2, $versions[1]->version);
    }
}
