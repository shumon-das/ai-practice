<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DomCrawler\Crawler;

#[AsCommand(
    name: 'scrap:links',
    description: 'Scrape story content from a webpage safely.',
)]
class ScrapLinksCommand extends Command
{
    private $params;

    public function __construct(ParameterBagInterface $params)
    {
        parent::__construct();
        $this->params = $params;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $url = 'https://bidyakolpo.in/premendra-mitra-bangla-golpo-bengali-story/';

        $linksDir = $this->params->get('kernel.project_dir') . '/links';
        if (!is_dir($linksDir)) {
            mkdir($linksDir, 0777, true);
        }
        $filename = $linksDir .'/premendra_mitra.txt';

        try {
            $client = HttpClient::create([
                'timeout' => 120,
                'max_redirects' => 5,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) '
                        . 'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ]
            ]);

            $response = $client->request('GET', $url);
            $html = $response->getContent();

            $crawler = new Crawler($html);

            // ✅ Get all links
            $links = $crawler->filter('.entry-title a')->each(fn($node) => $node->attr('href'));
            $links = array_filter($links); // remove null
            file_put_contents($filename, implode("\n", $links));

            $io->success('links scraped');

        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
        }

        return Command::SUCCESS;
    }
}
