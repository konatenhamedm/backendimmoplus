<?php

namespace App\Tests\Service\Seeding;

use App\Service\Seeding\EntityMetadata;
use App\Service\Seeding\FieldMetadata;
use App\Service\Seeding\RelationshipMetadata;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for EntityMetadata class.
 */
class EntityMetadataTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $fields = [
            'id' => new FieldMetadata('id', 'integer'),
            'email' => new FieldMetadata('email', 'string', 255, false, true),
        ];

        $relationships = [
            'user' => new RelationshipMetadata('user', 'ManyToOne', 'App\\Entity\\User', false),
        ];

        $uniqueFields = ['email'];
        $requiredFields = ['id', 'email'];

        $metadata = new EntityMetadata(
            className: 'App\\Entity\\Locataire',
            tableName: 'locataire',
            fields: $fields,
            relationships: $relationships,
            uniqueFields: $uniqueFields,
            requiredFields: $requiredFields
        );

        $this->assertSame('App\\Entity\\Locataire', $metadata->getClassName());
        $this->assertSame('locataire', $metadata->getTableName());
        $this->assertSame($fields, $metadata->getFields());
        $this->assertSame($relationships, $metadata->getRelationships());
        $this->assertSame($uniqueFields, $metadata->getUniqueFields());
        $this->assertSame($requiredFields, $metadata->getRequiredFields());
    }

    public function testDefaultValues(): void
    {
        $metadata = new EntityMetadata(
            className: 'App\\Entity\\User',
            tableName: 'user'
        );

        $this->assertSame('App\\Entity\\User', $metadata->getClassName());
        $this->assertSame('user', $metadata->getTableName());
        $this->assertSame([], $metadata->getFields());
        $this->assertSame([], $metadata->getRelationships());
        $this->assertSame([], $metadata->getUniqueFields());
        $this->assertSame([], $metadata->getRequiredFields());
    }

    public function testGetFieldReturnsCorrectField(): void
    {
        $emailField = new FieldMetadata('email', 'string', 255, false, true);
        $nameField = new FieldMetadata('name', 'string', 100);

        $fields = [
            'email' => $emailField,
            'name' => $nameField,
        ];

        $metadata = new EntityMetadata(
            className: 'App\\Entity\\User',
            tableName: 'user',
            fields: $fields
        );

        $this->assertSame($emailField, $metadata->getField('email'));
        $this->assertSame($nameField, $metadata->getField('name'));
    }

    public function testGetFieldReturnsNullForNonExistentField(): void
    {
        $fields = [
            'email' => new FieldMetadata('email', 'string', 255),
        ];

        $metadata = new EntityMetadata(
            className: 'App\\Entity\\User',
            tableName: 'user',
            fields: $fields
        );

        $this->assertNull($metadata->getField('nonexistent'));
        $this->assertNull($metadata->getField('name'));
    }

    public function testGetRelationshipReturnsCorrectRelationship(): void
    {
        $userRelation = new RelationshipMetadata('user', 'ManyToOne', 'App\\Entity\\User', false);
        $entrepriseRelation = new RelationshipMetadata('entreprise', 'ManyToOne', 'App\\Entity\\Entreprise', true);

        $relationships = [
            'user' => $userRelation,
            'entreprise' => $entrepriseRelation,
        ];

        $metadata = new EntityMetadata(
            className: 'App\\Entity\\Locataire',
            tableName: 'locataire',
            relationships: $relationships
        );

        $this->assertSame($userRelation, $metadata->getRelationship('user'));
        $this->assertSame($entrepriseRelation, $metadata->getRelationship('entreprise'));
    }

    public function testGetRelationshipReturnsNullForNonExistentRelationship(): void
    {
        $relationships = [
            'user' => new RelationshipMetadata('user', 'ManyToOne', 'App\\Entity\\User', false),
        ];

        $metadata = new EntityMetadata(
            className: 'App\\Entity\\Locataire',
            tableName: 'locataire',
            relationships: $relationships
        );

        $this->assertNull($metadata->getRelationship('nonexistent'));
        $this->assertNull($metadata->getRelationship('entreprise'));
    }

    public function testHasUniqueFieldsReturnsTrueWhenUniqueFieldsExist(): void
    {
        $metadata = new EntityMetadata(
            className: 'App\\Entity\\User',
            tableName: 'user',
            uniqueFields: ['email', 'username']
        );

        $this->assertTrue($metadata->hasUniqueFields());
    }

    public function testHasUniqueFieldsReturnsFalseWhenNoUniqueFields(): void
    {
        $metadata = new EntityMetadata(
            className: 'App\\Entity\\User',
            tableName: 'user',
            uniqueFields: []
        );

        $this->assertFalse($metadata->hasUniqueFields());
    }

    public function testHasUniqueFieldsReturnsFalseByDefault(): void
    {
        $metadata = new EntityMetadata(
            className: 'App\\Entity\\User',
            tableName: 'user'
        );

        $this->assertFalse($metadata->hasUniqueFields());
    }

    public function testEntityWithMultipleFieldsAndRelationships(): void
    {
        $fields = [
            'id' => new FieldMetadata('id', 'integer'),
            'email' => new FieldMetadata('email', 'string', 255, false, true),
            'name' => new FieldMetadata('name', 'string', 100, false),
            'description' => new FieldMetadata('description', 'text', null, true),
            'isActive' => new FieldMetadata('isActive', 'boolean', null, false),
        ];

        $relationships = [
            'user' => new RelationshipMetadata('user', 'ManyToOne', 'App\\Entity\\User', false),
            'entreprise' => new RelationshipMetadata('entreprise', 'ManyToOne', 'App\\Entity\\Entreprise', true),
            'locataires' => new RelationshipMetadata('locataires', 'OneToMany', 'App\\Entity\\Locataire', true),
        ];

        $uniqueFields = ['email'];
        $requiredFields = ['id', 'email', 'name', 'isActive'];

        $metadata = new EntityMetadata(
            className: 'App\\Entity\\Entreprise',
            tableName: 'entreprise',
            fields: $fields,
            relationships: $relationships,
            uniqueFields: $uniqueFields,
            requiredFields: $requiredFields
        );

        $this->assertCount(5, $metadata->getFields());
        $this->assertCount(3, $metadata->getRelationships());
        $this->assertCount(1, $metadata->getUniqueFields());
        $this->assertCount(4, $metadata->getRequiredFields());
        $this->assertTrue($metadata->hasUniqueFields());
    }

    public function testEntityWithNoFieldsOrRelationships(): void
    {
        $metadata = new EntityMetadata(
            className: 'App\\Entity\\EmptyEntity',
            tableName: 'empty_entity',
            fields: [],
            relationships: [],
            uniqueFields: [],
            requiredFields: []
        );

        $this->assertCount(0, $metadata->getFields());
        $this->assertCount(0, $metadata->getRelationships());
        $this->assertCount(0, $metadata->getUniqueFields());
        $this->assertCount(0, $metadata->getRequiredFields());
        $this->assertFalse($metadata->hasUniqueFields());
        $this->assertNull($metadata->getField('anyField'));
        $this->assertNull($metadata->getRelationship('anyRelation'));
    }

    public function testEntityWithGroupeRelationship(): void
    {
        $relationships = [
            'groupe' => new RelationshipMetadata('groupe', 'ManyToOne', 'App\\Entity\\Groupe', false),
        ];

        $metadata = new EntityMetadata(
            className: 'App\\Entity\\User',
            tableName: 'user',
            relationships: $relationships
        );

        $groupeRelation = $metadata->getRelationship('groupe');
        $this->assertNotNull($groupeRelation);
        $this->assertSame('App\\Entity\\Groupe', $groupeRelation->getTargetEntity());
        $this->assertSame('ManyToOne', $groupeRelation->getType());
    }

    public function testRequiredFieldsTracking(): void
    {
        $fields = [
            'id' => new FieldMetadata('id', 'integer', null, false),
            'email' => new FieldMetadata('email', 'string', 255, false, true),
            'description' => new FieldMetadata('description', 'text', null, true),
        ];

        $requiredFields = ['id', 'email'];

        $metadata = new EntityMetadata(
            className: 'App\\Entity\\User',
            tableName: 'user',
            fields: $fields,
            requiredFields: $requiredFields
        );

        $this->assertSame(['id', 'email'], $metadata->getRequiredFields());
        $this->assertContains('id', $metadata->getRequiredFields());
        $this->assertContains('email', $metadata->getRequiredFields());
        $this->assertNotContains('description', $metadata->getRequiredFields());
    }

    public function testUniqueFieldsTracking(): void
    {
        $fields = [
            'id' => new FieldMetadata('id', 'integer', null, false, false),
            'email' => new FieldMetadata('email', 'string', 255, false, true),
            'username' => new FieldMetadata('username', 'string', 50, false, true),
            'name' => new FieldMetadata('name', 'string', 100, false, false),
        ];

        $uniqueFields = ['email', 'username'];

        $metadata = new EntityMetadata(
            className: 'App\\Entity\\User',
            tableName: 'user',
            fields: $fields,
            uniqueFields: $uniqueFields
        );

        $this->assertSame(['email', 'username'], $metadata->getUniqueFields());
        $this->assertContains('email', $metadata->getUniqueFields());
        $this->assertContains('username', $metadata->getUniqueFields());
        $this->assertNotContains('name', $metadata->getUniqueFields());
        $this->assertTrue($metadata->hasUniqueFields());
    }
}
