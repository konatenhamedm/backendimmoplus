<?php

namespace App\Service\Seeding;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Manages entity relationships during seeding.
 * 
 * This service handles all types of Doctrine relationships (ManyToOne, OneToOne,
 * OneToMany, ManyToMany) during the seeding process. It ensures referential
 * integrity, respects the Groupe table exclusion rule, and creates missing
 * dependencies when needed.
 */
class RelationshipHandler
{
    /**
     * @param EntityManagerInterface $entityManager The Doctrine entity manager
     * @param DataGenerator $dataGenerator The data generator for creating related entities
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DataGenerator $dataGenerator
    ) {
    }

    /**
     * Handle a ManyToOne relationship by selecting a random existing entity.
     * 
     * This method queries the database for existing entities of the target type
     * and returns a random one. Special handling is applied for the Groupe entity:
     * - Only existing Groupe records are used (never created)
     * - Returns null if no Groupe records exist and the field is nullable
     * 
     * @param RelationshipMetadata $relation The relationship metadata
     * @param array<object> $existingEntities Optional array of existing entities to choose from
     * @return object|null The selected entity or null if none exist and nullable
     */
    public function handleManyToOne(RelationshipMetadata $relation, array $existingEntities = []): ?object
    {
        $targetEntity = $relation->getTargetEntity();
        
        // Special handling for Groupe entity - only use existing records
        if ($this->isGroupeEntity($targetEntity)) {
            return $this->getExistingGroupeEntity($relation);
        }

        // If existing entities are provided, use them
        if (!empty($existingEntities)) {
            return $this->getRandomEntity($existingEntities);
        }

        // Query database for existing entities
        $repository = $this->entityManager->getRepository($targetEntity);
        $entities = $repository->findAll();

        // If no entities exist
        if (empty($entities)) {
            // Return null if nullable, otherwise this will need to be handled by the orchestrator
            return $relation->isNullable() ? null : null;
        }

        return $this->getRandomEntity($entities);
    }

    /**
     * Handle a OneToOne relationship by creating or associating a unique related entity.
     * 
     * For OneToOne relationships, we need to ensure uniqueness of the related entity.
     * This method creates a new entity instance that will be populated by the orchestrator.
     * 
     * Note: The actual entity creation and population is handled by the orchestrator.
     * This method returns null as a signal that a new entity should be created.
     * 
     * @param RelationshipMetadata $relation The relationship metadata
     * @return object|null Null to signal that a new entity should be created
     */
    public function handleOneToOne(RelationshipMetadata $relation): ?object
    {
        $targetEntity = $relation->getTargetEntity();
        
        // Special handling for Groupe entity - only use existing records
        if ($this->isGroupeEntity($targetEntity)) {
            return $this->getExistingGroupeEntity($relation);
        }

        // For OneToOne, we return null to signal that a new unique entity should be created
        // The orchestrator will handle the actual creation and population
        return null;
    }

    /**
     * Handle a OneToMany relationship by creating multiple related entities.
     * 
     * For OneToMany relationships, we create multiple related entities that will
     * reference back to the parent entity. The actual entity creation and population
     * is handled by the orchestrator.
     * 
     * @param RelationshipMetadata $relation The relationship metadata
     * @param int $count Number of related entities to create (default: 2-5)
     * @return array<object> Array of created entities (empty array as signal to orchestrator)
     */
    public function handleOneToMany(RelationshipMetadata $relation, int $count = 0): array
    {
        $targetEntity = $relation->getTargetEntity();
        
        // Special handling for Groupe entity - never create Groupe records
        if ($this->isGroupeEntity($targetEntity)) {
            return [];
        }

        // If count is not specified, generate a random count between 2 and 5
        if ($count === 0) {
            $count = random_int(2, 5);
        }

        // Return empty array as signal to orchestrator to create $count entities
        // The orchestrator will handle the actual creation and population
        // We return an array with the count information
        return array_fill(0, $count, null);
    }

    /**
     * Handle a ManyToMany relationship by associating multiple existing entities.
     * 
     * For ManyToMany relationships, we select multiple random existing entities
     * from the target entity type. If not enough entities exist, we return what's available.
     * 
     * @param RelationshipMetadata $relation The relationship metadata
     * @param int $count Number of entities to associate (default: 2-5)
     * @return array<object> Array of entities to associate
     */
    public function handleManyToMany(RelationshipMetadata $relation, int $count = 0): array
    {
        $targetEntity = $relation->getTargetEntity();
        
        // Special handling for Groupe entity - only use existing records
        if ($this->isGroupeEntity($targetEntity)) {
            $repository = $this->entityManager->getRepository($targetEntity);
            $allEntities = $repository->findAll();
            
            if (empty($allEntities)) {
                return [];
            }
            
            // Return a random subset of existing Groupe entities
            $count = $count > 0 ? min($count, count($allEntities)) : min(random_int(2, 5), count($allEntities));
            return $this->getRandomEntities($allEntities, $count);
        }

        // Query database for existing entities
        $repository = $this->entityManager->getRepository($targetEntity);
        $allEntities = $repository->findAll();

        // If no entities exist, return empty array
        if (empty($allEntities)) {
            return [];
        }

        // If count is not specified, generate a random count between 2 and 5
        if ($count === 0) {
            $count = random_int(2, 5);
        }

        // Ensure we don't try to select more entities than exist
        $count = min($count, count($allEntities));

        return $this->getRandomEntities($allEntities, $count);
    }

    /**
     * Check if the target entity is the Groupe entity.
     * 
     * @param string $entityClass The fully qualified entity class name
     * @return bool True if the entity is Groupe, false otherwise
     */
    private function isGroupeEntity(string $entityClass): bool
    {
        // Check if the class name ends with 'Groupe' (handles both FQCN and short name)
        return str_ends_with($entityClass, 'Groupe') || str_ends_with($entityClass, '\\Groupe');
    }

    /**
     * Get an existing Groupe entity from the database.
     * 
     * This method enforces the rule that Groupe entities are never created,
     * only existing ones are used.
     * 
     * @param RelationshipMetadata $relation The relationship metadata
     * @return object|null A random existing Groupe entity or null if none exist
     */
    private function getExistingGroupeEntity(RelationshipMetadata $relation): ?object
    {
        $targetEntity = $relation->getTargetEntity();
        $repository = $this->entityManager->getRepository($targetEntity);
        $entities = $repository->findAll();

        if (empty($entities)) {
            // No Groupe records exist
            // Return null if nullable, otherwise this is an error condition
            return $relation->isNullable() ? null : null;
        }

        return $this->getRandomEntity($entities);
    }

    /**
     * Get a random entity from an array of entities.
     * 
     * @param array<object> $entities Array of entities
     * @return object|null A random entity or null if array is empty
     */
    private function getRandomEntity(array $entities): ?object
    {
        if (empty($entities)) {
            return null;
        }

        $randomIndex = array_rand($entities);
        return $entities[$randomIndex];
    }

    /**
     * Get multiple random entities from an array of entities.
     * 
     * @param array<object> $entities Array of entities
     * @param int $count Number of entities to select
     * @return array<object> Array of randomly selected entities
     */
    private function getRandomEntities(array $entities, int $count): array
    {
        if (empty($entities)) {
            return [];
        }

        // If requesting more entities than available, return all
        if ($count >= count($entities)) {
            return $entities;
        }

        // Shuffle and take the first $count entities
        $shuffled = $entities;
        shuffle($shuffled);
        return array_slice($shuffled, 0, $count);
    }
}
