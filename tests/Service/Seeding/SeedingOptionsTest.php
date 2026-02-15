<?php

namespace App\Tests\Service\Seeding;

use App\Service\Seeding\SeedingOptions;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SeedingOptions class.
 */
class SeedingOptionsTest extends TestCase
{
    /**
     * Test default values are set correctly.
     */
    public function testDefaultValues(): void
    {
        $options = new SeedingOptions();

        $this->assertSame(10, $options->getCount());
        $this->assertSame([], $options->getSpecificEntities());
        $this->assertFalse($options->shouldClearExisting());
        $this->assertSame(['Groupe'], $options->getExcludedEntities());
    }

    /**
     * Test custom values are set correctly.
     */
    public function testCustomValues(): void
    {
        $options = new SeedingOptions(
            count: 50,
            specificEntities: ['App\\Entity\\User', 'App\\Entity\\Entreprise'],
            clearExisting: true,
            excludedEntities: ['Groupe', 'Admin']
        );

        $this->assertSame(50, $options->getCount());
        $this->assertSame(['App\\Entity\\User', 'App\\Entity\\Entreprise'], $options->getSpecificEntities());
        $this->assertTrue($options->shouldClearExisting());
        $this->assertSame(['Groupe', 'Admin'], $options->getExcludedEntities());
    }

    /**
     * Test shouldSeedEntity returns false for excluded entities.
     */
    public function testShouldSeedEntityReturnsFalseForExcludedEntities(): void
    {
        $options = new SeedingOptions(excludedEntities: ['Groupe', 'Admin']);

        $this->assertFalse($options->shouldSeedEntity('Groupe'));
        $this->assertFalse($options->shouldSeedEntity('Admin'));
    }

    /**
     * Test shouldSeedEntity returns true for non-excluded entities when no specific entities are set.
     */
    public function testShouldSeedEntityReturnsTrueForNonExcludedEntitiesWithNoSpecificEntities(): void
    {
        $options = new SeedingOptions(excludedEntities: ['Groupe']);

        $this->assertTrue($options->shouldSeedEntity('App\\Entity\\User'));
        $this->assertTrue($options->shouldSeedEntity('App\\Entity\\Entreprise'));
        $this->assertTrue($options->shouldSeedEntity('App\\Entity\\Locataire'));
    }

    /**
     * Test shouldSeedEntity returns true only for specific entities when specified.
     */
    public function testShouldSeedEntityRespectsSpecificEntities(): void
    {
        $options = new SeedingOptions(
            specificEntities: ['App\\Entity\\User', 'App\\Entity\\Entreprise'],
            excludedEntities: ['Groupe']
        );

        $this->assertTrue($options->shouldSeedEntity('App\\Entity\\User'));
        $this->assertTrue($options->shouldSeedEntity('App\\Entity\\Entreprise'));
        $this->assertFalse($options->shouldSeedEntity('App\\Entity\\Locataire'));
        $this->assertFalse($options->shouldSeedEntity('App\\Entity\\Pays'));
    }

    /**
     * Test shouldSeedEntity returns false for excluded entities even if they are in specific entities list.
     */
    public function testShouldSeedEntityExclusionTakesPrecedenceOverSpecificEntities(): void
    {
        $options = new SeedingOptions(
            specificEntities: ['Groupe', 'App\\Entity\\User'],
            excludedEntities: ['Groupe']
        );

        $this->assertFalse($options->shouldSeedEntity('Groupe'));
        $this->assertTrue($options->shouldSeedEntity('App\\Entity\\User'));
    }

    /**
     * Test shouldSeedEntity with empty excluded entities list.
     */
    public function testShouldSeedEntityWithEmptyExcludedList(): void
    {
        $options = new SeedingOptions(excludedEntities: []);

        $this->assertTrue($options->shouldSeedEntity('Groupe'));
        $this->assertTrue($options->shouldSeedEntity('App\\Entity\\User'));
    }

    /**
     * Test shouldSeedEntity with both specific and excluded entities.
     */
    public function testShouldSeedEntityWithBothSpecificAndExcludedEntities(): void
    {
        $options = new SeedingOptions(
            specificEntities: ['App\\Entity\\User', 'App\\Entity\\Entreprise', 'App\\Entity\\Locataire'],
            excludedEntities: ['Groupe', 'App\\Entity\\Entreprise']
        );

        $this->assertTrue($options->shouldSeedEntity('App\\Entity\\User'));
        $this->assertFalse($options->shouldSeedEntity('App\\Entity\\Entreprise')); // Excluded
        $this->assertTrue($options->shouldSeedEntity('App\\Entity\\Locataire'));
        $this->assertFalse($options->shouldSeedEntity('App\\Entity\\Pays')); // Not in specific list
        $this->assertFalse($options->shouldSeedEntity('Groupe')); // Excluded
    }
}
