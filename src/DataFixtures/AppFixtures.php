<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker;
use function Symfony\Component\Clock\now;

class AppFixtures extends Fixture
{

    private UserPasswordHasherInterface $encoder;

    public function __construct(UserPasswordHasherInterface $encoder){
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager): void
    {

        // On instancie le Faker en langue française
        $faker = Faker\Factory::create('fr_FR');

        // Création d'un compte admin
        $admin = new User();

        $admin
            ->setEmail('a@a.a')
            ->setRegistrationDate( $faker->dateTimeBetween('-1 year', 'now') )
            ->setPseudonym('Hilda')
            ->setRoles(['ROLE_ADMIN'])
            ->setPassword(
                $this->encoder->hashPassword($admin, 'Azerty12!')
            )
        ;

        $manager->persist($admin);

        // Création de 10 comptes utilisateurs

        for($i = 0; $i < 10; $i++){

            $user = new User();

            $user
                ->setEmail($faker->email)
                ->setRegistrationDate( $faker->dateTimeBetween('-1 year', 'now') )
                ->setPseudonym($faker->userName)
                ->setPassword(
                    $this->encoder->hashPassword($admin, 'Azerty12!')
                )
            ;

            $manager->persist($user);

        }

        // Création de 50 articles
        for($i = 0; $i < 50; $i++){

            $article = new Article();

            $article
                ->setTitle( $faker->sentence(10) )
                ->setContent( $faker->paragraph(15) )
                ->setPublicationDate(  $faker->dateTimeBetween('-1 year', 'now') )
                ->setAuthor( $admin )
                ;

            $manager->persist( $article );

        }


        $manager->flush();
    }
}
