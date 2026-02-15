<?php

namespace App\Service\Seeding;

/**
 * Represents the result of a database seeding operation.
 * 
 * This class tracks statistics about the seeding process including which entities
 * were seeded, which were skipped, execution time, and total record counts.
 * It provides methods to incrementally build the result during seeding.
 */
class SeedingResult
{
    /**
     * @param array<string, int> $entitiesSeeded Map of entity class names to number of records seeded
     * @param array<string, string> $entitiesSkipped Map of entity class names to skip reason
     * @param float $executionTime Total execution time in seconds
     * @param int $totalRecords Total number of records seeded across all entities
     */
    public function __construct(
        private array $entitiesSeeded = [],
        private array $entitiesSkipped = [],
        private float $executionTime = 0.0,
        private int $totalRecords = 0
    ) {
    }

    /**
     * Get the map of entities seeded with their record counts.
     * 
     * @return array<string, int>
     */
    public function getEntitiesSeeded(): array
    {
        return $this->entitiesSeeded;
    }

    /**
     * Get the map of entities skipped with their skip reasons.
     * 
     * @return array<string, string>
     */
    public function getEntitiesSkipped(): array
    {
        return $this->entitiesSkipped;
    }

    /**
     * Get the total execution time in seconds.
     */
    public function getExecutionTime(): float
    {
        return $this->executionTime;
    }

    /**
     * Get the total number of records seeded across all entities.
     */
    public function getTotalRecords(): int
    {
        return $this->totalRecords;
    }

    /**
     * Add a seeded entity to the result.
     * 
     * @param string $entityClass The fully qualified class name of the entity
     * @param int $count The number of records seeded for this entity
     */
    public function addSeededEntity(string $entityClass, int $count): void
    {
        $this->entitiesSeeded[$entityClass] = $count;
        $this->totalRecords += $count;
    }

    /**
     * Add a skipped entity to the result.
     * 
     * @param string $entityClass The fully qualified class name of the entity
     * @param string $reason The reason why the entity was skipped
     */
    public function addSkippedEntity(string $entityClass, string $reason): void
    {
        $this->entitiesSkipped[$entityClass] = $reason;
    }

    /**
     * Set the execution time for the seeding operation.
     * 
     * @param float $executionTime The execution time in seconds
     */
    public function setExecutionTime(float $executionTime): void
    {
        $this->executionTime = $executionTime;
    }

    /**
     * Calculate and return the total number of entities processed (seeded + skipped).
     */
    public function getTotalEntitiesProcessed(): int
    {
        return count($this->entitiesSeeded) + count($this->entitiesSkipped);
    }

    /**
     * Check if any entities were seeded.
     */
    public function hasSeededEntities(): bool
    {
        return !empty($this->entitiesSeeded);
    }

    /**
     * Check if any entities were skipped.
     */
    public function hasSkippedEntities(): bool
    {
        return !empty($this->entitiesSkipped);
    }

    /**
     * Get the number of unique entity types that were seeded.
     */
    public function getSeededEntityCount(): int
    {
        return count($this->entitiesSeeded);
    }

    /**
     * Get the number of unique entity types that were skipped.
     */
    public function getSkippedEntityCount(): int
    {
        return count($this->entitiesSkipped);
    }
}
