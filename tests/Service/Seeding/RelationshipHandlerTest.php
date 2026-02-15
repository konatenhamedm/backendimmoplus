<?php

namespace App\Tests\Service\Seeding;

use App\Service\Seeding\DataGenerator;
use App\Service\Seeding\RelationshipHandler;
use App\Service\Seeding\RelationshipMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RelationshipHandler service.
 * 
 * Tests relationship handling for ManyToOne, OneToOne, OneToMany, and ManyToMany
 * relationships, including special handling for the Groupe entity.
 */
class RelationshipHandlerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private DataGenerator $dataGenerator;
    private RelationshipHandler $relationshipHandler;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->dataGenerator = $this->createMock(DataGenerator::class);
        $this->relationshipHandler = new RelationshipHandler(
            $this->entityManager,
            $this->dataGenerator
        );
    }

    /**
     * Test ManyToOne with existing entities provided.
     */
    public function testHandleManyToOneWithExistingEntities(): void
    {
        $relation = new RelationshipMetadata(
            fieldName: 'user',
            type: 'ManyToOne',
            targetEntity: 'App\\Entity\\User',
            nullable: false
        );

        $entity1 = new \stdClass();
        $entity1->id = 1;
        $entity2 = new \stdClass();
        $entity2->id = 2;
        $entity3 = new \stdClass();
        $entity3->id = 3;

        $existingEntities = [$entity1, $entity2, $entity3];

        $result = $this->relationshipHandler->handleManyToOne($relation, $existingEntities);

        $this->assertNotNull($result);
        $this->assertContains($result, $existingEntities);
    }

    /**
     * Test ManyToOne with no existing entities and nullable field.
     */
    public function testHandleManyToOneWithNoEntitiesNullable(): void
    {
        $relation = new RelationshipMetadata(
            fieldName: 'user',
            type: 'ManyToOne',
            targetEntity: 'App\\Entity\\User',
            nullable: true
        );

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findAll')->willReturn([]);

        $this->entityManager
            ->method('getRepository')
            ->with('App\\Entity\\User')
            ->willReturn($repository);

        $result = $this->relationshipHandler->handleManyToOne($relation);

        $this->assertNull($result);
    }

    /**
     * Test ManyToOne with entities from database.
     */
    public function testHandleManyToOneFromDatabase(): void
    {
        $relation = new RelationshipMetadata(
            fieldName: 'user',
            type: 'ManyToOne',
            targetEntity: 'App\\Entity\\User',
            nullable: false
        );

        $entity1 = new \stdClass();
        $entity1->id = 1;
        $entity2 = new \stdClass();
        $entity2->id = 2;

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findAll')->willReturn([$entity1, $entity2]);

        $this->entityManager
            ->method('getRepository')
            ->with('App\\Entity\\User')
            ->willReturn($repository);

        $result = $this->relationshipHandler->handleManyToOne($relation);

        $this->assertNotNull($result);
        $this->assertContains($result, [$entity1, $entity2]);
    }

    /**
     * Test ManyToOne with Groupe entity - should only use existing records.
     */
    public function testHandleManyToOneWithGroupeEntity(): void
    {
        $relation = new RelationshipMetadata(
            fieldName: 'groupe',
            type: 'ManyToOne',
            targetEntity: 'App\\Entity\\Groupe',
            nullable: false
        );

        $groupe1 = new \stdClass();
        $groupe1->id = 1;
        $groupe2 = new \stdClass();
        $groupe2->id = 2;

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findAll')->willReturn([$groupe1, $groupe2]);

        $this->entityManager
            ->method('getRepository')
            ->with('App\\Entity\\Groupe')
            ->willReturn($repository);

        $result = $this->relationshipHandler->handleManyToOne($relation);

        $this->assertNotNull($result);
        $this->assertContains($result, [$groupe1, $groupe2]);
    }

    /**
     * Test ManyToOne with Groupe entity when no Groupe records exist.
     */
    public function testHandleManyToOneWithGroupeEntityNoRecords(): void
    {
        $relation = new RelationshipMetadata(
            fieldName: 'groupe',
            type: 'ManyToOne',
            targetEntity: 'App\\Entity\\Groupe',
            nullable: true
        );

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findAll')->willReturn([]);

        $this->entityManager
            ->method('getRepository')
            ->with('App\\Entity\\Groupe')
            ->willReturn($repository);

        $result = $this->relationshipHandler->handleManyToOne($relation);

        $this->assertNull($result);
    }

    /**
     * Test OneToOne relationship handling.
     */
    public function testHandleOneToOne(): void
    {
        $relation = new RelationshipMetadata(
            fieldName: 'profile',
            type: 'OneToOne',
            targetEntity: 'App\\Entity\\Profile',
            nullable: false
        );

        $result = $this->relationshipHandler->handleOneToOne($relation);

        // OneToOne returns null to signal that a new entity should be created
        $this->assertNull($result);
    }

    /**
     * Test OneToOne with Groupe entity - should use existing records.
     */
    public function testHandleOneToOneWithGroupeEntity(): void
    {
        $relation = new RelationshipMetadata(
            fieldName: 'groupe',
            type: 'OneToOne',
            targetEntity: 'App\\Entity\\Groupe',
            nullable: false
        );

        $groupe1 = new \stdClass();
        $groupe1->id = 1;

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findAll')->willReturn([$groupe1]);

        $this->entityManager
            ->method('getRepository')
            ->with('App\\Entity\\Groupe')
            ->willReturn($repository);

        $result = $this->relationshipHandler->handleOneToOne($relation);

        $this->assertNotNull($result);
        $this->assertEquals($groupe1, $result);
    }

    /**
     * Test OneToMany relationship handling.
     */
    public function testHandleOneToMany(): void
    {
        $relation = new RelationshipMetadata(
            fieldName: 'comments',
            type: 'OneToMany',
            targetEntity: 'App\\Entity\\Comment',
            nullable: false
        );

        $result = $this->relationshipHandler->handleOneToMany($relation);

        // Should return an array with null elements (signal to create entities)
        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(2, count($result));
        $this->assertLessThanOrEqual(5, count($result));
    }

    /**
     * Test OneToMany with specific count.
     */
    public function testHandleOneToManyWithSpecificCount(): void
    {
        $relation = new RelationshipMetadata(
            fieldName: 'comments',
            type: 'OneToMany',
            targetEntity: 'App\\Entity\\Comment',
            nullable: false
        );

        $result = $this->relationshipHandler->handleOneToMany($relation, 3);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }

    /**
     * Test OneToMany with Groupe entity - should return empty array.
     */
    public function testHandleOneToManyWithGroupeEntity(): void
    {
        $relation = new RelationshipMetadata(
            fieldName: 'groupes',
            type: 'OneToMany',
            targetEntity: 'App\\Entity\\Groupe',
            nullable: false
        );

        $result = $this->relationshipHandler->handleOneToMany($relation);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test ManyToMany relationship handling.
     */
    public function testHandleManyToMany(): void
    {
        $relation = new RelationshipMetadata(
            fieldName: 'tags',
            type: 'ManyToMany',
            targetEntity: 'App\\Entity\\Tag',
            nullable: false
        );

        $entity1 = new \stdClass();
        $entity1->id = 1;
        $entity2 = new \stdClass();
        $entity2->id = 2;
        $entity3 = new \stdClass();
        $entity3->id = 3;
        $entity4 = new \stdClass();
        $entity4->id = 4;
        $entity5 = new \stdClass();
        $entity5->id = 5;

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findAll')->willReturn([$entity1, $entity2, $entity3, $entity4, $entity5]);

        $this->entityManager
            ->method('getRepository')
            ->with('App\\Entity\\Tag')
            ->willReturn($repository);

        $result = $this->relationshipHandler->handleManyToMany($relation);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(2, count($result));
        $this->assertLessThanOrEqual(5, count($result));
        
        // All returned entities should be from the original set
        foreach ($result as $entity) {
            $this->assertContains($entity, [$entity1, $entity2, $entity3, $entity4, $entity5]);
        }
    }

    /**
     * Test ManyToMany with specific count.
     */
    public function testHandleManyToManyWithSpecificCount(): void
    {
        $relation = new RelationshipMetadata(
            fieldName: 'tags',
            type: 'ManyToMany',
            targetEntity: 'App\\Entity\\Tag',
            nullable: false
        );

        $entities = [];
        for ($i = 1; $i <= 10; $i++) {
            $entity = new \stdClass();
            $entity->id = $i;
            $entities[] = $entity;
        }

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findAll')->willReturn($entities);

        $this->entityManager
            ->method('getRepository')
            ->with('App\\Entity\\Tag')
            ->willReturn($repository);

        $result = $this->relationshipHandler->handleManyToMany($relation, 3);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }

    /**
     * Test ManyToMany with no existing entities.
     */
    public function testHandleManyToManyWithNoEntities(): void
    {
        $relation = new RelationshipMetadata(
            fieldName: 'tags',
            type: 'ManyToMany',
            targetEntity: 'App\\Entity\\Tag',
            nullable: false
        );

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findAll')->willReturn([]);

        $this->entityManager
            ->method('getRepository')
            ->with('App\\Entity\\Tag')
            ->willReturn($repository);

        $result = $this->relationshipHandler->handleManyToMany($relation);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test ManyToMany with Groupe entity.
     */
    public function testHandleManyToManyWithGroupeEntity(): void
    {
        $relation = new RelationshipMetadata(
            fieldName: 'groupes',
            type: 'ManyToMany',
            targetEntity: 'App\\Entity\\Groupe',
            nullable: false
        );

        $groupe1 = new \stdClass();
        $groupe1->id = 1;
        $groupe2 = new \stdClass();
        $groupe2->id = 2;
        $groupe3 = new \stdClass();
        $groupe3->id = 3;

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findAll')->willReturn([$groupe1, $groupe2, $groupe3]);

        $this->entityManager
            ->method('getRepository')
            ->with('App\\Entity\\Groupe')
            ->willReturn($repository);

        $result = $this->relationshipHandler->handleManyToMany($relation);

        $this->assertIsArray($result);
        $this->assertGreaterThan(0, count($result));
        $this->assertLessThanOrEqual(3, count($result));
        
        // All returned entities should be existing Groupe entities
        foreach ($result as $entity) {
            $this->assertContains($entity, [$groupe1, $groupe2, $groupe3]);
        }
    }

    /**
     * Test ManyToMany when requesting more entities than available.
     */
    public function testHandleManyToManyWithMoreCountThanAvailable(): void
    {
        $relation = new RelationshipMetadata(
            fieldName: 'tags',
            type: 'ManyToMany',
            targetEntity: 'App\\Entity\\Tag',
            nullable: false
        );

        $entity1 = new \stdClass();
        $entity1->id = 1;
        $entity2 = new \stdClass();
        $entity2->id = 2;

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findAll')->willReturn([$entity1, $entity2]);

        $this->entityManager
            ->method('getRepository')
            ->with('App\\Entity\\Tag')
            ->willReturn($repository);

        $result = $this->relationshipHandler->handleManyToMany($relation, 10);

        // Should return all available entities (2) instead of requested 10
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    /**
     * Test that random selection is working (entities are different across calls).
     */
    public function testManyToOneRandomSelection(): void
    {
        $relation = new RelationshipMetadata(
            fieldName: 'user',
            type: 'ManyToOne',
            targetEntity: 'App\\Entity\\User',
            nullable: false
        );

        $entities = [];
        for ($i = 1; $i <= 10; $i++) {
            $entity = new \stdClass();
            $entity->id = $i;
            $entities[] = $entity;
        }

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findAll')->willReturn($entities);

        $this->entityManager
            ->method('getRepository')
            ->with('App\\Entity\\User')
            ->willReturn($repository);

        $results = [];
        for ($i = 0; $i < 20; $i++) {
            $result = $this->relationshipHandler->handleManyToOne($relation);
            $results[] = $result->id;
        }

        // With 10 entities and 20 selections, we should see some variety
        $uniqueResults = array_unique($results);
        $this->assertGreaterThan(1, count($uniqueResults), 'Should select different entities randomly');
    }

    /**
     * Test Groupe entity detection with fully qualified class name.
     */
    public function testGroupeEntityDetectionWithFQCN(): void
    {
        $relation = new RelationshipMetadata(
            fieldName: 'groupe',
            type: 'ManyToOne',
            targetEntity: 'App\\Entity\\Groupe',
            nullable: true
        );

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findAll')->willReturn([]);

        $this->entityManager
            ->method('getRepository')
            ->with('App\\Entity\\Groupe')
            ->willReturn($repository);

        $result = $this->relationshipHandler->handleManyToOne($relation);

        // Should return null because no Groupe records exist and field is nullable
        $this->assertNull($result);
    }
}
