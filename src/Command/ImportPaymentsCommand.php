<?php
// src/Command/ImportPaymentsCommand.php
namespace App\Command;

use App\Service\PaymentImporter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportPaymentsCommand extends Command
{
    protected static $defaultName = 'app:import-payments';

    private $paymentImporter;

    public function __construct(PaymentImporter $paymentImporter)
    {
        $this->paymentImporter = $paymentImporter;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Imports payments from an Excel file')
            ->addArgument('filePath', InputArgument::REQUIRED, 'The path to the Excel file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filePath = $input->getArgument('filePath');

        $this->paymentImporter->importFromFile($filePath);

        $output->writeln('Payments imported successfully.');

        return Command::SUCCESS;
    }
}
