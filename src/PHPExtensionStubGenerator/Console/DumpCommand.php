<?php
declare(strict_types=1);

namespace PHPExtensionStubGenerator\Console;

use Iterator;
use PHPExtensionStubGenerator\FilesDumper;
use ReflectionExtension;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DumpCommand extends Command
{
    protected static $defaultName = 'dump-files';

    protected function configure(): void
    {
        $this
            ->addArgument('extension-name', InputArgument::REQUIRED, 'The targeted PHP extension')
            ->addArgument('output-directory', InputArgument::REQUIRED, 'The output directory of generated stubs files')
            ->setHelp('This command dump stubs files for a given extension to a given directory.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $extensionName = $input->getArgument('extension-name');
        $outputDirectory = $input->getArgument('output-directory');

        try {
            $fileDumper = new FilesDumper(new ReflectionExtension($extensionName));
            $fileDumper->dumpFiles($outputDirectory);
        } catch (\Throwable $exception) {
            $io->error('Error while dumping: '. $exception->getMessage());

            return Command::FAILURE;
        }

        $io->success('Stubs are dumped to: '. $outputDirectory);

        return Command::SUCCESS;
    }


   /* protected function getGenerationTargets() : Iterator
    {
        foreach (parent::getGenerationTargets() as $file => $code) {
            $this->console->writeLine($file);
            yield $file => $code;
        }
    }*/
}