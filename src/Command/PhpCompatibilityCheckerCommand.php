<?php

declare(strict_types=1);

namespace Ilzrv\PhpCompatibilityChecker\Command;

use Composer\Semver\Semver;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'check',
)]
final class PhpCompatibilityCheckerCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('phpVersion', InputArgument::REQUIRED, 'PHP version for comparison ("8.0", "8.1")')
            ->addArgument('composerLockPath', InputArgument::REQUIRED, 'Path to composer.lock file');
    }


    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);

        $phpVersion = $input->getArgument('phpVersion');
        $composerLockPath = $input->getArgument('composerLockPath');

        if (file_exists($composerLockPath) === false) {
            throw new InvalidArgumentException('composer.lock file "' . $composerLockPath . '" not found');
        }

        $rows = [];

        $rules = $this->getRules();
        $packages = $this->getInstalledPackages($composerLockPath);

        foreach ($packages as $package) {
            if (!array_key_exists($package['name'], $rules)) {
                continue;
            }

            $constraints = $rules[$package['name']];

            foreach ($constraints as $constraint) {
                if ($constraint['php'] !== $phpVersion) {
                    continue;
                }

                $isSupported = !\is_null($constraint['working-version'])
                    && Semver::satisfies($package['version'], $constraint['working-version']);

                if (!$isSupported) {
                    $rows[] = [
                        $package['name'],
                        $package['version'],
                        '<error>' . ($constraint['working-version'] ?? 'Not supported' ). '</error>',
                        $constraint['problems'] === [] ? '-' : implode(', ', $constraint['problems']),
                        $constraint['comments'] === [] ? '-' : implode(', ', $constraint['comments']),
                    ];
                }
            }
        }

        if ($rows !== []) {
            foreach ($rows as $row) {
                $io->section('Package: ' . $row[0]);
                $io->listing([
                    'Current version: ' . $row[1],
                    'Working version: ' . $row[2],
                    'Problems: ' . $row[3],
                    'Comments: ' . $row[4],
                ]);
            }
        } else {
            $io->success("Packages are compatibility with PHP \"$phpVersion\" version");
        }

        return Command::SUCCESS;
    }

    /**
     * @throws \JsonException
     */
    private function getRules(): array
    {
        return json_decode(
            file_get_contents(__DIR__ . '/../../rules.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    /**
     * @throws \JsonException
     */
    private function getInstalledPackages(
        string $composerLockPath
    ): array {
        return json_decode(
            file_get_contents($composerLockPath),
            true,
            512,
            JSON_THROW_ON_ERROR
        )['packages'];
    }
}
