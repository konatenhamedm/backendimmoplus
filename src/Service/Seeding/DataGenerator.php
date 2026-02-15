<?php

namespace App\Service\Seeding;

use Faker\Generator;

/**
 * Generates realistic fake data for entity fields using Faker library.
 * 
 * This service generates type-appropriate fake data based on field metadata,
 * respects field constraints (length, nullable, unique), and uses smart
 * field name heuristics to generate contextually appropriate data.
 */
class DataGenerator
{
    /**
     * Cache for generated unique values to avoid duplicates.
     * Format: ['EntityClass::fieldName' => [value1, value2, ...]]
     */
    private array $uniqueValuesCache = [];

    /**
     * @param Generator $faker Faker generator instance
     */
    public function __construct(
        private Generator $faker
    ) {
    }

    /**
     * Generate a value for a field based on its metadata.
     * 
     * This is the main dispatcher method that:
     * 1. Handles nullable fields (20% chance of null)
     * 2. Tries field name heuristics first
     * 3. Falls back to type-based generation
     * 
     * @param FieldMetadata $field The field metadata
     * @param string|null $entityClass The entity class name (for unique value tracking)
     * @return mixed The generated value
     */
    public function generateValueForField(FieldMetadata $field, ?string $entityClass = null): mixed
    {
        // Handle nullable fields - 20% chance of null
        if ($field->isNullable() && $this->faker->boolean(20)) {
            return null;
        }

        // Try field name heuristics first
        $value = $this->generateByFieldName($field->getName(), $field);
        
        // Fall back to type-based generation if no pattern matched
        if ($value === null) {
            $value = $this->generateByType($field->getType(), $field);
        }

        return $value;
    }

    /**
     * Generate a unique value for a field.
     * 
     * Attempts to generate a unique value up to 100 times.
     * Caches generated values to ensure uniqueness.
     * 
     * @param FieldMetadata $field The field metadata
     * @param string $entityClass The entity class name
     * @return mixed The generated unique value
     * @throws \RuntimeException If unable to generate unique value after 100 attempts
     */
    public function generateUniqueValue(FieldMetadata $field, string $entityClass): mixed
    {
        $cacheKey = $entityClass . '::' . $field->getName();
        
        if (!isset($this->uniqueValuesCache[$cacheKey])) {
            $this->uniqueValuesCache[$cacheKey] = [];
        }

        $attempts = 0;
        $maxAttempts = 100;

        while ($attempts < $maxAttempts) {
            // Generate value (never null for unique fields)
            $value = $this->generateByFieldName($field->getName(), $field);
            if ($value === null) {
                $value = $this->generateByType($field->getType(), $field);
            }

            // Check if value is unique
            if (!in_array($value, $this->uniqueValuesCache[$cacheKey], true)) {
                $this->uniqueValuesCache[$cacheKey][] = $value;
                return $value;
            }

            $attempts++;
        }

        throw new \RuntimeException(
            sprintf(
                'Unable to generate unique value for field "%s" in entity "%s" after %d attempts',
                $field->getName(),
                $entityClass,
                $maxAttempts
            )
        );
    }

    /**
     * Generate a value based on field type.
     * 
     * Handles basic Doctrine types:
     * - string: Random text
     * - integer: Random integer
     * - boolean: Random true/false
     * - datetime/datetime_immutable: Random date
     * - text: Longer text content
     * - decimal/float: Random decimal number
     * 
     * @param string $type The field type
     * @param FieldMetadata $field The field metadata for constraints
     * @return mixed The generated value
     */
    private function generateByType(string $type, FieldMetadata $field): mixed
    {
        return match ($type) {
            'string' => $this->generateString($field),
            'integer', 'smallint', 'bigint' => $this->faker->numberBetween(1, 1000000),
            'boolean' => $this->faker->boolean(),
            'datetime', 'date', 'time' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'datetime_immutable' => \DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween('-2 years', 'now')),
            'text' => $this->faker->paragraphs(3, true),
            'decimal', 'float' => $this->faker->randomFloat(2, 0, 999999.99),
            'json' => [
                'key1' => $this->faker->word(),
                'key2' => $this->faker->numberBetween(1, 100),
            ],
            default => $this->faker->word(),
        };
    }

    /**
     * Generate a value based on field name heuristics.
     * 
     * Detects common field name patterns and generates appropriate data:
     * - email: Valid email addresses
     * - phone/mobile/contact: Phone numbers
     * - nom/name: Last names
     * - prenom/firstname: First names
     * - ville/city: City names
     * - adresse/address: Addresses
     * - code: Alphanumeric codes
     * - description: Sentences
     * - url/web/site: URLs
     * 
     * @param string $fieldName The field name
     * @param FieldMetadata $field The field metadata for constraints
     * @return mixed|null The generated value or null if no pattern matches
     */
    private function generateByFieldName(string $fieldName, FieldMetadata $field): mixed
    {
        $lowerName = strtolower($fieldName);

        // Roles detection (for User entity)
        if ($lowerName === 'roles') {
            return ['ROLE_USER']; // Return array of roles
        }

        // Email detection
        if (str_contains($lowerName, 'email') || str_contains($lowerName, 'mail')) {
            return $this->faker->email();
        }

        // Phone detection
        if (str_contains($lowerName, 'phone') || 
            str_contains($lowerName, 'mobile') || 
            str_contains($lowerName, 'telephone') ||
            str_contains($lowerName, 'contact')) {
            return $this->faker->phoneNumber();
        }

        // Name detection (last name)
        if (str_contains($lowerName, 'nom') || 
            (str_contains($lowerName, 'name') && !str_contains($lowerName, 'first') && !str_contains($lowerName, 'prenom'))) {
            return $this->faker->lastName();
        }

        // First name detection
        if (str_contains($lowerName, 'prenom') || str_contains($lowerName, 'firstname')) {
            return $this->faker->firstName();
        }

        // City detection
        if (str_contains($lowerName, 'ville') || str_contains($lowerName, 'city')) {
            return $this->faker->city();
        }

        // Address detection
        if (str_contains($lowerName, 'adresse') || str_contains($lowerName, 'address')) {
            return $this->truncateToLength($this->faker->address(), $field->getLength());
        }

        // Code detection
        if (str_contains($lowerName, 'code')) {
            $length = min($field->getLength() ?? 6, 10);
            return $this->faker->regexify('[A-Z0-9]{' . $length . '}');
        }

        // Description detection
        if (str_contains($lowerName, 'description') || str_contains($lowerName, 'desc')) {
            return $this->truncateToLength($this->faker->sentence(), $field->getLength());
        }

        // URL detection
        if (str_contains($lowerName, 'url') || 
            str_contains($lowerName, 'web') || 
            str_contains($lowerName, 'site')) {
            return $this->faker->url();
        }

        // No pattern matched
        return null;
    }

    /**
     * Generate a string value respecting length constraints.
     * 
     * @param FieldMetadata $field The field metadata
     * @return string The generated string
     */
    private function generateString(FieldMetadata $field): string
    {
        $length = $field->getLength();
        
        if ($length === null) {
            return $this->faker->text(100);
        }

        // For very short strings, use words
        if ($length <= 10) {
            return $this->faker->lexify(str_repeat('?', min($length, 8)));
        }

        // For medium strings, use words
        if ($length <= 50) {
            return $this->truncateToLength($this->faker->words(3, true), $length);
        }

        // For longer strings, use sentences
        return $this->truncateToLength($this->faker->sentence(), $length);
    }

    /**
     * Truncate a string to fit within a maximum length.
     * 
     * @param string $value The string to truncate
     * @param int|null $maxLength The maximum length, or null for no limit
     * @return string The truncated string
     */
    private function truncateToLength(string $value, ?int $maxLength): string
    {
        if ($maxLength === null) {
            return $value;
        }

        if (strlen($value) <= $maxLength) {
            return $value;
        }

        return substr($value, 0, $maxLength);
    }

    /**
     * Clear the unique values cache.
     * Useful for testing or when starting a new seeding operation.
     */
    public function clearUniqueValuesCache(): void
    {
        $this->uniqueValuesCache = [];
    }
}
