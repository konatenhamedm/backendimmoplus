<?php

namespace App\Tests\Service\Seeding;

use App\Service\Seeding\SeedingResult;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SeedingResult class.
 */
class SeedingResultTest extends TestCase
{
    /**
     * Test default values are set correctly.
     */
    public function testDefaultValues(): void
    {
        $result = new SeedingResult();

        $this->assertSame([], $result->getEntitiesSeeded());
        $this->assertSame([], $result->getEntitiesSkipped());
        $this->assertSame(0.0, $result->getExecutionTime());
        $this->assertSame(0, $result->getTotalRecords());
        $this->assertFalse($result->hasSeededEntities());
        $this->assertFalse($result->hasSkippedEntities());
    }

    /**
     * Test custom values are set correctly via constructor.
     */
    public function testCustomValues(): void
    {
        $entitiesSeeded = [
            'App\\Entity\\User' => 10,
            'App\\Entity\\Entreprise' => 5,
        ];
        $entitiesSkipped = [
            'Groupe' => 'Excluded by configuration',
        ];

        $result = new SeedingResult(
            entitiesSeeded: $entitiesSeeded,
            entitiesSkipped: $entitiesSkipped,
            executionTime: 2.5,
            totalRecords: 15
        );

        $this->assertSame($entitiesSeeded, $result->getEntitiesSeeded());
        $this->assertSame($entitiesSkipped, $result->getEntitiesSkipped());
        $this->assertSame(2.5, $result->getExecutionTime());
        $this->assertSame(15, $result->getTotalRecords());
    }

    /**
     * Test adding a seeded entity updates the result correctly.
     */
    public function testAddSeededEntity(): void
    {
        $result = new SeedingResult();

        $result->addSeededEntity('App\\Entity\\User', 10);

        $this->assertSame(['App\\Entity\\User' => 10], $result->getEntitiesSeeded());
        $this->assertSame(10, $result->getTotalRecords());
        $this->assertTrue($result->hasSeededEntities());
    }

    /**
     * Test adding multiple seeded entities accumulates total records.
     */
    public function testAddMultipleSeededEntitiesAccumulatesTotalRecords(): void
    {
        $result = new SeedingResult();

        $result->addSeededEntity('App\\Entity\\User', 10);
        $result->addSeededEntity('App\\Entity\\Entreprise', 5);
        $result->addSeededEntity('App\\Entity\\Locataire', 8);

        $expected = [
            'App\\Entity\\User' => 10,
            'App\\Entity\\Entreprise' => 5,
            'App\\Entity\\Locataire' => 8,
        ];

        $this->assertSame($expected, $result->getEntitiesSeeded());
        $this->assertSame(23, $result->getTotalRecords());
        $this->assertSame(3, $result->getSeededEntityCount());
    }

    /**
     * Test adding a skipped entity updates the result correctly.
     */
    public function testAddSkippedEntity(): void
    {
        $result = new SeedingResult();

        $result->addSkippedEntity('Groupe', 'Excluded by configuration');

        $this->assertSame(['Groupe' => 'Excluded by configuration'], $result->getEntitiesSkipped());
        $this->assertTrue($result->hasSkippedEntities());
        $this->assertSame(1, $result->getSkippedEntityCount());
    }

    /**
     * Test adding multiple skipped entities.
     */
    public function testAddMultipleSkippedEntities(): void
    {
        $result = new SeedingResult();

        $result->addSkippedEntity('Groupe', 'Excluded by configuration');
        $result->addSkippedEntity('App\\Entity\\Admin', 'No dependencies available');
        $result->addSkippedEntity('App\\Entity\\Log', 'Validation failed');

        $expected = [
            'Groupe' => 'Excluded by configuration',
            'App\\Entity\\Admin' => 'No dependencies available',
            'App\\Entity\\Log' => 'Validation failed',
        ];

        $this->assertSame($expected, $result->getEntitiesSkipped());
        $this->assertSame(3, $result->getSkippedEntityCount());
    }

    /**
     * Test setting execution time.
     */
    public function testSetExecutionTime(): void
    {
        $result = new SeedingResult();

        $result->setExecutionTime(3.14159);

        $this->assertSame(3.14159, $result->getExecutionTime());
    }

    /**
     * Test getTotalEntitiesProcessed returns sum of seeded and skipped entities.
     */
    public function testGetTotalEntitiesProcessed(): void
    {
        $result = new SeedingResult();

        $result->addSeededEntity('App\\Entity\\User', 10);
        $result->addSeededEntity('App\\Entity\\Entreprise', 5);
        $result->addSkippedEntity('Groupe', 'Excluded');
        $result->addSkippedEntity('App\\Entity\\Admin', 'No dependencies');

        $this->assertSame(4, $result->getTotalEntitiesProcessed());
    }

    /**
     * Test hasSeededEntities returns false when no entities are seeded.
     */
    public function testHasSeededEntitiesReturnsFalseWhenEmpty(): void
    {
        $result = new SeedingResult();

        $this->assertFalse($result->hasSeededEntities());
    }

    /**
     * Test hasSeededEntities returns true when entities are seeded.
     */
    public function testHasSeededEntitiesReturnsTrueWhenNotEmpty(): void
    {
        $result = new SeedingResult();
        $result->addSeededEntity('App\\Entity\\User', 10);

        $this->assertTrue($result->hasSeededEntities());
    }

    /**
     * Test hasSkippedEntities returns false when no entities are skipped.
     */
    public function testHasSkippedEntitiesReturnsFalseWhenEmpty(): void
    {
        $result = new SeedingResult();

        $this->assertFalse($result->hasSkippedEntities());
    }

    /**
     * Test hasSkippedEntities returns true when entities are skipped.
     */
    public function testHasSkippedEntitiesReturnsTrueWhenNotEmpty(): void
    {
        $result = new SeedingResult();
        $result->addSkippedEntity('Groupe', 'Excluded');

        $this->assertTrue($result->hasSkippedEntities());
    }

    /**
     * Test getSeededEntityCount returns correct count.
     */
    public function testGetSeededEntityCount(): void
    {
        $result = new SeedingResult();

        $this->assertSame(0, $result->getSeededEntityCount());

        $result->addSeededEntity('App\\Entity\\User', 10);
        $this->assertSame(1, $result->getSeededEntityCount());

        $result->addSeededEntity('App\\Entity\\Entreprise', 5);
        $this->assertSame(2, $result->getSeededEntityCount());
    }

    /**
     * Test getSkippedEntityCount returns correct count.
     */
    public function testGetSkippedEntityCount(): void
    {
        $result = new SeedingResult();

        $this->assertSame(0, $result->getSkippedEntityCount());

        $result->addSkippedEntity('Groupe', 'Excluded');
        $this->assertSame(1, $result->getSkippedEntityCount());

        $result->addSkippedEntity('App\\Entity\\Admin', 'No dependencies');
        $this->assertSame(2, $result->getSkippedEntityCount());
    }

    /**
     * Test adding seeded entity with zero count.
     */
    public function testAddSeededEntityWithZeroCount(): void
    {
        $result = new SeedingResult();

        $result->addSeededEntity('App\\Entity\\User', 0);

        $this->assertSame(['App\\Entity\\User' => 0], $result->getEntitiesSeeded());
        $this->assertSame(0, $result->getTotalRecords());
        $this->assertTrue($result->hasSeededEntities()); // Entity is tracked even with 0 records
    }

    /**
     * Test updating an existing seeded entity replaces the count.
     */
    public function testAddSeededEntityReplacesExistingCount(): void
    {
        $result = new SeedingResult();

        $result->addSeededEntity('App\\Entity\\User', 10);
        $result->addSeededEntity('App\\Entity\\User', 15);

        $this->assertSame(['App\\Entity\\User' => 15], $result->getEntitiesSeeded());
        // Note: Total records will be 25 (10 + 15) because addSeededEntity accumulates
        $this->assertSame(25, $result->getTotalRecords());
    }

    /**
     * Test updating an existing skipped entity replaces the reason.
     */
    public function testAddSkippedEntityReplacesExistingReason(): void
    {
        $result = new SeedingResult();

        $result->addSkippedEntity('Groupe', 'First reason');
        $result->addSkippedEntity('Groupe', 'Second reason');

        $this->assertSame(['Groupe' => 'Second reason'], $result->getEntitiesSkipped());
    }

    /**
     * Test complete seeding workflow with mixed results.
     */
    public function testCompleteWorkflow(): void
    {
        $result = new SeedingResult();

        // Add seeded entities
        $result->addSeededEntity('App\\Entity\\User', 10);
        $result->addSeededEntity('App\\Entity\\Entreprise', 5);
        $result->addSeededEntity('App\\Entity\\Locataire', 8);

        // Add skipped entities
        $result->addSkippedEntity('Groupe', 'Excluded by configuration');
        $result->addSkippedEntity('App\\Entity\\Admin', 'No dependencies available');

        // Set execution time
        $result->setExecutionTime(2.5);

        // Verify all results
        $this->assertSame(3, $result->getSeededEntityCount());
        $this->assertSame(2, $result->getSkippedEntityCount());
        $this->assertSame(23, $result->getTotalRecords());
        $this->assertSame(5, $result->getTotalEntitiesProcessed());
        $this->assertSame(2.5, $result->getExecutionTime());
        $this->assertTrue($result->hasSeededEntities());
        $this->assertTrue($result->hasSkippedEntities());
    }
}
