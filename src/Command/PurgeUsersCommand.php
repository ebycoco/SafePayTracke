<?php
// src/Command/PurgeUsersCommand.php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:purge-users',
    description: 'Purge les utilisateurs ne répondant pas aux critères définis'
)]
class PurgeUsersCommand extends Command
{
    private $userRepository;
    private $entityManager;

    public function __construct(UserRepository $userRepository, EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        // Configuration additionnelle si nécessaire
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $users = $this->userRepository->findAll();
        
        $io->section('Purge des utilisateurs ne répondant pas aux critères définis');

        foreach ($users as $user) {
            $roles = $user->getRoles();
            $isVerified = $user->isVerified();

            // Définir vos critères ici. Par exemple, conserver les utilisateurs vérifiés et ayant des rôles spécifiques.
            if (!$isVerified || !in_array('ROLE_USER', $roles)) {
                $this->entityManager->remove($user);
                $io->text('Utilisateur ID : ' . $user->getId() . ' marqué pour suppression.');
            }
        }

        $this->entityManager->flush();

        $io->success('Les utilisateurs ne répondant pas aux critères définis ont été purgés.');

        return Command::SUCCESS;
    }
}
