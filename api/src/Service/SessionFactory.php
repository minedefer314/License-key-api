<?php

namespace App\Service;

use App\Entity\License;
use App\Entity\Location;
use App\Entity\Session;
use Doctrine\ORM\EntityManagerInterface;

class SessionFactory
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    // Takes a license and location as parameters for now, but will later take their attributes instead and create them, then create the session
    public function createSession(License $license, Location $location): Session
    {
        $session = new Session($license, $location);
        $license->addSession($session);
        $location->addSession($session);

        $this->em->persist($session);
        $this->em->flush();

        return $session;
    }
}