<?php
namespace App\Service;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpClient\HttpClient;
use Doctrine\DBAL\ParameterType;

class EmbeddingService
{
    private $http;
    private $conn;
    private $apiToken = "eW5Fcuguc5a0hEARYoEGvSLNmqHfTXsB";
    private $model = "sentence-transformers/all-MiniLM-L6-v2";

    public function __construct(Connection $conn)
    {
        $this->http = HttpClient::create();
        $this->conn = $conn;
    }

    public function embedWithDownloadedModel(string $text): array
    {
        $url = "http://ollamatest:11434/api/embeddings";

        $response = $this->http->request('POST', $url, [
            'headers' => ['Content-Type'  => 'application/json'],
            'json' => [
                "model" => "Definity/snowflake-arctic-embed-l-v2.0-q8_0:latest",
                "prompt" => $text
            ]
        ]);

        $data = $response->toArray();

        if (isset($data['error'])) {
            throw new \RuntimeException("HF API error: " . $data['error']);
        }

        return $data['embedding'];
    }

    public function embed(string $text): array
    {
        $url = "https://api.mistral.ai/v1/embeddings";

        $response = $this->http->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                "model" => "mistral-embed",
                "input" => [$text]
            ]
        ]);

        $data = $response->toArray();

        if (isset($data['error'])) {
            throw new \RuntimeException("HF API error: " . $data['error']);
        }

        return $data['data'][0]['embedding'];
    }

    public function saveDocument(string $title, string $content, array $data): void
    {
        $title   = $this->normalize($title);
        $content = $this->normalize($content);

        $titleEmbed = $this->embedWithDownloadedModel($title);
        $contentEmbed = $this->embedWithDownloadedModel($content);
        $titleVectorStr = '[' . implode(',', $titleEmbed) . ']';
        $contentVectorStr = '[' . implode(',', $contentEmbed) . ']';

        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);

        $this->conn->executeStatement(
            'INSERT INTO document (title, content, data) VALUES (:titleEmbed, :contentEmbed, :jsonData)',
            ['titleEmbed' => $titleVectorStr, 'contentEmbed' => $contentVectorStr, 'jsonData' => $jsonData],
        );
    }

    public function search(string $query, int $limit = 5): array
    {
        // $embedding = $this->embed($query);
        $embedding = $this->embedWithDownloadedModel($query);
        $vectorStr = '[' . implode(',', $embedding) . ']';

        $sql = '
            SELECT id, data
            FROM document
            ORDER BY content <-> :embedding::vector
            LIMIT ' . (int) $limit;

        return $this->conn->fetchAllAssociative(
            $sql,
            ['embedding' => $vectorStr],
            ['embedding' => ParameterType::STRING]
        );
    }

    private function normalize(string $text): string
    {
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        $text = preg_replace('/[\x00-\x1F\x7F]/u', '', $text); // strip control chars
        return $text;
    }
}
