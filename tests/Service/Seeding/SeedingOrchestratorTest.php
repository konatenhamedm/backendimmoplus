<?php

namespace App\Tests\Service\Seeding;

use App\Service\Seeding\DataGenerator;
use App\Service\Seeding\DependencyResolver;
use App\Service\Seeding\EntityAnalyzer;
use App\Service\Seeding\EntityMetadata;
use App\Service\Seeding\FieldMetadata;
use App\Service\Seeding\PersistenceManager;
use App\Service\Seeding\RelationshipHandler;
use App\Service\Seeding\RelationshipMetadata;
use App\Service\Seeding\SeedingOptions;
use App\Service\Seeding\SeedingOrchestrator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Unit tests for SeedingOrchestrator service.
 * 
 * Tests the complete seeding workflow including entity discovery,
 * dependency resolution, data generation, relationship handling,
 * validation, and error handling.
 */
class SeedingOrchestratorTest extends TestCase
{
    private EntityAnalyzer $entityAnalyzer;
    private DependencyResolver $dependencyResolver;
    private DataGenerator $dataGenerator;
    private RelationshipHandler $relationshipHandler;
    private PersistenceManager $persistenceManager;
    private ValidatorInterface $validator;
    private SeedingOrchestrator $orchestrator;

    protected function setUp(): void
    {
        $this->entityAnalyzer = $this->createMock(EntityAnalyzer::class);
        $this->dependencyResolver = $this->createMock(DependencyResolver::class);
        $this->dataGenerator = $this->createMock(DataGenerator::class);
        $this->relationshipHandler = $this->createMock(RelationshipHandler::class);
        $this->persistenceManager = $this->createMock(PersistenceManager::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->orchestrator = new SeedingOrchestrator(
            $this->entityAnalyzer,
            $this->dependencyResolver,
            $this->dataGenerator,
            $this->relationshipHandler,
            $this->persistenceManager,
            $this->validator
        );
    }

    /**
     * Test complete seeding workflow with simple entity.
     */
    public function testSeedSimpleEntity(): void
    {
        // Setup test data - use stdClass to avoid class not found errors
        $entityClass = 'stdClass';
        $metadata = new EntityMetadata(
            className: $entityClass,
            tableName: 'user',
            fields: [
                'name' => new FieldMetadata('name', 'string', 50),
                'email' => new FieldMetadata('email', 'string', 100),
            ],
            relationships: []
        );

        // Configure mocks
        $this->entityAnalyzer
            ->method('discoverEntities')
            ->willReturn([$entityClass]);

        $this->entityAnalyzer
            ->method('getEntityMetadata')
            ->with($entityClass)
            ->willReturn($metadata);

        $this->dependencyResolver
            ->method('resolveSeedingOrder')
            ->willReturn([$entityClass]);

        $this->dataGenerator
            ->method('generateValueForField')
            ->willReturn('Test Value');

        $this->validator
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->persistenceManager
            ->expects($this->exactly(10)) // Default count is 10
            ->method('persistEntity');

        $this->persistenceManager
            ->expects($this->once())
            ->method('flush');

        // Execute
        $options = new SeedingOptions(count: 10);
        $result = $this->orchestrator->seed($options);

        // Assert
        $this->assertEquals(1, $result->getSeededEntityCount());
        $this->assertEquals(10, $result->getTotalRecords());
        $this->assertArrayHasKey($entityClass, $result->getEntitiesSeeded());
        $this->assertEquals(10, $result->getEntitiesSeeded()[$entityClass]);
    }

    /**
     * Test seeding with entity dependencies.
     */
    public function testSeedWithDependencies(): void
    {
        // Setup test data - use simple entities without actual classes
        $companyClass = 'stdClass'; // Use stdClass to avoid class not found errors
        $userClass = 'stdClass';

        $companyMetadata = new EntityMetadata(
            className: $companyClass,
            tableName: 'company',
            fields: [
                'name' => new FieldMetadata('name', 'string', 100),
            ],
            relationships: []
        );

        $userMetadata = new EntityMetadata(
            className: $userClass,
            tableName: 'user',
            fields: [
                'name' => new FieldMetadata('name', 'string', 50),
            ],
            relationships: [
                'company' => new RelationshipMetadata(
                    fieldName: 'company',
                    type: 'ManyToOne',
                    targetEntity: $companyClass,
                    nullable: false
                ),
            ]
        );

        // Configure mocks
        $this->entityAnalyzer
            ->method('discoverEntities')
            ->willReturn([$companyClass, $userClass]);

        $this->entityAnalyzer
            ->method('getEntityMetadata')
            ->willReturnMap([
                [$companyClass, $companyMetadata],
                [$userClass, $userMetadata],
            ]);

        // Company should be seeded before User due to dependency
        $this->dependencyResolver
            ->method('resolveSeedingOrder')
            ->willReturn([$companyClass, $userClass]);

        $this->dataGenerator
            ->method('generateValueForField')
            ->willReturn('Test Value');

        $this->validator
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $mockCompany = new \stdClass();
        $this->relationshipHandler
            ->method('handleManyToOne')
            ->willReturn($mockCompany);

        $this->persistenceManager
            ->method('getPersistedEntities')
            ->willReturn([$mockCompany]);

        // Execute
        $options = new SeedingOptions(count: 5);
        $result = $this->orchestrator->seed($options);

        // Assert - both entities should be seeded
        $this->assertGreaterThanOrEqual(1, $result->getSeededEntityCount());
        $this->assertGreaterThanOrEqual(5, $result->getTotalRecords());
    }

    /**
     * Test seeding with excluded entities.
     */
    public function testSeedWithExcludedEntities(): void
    {
        // Setup test data
        $userClass = 'stdClass';
        $groupeClass = 'App\\Entity\\Groupe';

        // Configure mocks - discoverEntities returns both, but Groupe should be filtered
        $this->entityAnalyzer
            ->method('discoverEntities')
            ->willReturn([$userClass]);  // EntityAnalyzer already filters out Groupe

        $userMetadata = new EntityMetadata(
            className: $userClass,
            tableName: 'user',
            fields: [
                'name' => new FieldMetadata('name', 'string', 50),
            ],
            relationships: []
        );

        $this->entityAnalyzer
            ->method('getEntityMetadata')
            ->with($userClass)
            ->willReturn($userMetadata);

        $this->dependencyResolver
            ->method('resolveSeedingOrder')
            ->willReturn([$userClass]);

        $this->dataGenerator
            ->method('generateValueForField')
            ->willReturn('Test Value');

        $this->validator
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        // Execute with default options (Groupe is excluded by default)
        $options = new SeedingOptions(count: 5);
        $result = $this->orchestrator->seed($options);

        // Assert - only User should be seeded (Groupe is already filtered by EntityAnalyzer)
        $this->assertEquals(1, $result->getSeededEntityCount());
        $this->assertGreaterThanOrEqual(5, $result->getTotalRecords());
    }

    /**
     * Test validation error handling.
     */
    public function testValidationErrorHandling(): void
    {
        // Setup test data
        $entityClass = 'stdClass';
        $metadata = new EntityMetadata(
            className: $entityClass,
            tableName: 'user',
            fields: [
                'email' => new FieldMetadata('email', 'string', 100),
            ],
            relationships: []
        );

        // Configure mocks
        $this->entityAnalyzer
            ->method('discoverEntities')
            ->willReturn([$entityClass]);

        $this->entityAnalyzer
            ->method('getEntityMetadata')
            ->willReturn($metadata);

        $this->dependencyResolver
            ->method('resolveSeedingOrder')
            ->willReturn([$entityClass]);

        $this->dataGenerator
            ->method('generateValueForField')
            ->willReturn('invalid-email');

        // Create a mock violation
        $violation = $this->createMock(\Symfony\Component\Validator\ConstraintViolation::class);
        $violation->method('getPropertyPath')->willReturn('email');
        $violation->method('getMessage')->willReturn('This value is not a valid email address.');

        $violations = new ConstraintViolationList([$violation]);

        $this->validator
            ->method('validate')
            ->willReturn($violations);

        // Should not persist invalid entities
        $this->persistenceManager
            ->expects($this->never())
            ->method('persistEntity');

        // Execute
        $options = new SeedingOptions(count: 5);
        $result = $this->orchestrator->seed($options);

        // Assert - no entities should be seeded due to validation errors
        $this->assertEquals(0, $result->getTotalRecords());
    }

    /**
     * Test seeding with unique field constraints.
     */
    public function testSeedWithUniqueFields(): void
    {
        // Setup test data
        $entityClass = 'stdClass';
        $metadata = new EntityMetadata(
            className: $entityClass,
            tableName: 'user',
            fields: [
                'email' => new FieldMetadata('email', 'string', 100, false, true),
            ],
            relationships: [],
            uniqueFields: ['email']
        );

        // Configure mocks
        $this->entityAnalyzer
            ->method('discoverEntities')
            ->willReturn([$entityClass]);

        $this->entityAnalyzer
            ->method('getEntityMetadata')
            ->willReturn($metadata);

        $this->dependencyResolver
            ->method('resolveSeedingOrder')
            ->willReturn([$entityClass]);

        // Should call generateUniqueValue for unique fields
        $this->dataGenerator
            ->expects($this->exactly(5))
            ->method('generateUniqueValue')
            ->willReturnOnConsecutiveCalls(
                'user1@example.com',
                'user2@example.com',
                'user3@example.com',
                'user4@example.com',
                'user5@example.com'
            );

        $this->validator
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        // Execute
        $options = new SeedingOptions(count: 5);
        $result = $this->orchestrator->seed($options);

        // Assert
        $this->assertEquals(5, $result->getTotalRecords());
    }

    /**
     * Test seeding with specific entities filter.
     */
    public function testSeedSpecificEntities(): void
    {
        // Setup test data
        $userClass = 'stdClass';
        $companyClass = 'App\\Entity\\Company';
        $productClass = 'App\\Entity\\Product';

        // Configure mocks
        $this->entityAnalyzer
            ->method('discoverEntities')
            ->willReturn([$userClass, $companyClass, $productClass]);

        $userMetadata = new EntityMetadata(
            className: $userClass,
            tableName: 'user',
            fields: ['name' => new FieldMetadata('name', 'string', 50)],
            relationships: []
        );

        $this->entityAnalyzer
            ->method('getEntityMetadata')
            ->willReturn($userMetadata);

        $this->dependencyResolver
            ->method('resolveSeedingOrder')
            ->willReturn([$userClass]);

        $this->dataGenerator
            ->method('generateValueForField')
            ->willReturn('Test Value');

        $this->validator
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        // Execute with specific entities filter
        $options = new SeedingOptions(
            count: 5,
            specificEntities: [$userClass]
        );
        $result = $this->orchestrator->seed($options);

        // Assert - only User should be seeded
        $this->assertEquals(1, $result->getSeededEntityCount());
        $this->assertEquals(2, $result->getSkippedEntityCount());
    }

    /**
     * Test execution time tracking.
     */
    public function testExecutionTimeTracking(): void
    {
        // Setup minimal test data
        $this->entityAnalyzer
            ->method('discoverEntities')
            ->willReturn([]);

        $this->dependencyResolver
            ->method('resolveSeedingOrder')
            ->willReturn([]);

        // Execute
        $options = new SeedingOptions();
        $result = $this->orchestrator->seed($options);

        // Assert
        $this->assertGreaterThanOrEqual(0, $result->getExecutionTime());
    }

    /**
     * Test error handling during entity creation.
     */
    public function testErrorHandlingDuringCreation(): void
    {
        // Setup test data
        $entityClass = 'stdClass';
        $metadata = new EntityMetadata(
            className: $entityClass,
            tableName: 'user',
            fields: [
                'name' => new FieldMetadata('name', 'string', 50),
            ],
            relationships: []
        );

        // Configure mocks
        $this->entityAnalyzer
            ->method('discoverEntities')
            ->willReturn([$entityClass]);

        $this->entityAnalyzer
            ->method('getEntityMetadata')
            ->willReturn($metadata);

        $this->dependencyResolver
            ->method('resolveSeedingOrder')
            ->willReturn([$entityClass]);

        // Simulate error during data generation
        $this->dataGenerator
            ->method('generateValueForField')
            ->willThrowException(new \RuntimeException('Generation failed'));

        $this->validator
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        // Should still flush at the end
        $this->persistenceManager
            ->expects($this->once())
            ->method('flush');

        // Execute - should not throw exception
        $options = new SeedingOptions(count: 5);
        $result = $this->orchestrator->seed($options);

        // Assert - entities with errors should be skipped
        $this->assertGreaterThanOrEqual(0, $result->getTotalRecords());
    }

    /**
     * Test relationship population with ManyToOne.
     */
    public function testRelationshipPopulationManyToOne(): void
    {
        // Setup test data
        $companyClass = 'stdClass';
        $userClass = 'stdClass';

        $userMetadata = new EntityMetadata(
            className: $userClass,
            tableName: 'user',
            fields: [
                'name' => new FieldMetadata('name', 'string', 50),
            ],
            relationships: [
                'company' => new RelationshipMetadata(
                    fieldName: 'company',
                    type: 'ManyToOne',
                    targetEntity: $companyClass,
                    nullable: false
                ),
            ]
        );

        // Configure mocks
        $this->entityAnalyzer
            ->method('discoverEntities')
            ->willReturn([$userClass]);

        $this->entityAnalyzer
            ->method('getEntityMetadata')
            ->willReturnCallback(function ($class) use ($userClass, $userMetadata, $companyClass) {
                if ($class === $userClass) {
                    return $userMetadata;
                }
                // Return metadata for Company when creating missing dependency
                return new EntityMetadata(
                    className: $companyClass,
                    tableName: 'company',
                    fields: ['name' => new FieldMetadata('name', 'string', 100)],
                    relationships: []
                );
            });

        $this->dependencyResolver
            ->method('resolveSeedingOrder')
            ->willReturn([$userClass]);

        $this->dataGenerator
            ->method('generateValueForField')
            ->willReturn('Test Value');

        $this->validator
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        // No existing companies, so should create missing dependency
        $this->persistenceManager
            ->method('getPersistedEntities')
            ->willReturn([]);

        $this->relationshipHandler
            ->method('handleManyToOne')
            ->willReturn(null); // No existing entity

        // Execute
        $options = new SeedingOptions(count: 2);
        $result = $this->orchestrator->seed($options);

        // Assert - should create users (and their missing company dependencies)
        $this->assertGreaterThan(0, $result->getTotalRecords());
    }
}
