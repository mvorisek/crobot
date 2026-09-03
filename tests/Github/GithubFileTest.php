<?php

declare(strict_types=1);

namespace Mvorisek\Crobot\Tests\Github;

use Atk4\Core\Phpunit\TestCase;
use Mvorisek\Crobot\Github\GithubApi;
use Mvorisek\Crobot\Github\GithubTree;
use PHPUnit\Framework\Attributes\DataProvider;

class GithubFileTest extends TestCase
{
    /**
     * @dataProvider provideIsExecutableCases
     */
    #[DataProvider('provideIsExecutableCases')]
    public function testIsExecutable(string $repo, string $commitSha, string $path, bool $expected): void
    {
        $githubApi = new GithubApi();
        $githubTree = new GithubTree($githubApi, $repo, $commitSha);

        self::assertSame($expected, $githubTree->listFiles()[$path]->isExecutable());
    }

    /**
     * @return iterable<list<mixed>>
     */
    public static function provideIsExecutableCases(): iterable
    {
        yield ['sebastianbergmann/phpunit', '4f1be6d3c782b1290de3753192d0a58549f2dba9', 'LICENSE', false];
        yield ['sebastianbergmann/phpunit', '4f1be6d3c782b1290de3753192d0a58549f2dba9', 'phpunit', true];
        yield ['sebastianbergmann/phpunit', '4f1be6d3c782b1290de3753192d0a58549f2dba9', 'tools/phpstan', false];
    }

    /**
     * @dataProvider provideIsSymlinkCases
     */
    #[DataProvider('provideIsSymlinkCases')]
    public function testIsSymlink(string $repo, string $commitSha, string $path, bool $expected): void
    {
        $githubApi = new GithubApi();
        $githubTree = new GithubTree($githubApi, 'sebastianbergmann/phpunit', '4f1be6d3c782b1290de3753192d0a58549f2dba9');

        self::assertSame($expected, $githubTree->listFiles()[$path]->isSymlink());
    }

    /**
     * @return iterable<list<mixed>>
     */
    public static function provideIsSymlinkCases(): iterable
    {
        yield ['sebastianbergmann/phpunit', '4f1be6d3c782b1290de3753192d0a58549f2dba9', 'LICENSE', false];
        yield ['sebastianbergmann/phpunit', '4f1be6d3c782b1290de3753192d0a58549f2dba9', 'phpunit', false];
        yield ['sebastianbergmann/phpunit', '4f1be6d3c782b1290de3753192d0a58549f2dba9', 'tools/phpstan', true];
    }

    public function testDownloadContent(): void
    {
        $githubApi = new GithubApi();
        $githubTree = new GithubTree($githubApi, 'mvorisek/crobot', '95f25881c55a6419605c0df2b86672d44bdda0ed');

        self::assertSame('Proprietary.' . "\n", $githubTree->listFiles()['LICENSE']->downloadContent());
    }

    public function testFindSymlinkTargetPath(): void
    {
        $githubApi = new GithubApi();
        $githubTree = new GithubTree($githubApi, 'sebastianbergmann/phpunit', '4f1be6d3c782b1290de3753192d0a58549f2dba9');

        self::assertSame('.phpstan/vendor/bin/phpstan', $githubTree->listFiles()['tools/phpstan']->findSymlinkTargetPath());
    }
}
