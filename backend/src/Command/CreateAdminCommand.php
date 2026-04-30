<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-admin', description: 'Crea el usuario admin o le asigna ROLE_ADMIN si ya existe')]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepo,
        private UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email       = 'admin@gmail.com';
        $displayName = 'admin';
        $plainPass   = 'admin';

        $user = $this->userRepo->findOneBy(['email' => $email]);

        if ($user === null) {
            $user = new User();
            $user->setEmail($email);
            $user->setDisplayName($displayName);
            $user->setIsVerified(true);
            $user->setPassword($this->hasher->hashPassword($user, $plainPass));
            $this->em->persist($user);
            $output->writeln('<info>Usuario admin creado.</info>');
        } else {
            $output->writeln('<comment>El usuario admin ya existe. Actualizando rol…</comment>');
        }

        $roles = array_filter($user->getRoles(), fn($r) => $r !== 'ROLE_ADMIN' && $r !== 'ROLE_USER');
        $roles[] = 'ROLE_ADMIN';
        $user->setRoles(array_values($roles));

        $this->em->flush();

        $output->writeln('<info>✓ admin tiene ROLE_ADMIN. Credenciales: email=admin@gmail.com / pass=admin</info>');

        return Command::SUCCESS;
    }
}
