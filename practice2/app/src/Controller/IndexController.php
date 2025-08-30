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


Class IndexController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'IndexController',
        ]);
    }

    #[Route('/books/insert-books', name: 'app_books_insert_books')]
    public function insertBooks(EntityManagerInterface $em): Response
    {
        return $this->render('books/insert.html.twig', [
            'message' => 'Create a new Book'
        ]);
    }

    #[Route('/books/save', name: 'app_save_book', methods: ['POST'])]
    public function saveBook(Request $request, EntityManagerInterface $em, EmbeddingService $embeddingService): Response
    {
        try {
            $req = $request->request->all();
            if (!isset($req['title']) || !isset($req['content'])) {
                return $this->render('books/insert.html.twig', [
                    'message' => 'Title and Content is required'
                ]);
            }

            /** @var Books $book */
            $book = new Books();
            $book->setTitle($req['title'])->setContent($req['content']);
            $em->persist($book);
            $em->flush();

            $shortContent = mb_substr($book->getContent(), 0, 50, 'UTF-8') . '...';
            $data = [
                'id' => $book->getId(),
                'title' => $book->getTitle(),
                'content' => $shortContent
            ];

            $embeddingService->saveDocument($book->getTitle(), $shortContent, $data);

            return $this->render('books/insert.html.twig', [
                'message' => 'Book Inserted Successfully!'
            ]);
        } catch (\Exception $e) {
            return $this->render('books/insert.html.twig', [
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    #[Route('/books/search', name: 'app_search_book_page', methods: ['GET'])]
    public function searchPage(Request $request, EmbeddingService $embeddingService): Response
    {
        return $this->render('books/search.html.twig', [
            'message' => 'Search functionality to be implemented',
            'data' => []
        ]);
    }

    #[Route('/books/search', name: 'app_search_book', methods: ['POST'])]
    public function searchBook(Request $request, EmbeddingService $embeddingService): Response
    {
        $req = $request->request->all();
        $search = $req['search'];
        $searchResults = $embeddingService->search($search, 10);

        $data = [];
        foreach ($searchResults as &$item) {
            $data[] = json_decode($item['data'], true);
        }

        return $this->render('books/search.html.twig', [
            'message' => 'Search functionality to be implemented',
            'data' => $data
        ]);
    }

    #[Route('/books/insert', name: 'app_books_insert')]
    public function insert(EntityManagerInterface $em): JsonResponse
    {
        /** @var Books $book */
        $book = new Books();
        $book
            ->setTitle('Book Title ' . rand(1, 100))
            ->setContent('Book Content ' . rand(1, 100));

        $em->persist($book);
        $em->flush();

        $books = $em->getRepository(Books::class)->findBy([], ['id' => 'DESC']);
        $allBooks = array_map(function ($book) {
            return [
                'id' => $book->getId(),
                'title' => $book->getTitle(),
                'content' => $book->getContent(),
            ];
        }, $books);

        return new JsonResponse([
            'status' => true,
            'message' => 'Book inserted!',
            'data' => $allBooks
        ]);
    }

    #[Route('/books/embedding/save', name: 'app_books_embedding_save')]
    public function save(EmbeddingService $embeddingService): JsonResponse
    {
        $title = 'The Great Gatsby';
        $content = 'The Great Gatsby is a 1925 novel by American writer F. Scott Fitzgerald. Set in the Jazz Age on Long Island, near New York City, the novel depicts first-person narrator Nick Carraway\'s interactions with mysterious millionaire Jay Gatsby and Gatsby\'s obsession';
        $shortContent = substr($content, 0, 50) . '...';
        $embeddingService->saveDocument($title, $content, ['id' => 1, 'title' => $title, 'content' => $shortContent]);
        
        return new JsonResponse([
            'status' => true,
            'message' => 'Books embedding saved!',
            'data' => []
        ]);
    }
}