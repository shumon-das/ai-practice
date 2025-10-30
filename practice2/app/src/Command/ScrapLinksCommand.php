<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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

    protected function configure(): void
    {
        $this
            ->addArgument('filename', InputArgument::OPTIONAL, 'Filename')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
            // ->addArgument('url', InputArgument::OPTIONAL, 'Url')
            // ->addOption('option2', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filename = $input->getArgument('filename');
        if (!$filename) {
            $io->error('Filename is Required');
            return Command::INVALID;
        }

        // $url = $input->getArgument('url');
        // if (!$url) {
        //     $io->error('Url is Required');
        //     return Command::INVALID;
        // }
        $url = 'https://bidyakolpo.in/sharadindu-bandyopadhyay-bangla-golpo-bengali-story/page/';

        $linksDir = $this->params->get('kernel.project_dir') . '/links';
        if (!is_dir($linksDir)) {
            mkdir($linksDir, 0777, true);
        }
        $filename = $linksDir . '/' . $filename . '.txt';

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

            $allLinks = [];
            for ($i=1; $i < 100; $i++) {
                $pageUrl = $url . $i;

                $response = $client->request('GET', $pageUrl);
                if (404 === $response->getStatusCode() || 200 !== $response->getStatusCode()) {
                    $io->success("book page end at $i");
                    break;
                }

                $html = $response->getContent();
                $crawler = new Crawler($html);
                $links = $crawler->filter('.entry-title a')->each(fn($node) => $node->attr('href'));
                $links = array_filter($links); // remove null
                $allLinks = array_merge($allLinks, $links);
            }

            file_put_contents($filename, implode("\n", $allLinks));

            $io->success('All links scraped');

        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
        }

        return Command::SUCCESS;
    }
}
