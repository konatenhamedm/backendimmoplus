<?php

namespace App\Tests\Service\Seeding;

use App\Service\Seeding\PersistenceManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PersistenceManager service.
 * 
 * Tests entity persistence, batch flushing, EntityManager clearing,
 * and persisted entity tracking.
 */
class PersistenceManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private PersistenceManager $persistenceManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->persistenceManager = new PersistenceManager($this->entityManager);
    }

    /**
     * Test basic entity persistence.
     */
    public function testPersistEntity(): void
    {
        $entity = new \stdClass();
        $entity->id = 1;

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($entity);

        $this->persistenceManager->persistEntity($entity);
    }

    /**
     * Test that persisted entities are tracked by class name.
     */
    public function testPersistedEntitiesAreTracked(): void
    {
        $entity1 = new \stdClass();
        $entity1->id = 1;
        
        $entity2 = new \stdClass();
        $entity2->id = 2;

        $this->entityManager->expects($this->exactly(2))
            ->method('persist');

        $this->persistenceManager->persistEntity($entity1);
        $this->persistenceManager->persistEntity($entity2);

        $persistedEntities = $this->persistenceManager->getPersistedEntities(\stdClass::class);
        
        $this->assertCount(2, $persistedEntities);
        $this->assertSame($entity1, $persistedEntities[0]);
        $this->assertSame($entity2, $persistedEntities[1]);
    }

    /**
     * Test that different entity classes are tracked separately.
     */
    public function testDifferentEntityClassesTrackedSeparately(): void
    {
        $entity1 = new \stdClass();
        $entity2 = new \ArrayObject();

        $this->entityManager->expects($this->exactly(2))
            ->method('persist');

        $this->persistenceManager->persistEntity($entity1);
        $this->persistenceManager->persistEntity($entity2);

        $stdClassEntities = $this->persistenceManager->getPersistedEntities(\stdClass::class);
        $arrayObjectEntities = $this->persistenceManager->getPersistedEntities(\ArrayObject::class);

        $this->assertCount(1, $stdClassEntities);
        $this->assertCount(1, $arrayObjectEntities);
        $this->assertSame($entity1, $stdClassEntities[0]);
        $this->assertSame($entity2, $arrayObjectEntities[0]);
    }

    /**
     * Test getting persisted entities for a class with no entities.
     */
    public function testGetPersistedEntitiesForNonExistentClass(): void
    {
        $entities = $this->persistenceManager->getPersistedEntities('NonExistentClass');
        
        $this->assertIsArray($entities);
        $this->assertEmpty($entities);
    }

    /**
     * Test manual flush operation.
     */
    public function testFlush(): void
    {
        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->persistenceManager->flush();
    }

    /**
     * Test manual clear operation.
     */
    public function testClear(): void
    {
        $this->entityManager->expects($this->once())
            ->method('clear');

        $this->persistenceManager->clear();
    }

    /**
     * Test auto-flush when batch size (50) is reached.
     */
    public function testAutoFlushAtBatchSize(): void
    {
        // Expect flush to be called once when we reach 50 entities
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Expect clear to be called once after flush
        $this->entityManager->expects($this->once())
            ->method('clear');

        // Expect persist to be called 50 times
        $this->entityManager->expects($this->exactly(50))
            ->method('persist');

        // Persist 50 entities
        for ($i = 0; $i < 50; $i++) {
            $entity = new \stdClass();
            $entity->id = $i;
            $this->persistenceManager->persistEntity($entity);
        }
    }

    /**
     * Test no auto-flush when batch size is not reached.
     */
    public function testNoAutoFlushBelowBatchSize(): void
    {
        // Expect flush to NOT be called
        $this->entityManager->expects($this->never())
            ->method('flush');

        // Expect clear to NOT be called
        $this->entityManager->expects($this->never())
            ->method('clear');

        // Expect persist to be called 49 times
        $this->entityManager->expects($this->exactly(49))
            ->method('persist');

        // Persist 49 entities (one less than batch size)
        for ($i = 0; $i < 49; $i++) {
            $entity = new \stdClass();
            $entity->id = $i;
            $this->persistenceManager->persistEntity($entity);
        }
    }

    /**
     * Test multiple batch flushes.
     */
    public function testMultipleBatchFlushes(): void
    {
        // Expect flush to be called twice (at 50 and 100 entities)
        $this->entityManager->expects($this->exactly(2))
            ->method('flush');

        // Expect clear to be called twice
        $this->entityManager->expects($this->exactly(2))
            ->method('clear');

        // Expect persist to be called 100 times
        $this->entityManager->expects($this->exactly(100))
            ->method('persist');

        // Persist 100 entities
        for ($i = 0; $i < 100; $i++) {
            $entity = new \stdClass();
            $entity->id = $i;
            $this->persistenceManager->persistEntity($entity);
        }
    }

    /**
     * Test that persisted entities remain tracked after flush and clear.
     */
    public function testPersistedEntitiesRemainTrackedAfterFlushAndClear(): void
    {
        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->entityManager->expects($this->once())
            ->method('clear');

        $this->entityManager->expects($this->exactly(50))
            ->method('persist');

        // Persist 50 entities to trigger auto-flush
        $entities = [];
        for ($i = 0; $i < 50; $i++) {
            $entity = new \stdClass();
            $entity->id = $i;
            $entities[] = $entity;
            $this->persistenceManager->persistEntity($entity);
        }

        // Verify all entities are still tracked after flush and clear
        $persistedEntities = $this->persistenceManager->getPersistedEntities(\stdClass::class);
        $this->assertCount(50, $persistedEntities);
        
        // Verify the entities are the same objects
        for ($i = 0; $i < 50; $i++) {
            $this->assertSame($entities[$i], $persistedEntities[$i]);
        }
    }

    /**
     * Test batch processing with partial batch at the end.
     */
    public function testBatchProcessingWithPartialBatch(): void
    {
        // Expect flush to be called once (at 50 entities)
        // The remaining 25 entities won't trigger auto-flush
        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->entityManager->expects($this->once())
            ->method('clear');

        $this->entityManager->expects($this->exactly(75))
            ->method('persist');

        // Persist 75 entities (1.5 batches)
        for ($i = 0; $i < 75; $i++) {
            $entity = new \stdClass();
            $entity->id = $i;
            $this->persistenceManager->persistEntity($entity);
        }

        // All 75 entities should be tracked
        $persistedEntities = $this->persistenceManager->getPersistedEntities(\stdClass::class);
        $this->assertCount(75, $persistedEntities);
    }

    /**
     * Test that manual flush can be called after partial batch.
     */
    public function testManualFlushAfterPartialBatch(): void
    {
        // Expect flush to be called twice: once auto (at 50), once manual
        $this->entityManager->expects($this->exactly(2))
            ->method('flush');

        $this->entityManager->expects($this->once())
            ->method('clear');

        $this->entityManager->expects($this->exactly(75))
            ->method('persist');

        // Persist 75 entities
        for ($i = 0; $i < 75; $i++) {
            $entity = new \stdClass();
            $entity->id = $i;
            $this->persistenceManager->persistEntity($entity);
        }

        // Manually flush the remaining entities
        $this->persistenceManager->flush();
    }

    /**
     * Test exact batch size boundary (50th entity triggers flush).
     */
    public function testExactBatchSizeBoundary(): void
    {
        // Track when flush is called
        $flushCalled = false;
        
        $this->entityManager->expects($this->once())
            ->method('flush')
            ->willReturnCallback(function () use (&$flushCalled) {
                $flushCalled = true;
            });

        $this->entityManager->expects($this->exactly(50))
            ->method('persist');

        // Persist 49 entities - should not flush
        for ($i = 0; $i < 49; $i++) {
            $entity = new \stdClass();
            $entity->id = $i;
            $this->persistenceManager->persistEntity($entity);
            $this->assertFalse($flushCalled, "Flush should not be called before 50th entity");
        }

        // Persist 50th entity - should trigger flush
        $entity = new \stdClass();
        $entity->id = 49;
        $this->persistenceManager->persistEntity($entity);
        $this->assertTrue($flushCalled, "Flush should be called on 50th entity");
    }

    /**
     * Test that entity counter resets after flush.
     */
    public function testEntityCounterResetsAfterFlush(): void
    {
        // First batch: flush at 50
        $this->entityManager->expects($this->exactly(2))
            ->method('flush');

        $this->entityManager->expects($this->exactly(100))
            ->method('persist');

        // Persist 50 entities (triggers first flush)
        for ($i = 0; $i < 50; $i++) {
            $entity = new \stdClass();
            $entity->id = $i;
            $this->persistenceManager->persistEntity($entity);
        }

        // Persist another 50 entities (triggers second flush)
        for ($i = 50; $i < 100; $i++) {
            $entity = new \stdClass();
            $entity->id = $i;
            $this->persistenceManager->persistEntity($entity);
        }

        // All 100 entities should be tracked
        $persistedEntities = $this->persistenceManager->getPersistedEntities(\stdClass::class);
        $this->assertCount(100, $persistedEntities);
    }
}
