<?php

namespace App\Service\Seeding;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Discovers and analyzes Doctrine entities to extract metadata needed for seeding.
 * 
 * This service scans the Entity directory, identifies all Doctrine entities,
 * and extracts their metadata including fields, relationships, and constraints.
 * It filters out excluded entities (like Groupe) from the seeding process.
 */
class EntityAnalyzer
{
    /**
     * @param EntityManagerInterface $entityManager The Doctrine entity manager
     * @param string $entityNamespace The namespace where entities are located
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private string $entityNamespace = 'App\\Entity'
    ) {
    }

    /**
     * Discover all Doctrine entities in the configured namespace.
     * 
     * This method scans the Entity directory and returns all entity class names
     * except for explicitly excluded entities (Groupe).
     * 
     * @return array<string> Array of fully qualified entity class names
     */
    public function discoverEntities(): array
    {
        // Get all metadata from Doctrine's entity manager
        $allMetadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        
        $entities = [];
        
        foreach ($allMetadata as $metadata) {
            $className = $metadata->getName();
            
            // Filter out excluded entities
            if ($this->isExcludedEntity($className)) {
                continue;
            }
            
            $entities[] = $className;
        }
        
        return $entities;
    }

    /**
     * Get complete metadata for a specific entity.
     * 
     * This method extracts all relevant information about an entity including
     * its fields, relationships, constraints, and validation rules.
     * 
     * @param string $entityClass The fully qualified entity class name
     * @return EntityMetadata The complete entity metadata
     */
    public function getEntityMetadata(string $entityClass): EntityMetadata
    {
        $doctrineMetadata = $this->entityManager->getClassMetadata($entityClass);
        
        $fields = $this->extractFieldMetadata($doctrineMetadata);
        $relationships = $this->extractRelationships($doctrineMetadata);
        
        // Extract unique and required fields
        $uniqueFields = [];
        $requiredFields = [];
        
        foreach ($fields as $fieldName => $fieldMetadata) {
            if ($fieldMetadata->isUnique()) {
                $uniqueFields[] = $fieldName;
            }
            if (!$fieldMetadata->isNullable()) {
                $requiredFields[] = $fieldName;
            }
        }
        
        return new EntityMetadata(
            className: $entityClass,
            tableName: $doctrineMetadata->getTableName(),
            fields: $fields,
            relationships: $relationships,
            uniqueFields: $uniqueFields,
            requiredFields: $requiredFields
        );
    }

    /**
     * Check if an entity should be excluded from seeding.
     * 
     * @param string $entityClass The fully qualified entity class name
     * @return bool True if the entity should be excluded, false otherwise
     */
    public function isExcludedEntity(string $entityClass): bool
    {
        // Extract the short class name (without namespace)
        $shortClassName = substr($entityClass, strrpos($entityClass, '\\') + 1);
        
        // List of excluded entities
        $excludedEntities = ['Groupe'];
        
        return in_array($shortClassName, $excludedEntities, true);
    }

    /**
     * Extract field metadata from Doctrine class metadata.
     * 
     * @param ClassMetadata $metadata The Doctrine class metadata
     * @return array<string, FieldMetadata> Array of field metadata indexed by field name
     */
    private function extractFieldMetadata(ClassMetadata $metadata): array
    {
        $fields = [];
        
        foreach ($metadata->getFieldNames() as $fieldName) {
            // Skip identifier fields (they are auto-generated)
            if (in_array($fieldName, $metadata->getIdentifierFieldNames(), true)) {
                continue;
            }
            
            $fieldMapping = $metadata->getFieldMapping($fieldName);
            
            $fields[$fieldName] = new FieldMetadata(
                name: $fieldName,
                type: $fieldMapping->type,
                length: $fieldMapping->length ?? null,
                nullable: $fieldMapping->nullable ?? false,
                unique: $fieldMapping->unique ?? false,
                constraints: []
            );
        }
        
        return $fields;
    }

    /**
     * Extract relationship metadata from Doctrine class metadata.
     * 
     * @param ClassMetadata $metadata The Doctrine class metadata
     * @return array<string, RelationshipMetadata> Array of relationship metadata indexed by field name
     */
    private function extractRelationships(ClassMetadata $metadata): array
    {
        $relationships = [];
        
        // Extract association mappings (relationships)
        foreach ($metadata->getAssociationNames() as $associationName) {
            $associationMapping = $metadata->getAssociationMapping($associationName);
            
            // Determine relationship type based on the class type
            $mappingClass = get_class($associationMapping);
            $type = match (true) {
                str_contains($mappingClass, 'ManyToOne') => 'ManyToOne',
                str_contains($mappingClass, 'OneToOne') => 'OneToOne',
                str_contains($mappingClass, 'OneToMany') => 'OneToMany',
                str_contains($mappingClass, 'ManyToMany') => 'ManyToMany',
                default => 'Unknown'
            };
            
            // Get the target entity class
            $targetEntity = $associationMapping->targetEntity;
            
            // Determine if the relationship is nullable
            $nullable = true; // Default to true for relationships
            
            // Check join columns for nullable property (for owning side relationships)
            if (property_exists($associationMapping, 'joinColumns') && !empty($associationMapping->joinColumns)) {
                foreach ($associationMapping->joinColumns as $joinColumn) {
                    if (property_exists($joinColumn, 'nullable') && $joinColumn->nullable !== null) {
                        $nullable = $joinColumn->nullable;
                        break;
                    }
                }
            }
            
            $relationships[$associationName] = new RelationshipMetadata(
                fieldName: $associationName,
                type: $type,
                targetEntity: $targetEntity,
                nullable: $nullable
            );
        }
        
        return $relationships;
    }
}
