<?php

namespace App\Service\Seeding;

/**
 * Analyzes entity relationships and determines the correct order for seeding.
 * 
 * This service builds a dependency graph based on foreign key relationships
 * and performs topological sorting to ensure that referenced entities are
 * seeded before entities that reference them.
 */
class DependencyResolver
{
    /**
     * Build a dependency graph from entity metadata.
     * 
     * This method analyzes entity relationships and creates an adjacency list
     * representing dependencies. ManyToOne and OneToOne relationships are
     * considered as dependencies (the entity depends on the target entity).
     * 
     * @param array<string, EntityMetadata> $entities Array of entity metadata indexed by class name
     * @return array<string, array<string>> Adjacency list where keys are entity class names
     *                                       and values are arrays of entity class names they depend on
     */
    public function buildDependencyGraph(array $entities): array
    {
        $graph = [];
        
        // Initialize graph with all entities
        foreach ($entities as $className => $metadata) {
            $graph[$className] = [];
        }
        
        // Build dependencies based on relationships
        foreach ($entities as $className => $metadata) {
            $relationships = $metadata->getRelationships();
            
            foreach ($relationships as $relationship) {
                $type = $relationship->getType();
                $targetEntity = $relationship->getTargetEntity();
                
                // Only ManyToOne and OneToOne relationships create dependencies
                // (the entity needs the target entity to exist first)
                // However, if the relationship is nullable, it's not a hard dependency
                if (($type === 'ManyToOne' || $type === 'OneToOne') && !$relationship->isNullable()) {
                    // Only add dependency if the target entity is in our seeding list
                    if (isset($entities[$targetEntity])) {
                        // Add target entity as a dependency
                        if (!in_array($targetEntity, $graph[$className], true)) {
                            $graph[$className][] = $targetEntity;
                        }
                    }
                }
                
                // OneToMany and ManyToMany don't create dependencies for the owning side
                // They will be handled by the inverse side or can be populated after
            }
        }
        
        return $graph;
    }

    /**
     * Resolve the seeding order using topological sort.
     * 
     * This method performs a topological sort on the dependency graph to determine
     * the correct order for seeding entities. Entities with no dependencies are
     * seeded first, followed by entities that depend on them.
     * 
     * @param array<string, EntityMetadata> $entities Array of entity metadata indexed by class name
     * @return array<string> Array of entity class names in seeding order
     * @throws \RuntimeException If circular dependencies are detected
     */
    public function resolveSeedingOrder(array $entities): array
    {
        $graph = $this->buildDependencyGraph($entities);
        
        // Check for circular dependencies
        $cycle = $this->detectCircularDependencies($graph);
        if ($cycle !== null) {
            throw new \RuntimeException(
                'Circular dependency detected: ' . implode(' -> ', $cycle)
            );
        }
        
        // Perform topological sort using Kahn's algorithm
        return $this->topologicalSort($graph);
    }

    /**
     * Perform topological sort using Kahn's algorithm.
     * 
     * @param array<string, array<string>> $graph Adjacency list representing dependencies
     * @return array<string> Array of entity class names in topological order
     */
    private function topologicalSort(array $graph): array
    {
        $sorted = [];
        $inDegree = [];
        
        // Calculate in-degree for each node
        foreach ($graph as $node => $dependencies) {
            if (!isset($inDegree[$node])) {
                $inDegree[$node] = 0;
            }
            
            foreach ($dependencies as $dependency) {
                if (!isset($inDegree[$dependency])) {
                    $inDegree[$dependency] = 0;
                }
                $inDegree[$node]++;
            }
        }
        
        // Find all nodes with in-degree 0 (no dependencies)
        $queue = [];
        foreach ($inDegree as $node => $degree) {
            if ($degree === 0) {
                $queue[] = $node;
            }
        }
        
        // Process nodes in order
        while (!empty($queue)) {
            // Remove a node from the queue
            $current = array_shift($queue);
            $sorted[] = $current;
            
            // For each node that depends on the current node
            foreach ($graph as $node => $dependencies) {
                if (in_array($current, $dependencies, true)) {
                    // Decrease in-degree
                    $inDegree[$node]--;
                    
                    // If in-degree becomes 0, add to queue
                    if ($inDegree[$node] === 0) {
                        $queue[] = $node;
                    }
                }
            }
        }
        
        return $sorted;
    }

    /**
     * Detect circular dependencies in the dependency graph.
     * 
     * Uses depth-first search to detect cycles in the graph.
     * 
     * @param array<string, array<string>> $graph Adjacency list representing dependencies
     * @return array<string>|null Array representing the cycle if found, null otherwise
     */
    private function detectCircularDependencies(array $graph): ?array
    {
        $visited = [];
        $recursionStack = [];
        $path = [];
        
        foreach ($graph as $node => $dependencies) {
            if (!isset($visited[$node])) {
                $cycle = $this->dfsDetectCycle($node, $graph, $visited, $recursionStack, $path);
                if ($cycle !== null) {
                    return $cycle;
                }
            }
        }
        
        return null;
    }

    /**
     * Depth-first search helper for cycle detection.
     * 
     * @param string $node Current node being visited
     * @param array<string, array<string>> $graph Adjacency list representing dependencies
     * @param array<string, bool> $visited Array tracking visited nodes
     * @param array<string, bool> $recursionStack Array tracking nodes in current recursion path
     * @param array<string> $path Current path being explored
     * @return array<string>|null Array representing the cycle if found, null otherwise
     */
    private function dfsDetectCycle(
        string $node,
        array $graph,
        array &$visited,
        array &$recursionStack,
        array &$path
    ): ?array {
        $visited[$node] = true;
        $recursionStack[$node] = true;
        $path[] = $node;
        
        // Visit all dependencies
        if (isset($graph[$node])) {
            foreach ($graph[$node] as $dependency) {
                if (!isset($visited[$dependency])) {
                    $cycle = $this->dfsDetectCycle($dependency, $graph, $visited, $recursionStack, $path);
                    if ($cycle !== null) {
                        return $cycle;
                    }
                } elseif (isset($recursionStack[$dependency]) && $recursionStack[$dependency]) {
                    // Found a cycle - extract the cycle from the path
                    $cycleStart = array_search($dependency, $path, true);
                    $cycle = array_slice($path, $cycleStart);
                    $cycle[] = $dependency; // Close the cycle
                    return $cycle;
                }
            }
        }
        
        // Remove from recursion stack and path
        $recursionStack[$node] = false;
        array_pop($path);
        
        return null;
    }
}
