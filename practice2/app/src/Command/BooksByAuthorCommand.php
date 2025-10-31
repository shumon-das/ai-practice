<?php
/**
 * using guild
 * 1) scrap:links
 * 2) books:author
 * 3) scrap:post:books
 */
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DomCrawler\Crawler;

#[AsCommand(
    name: 'books:author',
    description: 'Add a short description for your command',
)]
class BooksByAuthorCommand extends Command
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
            ->addArgument('linksfilename', InputArgument::OPTIONAL, 'LinksFilename')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $linksfilename = $input->getArgument('linksfilename');
        if (!$linksfilename) {
            $io->error('Links Filename is Required');
            return Command::INVALID;
        }

        $directory = $this->params->get('kernel.project_dir') . '/links';
        $file = $directory . '/' . $linksfilename;
        $links = include $file;

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

        foreach ($links as $link) {
            $response = $client->request('GET', $link);
            $html = $response->getContent();

            $crawler = new Crawler($html);

            if ($crawler->filter('p')->count() > 0) {
                $bookTitle = $crawler->filter('h1')->count() > 0 ? $crawler->filter('h1')->outerHtml() : '';

                // Combine all paragraphs into one string
                $paragraphs = $crawler->filter('.entry-content p')->each(fn($node) => $node->outerHtml());
                $contentHtml = $bookTitle . implode("\n", $paragraphs);

                $filename = str_replace('<h1 class="page-title">', ' ', $bookTitle);
                $filename = str_replace('</h1>', ' ', $filename);
                $filename = $this->params->get('kernel.project_dir') . '/books/' . $filename. '.txt';
                file_put_contents($filename, $contentHtml);

                $io->success("✅ All <p> elements saved to $filename\n");
            }
        }

        $count = count($links);
        $io->success("*** Total $count Book Scrapped ***");

        return Command::SUCCESS;
    }
}
