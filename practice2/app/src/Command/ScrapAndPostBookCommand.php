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
    name: 'scrap:post-books',
    description: 'Scrape story content from a webpage safely.',
)]
class ScrapAndPostBookCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $directory = $this->getParameter('kernel.project_dir') . '/books';
        if (!is_dir($directory)) {
            $io->error('Books directory not found');
        }

        // Get all .txt files
        $files = glob($directory . '/*.txt');

        if (!$files) {
            return new JsonResponse(['message' => 'No .txt files found in books directory']);
        }

        $url = 'https://bidyakolpo.in/jashim-uddin-bangla-golpo-bengali-story/page/2/';

    }
}
