<?php

declare(strict_types=1);

namespace Mvorisek\Crobot\Github;

class GithubTreeStaged
{
    protected GithubTree $baseTree;

    /** @var array<string, array{string, string}|false> */
    protected array $newFiles = [];

    public function __construct(GithubTree $baseTree)
    {
        $this->baseTree = $baseTree;
    }

    public function addFile(string $path, string $content, bool $isExecutable = false): void
    {
        $this->newFiles[$path] = [$content, $isExecutable ? '100755' : '100644'];
    }

    public function deleteFile(string $path): void
    {
        $this->newFiles[$path] = false;
    }

    protected function uploadBlob(string $content): string
    {
        $requestData = [
            'content' => base64_encode($content),
            'encoding' => 'base64',
        ];

        $response = $this->baseTree->getApi()->sendRequest('post', $this->baseTree->getApi()->makeRepoApiUrl($this->baseTree->getRepo()) . '/git/blobs', $requestData);
        assert($response[0] === 201);

        return $response[1]['sha'];
    }

    public function upload(): GithubTree
    {
        if ($this->newFiles === []) {
            return $this->baseTree;
        }

        $requestData = [
            'base_tree' => $this->baseTree->getSha(),
            'tree' => [],
        ];

        foreach ($this->newFiles as $path => $item) {
            if ($item === false) {
                $itemData = [
                    'path' => $path,
                    'mode' => '100644',
                    'type' => 'blob',
                    'sha' => null,
                ];
            } else {
                $itemData = [
                    'path' => $path,
                    'mode' => $item[1],
                    'type' => 'blob',
                    'content' => $item[0],
                ];

                if (strlen($item[0]) >= 1024 * 1024 || str_contains($item[0], "\x00") || !mb_check_encoding($item[0], 'UTF-8')) {
                    unset($itemData['content']);
                    $itemData['sha'] = $this->uploadBlob($item[0]);
                }
            }

            $requestData['tree'][] = $itemData;
        }

        $response = $this->baseTree->getApi()->sendRequest('post', $this->baseTree->getApi()->makeRepoApiUrl($this->baseTree->getRepo()) . '/git/trees', $requestData);
        assert($response[0] === 201);

        return new GithubTree($this->baseTree->getApi(), $this->baseTree->getRepo(), $response[1]['sha']);
    }
}
