<?php

namespace App\Tests\Service\Seeding;

use App\Service\Seeding\RelationshipMetadata;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RelationshipMetadata class.
 */
class RelationshipMetadataTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $relationship = new RelationshipMetadata(
            fieldName: 'user',
            type: 'ManyToOne',
            targetEntity: 'App\\Entity\\User',
            nullable: false
        );

        $this->assertSame('user', $relationship->getFieldName());
        $this->assertSame('ManyToOne', $relationship->getType());
        $this->assertSame('App\\Entity\\User', $relationship->getTargetEntity());
        $this->assertFalse($relationship->isNullable());
    }

    public function testDefaultNullableValue(): void
    {
        $relationship = new RelationshipMetadata(
            fieldName: 'entreprise',
            type: 'ManyToOne',
            targetEntity: 'App\\Entity\\Entreprise'
        );

        $this->assertSame('entreprise', $relationship->getFieldName());
        $this->assertSame('ManyToOne', $relationship->getType());
        $this->assertSame('App\\Entity\\Entreprise', $relationship->getTargetEntity());
        $this->assertFalse($relationship->isNullable());
    }

    public function testNullableRelationship(): void
    {
        $relationship = new RelationshipMetadata(
            fieldName: 'manager',
            type: 'ManyToOne',
            targetEntity: 'App\\Entity\\User',
            nullable: true
        );

        $this->assertTrue($relationship->isNullable());
        $this->assertSame('manager', $relationship->getFieldName());
    }

    public function testOneToOneRelationship(): void
    {
        $relationship = new RelationshipMetadata(
            fieldName: 'profile',
            type: 'OneToOne',
            targetEntity: 'App\\Entity\\Profile',
            nullable: false
        );

        $this->assertSame('OneToOne', $relationship->getType());
        $this->assertSame('profile', $relationship->getFieldName());
        $this->assertSame('App\\Entity\\Profile', $relationship->getTargetEntity());
    }

    public function testOneToManyRelationship(): void
    {
        $relationship = new RelationshipMetadata(
            fieldName: 'locataires',
            type: 'OneToMany',
            targetEntity: 'App\\Entity\\Locataire',
            nullable: true
        );

        $this->assertSame('OneToMany', $relationship->getType());
        $this->assertSame('locataires', $relationship->getFieldName());
        $this->assertSame('App\\Entity\\Locataire', $relationship->getTargetEntity());
        $this->assertTrue($relationship->isNullable());
    }

    public function testManyToManyRelationship(): void
    {
        $relationship = new RelationshipMetadata(
            fieldName: 'roles',
            type: 'ManyToMany',
            targetEntity: 'App\\Entity\\Role',
            nullable: false
        );

        $this->assertSame('ManyToMany', $relationship->getType());
        $this->assertSame('roles', $relationship->getFieldName());
        $this->assertSame('App\\Entity\\Role', $relationship->getTargetEntity());
        $this->assertFalse($relationship->isNullable());
    }

    public function testGroupeEntityReference(): void
    {
        $relationship = new RelationshipMetadata(
            fieldName: 'groupe',
            type: 'ManyToOne',
            targetEntity: 'App\\Entity\\Groupe',
            nullable: false
        );

        $this->assertSame('groupe', $relationship->getFieldName());
        $this->assertSame('App\\Entity\\Groupe', $relationship->getTargetEntity());
        $this->assertFalse($relationship->isNullable());
    }
}
