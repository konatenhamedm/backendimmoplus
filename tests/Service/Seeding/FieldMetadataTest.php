<?php

namespace App\Tests\Service\Seeding;

use App\Service\Seeding\FieldMetadata;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FieldMetadata class.
 */
class FieldMetadataTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $field = new FieldMetadata(
            name: 'email',
            type: 'string',
            length: 255,
            nullable: false,
            unique: true,
            constraints: ['email' => true]
        );

        $this->assertSame('email', $field->getName());
        $this->assertSame('string', $field->getType());
        $this->assertSame(255, $field->getLength());
        $this->assertFalse($field->isNullable());
        $this->assertTrue($field->isUnique());
        $this->assertSame(['email' => true], $field->getConstraints());
    }

    public function testDefaultValues(): void
    {
        $field = new FieldMetadata(
            name: 'id',
            type: 'integer'
        );

        $this->assertSame('id', $field->getName());
        $this->assertSame('integer', $field->getType());
        $this->assertNull($field->getLength());
        $this->assertFalse($field->isNullable());
        $this->assertFalse($field->isUnique());
        $this->assertSame([], $field->getConstraints());
    }

    public function testNullableField(): void
    {
        $field = new FieldMetadata(
            name: 'description',
            type: 'text',
            nullable: true
        );

        $this->assertTrue($field->isNullable());
        $this->assertFalse($field->isUnique());
    }

    public function testFieldWithConstraints(): void
    {
        $constraints = [
            'min' => 1,
            'max' => 100,
            'pattern' => '/^[A-Z]+$/'
        ];

        $field = new FieldMetadata(
            name: 'code',
            type: 'string',
            length: 10,
            unique: true,
            constraints: $constraints
        );

        $this->assertSame($constraints, $field->getConstraints());
        $this->assertTrue($field->isUnique());
        $this->assertSame(10, $field->getLength());
    }

    public function testBooleanField(): void
    {
        $field = new FieldMetadata(
            name: 'isActive',
            type: 'boolean',
            nullable: false
        );

        $this->assertSame('boolean', $field->getType());
        $this->assertNull($field->getLength());
        $this->assertFalse($field->isNullable());
    }

    public function testDateTimeField(): void
    {
        $field = new FieldMetadata(
            name: 'createdAt',
            type: 'datetime',
            nullable: false
        );

        $this->assertSame('datetime', $field->getType());
        $this->assertNull($field->getLength());
    }
}
