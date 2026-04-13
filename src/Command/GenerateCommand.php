<?php

declare(strict_types=1);

namespace Xterr\Espd\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Xterr\Espd\Criterion\TaxonomyMapper;
use Xterr\UBL\Generator\Config\GeneratorConfig;
use Xterr\UBL\Generator\UblGenerator;

#[AsCommand(
    name: 'espd:generate',
    description: 'Generate ESPD PHP classes, codelist enums, and v2↔v4 criterion mapping',
)]
final class GenerateCommand extends Command
{
    private const V2_TAXONOMY = 'resources/criterion/v2.1.1/ESPD-CriteriaTaxonomy-Basic.xml';
    private const V4_TAXONOMY = 'resources/criterion/v4.1.0/ESPD-criterion.xml';
    private const MAPPING_OUTPUT = 'resources/criterion/v2-to-v4-mapping.php';
    private const GC_OUTPUT = 'resources/codelists/gc/CriteriaTypeCode.gc';
    private const GENERATOR_CONFIG = 'ubl-generator.yaml';

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Actually generate (without this, dry-run)')
            ->addOption('codelists-only', null, InputOption::VALUE_NONE, 'Only regenerate codelist enums (skip classes)')
            ->addOption('mapping-only', null, InputOption::VALUE_NONE, 'Only regenerate v2→v4 mapping (skip generator)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('ESPD PHP Code Generator');

        $force = (bool) $input->getOption('force');
        $codelistsOnly = (bool) $input->getOption('codelists-only');
        $mappingOnly = (bool) $input->getOption('mapping-only');

        if (!$force) {
            $io->warning('Dry-run mode. Use --force to actually generate files.');

            return Command::SUCCESS;
        }

        $io->section('Step 1: Parsing criterion taxonomies');

        $mapper = new TaxonomyMapper();

        $v2Map = $mapper->parse(self::V2_TAXONOMY);
        $io->text(sprintf('  V2 taxonomy: %d criteria', count($v2Map)));

        $v4Map = $mapper->parse(self::V4_TAXONOMY);
        $io->text(sprintf('  V4 taxonomy: %d criteria', count($v4Map)));

        $io->section('Step 2: Cross-referencing UUIDs');

        $mapping = $mapper->crossReference($v2Map, $v4Map);
        $v2OnlyCodes = array_diff_key($v2Map, $v4Map);
        $io->text(sprintf('  Mapped: %d codes, V2-only: %d codes', count($mapping), count($v2OnlyCodes)));

        $io->section('Step 3: Generating v2→v4 mapping file');

        $mapper->generateMappingFile($mapping, self::MAPPING_OUTPUT);
        $io->text('  → ' . self::MAPPING_OUTPUT);

        $io->section('Step 4: Generating CriteriaTypeCode.gc');

        $mapper->generateGenericode($v2Map, self::GC_OUTPUT);
        $io->text('  → ' . self::GC_OUTPUT);

        if ($mappingOnly) {
            $io->success('Mapping files generated successfully.');

            return Command::SUCCESS;
        }

        $io->section('Step 5: Running UBL generator');

        if (!file_exists(self::GENERATOR_CONFIG)) {
            $io->error('Generator config not found: ' . self::GENERATOR_CONFIG);

            return Command::FAILURE;
        }

        $config = GeneratorConfig::fromYaml(self::GENERATOR_CONFIG);
        $generator = new UblGenerator($config);

        $progressCallback = static function (string $stage, int $current, int $total) use ($io): void {
            $io->text('  ' . $stage . '...');
        };

        if ($codelistsOnly) {
            $result = $generator->generateCodelists($progressCallback);
        } else {
            $result = $generator->generate($progressCallback);
        }

        $io->section('Result');
        $io->success($result->summary());

        return Command::SUCCESS;
    }
}
