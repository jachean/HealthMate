<?php

namespace App\Command;

use App\Service\TimeSlotGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:timeslots:generate',
    description: 'Generates sliding 1h time slots (30m step) and demo appointments'
)]
final class GenerateTimeSlotsCommand extends Command
{
    public function __construct(
        private readonly TimeSlotGenerator $generator
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Starting time slot generation...</info>');

        $this->generator->run();

        $output->writeln('<info>Time slots generated successfully.</info>');

        return Command::SUCCESS;
    }
}
