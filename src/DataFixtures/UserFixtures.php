<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager)
    {
        // Create a new User entity
        $user = new User();
        $user->setEmail('admin@harakirimail.com');
        $user->setFirstName('Admin');
        $user->setLastName('User');

        // Set the password (hashed)
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'password123');
        $user->setPassword($hashedPassword);

        // Set roles
        $user->setRoles(['ROLE_ADMIN']);

        // Set timestamps
        $user->setCreatedAt(new \DateTime());
        $user->setUpdatedAt(new \DateTime());

        // Persist the user to the database
        $manager->persist($user);
        $manager->flush();
    }
}
