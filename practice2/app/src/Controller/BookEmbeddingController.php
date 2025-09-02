<?php

namespace App\Controller;

use App\Entity\Books;
use App\Service\EmbeddingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\Tools\Pagination\Paginator;

Class BookEmbeddingController extends AbstractController
{
    public function __construct(protected EmbeddingService $embeddingService, protected EntityManagerInterface $em)
    {
    }
    
    #[Route('/books/embedding/chunks', name: 'app_books_embedding_chunks')]
    public function test(EmbeddingService $embeddingService, EntityManagerInterface $em): JsonResponse
    {
        $page  = 1;
        $limit = 5;
        
        while (true) {
            $qb = $this->em->createQueryBuilder()
                ->select('b')
                ->from('App\Entity\Books', 'b')
                ->orderBy('b.id', 'DESC')
                ->setFirstResult(($page - 1) * $limit) // offset
                ->setMaxResults($limit);               // limit

            $paginator = new Paginator($qb);
            $data = iterator_to_array($paginator);

            if (empty($data)) {
                break; // no more rows
            }

            /** @var Books $book */
            foreach ($data as $book) {
                $bookContent = $book->getContent();
                $chunks = $this->splitTextIntoChunks($bookContent, 2000); // 2000 chars per chunk
                foreach ($chunks as $index => $chunk) {
                    $embeddingService->saveDocument($book->getTitle(), $chunk, [
                        'id' => $book->getId(),
                        'chunk_index' => $index,
                        'title' => $book->getTitle(),
                        'content' => $chunk,
                    ]);    
                
                }
                if ($book->getId() === 5) {
                    $page++; // go to next page
                    continue 2; // skip to while(true), refill $data
                }
            }

            $page++;
        }

        return $this->json([
            'status' => 'completed',
            'message' => 'completed'
        ]);
    }

    private function splitTextIntoChunks(string $text, int $chunkSize = 2000): array 
    {
        $chunks = [];
        $length = mb_strlen($text);
        $start = 0;

        while ($start < $length) {
            $chunks[] = mb_substr($text, $start, $chunkSize);
            $start += $chunkSize;
        }

        return $chunks;
    }

    #[Route('/books/embedding/test-chunk', name: 'app_books_embedding_test_chunk')]
    public function embeddingTest(): JsonResponse
    {
        $page  = 1;
        $limit = 10;
        $return = [];
        while (true) {
            $qb = $this->em->createQueryBuilder()
                ->select('b')
                ->from('App\Entity\Books', 'b')
                ->orderBy('b.id', 'DESC')
                ->setFirstResult(($page - 1) * $limit) // offset
                ->setMaxResults($limit);               // limit

            $paginator = new Paginator($qb);
            $data = iterator_to_array($paginator);

            if (empty($data)) {
                break; // no more rows
            }

            foreach ($data as $book) {
                $bookContent = $book['content'];
                $chunks = $this->splitTextIntoChunks($bookContent, 2000); // 2000 chars per chunk
                foreach ($chunks as $index => $chunk) {
                    $return = [
                        'id' => $book['id'],
                        'chunk_index' => $index,
                        'title' => $book['title'],
                        'content' => $chunk,
                    ];
                
                }
                if ($book->getId() === 5) {
                    $page++; // go to next page
                    continue 2; // skip to while(true), refill $data
                }
            }

            $page++;
        }

        return $this->json([
            'status' => 'completed',
            'message' => 'completed',
            'data' => $return,
        ]);
    }

}