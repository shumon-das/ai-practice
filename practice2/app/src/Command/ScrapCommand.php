<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DomCrawler\Crawler;

#[AsCommand(
    name: 'scrap:text',
    description: 'Scrape story content from a webpage safely.',
)]
class ScrapCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        // $url = 'https://bidyakolpo.in/bengali-story-amola-by-bolaichand-mukhopadhyay-bonoful/';
//         $url = 'https://bidyakolpo.in/ashol-benarosi-langra-story-ashapurna-devi/';
        $url = 'https://bidyakolpo.in/jogmohoner-mrittu-story-mahasweta-devi/';
        try {
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

            // ✅ Important: use getContent(false) to skip waiting for long idle timeout
            $response = $client->request('GET', $url);
            $html = $response->getContent();

            $crawler = new Crawler($html);

            if ($crawler->filter('p')->count() > 0) {
                $bookTitle = $crawler->filter('h1')->count() > 0 ? $crawler->filter('h1')->outerHtml() : '';

                // Combine all paragraphs into one string
                $paragraphs = $crawler->filter('.entry-content p')->each(fn($node) => $node->outerHtml());
                $contentHtml = $bookTitle . implode("\n", $paragraphs);

                $filename = str_replace('<h1 class="page-title">', ' ', $bookTitle);
                $filename = str_replace('</h1>', ' ', $filename) . '.html';
                file_put_contents($filename, $contentHtml);

                echo "✅ All <p> elements saved to $filename\n";
            } else {
                echo "⚠️ No <p> tags found.\n";
            }

        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
        }

        return Command::SUCCESS;
    }
}
