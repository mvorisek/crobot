<?php

declare(strict_types=1);

namespace Mvorisek\Crobot\Github;

use Mvorisek\Crobot\HttpUtil;

class GithubApi
{
    protected HttpUtil $httpUtil;

    public function __construct()
    {
        $this->httpUtil = new HttpUtil();
    }

    public function decodeDt(string $v): \DateTime
    {
        $res = \DateTime::createFromFormat('Y-m-d\TH:i:s.vP', $v);
        if ($res !== false) {
            return $res;
        }

        return \DateTime::createFromFormat('Y-m-d\TH:i:sP', $v);
    }

    public function encodeDt(\DateTime $v): string
    {
        return (clone $v)->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.v\Z');
    }

    public function getElapsedSecondsFromNowAndDt(\DateTime $v): float
    {
        return microtime(true) - $v->getTimestamp();
    }

    /**
     * @param array<mixed> $response
     *
     * @return array<mixed>
     */
    public function decodeDtRecursively(array $response): array
    {
        $res = $response;
        foreach ($response as $k => $v) {
            if (is_array($v)) {
                $res[$k] = $this->decodeDtRecursively($v);
            }

            if (is_string($k) && str_ends_with($k, '_at') && $v !== null) {
                $res[$k] = $this->decodeDt($v);
            }
        }

        return $res;
    }

    public function logLine(string $v): void
    {
        echo $v . "\n";
    }

    /**
     * @param 'get'|'put'|'post'                                $method
     * @param ($method is 'post' ? array<string, mixed> : null) $data
     *
     * @return array{int<100, 999>, array<string, mixed>}
     */
    public function sendRequest(string $method, string $url, ?array $data = null): ?array
    {
        preg_match('~^https://api.github.com/repos/([^/]+)/~', $url, $matches);
        $repo = $matches[1];

        $tokens = require __DIR__ . '/../../github-token.php.local'; // @phpstan-ignore require.fileNotFound
        $token = is_array($tokens)
            ? $tokens[$repo] ?? $tokens['mvorisek']
            : $tokens;

        $this->logLine("\n" . '>>> ' . strtoupper($method) . ' ' . $url);

        $response = $this->httpUtil->sendRequest(
            $method,
            $url,
            [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',
                'Accept' => 'application/vnd.github+json',
                'Authorization' => 'Bearer ' . $token,
                'X-GitHub-Api-Version' => '2026-03-10',
            ],
            $data !== null ? json_encode($data, \JSON_THROW_ON_ERROR, 512) : null
        );

        $this->logLine('    ' . $response[0]);

        $responseData = $response[2] === ''
            ? null
            : json_decode($response[2], true, 512, \JSON_BIGINT_AS_STRING | \JSON_THROW_ON_ERROR);

        if ($response[0] >= 300) {
            $this->logLine('    API message: ' . ($responseData['message'] ?? 'n/a'));
        }

        return [
            $response[0],
            $responseData,
        ];
    }

    /**
     * @param positive-int $maxCount
     *
     * @return list<array{id: string, created_at: \DateTime, ...<mixed>}> Newer items are sorted last
     */
    public function fetchAllUsingCreatedDtRange(string $url, string $listName, ?int $maxCount, ?\DateTime $minDt = null, ?\DateTime $maxDt = null): array
    {
        if ($maxCount === null) {
            $maxCount = \PHP_INT_MAX;
        }
        if ($minDt === null) {
            $minDt = new \DateTime('2020-01-01 00:00:00 UTC');
        }
        if ($maxDt === null) {
            $maxDt = new \DateTime('now +2 days');
        }

        $maxPageCount = 100;

        $urlSingle = $url;
        $urlSingle .= str_contains($url, '?')
            ? '&'
            : '?';
        $urlSingle .= 'per_page=' . $maxPageCount;
        $urlSingle .= '&created=' . $this->encodeDt($minDt) . '..' . $this->encodeDt($maxDt);

        $response = $this->sendRequest('get', $urlSingle);
        assert($response[0] === 200);

        $list = $response[1][$listName];

        usort($list, function ($a, $b) {
            $res = $this->decodeDt($a['created_at']) <=> $this->decodeDt($b['created_at']);

            return $res !== 0
                ? $res
                : $a['id'] <=> $b['id'];
        });

        if (count($list) >= $maxPageCount) {
            if (count($list) >= $maxCount) {
                $minDt = $this->decodeDt($list[count($list) - $maxCount]['created_at']);
            }

            $middleDt = new \DateTime('@' . intdiv($minDt->getTimestamp() + $maxDt->getTimestamp(), 2));

            $list = $this->fetchAllUsingCreatedDtRange($url, $listName, $maxCount, $middleDt, $maxDt);

            if (count($list) < $maxCount) {
                $list = [
                    ...$this->fetchAllUsingCreatedDtRange($url, $listName, $maxCount - count($list), $minDt, $middleDt),
                    ...$list,
                ];

                $list = array_values(array_combine(
                    array_map(static fn ($v) => $v['id'], $list),
                    $list
                ));
            }
        }

        $list = array_slice($list, -$maxCount);

        return $list;
    }

    public function makeRepoApiUrl(string $repo): string
    {
        return 'https://api.github.com/repos/' . $repo;
    }

    /**
     * @return array{created_at: \DateTime, updated_at: \DateTime, ...<mixed>}
     */
    public function fetchWorkflowDetails(string $repo, string $workflow): array
    {
        $response = $this->sendRequest('get', $this->makeRepoApiUrl($repo) . '/actions/workflows/' . $workflow);
        assert($response[0] === 200);

        return $this->decodeDtRecursively($response[1]);
    }

    public function enableWorkflow(string $repo, string $workflow): void
    {
        $response = $this->sendRequest('put', $this->makeRepoApiUrl($repo) . '/actions/workflows/' . $workflow . '/enable');
        assert($response[0] === 204);
    }

    public function disableWorkflow(string $repo, string $workflow): void
    {
        $response = $this->sendRequest('put', $this->makeRepoApiUrl($repo) . '/actions/workflows/' . $workflow . '/disable');
        assert($response[0] === 204);
    }

    public function keepWorkflowEnabled(string $repo, string $workflow): void
    {
        $details = $this->fetchWorkflowDetails($repo, $workflow);

        $isRecentlyUpdatedFx = fn ($details) => $this->getElapsedSecondsFromNowAndDt($details['updated_at']) < 3600 * 24 * 45;

        if ($details['state'] === 'active' && !$isRecentlyUpdatedFx($details)) {
            $this->disableWorkflow($repo, $workflow);

            $details = $this->fetchWorkflowDetails($repo, $workflow);
        }

        if ($details['state'] === 'disabled_inactivity' || $details['state'] === 'disabled_manually') {
            $this->enableWorkflow($repo, $workflow);

            $details = $this->fetchWorkflowDetails($repo, $workflow);
        }

        assert($details['state'] === 'active' && $isRecentlyUpdatedFx($details));
    }

    /**
     * @param positive-int $maxCount
     *
     * @return list<array<mixed>> Newer runs are sorted last
     */
    public function fetchLastWorkflowRuns(string $repo, string $workflow, ?string $branch, ?int $maxCount, ?\DateTime $minDt = null, ?\DateTime $maxDt = null): array
    {
        $response = $this->fetchAllUsingCreatedDtRange($this->makeRepoApiUrl($repo) . '/actions/workflows/' . $workflow . '/runs?branch=' . $branch, 'workflow_runs', $maxCount);

        return $this->decodeDtRecursively($response);
    }

    /**
     * @param 'heads'|'tags' $filter
     *
     * @return array<string, string>
     */
    protected function listReferences(string $repo, string $filter): array
    {
        $response = $this->sendRequest('get', $this->makeRepoApiUrl($repo) . '/git/matching-refs/' . $filter);
        assert($response[0] === 200);

        $prefix = 'refs/' . $filter . '/';

        return array_combine(
            array_map(static function ($v) use ($prefix) {
                $k = $v['ref'];
                assert(str_starts_with($k, $prefix));

                return substr($k, strlen($prefix));
            }, $response[1]),
            array_map(static fn ($v) => $v['object']['sha'], $response[1])
        );
    }

    /**
     * @return array<string, string>
     */
    public function listBranches(string $repo): array
    {
        return $this->listReferences($repo, 'heads');
    }

    /**
     * @return array<string, string>
     */
    public function listTags(string $repo): array
    {
        return $this->listReferences($repo, 'tags');
    }

    /**
     * @param positive-int $maxCount
     *
     * @return array<string, array<mixed>> Newer commits are sorted last
     */
    public function listLastCommits(string $repo, string $sha, ?int $maxCount, ?\DateTime $minDt = null): array
    {
        if ($maxCount === null) {
            $maxCount = \PHP_INT_MAX;
        }

        $response = $this->sendRequest('get', $this->makeRepoApiUrl($repo) . '/commits?sha=' . $sha . '&per_page=' . min($maxCount, 100) . ($minDt !== null ? '&since=' . $this->encodeDt($minDt) : ''));
        assert($response[0] === 200);

        $res = array_reverse($response[1]);
        $res = array_combine(array_map(static fn ($v) => $v['sha'], $res), $res);
        $res = $this->decodeDtRecursively($res);

        if (count($res) < $maxCount && count($res) === 100) {
            $res = $this->listLastCommits($repo, array_key_first($res), $maxCount - count($res) + 1, $minDt) + $res;
        }

        return $res;
    }
}
