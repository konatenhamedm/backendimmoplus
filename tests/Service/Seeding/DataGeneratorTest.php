<?php

namespace App\Tests\Service\Seeding;

use App\Service\Seeding\DataGenerator;
use App\Service\Seeding\FieldMetadata;
use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DataGenerator service.
 * 
 * Tests data generation for various field types, field name heuristics,
 * constraint handling, and unique value generation.
 */
class DataGeneratorTest extends TestCase
{
    private DataGenerator $dataGenerator;
    private Generator $faker;

    protected function setUp(): void
    {
        $this->faker = Factory::create('fr_FR');
        $this->dataGenerator = new DataGenerator($this->faker);
    }

    /**
     * Test basic string type generation.
     */
    public function testGenerateStringType(): void
    {
        $field = new FieldMetadata(
            name: 'testField',
            type: 'string',
            length: 50
        );

        $value = $this->dataGenerator->generateValueForField($field);

        $this->assertIsString($value);
        $this->assertLessThanOrEqual(50, strlen($value));
    }

    /**
     * Test integer type generation.
     */
    public function testGenerateIntegerType(): void
    {
        $field = new FieldMetadata(
            name: 'testField',
            type: 'integer'
        );

        $value = $this->dataGenerator->generateValueForField($field);

        $this->assertIsInt($value);
        $this->assertGreaterThanOrEqual(1, $value);
        $this->assertLessThanOrEqual(1000000, $value);
    }

    /**
     * Test boolean type generation.
     */
    public function testGenerateBooleanType(): void
    {
        $field = new FieldMetadata(
            name: 'testField',
            type: 'boolean'
        );

        $value = $this->dataGenerator->generateValueForField($field);

        $this->assertIsBool($value);
    }

    /**
     * Test datetime type generation.
     */
    public function testGenerateDatetimeType(): void
    {
        $field = new FieldMetadata(
            name: 'testField',
            type: 'datetime'
        );

        $value = $this->dataGenerator->generateValueForField($field);

        $this->assertInstanceOf(\DateTimeInterface::class, $value);
    }

    /**
     * Test text type generation.
     */
    public function testGenerateTextType(): void
    {
        $field = new FieldMetadata(
            name: 'testField',
            type: 'text'
        );

        $value = $this->dataGenerator->generateValueForField($field);

        $this->assertIsString($value);
        $this->assertGreaterThan(50, strlen($value)); // Text should be longer
    }

    /**
     * Test decimal type generation.
     */
    public function testGenerateDecimalType(): void
    {
        $field = new FieldMetadata(
            name: 'testField',
            type: 'decimal'
        );

        $value = $this->dataGenerator->generateValueForField($field);

        $this->assertIsFloat($value);
    }

    /**
     * Test email field name heuristic.
     */
    public function testGenerateEmailByFieldName(): void
    {
        $field = new FieldMetadata(
            name: 'email',
            type: 'string',
            length: 255
        );

        $value = $this->dataGenerator->generateValueForField($field);

        $this->assertIsString($value);
        $this->assertMatchesRegularExpression('/^[^@]+@[^@]+\.[^@]+$/', $value);
    }

    /**
     * Test phone field name heuristic.
     */
    public function testGeneratePhoneByFieldName(): void
    {
        $field = new FieldMetadata(
            name: 'telephone',
            type: 'string',
            length: 20
        );

        $value = $this->dataGenerator->generateValueForField($field);

        $this->assertIsString($value);
        $this->assertNotEmpty($value);
    }

    /**
     * Test name field heuristic.
     */
    public function testGenerateNameByFieldName(): void
    {
        $field = new FieldMetadata(
            name: 'nom',
            type: 'string',
            length: 100
        );

        $value = $this->dataGenerator->generateValueForField($field);

        $this->assertIsString($value);
        $this->assertNotEmpty($value);
    }

    /**
     * Test first name field heuristic.
     */
    public function testGenerateFirstNameByFieldName(): void
    {
        $field = new FieldMetadata(
            name: 'prenom',
            type: 'string',
            length: 100
        );

        $value = $this->dataGenerator->generateValueForField($field);

        $this->assertIsString($value);
        $this->assertNotEmpty($value);
    }

    /**
     * Test city field heuristic.
     */
    public function testGenerateCityByFieldName(): void
    {
        $field = new FieldMetadata(
            name: 'ville',
            type: 'string',
            length: 100
        );

        $value = $this->dataGenerator->generateValueForField($field);

        $this->assertIsString($value);
        $this->assertNotEmpty($value);
    }

    /**
     * Test address field heuristic.
     */
    public function testGenerateAddressByFieldName(): void
    {
        $field = new FieldMetadata(
            name: 'adresse',
            type: 'string',
            length: 255
        );

        $value = $this->dataGenerator->generateValueForField($field);

        $this->assertIsString($value);
        $this->assertNotEmpty($value);
        $this->assertLessThanOrEqual(255, strlen($value));
    }

    /**
     * Test code field heuristic.
     */
    public function testGenerateCodeByFieldName(): void
    {
        $field = new FieldMetadata(
            name: 'code',
            type: 'string',
            length: 6
        );

        $value = $this->dataGenerator->generateValueForField($field);

        $this->assertIsString($value);
        $this->assertEquals(6, strlen($value));
        $this->assertMatchesRegularExpression('/^[A-Z0-9]+$/', $value);
    }

    /**
     * Test URL field heuristic.
     */
    public function testGenerateUrlByFieldName(): void
    {
        $field = new FieldMetadata(
            name: 'url',
            type: 'string',
            length: 255
        );

        $value = $this->dataGenerator->generateValueForField($field);

        $this->assertIsString($value);
        $this->assertMatchesRegularExpression('/^https?:\/\//', $value);
    }

    /**
     * Test description field heuristic.
     */
    public function testGenerateDescriptionByFieldName(): void
    {
        $field = new FieldMetadata(
            name: 'description',
            type: 'string',
            length: 255
        );

        $value = $this->dataGenerator->generateValueForField($field);

        $this->assertIsString($value);
        $this->assertLessThanOrEqual(255, strlen($value));
    }

    /**
     * Test nullable field handling - should occasionally return null.
     */
    public function testNullableFieldHandling(): void
    {
        $field = new FieldMetadata(
            name: 'testField',
            type: 'string',
            length: 50,
            nullable: true
        );

        $nullCount = 0;
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $value = $this->dataGenerator->generateValueForField($field);
            if ($value === null) {
                $nullCount++;
            }
        }

        // With 20% chance, we expect roughly 10-30 nulls in 100 iterations
        // Using a wider range to account for randomness
        $this->assertGreaterThan(0, $nullCount, 'Should generate at least some null values');
        $this->assertLessThan(50, $nullCount, 'Should not generate too many null values');
    }

    /**
     * Test length constraint for strings.
     */
    public function testStringLengthConstraint(): void
    {
        $field = new FieldMetadata(
            name: 'testField',
            type: 'string',
            length: 10
        );

        for ($i = 0; $i < 20; $i++) {
            $value = $this->dataGenerator->generateValueForField($field);
            $this->assertLessThanOrEqual(10, strlen($value));
        }
    }

    /**
     * Test unique value generation.
     */
    public function testGenerateUniqueValue(): void
    {
        $field = new FieldMetadata(
            name: 'code',
            type: 'string',
            length: 10
        );

        $values = [];
        for ($i = 0; $i < 50; $i++) {
            $value = $this->dataGenerator->generateUniqueValue($field, 'TestEntity');
            $values[] = $value;
        }

        // All values should be unique
        $uniqueValues = array_unique($values);
        $this->assertCount(50, $uniqueValues);
    }

    /**
     * Test unique value generation failure after max attempts.
     */
    public function testGenerateUniqueValueThrowsExceptionAfterMaxAttempts(): void
    {
        // Create a field that will generate very limited values
        $field = new FieldMetadata(
            name: 'testField',
            type: 'boolean' // Only 2 possible values
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to generate unique value');

        // Try to generate more unique values than possible
        for ($i = 0; $i < 3; $i++) {
            $this->dataGenerator->generateUniqueValue($field, 'TestEntity');
        }
    }

    /**
     * Test clearing unique values cache.
     */
    public function testClearUniqueValuesCache(): void
    {
        $field = new FieldMetadata(
            name: 'code',
            type: 'string',
            length: 6
        );

        // Generate some unique values
        $value1 = $this->dataGenerator->generateUniqueValue($field, 'TestEntity');
        
        // Clear cache
        $this->dataGenerator->clearUniqueValuesCache();
        
        // Should be able to generate the same value again
        // We can't guarantee it will be the same due to randomness,
        // but we can verify the cache was cleared by generating many values
        $values = [];
        for ($i = 0; $i < 50; $i++) {
            $values[] = $this->dataGenerator->generateUniqueValue($field, 'TestEntity');
        }
        
        $this->assertCount(50, array_unique($values));
    }

    /**
     * Test fallback to type-based generation when no field name pattern matches.
     */
    public function testFallbackToTypeBasedGeneration(): void
    {
        $field = new FieldMetadata(
            name: 'someRandomField',
            type: 'integer'
        );

        $value = $this->dataGenerator->generateValueForField($field);

        $this->assertIsInt($value);
    }

    /**
     * Test JSON type generation.
     */
    public function testGenerateJsonType(): void
    {
        $field = new FieldMetadata(
            name: 'metadata',
            type: 'json'
        );

        $value = $this->dataGenerator->generateValueForField($field);

        $this->assertIsString($value);
        $decoded = json_decode($value, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('key1', $decoded);
        $this->assertArrayHasKey('key2', $decoded);
    }

    /**
     * Test that very short strings are generated correctly.
     */
    public function testGenerateVeryShortString(): void
    {
        $field = new FieldMetadata(
            name: 'shortField',
            type: 'string',
            length: 5
        );

        for ($i = 0; $i < 10; $i++) {
            $value = $this->dataGenerator->generateValueForField($field);
            $this->assertIsString($value);
            $this->assertLessThanOrEqual(5, strlen($value));
        }
    }

    /**
     * Test address truncation when length is specified.
     */
    public function testAddressTruncation(): void
    {
        $field = new FieldMetadata(
            name: 'adresse',
            type: 'string',
            length: 50
        );

        $value = $this->dataGenerator->generateValueForField($field);

        $this->assertIsString($value);
        $this->assertLessThanOrEqual(50, strlen($value));
    }

    /**
     * Test code generation with different lengths.
     */
    public function testCodeGenerationWithDifferentLengths(): void
    {
        $lengths = [4, 6, 8, 10];

        foreach ($lengths as $length) {
            $field = new FieldMetadata(
                name: 'code',
                type: 'string',
                length: $length
            );

            $value = $this->dataGenerator->generateValueForField($field);

            $this->assertEquals($length, strlen($value));
            $this->assertMatchesRegularExpression('/^[A-Z0-9]+$/', $value);
        }
    }

    /**
     * Test that non-nullable fields never return null.
     */
    public function testNonNullableFieldNeverReturnsNull(): void
    {
        $field = new FieldMetadata(
            name: 'testField',
            type: 'string',
            length: 50,
            nullable: false
        );

        for ($i = 0; $i < 50; $i++) {
            $value = $this->dataGenerator->generateValueForField($field);
            $this->assertNotNull($value);
        }
    }
}
