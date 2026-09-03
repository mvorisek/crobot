<?php

declare(strict_types=1);

namespace Mvorisek\Crobot\Tests;

use Atk4\Core\Phpunit\TestCase;
use Mvorisek\Crobot\Github\GithubApi;
use PHPUnit\Framework\Attributes\DataProvider;

class CronTest extends TestCase
{
    /**
     * @dataProvider provideKeepGithubWorkflowEnabledCases
     */
    #[DataProvider('provideKeepGithubWorkflowEnabledCases')]
    public function testKeepGithubWorkflowEnabled(string $repo, string $workflow): void
    {
        $githubApi = new GithubApi();
        $githubApi->keepWorkflowEnabled($repo, $workflow);

        self::assertTrue(true); // @phpstan-ignore staticMethod.alreadyNarrowedType
    }

    /**
     * @return iterable<list<mixed>>
     */
    public static function provideKeepGithubWorkflowEnabledCases(): iterable
    {
        foreach (static::provideGithubWorkflowIfLastRunIsNotTooOldCases() as [$repo, $workflow]) {
            yield [$repo, $workflow];
        }
    }

    /**
     * @dataProvider provideGithubWorkflowIfLastRunIsNotTooOldCases
     */
    #[DataProvider('provideGithubWorkflowIfLastRunIsNotTooOldCases')]
    public function testGithubWorkflowIfLastRunIsNotTooOld(string $repo, string $workflow, string $branch, float $maxElapsedHours = 6): void
    {
        $githubApi = new GithubApi();
        $runs = $githubApi->fetchLastWorkflowRuns($repo, $workflow, $branch, 1);

        self::assertLessThan($maxElapsedHours, $githubApi->getElapsedSecondsFromNowAndDt(array_last($runs)['created_at']) / 3600);
    }

    /**
     * @return iterable<list<mixed>>
     */
    public static function provideGithubWorkflowIfLastRunIsNotTooOldCases(): iterable
    {
        yield ['mvorisek/crobot', 'test-unit.yml', 'main', 0.9];

        yield ['mvorisek/image-php', 'ci.yml', 'master', 35 * 24];

        yield ['atk4/core', 'test-unit.yml', 'develop'];
        yield ['atk4/data', 'test-unit.yml', 'develop'];
        yield ['atk4/ui', 'test-unit.yml', 'develop'];
        yield ['atk4/chart', 'test-unit.yml', 'develop'];
        yield ['atk4/i18n', 'test-unit.yml', 'develop'];
        yield ['atk4/login', 'test-unit.yml', 'develop'];
    }
}
