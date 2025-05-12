<?php

namespace App\Command;

use App\Repository\SessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:terminate-expired-sessions',
)]
class TerminateExpiredSessionsCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private SessionRepository $sessionRepository;

    public function __construct(SessionRepository $sessionRepository, EntityManagerInterface $entityManager)
    {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->sessionRepository = $sessionRepository;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Terminates all expired sessions.')
            ->addArgument(
                name: "expiration",
                description: "The expiration time in minutes."
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $expiration = $input->getArgument('expiration');
        $expirationDelay = intval($expiration) ?? null;

        $expiredSessions = $this->sessionRepository->findActiveExpiredSessions($expirationDelay);

        foreach ($expiredSessions as $session) {
            $session->terminate();
            $this->entityManager->persist($session);

            $output->writeln(sprintf(
                "Session terminated :\n\tId: %u\n\tLicense: %s\n\tIp: %s\n\tOwner: %s\n\tDuration: %s",
                $session->getId(),
                $session->getLicense()->getUuid(),
                $session->getLocation()->getIpAddr(),
                $session->getLicense()->getOwner(),
                $session->getDuration()->format('%h hour(s), %i minute(s), %s second(s)')
            ));
        }

        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
