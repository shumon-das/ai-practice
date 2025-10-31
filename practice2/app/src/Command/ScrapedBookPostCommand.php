<?php
/**
 * using guild
 * 1) scrap:links filename url
 * 2) books:author linksfilename
 * 3) scrap:post:books authorid
 */
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DomCrawler\Crawler;

#[AsCommand(
    name: 'scrap:post:books',
    description: 'Scrape story content from a webpage safely.',
)]
class ScrapedBookPostCommand extends Command
{
    private $params;

    public function __construct(ParameterBagInterface $params)
    {
        parent::__construct();
        $this->params = $params;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('authorid', InputArgument::OPTIONAL, 'Author Id')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $authorid = $input->getArgument('authorid');
        if (!$authorid) {
            $io->error('Author Id is Required');
            return Command::INVALID;
        }


        $directory = $this->params->get('kernel.project_dir') . '/books';
        if (!is_dir($directory)) {
            $io->error('Books directory not found');
        }

        // Get all .txt files
        $files = glob($directory . '/*.txt');
        
        if (!$files) {
            $io->warning(['message' => 'No .txt files found in books directory']);
        }

        $client = HttpClient::create([
            // ⏱️ Extend timeout and allow redirects
            'timeout' => 120,
            'max_redirects' => 5,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) '
                    . 'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ]
        ]);

        $loginResponse = $client->request('POST', 'https://api.bookspointer.com/api/login', [
            'json' => [
                'email' => 'shakhordas400@gmail.com',
                'password' => '444444'
            ],
        ]);
        $response = $loginResponse->toArray();
        $token = $response['token'];
        $user = $response['user'];

        if ($token) {
            // $file = $files[1];
            foreach($files as $file) {
                $content = file_get_contents($file);
                $crawler = new Crawler($content);
                
                $title = $crawler->filter('h1')->count() > 0 ? $crawler->filter('h1')->text() : null;
                
                // Remove the <h1> element from the original content
                $crawler->filter('h1')->each(function ($node) {
                    $node->getNode(0)->parentNode->removeChild($node->getNode(0));
                });
                
                $payload = $this->setData($title, $content, $authorid);
                $response = $client->request('POST', 'https://api.bookspointer.com/admin/create-book', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                    'body' =>  http_build_query([
                        'data' => json_encode($payload),
                    ]),
                ]);

                $io->success("$title posted successfully");
            }
        }
        $filesCount = count($files);
        $io->success("*** Total $filesCount Books posted successfully ***");

        return Command::SUCCESS;
    }

    private function setData(string $title, string $content, int $authorid): array
    {
        $data['title'] = $title;
        $data['content'] = $content;
        $data['category']['id'] = 20;
        $data['author']['id'] = $authorid;
        $data["estimatedReadTime"] = ["words" => 0, "minutes" => 1];
        $data['seriesName'] = '';
        $data['tags'] = [];

        return $data;
    }
}
