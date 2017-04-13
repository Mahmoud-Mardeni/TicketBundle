<?php

namespace Hackzilla\Bundle\TicketBundle\Manager;

use Doctrine\ORM\EntityRepository;
use Hackzilla\Bundle\TicketBundle\Model\TicketInterface;
use Hackzilla\Bundle\TicketBundle\Model\UserInterface;
use Hackzilla\Bundle\TicketBundle\TicketRole;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AdminsManager
{

    public function getAdminsAsSelect()
    {
        return [];
    }

    public function getAdmins()
    {

    }

}
