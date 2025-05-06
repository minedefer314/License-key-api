<?php

namespace App\Service\Api\SessionManagement;

use App\DTO\RequestDataDTO;
use App\Entity\License;
use App\Entity\Location;
use App\Entity\Session;
use App\Repository\LicenseRepository;
use App\Repository\LocationRepository;
use Doctrine\ORM\EntityManagerInterface;

class SessionFactory
{
    private EntityManagerInterface $entityManager;
    private LicenseRepository $licenseRepository;
    private LocationRepository $locationRepository;

    public function __construct(EntityManagerInterface $entityManager, LicenseRepository $licenseRepository, LocationRepository $locationRepository)
    {
        $this->entityManager = $entityManager;
        $this->licenseRepository = $licenseRepository;
        $this->locationRepository = $locationRepository;
    }

    // Takes a license and location as parameters for now, but will later take their attributes instead and create them, then create the session
    private function createSession(License $license, Location $location): Session
    {
        $session = new Session($license, $location);
        $license->addSession($session);
        $location->addSession($session);

        $this->entityManager->persist($session);
        $this->entityManager->flush();

        return $session;
    }

    public function createSessionFromDTO(RequestDataDTO $data): Session
    {
        $location = $this->locationRepository->findByIp($data->ipAddress);

        if(!$location) {
            $location = new Location($data->ipAddress);
            $this->entityManager->persist($location);
        }

        $license = $this->licenseRepository->findByLicenseKey($data->licenseKey);

        return $this->createSession($license, $location);
    }
}