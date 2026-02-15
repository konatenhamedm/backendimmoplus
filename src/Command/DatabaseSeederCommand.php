<?php

namespace App\Command;

use App\Service\Seeding\SeedingOptions;
use App\Service\Seeding\SeedingOrchestrator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Symfony console command for seeding the database with fake data.
 * 
 * This command provides a CLI interface to the database seeding system,
 * allowing users to generate realistic test data for all entities except Groupe.
 */
#[AsCommand(
    name: 'app:seed-database',
    description: 'Seed the database with fake data for all entities except Groupe'
)]
class DatabaseSeederCommand extends Command
{
    /**
     * @param SeedingOrchestrator $seedingOrchestrator The orchestrator service
     */
    public function __construct(
        private SeedingOrchestrator $seedingOrchestrator
    ) {
        parent::__construct();
    }

    /**
     * Configure the command options.
     */
    protected function configure(): void
    {
        $this
            ->addOption(
                'count',
                'c',
                InputOption::VALUE_REQUIRED,
                'Number of records to generate per entity',
                10
            )
            ->addOption(
                'entities',
                null,
                InputOption::VALUE_REQUIRED,
                'Comma-separated list of specific entities to seed (e.g., User,Entreprise)'
            )
            ->addOption(
                'clear',
                null,
                InputOption::VALUE_NONE,
                'Clear existing data before seeding (WARNING: This will delete all data!)'
            )
            ->addOption(
                'exclude-groupe',
                null,
                InputOption::VALUE_NONE,
                'Explicitly exclude Groupe table (default: true, this option is for clarity)'
            )
            ->setHelp(
                <<<'HELP'
The <info>app:seed-database</info> command generates fake data for your database entities.

<info>php bin/console app:seed-database</info>

By default, it generates 10 records per entity and excludes the Groupe table.

You can specify the number of records:
<info>php bin/console app:seed-database --count=50</info>

You can seed specific entities only:
<info>php bin/console app:seed-database --entities=User,Entreprise,Locataire</info>

WARNING: Use --clear to delete all existing data before seeding:
<info>php bin/console app:seed-database --clear</info>

The Groupe table is ALWAYS excluded from seeding to preserve user permissions.
HELP
            );
    }

    /**
     * Execute the seeding command.
     * 
     * @param InputInterface $input The input interface
     * @param OutputInterface $output The output interface
     * @return int The command exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Display banner
        $io->title('Database Seeding');
        $io->text('Generating fake data for database entities...');
        $io->newLine();

        try {
            // Parse command options
            $options = $this->parseOptions($input, $io);

            // Warn about --clear flag
            if ($options->shouldClearExisting()) {
                $io->warning('You are about to DELETE ALL EXISTING DATA!');
                
                if (!$io->confirm('Are you sure you want to continue?', false)) {
                    $io->info('Seeding cancelled.');
                    return Command::SUCCESS;
                }
                
                $io->text('Clearing existing data...');
                // Note: Actual clearing will be handled by the orchestrator
                $io->newLine();
            }

            // Display configuration
            $this->displayConfiguration($io, $options);

            // Start seeding
            $io->section('Seeding Progress');
            
            $result = $this->seedingOrchestrator->seed($options);

            // Display results
            $this->displaySummary($io, $result);

            $io->success('Database seeding completed successfully!');
            
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error([
                'An error occurred during seeding:',
                $e->getMessage(),
                '',
                'Stack trace:',
                $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }

    /**
     * Parse command options into SeedingOptions object.
     * 
     * @param InputInterface $input The input interface
     * @param SymfonyStyle $io The styled output
     * @return SeedingOptions The parsed options
     */
    private function parseOptions(InputInterface $input, SymfonyStyle $io): SeedingOptions
    {
        // Parse count option
        $count = (int) $input->getOption('count');
        if ($count < 1) {
            $io->warning(sprintf('Invalid count value: %d. Using default: 10', $count));
            $count = 10;
        }

        // Parse entities option
        $specificEntities = [];
        $entitiesOption = $input->getOption('entities');
        if ($entitiesOption) {
            $entities = array_map('trim', explode(',', $entitiesOption));
            
            // Convert short names to fully qualified class names
            foreach ($entities as $entity) {
                // Remove 'App\Entity\' prefix if provided
                $entity = str_replace('App\\Entity\\', '', $entity);
                $specificEntities[] = 'App\\Entity\\' . $entity;
            }
        }

        // Parse clear option
        $clearExisting = $input->getOption('clear');

        // Groupe is always excluded (this is enforced in SeedingOptions by default)
        $excludedEntities = ['App\\Entity\\Groupe'];

        return new SeedingOptions(
            count: $count,
            specificEntities: $specificEntities,
            clearExisting: $clearExisting,
            excludedEntities: $excludedEntities
        );
    }

    /**
     * Display the seeding configuration.
     * 
     * @param SymfonyStyle $io The styled output
     * @param SeedingOptions $options The seeding options
     */
    private function displayConfiguration(SymfonyStyle $io, SeedingOptions $options): void
    {
        $io->section('Configuration');
        
        $config = [
            'Records per entity' => $options->getCount(),
            'Clear existing data' => $options->shouldClearExisting() ? 'Yes' : 'No',
            'Excluded entities' => implode(', ', array_map(
                fn($class) => basename(str_replace('\\', '/', $class)),
                $options->getExcludedEntities()
            ))
        ];

        if (!empty($options->getSpecificEntities())) {
            $config['Specific entities'] = implode(', ', array_map(
                fn($class) => basename(str_replace('\\', '/', $class)),
                $options->getSpecificEntities()
            ));
        } else {
            $config['Entities to seed'] = 'All (except excluded)';
        }

        $io->horizontalTable(
            array_keys($config),
            [array_values($config)]
        );
        
        $io->newLine();
    }

    /**
     * Display the seeding results summary.
     * 
     * @param SymfonyStyle $io The styled output
     * @param mixed $result The seeding result
     */
    private function displaySummary(SymfonyStyle $io, $result): void
    {
        $io->section('Seeding Results');

        // Display seeded entities
        $seededEntities = $result->getEntitiesSeeded();
        
        if (!empty($seededEntities)) {
            $io->text('<info>Entities Seeded:</info>');
            
            $tableData = [];
            foreach ($seededEntities as $entityClass => $count) {
                $entityName = basename(str_replace('\\', '/', $entityClass));
                $tableData[] = [$entityName, $count];
            }
            
            $io->table(['Entity', 'Records'], $tableData);
        } else {
            $io->warning('No entities were seeded.');
        }

        // Display skipped entities
        $skippedEntities = $result->getEntitiesSkipped();
        
        if (!empty($skippedEntities)) {
            $io->newLine();
            $io->text('<comment>Entities Skipped:</comment>');
            
            $tableData = [];
            foreach ($skippedEntities as $entityClass => $reason) {
                $entityName = basename(str_replace('\\', '/', $entityClass));
                $tableData[] = [$entityName, $reason];
            }
            
            $io->table(['Entity', 'Reason'], $tableData);
        }

        // Display statistics
        $io->newLine();
        $io->text(sprintf(
            '<info>Total records created:</info> %d',
            $result->getTotalRecords()
        ));
        
        $io->text(sprintf(
            '<info>Execution time:</info> %.2f seconds',
            $result->getExecutionTime()
        ));
        
        $io->newLine();
    }
}
