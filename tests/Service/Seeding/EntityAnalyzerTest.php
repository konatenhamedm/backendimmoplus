<?php

namespace App\Tests\Service\Seeding;

use App\Service\Seeding\EntityAnalyzer;
use App\Service\Seeding\EntityMetadata;
use App\Service\Seeding\FieldMetadata;
use App\Service\Seeding\RelationshipMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
use Doctrine\ORM\Mapping\JoinColumnMapping;
use Doctrine\ORM\Mapping\ManyToOneAssociationMapping;
use Doctrine\ORM\Mapping\ClassMetadataFactory as OrmClassMetadataFactory;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for EntityAnalyzer service.
 * 
 * Tests entity discovery, Groupe exclusion, and metadata extraction.
 */
class EntityAnalyzerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private OrmClassMetadataFactory $metadataFactory;
    private EntityAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->metadataFactory = $this->createMock(OrmClassMetadataFactory::class);
        
        $this->entityManager
            ->method('getMetadataFactory')
            ->willReturn($this->metadataFactory);
        
        $this->analyzer = new EntityAnalyzer($this->entityManager);
    }

    /**
     * Test that discoverEntities returns all entities except Groupe.
     */
    public function testDiscoverEntitiesExcludesGroupe(): void
    {
        // Create mock metadata for several entities including Groupe
        $userMetadata = $this->createMock(ClassMetadata::class);
        $userMetadata->method('getName')->willReturn('App\\Entity\\User');
        
        $groupeMetadata = $this->createMock(ClassMetadata::class);
        $groupeMetadata->method('getName')->willReturn('App\\Entity\\Groupe');
        
        $entrepriseMetadata = $this->createMock(ClassMetadata::class);
        $entrepriseMetadata->method('getName')->willReturn('App\\Entity\\Entreprise');
        
        $this->metadataFactory
            ->method('getAllMetadata')
            ->willReturn([$userMetadata, $groupeMetadata, $entrepriseMetadata]);
        
        $entities = $this->analyzer->discoverEntities();
        
        // Assert that Groupe is excluded
        $this->assertCount(2, $entities);
        $this->assertContains('App\\Entity\\User', $entities);
        $this->assertContains('App\\Entity\\Entreprise', $entities);
        $this->assertNotContains('App\\Entity\\Groupe', $entities);
    }

    /**
     * Test that discoverEntities returns empty array when no entities exist.
     */
    public function testDiscoverEntitiesReturnsEmptyArrayWhenNoEntities(): void
    {
        $this->metadataFactory
            ->method('getAllMetadata')
            ->willReturn([]);
        
        $entities = $this->analyzer->discoverEntities();
        
        $this->assertIsArray($entities);
        $this->assertEmpty($entities);
    }

    /**
     * Test that isExcludedEntity correctly identifies Groupe.
     */
    public function testIsExcludedEntityIdentifiesGroupe(): void
    {
        $this->assertTrue($this->analyzer->isExcludedEntity('App\\Entity\\Groupe'));
        $this->assertFalse($this->analyzer->isExcludedEntity('App\\Entity\\User'));
        $this->assertFalse($this->analyzer->isExcludedEntity('App\\Entity\\Entreprise'));
    }

    /**
     * Test that getEntityMetadata extracts field information correctly.
     */
    public function testGetEntityMetadataExtractsFields(): void
    {
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->method('getName')->willReturn('App\\Entity\\User');
        $classMetadata->method('getTableName')->willReturn('users');
        $classMetadata->method('getIdentifierFieldNames')->willReturn(['id']);
        $classMetadata->method('getFieldNames')->willReturn(['id', 'login', 'password', 'isActive']);
        $classMetadata->method('getAssociationNames')->willReturn([]);
        
        // Create mock FieldMapping objects
        $loginMapping = new FieldMapping(
            fieldName: 'login',
            type: 'string',
            columnName: 'login'
        );
        $loginMapping->length = 180;
        $loginMapping->nullable = false;
        $loginMapping->unique = true;
        
        $passwordMapping = new FieldMapping(
            fieldName: 'password',
            type: 'string',
            columnName: 'password'
        );
        $passwordMapping->length = 255;
        $passwordMapping->nullable = false;
        $passwordMapping->unique = false;
        
        $isActiveMapping = new FieldMapping(
            fieldName: 'isActive',
            type: 'boolean',
            columnName: 'is_active'
        );
        $isActiveMapping->nullable = false;
        $isActiveMapping->unique = false;
        
        // Mock field mappings
        $classMetadata->method('getFieldMapping')->willReturnCallback(function ($fieldName) use ($loginMapping, $passwordMapping, $isActiveMapping) {
            return match ($fieldName) {
                'login' => $loginMapping,
                'password' => $passwordMapping,
                'isActive' => $isActiveMapping,
                default => throw new \Exception("Unknown field: $fieldName")
            };
        });
        
        $this->entityManager
            ->method('getClassMetadata')
            ->with('App\\Entity\\User')
            ->willReturn($classMetadata);
        
        $metadata = $this->analyzer->getEntityMetadata('App\\Entity\\User');
        
        $this->assertInstanceOf(EntityMetadata::class, $metadata);
        $this->assertEquals('App\\Entity\\User', $metadata->getClassName());
        $this->assertEquals('users', $metadata->getTableName());
        
        // Check fields (id should be excluded as it's an identifier)
        $fields = $metadata->getFields();
        $this->assertCount(3, $fields);
        $this->assertArrayHasKey('login', $fields);
        $this->assertArrayHasKey('password', $fields);
        $this->assertArrayHasKey('isActive', $fields);
        $this->assertArrayNotHasKey('id', $fields);
        
        // Check login field details
        $loginField = $fields['login'];
        $this->assertInstanceOf(FieldMetadata::class, $loginField);
        $this->assertEquals('login', $loginField->getName());
        $this->assertEquals('string', $loginField->getType());
        $this->assertEquals(180, $loginField->getLength());
        $this->assertFalse($loginField->isNullable());
        $this->assertTrue($loginField->isUnique());
        
        // Check unique and required fields
        $this->assertEquals(['login'], $metadata->getUniqueFields());
        $this->assertContains('login', $metadata->getRequiredFields());
        $this->assertContains('password', $metadata->getRequiredFields());
        $this->assertContains('isActive', $metadata->getRequiredFields());
    }

    /**
     * Test that getEntityMetadata extracts relationship information correctly.
     */
    public function testGetEntityMetadataExtractsRelationships(): void
    {
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->method('getName')->willReturn('App\\Entity\\User');
        $classMetadata->method('getTableName')->willReturn('users');
        $classMetadata->method('getIdentifierFieldNames')->willReturn(['id']);
        $classMetadata->method('getFieldNames')->willReturn(['id']);
        $classMetadata->method('getAssociationNames')->willReturn(['groupe', 'entreprise']);
        
        // Create mock association mappings
        $groupeJoinColumn = new JoinColumnMapping(
            name: 'groupe_id',
            referencedColumnName: 'id'
        );
        $groupeJoinColumn->nullable = true;
        
        $groupeMapping = new ManyToOneAssociationMapping(
            fieldName: 'groupe',
            sourceEntity: 'App\\Entity\\User',
            targetEntity: 'App\\Entity\\Groupe'
        );
        $groupeMapping->joinColumns = [$groupeJoinColumn];
        
        $entrepriseJoinColumn = new JoinColumnMapping(
            name: 'entreprise_id',
            referencedColumnName: 'id'
        );
        $entrepriseJoinColumn->nullable = true;
        
        $entrepriseMapping = new ManyToOneAssociationMapping(
            fieldName: 'entreprise',
            sourceEntity: 'App\\Entity\\User',
            targetEntity: 'App\\Entity\\Entreprise'
        );
        $entrepriseMapping->joinColumns = [$entrepriseJoinColumn];
        
        // Mock association mappings
        $classMetadata->method('getAssociationMapping')->willReturnCallback(function ($associationName) use ($groupeMapping, $entrepriseMapping) {
            return match ($associationName) {
                'groupe' => $groupeMapping,
                'entreprise' => $entrepriseMapping,
                default => throw new \Exception("Unknown association: $associationName")
            };
        });
        
        $this->entityManager
            ->method('getClassMetadata')
            ->with('App\\Entity\\User')
            ->willReturn($classMetadata);
        
        $metadata = $this->analyzer->getEntityMetadata('App\\Entity\\User');
        
        $relationships = $metadata->getRelationships();
        $this->assertCount(2, $relationships);
        $this->assertArrayHasKey('groupe', $relationships);
        $this->assertArrayHasKey('entreprise', $relationships);
        
        // Check groupe relationship
        $groupeRelation = $relationships['groupe'];
        $this->assertInstanceOf(RelationshipMetadata::class, $groupeRelation);
        $this->assertEquals('groupe', $groupeRelation->getFieldName());
        $this->assertEquals('ManyToOne', $groupeRelation->getType());
        $this->assertEquals('App\\Entity\\Groupe', $groupeRelation->getTargetEntity());
        $this->assertTrue($groupeRelation->isNullable());
    }

    /**
     * Test that nullable fields are correctly identified.
     */
    public function testGetEntityMetadataIdentifiesNullableFields(): void
    {
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->method('getName')->willReturn('App\\Entity\\User');
        $classMetadata->method('getTableName')->willReturn('users');
        $classMetadata->method('getIdentifierFieldNames')->willReturn(['id']);
        $classMetadata->method('getFieldNames')->willReturn(['id', 'nom', 'prenoms']);
        $classMetadata->method('getAssociationNames')->willReturn([]);
        
        // Create mock FieldMapping objects
        $nomMapping = new FieldMapping(
            fieldName: 'nom',
            type: 'string',
            columnName: 'nom'
        );
        $nomMapping->length = 255;
        $nomMapping->nullable = true;
        $nomMapping->unique = false;
        
        $prenomsMapping = new FieldMapping(
            fieldName: 'prenoms',
            type: 'string',
            columnName: 'prenoms'
        );
        $prenomsMapping->length = 255;
        $prenomsMapping->nullable = true;
        $prenomsMapping->unique = false;
        
        $classMetadata->method('getFieldMapping')->willReturnCallback(function ($fieldName) use ($nomMapping, $prenomsMapping) {
            return match ($fieldName) {
                'nom' => $nomMapping,
                'prenoms' => $prenomsMapping,
                default => throw new \Exception("Unknown field: $fieldName")
            };
        });
        
        $this->entityManager
            ->method('getClassMetadata')
            ->with('App\\Entity\\User')
            ->willReturn($classMetadata);
        
        $metadata = $this->analyzer->getEntityMetadata('App\\Entity\\User');
        
        $fields = $metadata->getFields();
        $this->assertTrue($fields['nom']->isNullable());
        $this->assertTrue($fields['prenoms']->isNullable());
        
        // Required fields should be empty since all fields are nullable
        $this->assertEmpty($metadata->getRequiredFields());
    }
}
