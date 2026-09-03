<?php

declare(strict_types=1);

namespace Mvorisek\Crobot\Tests\Github;

use Atk4\Core\Phpunit\TestCase;
use Mvorisek\Crobot\Github\GithubApi;
use Mvorisek\Crobot\Github\GithubIsFileException;
use Mvorisek\Crobot\Github\GithubPathNotFoundException;
use Mvorisek\Crobot\Github\GithubTree;
use PHPUnit\Framework\Attributes\DataProvider;

class GithubTreeTest extends TestCase
{
    /**
     * @dataProvider provideListFilesCases
     *
     * @param list<string> $expectedPaths
     */
    #[DataProvider('provideListFilesCases')]
    public function testListFiles(string $repo, string $commitSha, string $path, bool $recursive, array $expectedPaths): void
    {
        $githubApi = new GithubApi();
        $githubTree = new GithubTree($githubApi, $repo, $commitSha);

        self::assertSame($expectedPaths, array_keys($githubTree->listFiles($path, $recursive)));
    }

    /**
     * @return iterable<list<mixed>>
     */
    public static function provideListFilesCases(): iterable
    {
        foreach (static::provideListFilesRecursiveCases() as [$repo, $commitSha, $path, $expectedPaths]) {
            yield [$repo, $commitSha, $path, true, $expectedPaths];

            yield [$repo, $commitSha, $path, false, array_values(array_filter($expectedPaths, static function ($v) use ($path) {
                $vDir = dirname($v);
                if ($vDir === '.') {
                    $vDir = '';
                }

                return $vDir === $path || $v === $path;
            }))];
        }

        // repo with 100k+ files, API response is truncated when recursive flag is used
        yield ['NixOS/nixpkgs', '8c50a710ddca43d7a530fb805ad55bde8d0141c5', '', false, [
            '.editorconfig',
            '.git-blame-ignore-revs',
            '.gitattributes',
            '.gitignore',
            '.mailmap',
            '.version',
            'CONTRIBUTING.md',
            'COPYING',
            'README.md',
            'default.nix',
            'flake.nix',
            'shell.nix',
        ]];

        yield ['NixOS/nixpkgs', '8c50a710ddca43d7a530fb805ad55bde8d0141c5', 'pkgs', false, [
            'pkgs/README.md',
        ]];

        yield ['NixOS/nixpkgs', '8c50a710ddca43d7a530fb805ad55bde8d0141c5', 'pkgs/by-name', false, [
            'pkgs/by-name/README.md',
        ]];

        yield ['NixOS/nixpkgs', '8c50a710ddca43d7a530fb805ad55bde8d0141c5', 'pkgs/by-name/aa', false, []];
    }

    /**
     * @return iterable<list<mixed>>
     */
    public static function provideListFilesRecursiveCases(): iterable
    {
        yield ['mvorisek/crobot', '95f25881c55a6419605c0df2b86672d44bdda0ed', '', [
            '.github/workflows/test-unit.yml',
            '.gitignore',
            '.php-cs-fixer.dist.php',
            'LICENSE',
            'README.md',
            'composer.json',
            'phpstan.neon.dist',
            'phpunit.xml.dist',
            'src/GithubApi.php',
            'src/HttpUtil.php',
            'tests/CronTest.php',
        ]];

        yield ['mvorisek/crobot', '95f25881c55a6419605c0df2b86672d44bdda0ed', '.github', [
            '.github/workflows/test-unit.yml',
        ]];

        yield ['mvorisek/crobot', '95f25881c55a6419605c0df2b86672d44bdda0ed', '.github/workflows', [
            '.github/workflows/test-unit.yml',
        ]];

        yield ['NixOS/nixpkgs', '8c50a710ddca43d7a530fb805ad55bde8d0141c5', 'pkgs/by-name/aa', [
            'pkgs/by-name/aa/aaa/package.nix',
            'pkgs/by-name/aa/aaaaxy/package.nix',
            'pkgs/by-name/aa/aab/allow-manually-setting-modtime.patch',
            'pkgs/by-name/aa/aab/fix-flaky-tests.patch',
            'pkgs/by-name/aa/aab/only-call-git-when-necessary.patch',
            'pkgs/by-name/aa/aab/package.nix',
            'pkgs/by-name/aa/aacgain/package.nix',
            'pkgs/by-name/aa/aactivator/package.nix',
            'pkgs/by-name/aa/aalib/clang.patch',
            'pkgs/by-name/aa/aalib/darwin.patch',
            'pkgs/by-name/aa/aalib/ncurses-6.5.patch',
            'pkgs/by-name/aa/aalib/package.nix',
            'pkgs/by-name/aa/aaphoto/package.nix',
            'pkgs/by-name/aa/aapt/package.nix',
            'pkgs/by-name/aa/aarch64-esr-decoder/package.nix',
            'pkgs/by-name/aa/aardvark-dns/package.nix',
            'pkgs/by-name/aa/aasvg-rs/package.nix',
            'pkgs/by-name/aa/aasvg/package.nix',
            'pkgs/by-name/aa/aaxtomp3/package.nix',
        ]];
    }

    /**
     * @dataProvider provideFindSubtreeIsFileExceptionCases
     */
    #[DataProvider('provideFindSubtreeIsFileExceptionCases')]
    public function testFindSubtreeIsFileException(string $repo, string $commitSha, string $path): void
    {
        $githubApi = new GithubApi();
        $githubTree = new GithubTree($githubApi, $repo, $commitSha);

        $this->expectException(GithubIsFileException::class);
        $this->expectExceptionMessageIs('Path is a file');
        $githubTree->findSubtree($path);
    }

    /**
     * @return iterable<list<mixed>>
     */
    public static function provideFindSubtreeIsFileExceptionCases(): iterable
    {
        foreach (['README.md', '.github/workflows/test-unit.yml'] as $path) {
            yield ['mvorisek/crobot', '95f25881c55a6419605c0df2b86672d44bdda0ed', $path];
        }
    }

    public function testFindSubtreePathInFileException(): void
    {
        $githubApi = new GithubApi();
        $githubTree = new GithubTree($githubApi, 'mvorisek/crobot', '95f25881c55a6419605c0df2b86672d44bdda0ed');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageIs('Part of the path is a file');
        $githubTree->findSubtree('.github/workflows/test-unit.yml/foo');
    }

    /**
     * @dataProvider provideFindSubtreePathNotFoundExceptionCases
     */
    #[DataProvider('provideFindSubtreePathNotFoundExceptionCases')]
    public function testFindSubtreePathNotFoundException(string $repo, string $commitSha, string $path): void
    {
        $githubApi = new GithubApi();
        $githubTree = new GithubTree($githubApi, $repo, $commitSha);

        $this->expectException(GithubPathNotFoundException::class);
        $this->expectExceptionMessageIs('Path does not exist');
        $githubTree->findSubtree($path);
    }

    /**
     * @return iterable<list<mixed>>
     */
    public static function provideFindSubtreePathNotFoundExceptionCases(): iterable
    {
        foreach (['.githu', '.github/foo'] as $path) {
            yield ['mvorisek/crobot', '95f25881c55a6419605c0df2b86672d44bdda0ed', $path];
        }
    }
}
