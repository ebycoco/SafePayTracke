<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Faker\Factory;
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
        $faker = Factory::create();

        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            if ($i === 0) {
                $user->setEmail("brouyaoeric7@gmail.com");
                $user->setRoles(['ROLE_ADMIN']);
            } else {
                $user->setEmail($faker->email);
                $user->setRoles(['ROLE_USER']);
            }
            $hashedPassword = $this->passwordEncoder->hashPassword($user, '123456');
            $user->setPassword($hashedPassword); // Vous devez utiliser un mot de passe sécurisé

            // Générer des données aléatoires avec Faker
            $user->setNumero($faker->phoneNumber);
            $user->setNomDeSociete($faker->company);

            $manager->persist($user);
        }

        $manager->flush();
    }
}
