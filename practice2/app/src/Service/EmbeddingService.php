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
        $titleEmbed = $this->embed($title);
        $contentEmbed = $this->embed($content);
        $titleVectorStr = '[' . implode(',', $titleEmbed) . ']';
        $contentVectorStr = '[' . implode(',', $contentEmbed) . ']';

        $jsonData = json_encode($data);

        $this->conn->executeStatement(
            'INSERT INTO document (title, content, data) VALUES (:titleEmbed, :contentEmbed, :jsonData)',
            ['titleEmbed' => $titleVectorStr, 'contentEmbed' => $contentVectorStr, 'jsonData' => $jsonData],
        );
    }

    public function search(string $query, int $limit = 5): array
    {
        $embedding = $this->embed($query);
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
}
