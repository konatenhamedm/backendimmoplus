<?php

namespace App\Service\Seeding;

/**
 * Configuration options for the database seeding process.
 * 
 * This class encapsulates all configurable parameters for seeding operations,
 * including the number of records to generate, entity filtering, and exclusion rules.
 */
class SeedingOptions
{
    /**
     * @param int $count Number of records to generate per entity (default: 10)
     * @param array<string> $specificEntities List of specific entity class names to seed (empty = all entities)
     * @param bool $clearExisting Whether to clear existing data before seeding (default: false)
     * @param array<string> $excludedEntities List of entity class names to exclude from seeding (default: ['Groupe'])
     */
    public function __construct(
        private int $count = 10,
        private array $specificEntities = [],
        private bool $clearExisting = false,
        private array $excludedEntities = ['Groupe']
    ) {
    }

    /**
     * Get the number of records to generate per entity.
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * Get the list of specific entities to seed.
     * 
     * @return array<string> Empty array means seed all entities
     */
    public function getSpecificEntities(): array
    {
        return $this->specificEntities;
    }

    /**
     * Check if existing data should be cleared before seeding.
     */
    public function shouldClearExisting(): bool
    {
        return $this->clearExisting;
    }

    /**
     * Get the list of excluded entity class names.
     * 
     * @return array<string>
     */
    public function getExcludedEntities(): array
    {
        return $this->excludedEntities;
    }

    /**
     * Check if a specific entity should be seeded.
     * 
     * An entity should be seeded if:
     * - It is not in the excluded list, AND
     * - Either no specific entities are specified, OR the entity is in the specific entities list
     * 
     * @param string $entityClass The fully qualified class name of the entity
     * @return bool True if the entity should be seeded, false otherwise
     */
    public function shouldSeedEntity(string $entityClass): bool
    {
        // Check if entity is excluded
        if (in_array($entityClass, $this->excludedEntities, true)) {
            return false;
        }

        // If specific entities are specified, check if this entity is in the list
        if (!empty($this->specificEntities)) {
            return in_array($entityClass, $this->specificEntities, true);
        }

        // No specific entities specified, so seed all non-excluded entities
        return true;
    }
}
