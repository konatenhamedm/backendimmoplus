<?php

namespace App\Service\Seeding;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Handles efficient batch persistence of entities during seeding.
 * 
 * This service manages entity persistence in batches to optimize performance
 * and memory usage. It tracks persisted entities for relationship references
 * and automatically flushes when batch size is reached.
 */
class PersistenceManager
{
    /**
     * Number of entities to persist before flushing to database.
     * This balances performance and memory usage.
     */
    private const BATCH_SIZE = 50;

    /**
     * Counter for entities queued since last flush.
     */
    private int $entityCount = 0;

    /**
     * Tracks persisted entities by class name for relationship references.
     * 
     * @var array<string, array<object>>
     */
    private array $persistedEntities = [];

    /**
     * @param EntityManagerInterface $entityManager The Doctrine entity manager
     */
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Queue an entity for persistence.
     * 
     * This method adds an entity to the persistence queue and tracks it
     * for relationship references. It automatically flushes when the
     * batch size is reached.
     * 
     * @param object $entity The entity to persist
     */
    public function persistEntity(object $entity): void
    {
        // Persist the entity
        $this->entityManager->persist($entity);
        
        // Track the entity by class name
        $className = get_class($entity);
        if (!isset($this->persistedEntities[$className])) {
            $this->persistedEntities[$className] = [];
        }
        $this->persistedEntities[$className][] = $entity;
        
        // Increment counter
        $this->entityCount++;
        
        // Auto-flush if batch size is reached
        if ($this->shouldFlush()) {
            $this->flush();
            $this->clear();
        }
    }

    /**
     * Flush all queued entities to the database.
     * 
     * This method persists all queued entities to the database
     * and resets the entity counter.
     */
    public function flush(): void
    {
        $this->entityManager->flush();
        $this->entityCount = 0;
    }

    /**
     * Clear the EntityManager to free memory.
     * 
     * This method clears the EntityManager's internal cache,
     * freeing memory after a batch has been persisted.
     * Note: This does NOT clear the persistedEntities tracking array,
     * as we need to maintain references for relationships.
     */
    public function clear(): void
    {
        $this->entityManager->clear();
    }

    /**
     * Get all persisted entities of a specific class.
     * 
     * This method returns all entities that have been persisted
     * for a given class, useful for relationship references.
     * 
     * @param string $entityClass The fully qualified entity class name
     * @return array<object> Array of persisted entities
     */
    public function getPersistedEntities(string $entityClass): array
    {
        return $this->persistedEntities[$entityClass] ?? [];
    }

    /**
     * Check if the batch size has been reached and a flush is needed.
     * 
     * @return bool True if batch size is reached, false otherwise
     */
    private function shouldFlush(): bool
    {
        return $this->entityCount >= self::BATCH_SIZE;
    }
}
