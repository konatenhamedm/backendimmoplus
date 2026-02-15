<?php

namespace App\Tests\Service\Seeding;

use App\Service\Seeding\DependencyResolver;
use App\Service\Seeding\EntityMetadata;
use App\Service\Seeding\RelationshipMetadata;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DependencyResolver service.
 * 
 * Tests the dependency graph building and topological sorting functionality
 * to ensure entities are seeded in the correct order.
 */
class DependencyResolverTest extends TestCase
{
    private DependencyResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new DependencyResolver();
    }

    /**
     * Test building a simple dependency graph with no relationships.
     */
    public function testBuildDependencyGraphWithNoRelationships(): void
    {
        $entities = [
            'App\\Entity\\User' => new EntityMetadata(
                className: 'App\\Entity\\User',
                tableName: 'user',
                fields: [],
                relationships: []
            ),
            'App\\Entity\\Product' => new EntityMetadata(
                className: 'App\\Entity\\Product',
                tableName: 'product',
                fields: [],
                relationships: []
            ),
        ];

        $graph = $this->resolver->buildDependencyGraph($entities);

        $this->assertArrayHasKey('App\\Entity\\User', $graph);
        $this->assertArrayHasKey('App\\Entity\\Product', $graph);
        $this->assertEmpty($graph['App\\Entity\\User']);
        $this->assertEmpty($graph['App\\Entity\\Product']);
    }

    /**
     * Test building a dependency graph with ManyToOne relationships.
     */
    public function testBuildDependencyGraphWithManyToOne(): void
    {
        $entities = [
            'App\\Entity\\User' => new EntityMetadata(
                className: 'App\\Entity\\User',
                tableName: 'user',
                fields: [],
                relationships: [
                    'company' => new RelationshipMetadata(
                        fieldName: 'company',
                        type: 'ManyToOne',
                        targetEntity: 'App\\Entity\\Company',
                        nullable: false
                    ),
                ]
            ),
            'App\\Entity\\Company' => new EntityMetadata(
                className: 'App\\Entity\\Company',
                tableName: 'company',
                fields: [],
                relationships: []
            ),
        ];

        $graph = $this->resolver->buildDependencyGraph($entities);

        $this->assertContains('App\\Entity\\Company', $graph['App\\Entity\\User']);
        $this->assertEmpty($graph['App\\Entity\\Company']);
    }

    /**
     * Test building a dependency graph with OneToOne relationships.
     */
    public function testBuildDependencyGraphWithOneToOne(): void
    {
        $entities = [
            'App\\Entity\\User' => new EntityMetadata(
                className: 'App\\Entity\\User',
                tableName: 'user',
                fields: [],
                relationships: [
                    'profile' => new RelationshipMetadata(
                        fieldName: 'profile',
                        type: 'OneToOne',
                        targetEntity: 'App\\Entity\\Profile',
                        nullable: true
                    ),
                ]
            ),
            'App\\Entity\\Profile' => new EntityMetadata(
                className: 'App\\Entity\\Profile',
                tableName: 'profile',
                fields: [],
                relationships: []
            ),
        ];

        $graph = $this->resolver->buildDependencyGraph($entities);

        $this->assertContains('App\\Entity\\Profile', $graph['App\\Entity\\User']);
        $this->assertEmpty($graph['App\\Entity\\Profile']);
    }

    /**
     * Test that OneToMany relationships don't create dependencies.
     */
    public function testBuildDependencyGraphIgnoresOneToMany(): void
    {
        $entities = [
            'App\\Entity\\Company' => new EntityMetadata(
                className: 'App\\Entity\\Company',
                tableName: 'company',
                fields: [],
                relationships: [
                    'users' => new RelationshipMetadata(
                        fieldName: 'users',
                        type: 'OneToMany',
                        targetEntity: 'App\\Entity\\User',
                        nullable: true
                    ),
                ]
            ),
            'App\\Entity\\User' => new EntityMetadata(
                className: 'App\\Entity\\User',
                tableName: 'user',
                fields: [],
                relationships: []
            ),
        ];

        $graph = $this->resolver->buildDependencyGraph($entities);

        // OneToMany should not create a dependency
        $this->assertEmpty($graph['App\\Entity\\Company']);
        $this->assertEmpty($graph['App\\Entity\\User']);
    }

    /**
     * Test that ManyToMany relationships don't create dependencies.
     */
    public function testBuildDependencyGraphIgnoresManyToMany(): void
    {
        $entities = [
            'App\\Entity\\User' => new EntityMetadata(
                className: 'App\\Entity\\User',
                tableName: 'user',
                fields: [],
                relationships: [
                    'roles' => new RelationshipMetadata(
                        fieldName: 'roles',
                        type: 'ManyToMany',
                        targetEntity: 'App\\Entity\\Role',
                        nullable: true
                    ),
                ]
            ),
            'App\\Entity\\Role' => new EntityMetadata(
                className: 'App\\Entity\\Role',
                tableName: 'role',
                fields: [],
                relationships: []
            ),
        ];

        $graph = $this->resolver->buildDependencyGraph($entities);

        // ManyToMany should not create a dependency
        $this->assertEmpty($graph['App\\Entity\\User']);
        $this->assertEmpty($graph['App\\Entity\\Role']);
    }

    /**
     * Test resolving seeding order with simple dependency chain.
     */
    public function testResolveSeedingOrderSimpleChain(): void
    {
        $entities = [
            'App\\Entity\\User' => new EntityMetadata(
                className: 'App\\Entity\\User',
                tableName: 'user',
                fields: [],
                relationships: [
                    'company' => new RelationshipMetadata(
                        fieldName: 'company',
                        type: 'ManyToOne',
                        targetEntity: 'App\\Entity\\Company',
                        nullable: false
                    ),
                ]
            ),
            'App\\Entity\\Company' => new EntityMetadata(
                className: 'App\\Entity\\Company',
                tableName: 'company',
                fields: [],
                relationships: []
            ),
        ];

        $order = $this->resolver->resolveSeedingOrder($entities);

        // Company should come before User
        $companyIndex = array_search('App\\Entity\\Company', $order, true);
        $userIndex = array_search('App\\Entity\\User', $order, true);
        
        $this->assertNotFalse($companyIndex);
        $this->assertNotFalse($userIndex);
        $this->assertLessThan($userIndex, $companyIndex);
    }

    /**
     * Test resolving seeding order with complex dependency graph.
     */
    public function testResolveSeedingOrderComplexGraph(): void
    {
        $entities = [
            'App\\Entity\\User' => new EntityMetadata(
                className: 'App\\Entity\\User',
                tableName: 'user',
                fields: [],
                relationships: [
                    'company' => new RelationshipMetadata(
                        fieldName: 'company',
                        type: 'ManyToOne',
                        targetEntity: 'App\\Entity\\Company',
                        nullable: false
                    ),
                    'country' => new RelationshipMetadata(
                        fieldName: 'country',
                        type: 'ManyToOne',
                        targetEntity: 'App\\Entity\\Country',
                        nullable: false
                    ),
                ]
            ),
            'App\\Entity\\Company' => new EntityMetadata(
                className: 'App\\Entity\\Company',
                tableName: 'company',
                fields: [],
                relationships: [
                    'country' => new RelationshipMetadata(
                        fieldName: 'country',
                        type: 'ManyToOne',
                        targetEntity: 'App\\Entity\\Country',
                        nullable: false
                    ),
                ]
            ),
            'App\\Entity\\Country' => new EntityMetadata(
                className: 'App\\Entity\\Country',
                tableName: 'country',
                fields: [],
                relationships: []
            ),
        ];

        $order = $this->resolver->resolveSeedingOrder($entities);

        // Country should come first, then Company, then User
        $countryIndex = array_search('App\\Entity\\Country', $order, true);
        $companyIndex = array_search('App\\Entity\\Company', $order, true);
        $userIndex = array_search('App\\Entity\\User', $order, true);
        
        $this->assertNotFalse($countryIndex);
        $this->assertNotFalse($companyIndex);
        $this->assertNotFalse($userIndex);
        $this->assertLessThan($companyIndex, $countryIndex);
        $this->assertLessThan($userIndex, $companyIndex);
    }

    /**
     * Test that circular dependencies are detected.
     */
    public function testDetectCircularDependencies(): void
    {
        $entities = [
            'App\\Entity\\User' => new EntityMetadata(
                className: 'App\\Entity\\User',
                tableName: 'user',
                fields: [],
                relationships: [
                    'company' => new RelationshipMetadata(
                        fieldName: 'company',
                        type: 'ManyToOne',
                        targetEntity: 'App\\Entity\\Company',
                        nullable: false
                    ),
                ]
            ),
            'App\\Entity\\Company' => new EntityMetadata(
                className: 'App\\Entity\\Company',
                tableName: 'company',
                fields: [],
                relationships: [
                    'owner' => new RelationshipMetadata(
                        fieldName: 'owner',
                        type: 'ManyToOne',
                        targetEntity: 'App\\Entity\\User',
                        nullable: false
                    ),
                ]
            ),
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Circular dependency detected');
        
        $this->resolver->resolveSeedingOrder($entities);
    }

    /**
     * Test resolving order with entities that have no dependencies.
     */
    public function testResolveSeedingOrderNoDependencies(): void
    {
        $entities = [
            'App\\Entity\\User' => new EntityMetadata(
                className: 'App\\Entity\\User',
                tableName: 'user',
                fields: [],
                relationships: []
            ),
            'App\\Entity\\Product' => new EntityMetadata(
                className: 'App\\Entity\\Product',
                tableName: 'product',
                fields: [],
                relationships: []
            ),
            'App\\Entity\\Category' => new EntityMetadata(
                className: 'App\\Entity\\Category',
                tableName: 'category',
                fields: [],
                relationships: []
            ),
        ];

        $order = $this->resolver->resolveSeedingOrder($entities);

        // All entities should be in the result
        $this->assertCount(3, $order);
        $this->assertContains('App\\Entity\\User', $order);
        $this->assertContains('App\\Entity\\Product', $order);
        $this->assertContains('App\\Entity\\Category', $order);
    }

    /**
     * Test that dependencies to entities not in the seeding list are ignored.
     */
    public function testBuildDependencyGraphIgnoresExternalEntities(): void
    {
        $entities = [
            'App\\Entity\\User' => new EntityMetadata(
                className: 'App\\Entity\\User',
                tableName: 'user',
                fields: [],
                relationships: [
                    'company' => new RelationshipMetadata(
                        fieldName: 'company',
                        type: 'ManyToOne',
                        targetEntity: 'App\\Entity\\Company',
                        nullable: false
                    ),
                    'groupe' => new RelationshipMetadata(
                        fieldName: 'groupe',
                        type: 'ManyToOne',
                        targetEntity: 'App\\Entity\\Groupe',
                        nullable: false
                    ),
                ]
            ),
            'App\\Entity\\Company' => new EntityMetadata(
                className: 'App\\Entity\\Company',
                tableName: 'company',
                fields: [],
                relationships: []
            ),
            // Note: Groupe is not in the entities list (it's excluded)
        ];

        $graph = $this->resolver->buildDependencyGraph($entities);

        // Should only have dependency on Company, not Groupe
        $this->assertContains('App\\Entity\\Company', $graph['App\\Entity\\User']);
        $this->assertNotContains('App\\Entity\\Groupe', $graph['App\\Entity\\User']);
        $this->assertCount(1, $graph['App\\Entity\\User']);
    }
}
