<?php

namespace App\Service\Seeding;

use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Coordinates the entire database seeding process.
 * 
 * This service orchestrates all seeding components to discover entities,
 * resolve dependencies, generate data, handle relationships, and persist
 * entities to the database. It tracks progress and handles errors gracefully.
 */
class SeedingOrchestrator
{
    /**
     * @param EntityAnalyzer $entityAnalyzer Service for discovering and analyzing entities
     * @param DependencyResolver $dependencyResolver Service for resolving entity dependencies
     * @param DataGenerator $dataGenerator Service for generating fake data
     * @param RelationshipHandler $relationshipHandler Service for handling entity relationships
     * @param PersistenceManager $persistenceManager Service for batch persistence
     * @param ValidatorInterface $validator Symfony validator for entity validation
     */
    public function __construct(
        private EntityAnalyzer $entityAnalyzer,
        private DependencyResolver $dependencyResolver,
        private DataGenerator $dataGenerator,
        private RelationshipHandler $relationshipHandler,
        private PersistenceManager $persistenceManager,
        private ValidatorInterface $validator
    ) {
    }

    /**
     * Main entry point for the seeding process.
     * 
     * This method orchestrates the complete seeding workflow:
     * 1. Discover all entities
     * 2. Resolve seeding order based on dependencies
     * 3. Seed each entity in order
     * 4. Track results and execution time
     * 
     * @param SeedingOptions $options Configuration options for seeding
     * @return SeedingResult The result of the seeding operation
     */
    public function seed(SeedingOptions $options): SeedingResult
    {
        $startTime = microtime(true);
        $result = new SeedingResult();

        try {
            // Step 1: Discover entities
            $entityClasses = $this->entityAnalyzer->discoverEntities();
            
            // Step 2: Build entity metadata map
            $entities = [];
            foreach ($entityClasses as $entityClass) {
                // Check if entity should be seeded based on options
                if (!$options->shouldSeedEntity($entityClass)) {
                    $result->addSkippedEntity($entityClass, 'Excluded by configuration');
                    continue;
                }
                
                $entities[$entityClass] = $this->entityAnalyzer->getEntityMetadata($entityClass);
            }

            // Step 3: Resolve seeding order
            $seedingOrder = $this->dependencyResolver->resolveSeedingOrder($entities);

            // Step 4: Seed each entity in order
            foreach ($seedingOrder as $entityClass) {
                if (!isset($entities[$entityClass])) {
                    continue; // Entity was filtered out
                }

                $metadata = $entities[$entityClass];
                $count = $this->seedEntity($metadata, $options->getCount());
                
                if ($count > 0) {
                    $result->addSeededEntity($entityClass, $count);
                }
            }

            // Final flush to persist any remaining entities
            $this->persistenceManager->flush();

        } catch (\Exception $e) {
            // Ensure any pending entities are flushed before re-throwing
            try {
                $this->persistenceManager->flush();
            } catch (\Exception $flushException) {
                // Ignore flush errors during error handling
            }
            
            throw $e;
        }

        // Calculate execution time
        $executionTime = microtime(true) - $startTime;
        $result->setExecutionTime($executionTime);

        return $result;
    }

    /**
     * Seed a single entity type.
     * 
     * This method creates the specified number of instances for an entity type,
     * populates their fields and relationships, validates them, and persists them.
     * 
     * @param EntityMetadata $metadata The entity metadata
     * @param int $count The number of instances to create
     * @return int The number of entities successfully seeded
     */
    private function seedEntity(EntityMetadata $metadata, int $count): int
    {
        $entityClass = $metadata->getClassName();
        $seededCount = 0;

        for ($i = 0; $i < $count; $i++) {
            try {
                // Create entity instance
                $entity = $this->createEntityInstance($metadata);

                // Populate fields
                $this->populateEntityFields($entity, $metadata);

                // Populate relationships
                $this->populateEntityRelationships($entity, $metadata);

                // Validate entity
                $violations = $this->validator->validate($entity);
                
                if (count($violations) > 0) {
                    // Log validation errors and skip this entity
                    $errors = [];
                    foreach ($violations as $violation) {
                        $errors[] = sprintf(
                            '%s: %s',
                            $violation->getPropertyPath(),
                            $violation->getMessage()
                        );
                    }
                    
                    error_log(sprintf(
                        'Validation failed for entity %s: %s',
                        $entityClass,
                        implode(', ', $errors)
                    ));
                    
                    continue; // Skip this entity and continue with the next one
                }

                // Persist entity
                $this->persistenceManager->persistEntity($entity);
                $seededCount++;

            } catch (\Exception $e) {
                // Log error and continue with next entity
                error_log(sprintf(
                    'Error seeding entity %s: %s',
                    $entityClass,
                    $e->getMessage()
                ));
                
                continue;
            }
        }

        return $seededCount;
    }

    /**
     * Create an entity instance using reflection.
     * 
     * @param EntityMetadata $metadata The entity metadata
     * @return object The created entity instance
     */
    private function createEntityInstance(EntityMetadata $metadata): object
    {
        $className = $metadata->getClassName();
        
        // Use reflection to create instance without calling constructor
        // This is safer as some entities may have required constructor parameters
        $reflectionClass = new \ReflectionClass($className);
        
        // Try to create instance with constructor if it has no required parameters
        $constructor = $reflectionClass->getConstructor();
        
        if ($constructor === null || $constructor->getNumberOfRequiredParameters() === 0) {
            return new $className();
        }
        
        // If constructor has required parameters, create without constructor
        return $reflectionClass->newInstanceWithoutConstructor();
    }

    /**
     * Populate entity fields with generated data.
     * 
     * This method iterates through all fields in the entity metadata and
     * generates appropriate values using the DataGenerator. It handles
     * unique constraints and required fields.
     * 
     * @param object $entity The entity instance to populate
     * @param EntityMetadata $metadata The entity metadata
     */
    private function populateEntityFields(object $entity, EntityMetadata $metadata): void
    {
        $reflectionClass = new \ReflectionClass($entity);
        
        foreach ($metadata->getFields() as $fieldName => $fieldMetadata) {
            try {
                // Generate value based on whether field is unique
                if ($fieldMetadata->isUnique()) {
                    $value = $this->dataGenerator->generateUniqueValue(
                        $fieldMetadata,
                        $metadata->getClassName()
                    );
                } else {
                    $value = $this->dataGenerator->generateValueForField(
                        $fieldMetadata,
                        $metadata->getClassName()
                    );
                }

                // Set the field value using reflection
                $this->setFieldValue($entity, $fieldName, $value, $reflectionClass);

            } catch (\Exception $e) {
                // Log error but continue with other fields
                error_log(sprintf(
                    'Error generating value for field %s in entity %s: %s',
                    $fieldName,
                    $metadata->getClassName(),
                    $e->getMessage()
                ));
                
                // For required fields, set a default value to avoid validation errors
                if (!$fieldMetadata->isNullable()) {
                    $defaultValue = $this->getDefaultValueForType($fieldMetadata->getType());
                    $this->setFieldValue($entity, $fieldName, $defaultValue, $reflectionClass);
                }
            }
        }
    }

    /**
     * Populate entity relationships.
     * 
     * This method handles all types of relationships (ManyToOne, OneToOne,
     * OneToMany, ManyToMany) using the RelationshipHandler. It creates
     * missing dependencies when needed.
     * 
     * @param object $entity The entity instance to populate
     * @param EntityMetadata $metadata The entity metadata
     */
    private function populateEntityRelationships(object $entity, EntityMetadata $metadata): void
    {
        $reflectionClass = new \ReflectionClass($entity);
        
        foreach ($metadata->getRelationships() as $fieldName => $relationshipMetadata) {
            try {
                $relatedEntity = null;
                
                // Get existing entities of the target type for relationship references
                $targetEntity = $relationshipMetadata->getTargetEntity();
                $existingEntities = $this->persistenceManager->getPersistedEntities($targetEntity);

                switch ($relationshipMetadata->getType()) {
                    case 'ManyToOne':
                        $relatedEntity = $this->relationshipHandler->handleManyToOne(
                            $relationshipMetadata,
                            $existingEntities
                        );
                        
                        // If no related entity exists and relationship is required, create one
                        if ($relatedEntity === null && !$relationshipMetadata->isNullable()) {
                            $relatedEntity = $this->createMissingDependency($targetEntity);
                        }
                        
                        $this->setFieldValue($entity, $fieldName, $relatedEntity, $reflectionClass);
                        break;

                    case 'OneToOne':
                        $relatedEntity = $this->relationshipHandler->handleOneToOne($relationshipMetadata);
                        
                        // If null is returned, create a new unique entity
                        if ($relatedEntity === null && !$relationshipMetadata->isNullable()) {
                            $relatedEntity = $this->createMissingDependency($targetEntity);
                        }
                        
                        $this->setFieldValue($entity, $fieldName, $relatedEntity, $reflectionClass);
                        break;

                    case 'OneToMany':
                        // OneToMany relationships are typically managed by the inverse side
                        // We skip them here as they will be populated when the related entities are seeded
                        break;

                    case 'ManyToMany':
                        $relatedEntities = $this->relationshipHandler->handleManyToMany(
                            $relationshipMetadata
                        );
                        
                        // Set the collection of related entities
                        if (!empty($relatedEntities)) {
                            $this->setFieldValue($entity, $fieldName, $relatedEntities, $reflectionClass);
                        }
                        break;
                }

            } catch (\Exception $e) {
                // Log error but continue with other relationships
                error_log(sprintf(
                    'Error populating relationship %s in entity %s: %s',
                    $fieldName,
                    $metadata->getClassName(),
                    $e->getMessage()
                ));
            }
        }
    }

    /**
     * Create a missing dependency entity.
     * 
     * When a required relationship has no existing entities, this method
     * creates a new entity of the target type to satisfy the dependency.
     * 
     * @param string $entityClass The fully qualified class name of the entity to create
     * @return object|null The created entity or null if creation fails
     */
    private function createMissingDependency(string $entityClass): ?object
    {
        try {
            // Get metadata for the dependency entity
            $metadata = $this->entityAnalyzer->getEntityMetadata($entityClass);
            
            // Create and populate the entity
            $entity = $this->createEntityInstance($metadata);
            $this->populateEntityFields($entity, $metadata);
            
            // Note: We don't populate relationships for dependencies to avoid infinite recursion
            // The dependency's relationships will be null or empty
            
            // Validate the entity
            $violations = $this->validator->validate($entity);
            
            if (count($violations) > 0) {
                // If validation fails, return null
                return null;
            }
            
            // Persist the dependency
            $this->persistenceManager->persistEntity($entity);
            
            return $entity;

        } catch (\Exception $e) {
            error_log(sprintf(
                'Error creating missing dependency %s: %s',
                $entityClass,
                $e->getMessage()
            ));
            
            return null;
        }
    }

    /**
     * Set a field value on an entity using reflection.
     * 
     * This method tries multiple approaches to set a field value:
     * 1. Use a setter method if available
     * 2. Set the property directly if accessible
     * 3. Use reflection to set private/protected properties
     * 
     * @param object $entity The entity instance
     * @param string $fieldName The field name
     * @param mixed $value The value to set
     * @param \ReflectionClass $reflectionClass The reflection class for the entity
     */
    private function setFieldValue(
        object $entity,
        string $fieldName,
        mixed $value,
        \ReflectionClass $reflectionClass
    ): void {
        // Try setter method first (e.g., setFieldName)
        $setterMethod = 'set' . ucfirst($fieldName);
        
        if ($reflectionClass->hasMethod($setterMethod)) {
            $method = $reflectionClass->getMethod($setterMethod);
            if ($method->isPublic()) {
                // Check if the setter accepts null when value is null
                if ($value === null) {
                    $parameters = $method->getParameters();
                    if (!empty($parameters)) {
                        $firstParam = $parameters[0];
                        $paramType = $firstParam->getType();
                        
                        // If parameter doesn't allow null, skip setting this field
                        if ($paramType && !$paramType->allowsNull()) {
                            return;
                        }
                    }
                }
                
                $entity->$setterMethod($value);
                return;
            }
        }

        // Try to set property directly
        if ($reflectionClass->hasProperty($fieldName)) {
            $property = $reflectionClass->getProperty($fieldName);
            $property->setValue($entity, $value);
            return;
        }

        // If we get here, we couldn't set the field
        error_log(sprintf(
            'Unable to set field %s on entity %s',
            $fieldName,
            get_class($entity)
        ));
    }

    /**
     * Get a default value for a field type.
     * 
     * Used as a fallback when value generation fails for required fields.
     * 
     * @param string $type The field type
     * @return mixed A default value for the type
     */
    private function getDefaultValueForType(string $type): mixed
    {
        return match ($type) {
            'string' => 'default',
            'integer', 'smallint', 'bigint' => 0,
            'boolean' => false,
            'datetime', 'datetime_immutable', 'date', 'time' => new \DateTime(),
            'text' => 'Default text',
            'decimal', 'float' => 0.0,
            'json' => json_encode([]),
            default => null,
        };
    }
}
