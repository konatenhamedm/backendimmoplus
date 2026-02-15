<?php

namespace App\Service\Seeding;

/**
 * Represents complete metadata for a Doctrine entity.
 * 
 * This class aggregates all information about an entity including its fields,
 * relationships, constraints, and validation rules. It provides helper methods
 * to query entity metadata during the seeding process.
 */
class EntityMetadata
{
    /**
     * @param string $className The fully qualified class name of the entity
     * @param string $tableName The database table name
     * @param array<string, FieldMetadata> $fields Array of field metadata indexed by field name
     * @param array<string, RelationshipMetadata> $relationships Array of relationship metadata indexed by field name
     * @param array<string> $uniqueFields Array of field names that have unique constraints
     * @param array<string> $requiredFields Array of field names that are required (not nullable)
     */
    public function __construct(
        private string $className,
        private string $tableName,
        private array $fields = [],
        private array $relationships = [],
        private array $uniqueFields = [],
        private array $requiredFields = []
    ) {
    }

    /**
     * Get the entity class name.
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * Get the database table name.
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * Get all field metadata.
     * 
     * @return array<string, FieldMetadata>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Get all relationship metadata.
     * 
     * @return array<string, RelationshipMetadata>
     */
    public function getRelationships(): array
    {
        return $this->relationships;
    }

    /**
     * Get the list of unique field names.
     * 
     * @return array<string>
     */
    public function getUniqueFields(): array
    {
        return $this->uniqueFields;
    }

    /**
     * Get the list of required field names.
     * 
     * @return array<string>
     */
    public function getRequiredFields(): array
    {
        return $this->requiredFields;
    }

    /**
     * Get metadata for a specific field.
     * 
     * @param string $fieldName The name of the field
     * @return FieldMetadata|null The field metadata or null if field doesn't exist
     */
    public function getField(string $fieldName): ?FieldMetadata
    {
        return $this->fields[$fieldName] ?? null;
    }

    /**
     * Get metadata for a specific relationship.
     * 
     * @param string $fieldName The name of the relationship field
     * @return RelationshipMetadata|null The relationship metadata or null if relationship doesn't exist
     */
    public function getRelationship(string $fieldName): ?RelationshipMetadata
    {
        return $this->relationships[$fieldName] ?? null;
    }

    /**
     * Check if the entity has any unique fields.
     * 
     * @return bool True if the entity has at least one unique field, false otherwise
     */
    public function hasUniqueFields(): bool
    {
        return !empty($this->uniqueFields);
    }
}
