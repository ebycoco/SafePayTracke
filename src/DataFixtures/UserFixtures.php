<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private $passwordEncoder;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordEncoder = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $usersData = [
            ['email' => 'aroapartners@gmail.com', 'password' => '123456', 'numero' => '1234567890', 'nomDeSociete' => 'AROA Partners', 'isVerified' => true, 'roles' => ['ROLE_LOCATEUR']],
            ['email' => 'd2a@gmail.com', 'password' => '123456', 'numero' => '0987654321', 'nomDeSociete' => 'D2A', 'isVerified' => true, 'roles' => ['ROLE_LOCATEUR']],
            ['email' => 'ndoumy@gmail.com', 'password' => '123456', 'numero' => '1122334455', 'nomDeSociete' => 'M.Ndoumy', 'isVerified' => true, 'roles' => ['ROLE_LOCATEUR']],
            ['email' => 'gemica@gmail.com', 'password' => '123456', 'numero' => '5566778899', 'nomDeSociete' => 'GEMICA', 'isVerified' => true, 'roles' => ['ROLE_LOCATEUR']],
            ['email' => 'info@gmail.com', 'password' => '123456', 'numero' => '5566778899', 'nomDeSociete' => 'DUNAMIST SECURITY', 'isVerified' => true, 'roles' => ['ROLE_GARDIEN']],
            ['email' => 'brouyaoeric7@gmail.com', 'password' => '123456', 'numero' => '5566778899', 'nomDeSociete' => 'AROA', 'isVerified' => true, 'roles' => ['ROLE_ADMIN']],
        ];

        foreach ($usersData as $userData) {
            $user = new User();
            $user->setEmail($userData['email']);
            $user->setRoles($userData['roles']);
            $user->setPassword($this->passwordEncoder->hashPassword($user, $userData['password']));
            $user->setNumero($userData['numero']);
            $user->setNomDeSociete($userData['nomDeSociete']);
            $user->setIsVerified($userData['isVerified']);

            $manager->persist($user);
        }

        $manager->flush();
    }
}
