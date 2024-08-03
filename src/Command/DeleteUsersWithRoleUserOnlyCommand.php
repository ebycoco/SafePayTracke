<?php
// src/Command/DeleteUsersWithRoleUserOnlyCommand.php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:delete-users-with-role-user-only',
    description: 'Supprime les utilisateurs avec uniquement le rôle ROLE_USER'
)]
class DeleteUsersWithRoleUserOnlyCommand extends Command
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
        
        $io->section('Vérification des utilisateurs avec uniquement le rôle ROLE_USER');

        foreach ($users as $user) {
            $roles = $user->getRoles();
            $io->text('Utilisateur ID : ' . $user->getId() . ', Rôles : ' . json_encode($roles));
            
            if ($roles === ['ROLE_USER']) {
                $this->entityManager->remove($user);
                $io->text('Utilisateur ID : ' . $user->getId() . ' marqué pour suppression.');
            }
        }

        $this->entityManager->flush();

        $io->success('Tous les utilisateurs avec uniquement le rôle ROLE_USER ont été supprimés.');

        return Command::SUCCESS;
    }
}
