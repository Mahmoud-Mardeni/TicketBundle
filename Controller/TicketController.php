<?php

namespace Hackzilla\Bundle\TicketBundle\Controller;

use AndreaSprega\Bundle\BreadcrumbBundle\Annotation\Breadcrumb;
use Hackzilla\Bundle\TicketBundle\Entity\TicketMessage;
use Hackzilla\Bundle\TicketBundle\Event\TicketEvent;
use Hackzilla\Bundle\TicketBundle\Form\Type\AssignmentType;
use Hackzilla\Bundle\TicketBundle\Form\Type\TicketMessageType;
use Hackzilla\Bundle\TicketBundle\Form\Type\TicketType;
use Hackzilla\Bundle\TicketBundle\Model\TicketInterface;
use Hackzilla\Bundle\TicketBundle\Model\TicketMessageInterface;
use Hackzilla\Bundle\TicketBundle\TicketEvents;
use Hackzilla\Bundle\TicketBundle\TicketRole;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Ticket controller.
 * @Breadcrumb({
 *     {"label" = "الرئيسية", "route" = "homepage", "params" = {}},
 *     {"label" = "الدعم الفني", "route" = "hackzilla_ticket", "params" = {}},
 * })
 */
class TicketController extends Controller
{
    /**
     * Lists all Ticket entities.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Security("has_role('ROLE_USER')")
     * @Breadcrumb({
     *   "label" = "قائمة التذاكر",
     * })
     */
    public function indexAction(Request $request)
    {
        $userManager = $this->getUserManager();
        $ticketManager = $this->get('hackzilla_ticket.ticket_manager');

        $ticketPriorityStr = $request->get('priority', null);
        $ticketPriority = $ticketManager->getTicketPriority($ticketPriorityStr);
        $viewData = [];
        if ($this->get('security.authorization_checker')->isGranted('assign_tickets')) {
            $notAssignedQuery = $ticketManager->getTicketList(
                $userManager,
                TicketMessage::STATUS_OPEN,
                $ticketPriority,
                false
            );
            $paginationNotAssigned = $this->get('knp_paginator')->paginate(
                $notAssignedQuery->getQuery(),
                $request->query->get('not_assigned_page', 1)/*page number*/,
                10/*limit per page*/,
                ['pageParameterName' => 'not_assigned_page']
            );
            $viewData['paginationNotAssigned'] = $paginationNotAssigned;
        }

        $openedQuery = $ticketManager->getTicketList(
            $userManager,
            TicketMessage::STATUS_OPEN,
            $ticketPriority,
            true
        );

        $closedQuery = $ticketManager->getTicketList(
            $userManager,
            TicketMessage::STATUS_CLOSED,
            $ticketPriority,
            null
        );


        $paginationOpened = $this->get('knp_paginator')->paginate(
            $openedQuery->getQuery(),
            $request->query->get('opened_page', 1)/*page number*/,
            10/*limit per page*/
            ,
            ['pageParameterName' => 'opened_page']
        );

        $paginationClosed = $this->get('knp_paginator')->paginate(
            $closedQuery->getQuery(),
            $request->query->get('closed_page', 1)/*page number*/,
            5/*limit per page*/
            ,
            ['pageParameterName' => 'closed_page']
        );

        return $this->render(
            $this->container->getParameter('hackzilla_ticket.templates')['index'], array_merge($viewData,
                [
                    'paginationClosed' => $paginationClosed,
                    'paginationOpened' => $paginationOpened,
                    'ticketPriority' => $ticketPriorityStr,
                ]
            )
        );
    }

    public function viewAllAction(Request $request)
    {
        $userManager = $this->getUserManager();
        $ticketManager = $this->get('hackzilla_ticket.ticket_manager');

        $ticketPriorityStr = $request->get('priority', null);
        $ticketPriority = $ticketManager->getTicketPriority($ticketPriorityStr);
        $viewData = [];
            $notAssignedQuery = $ticketManager->getTicketList(
                $userManager,
                TicketMessage::STATUS_OPEN,
                $ticketPriority,
                false,
                false
            );
            $paginationNotAssigned = $this->get('knp_paginator')->paginate(
                $notAssignedQuery->getQuery(),
                $request->query->get('not_assigned_page', 1)/*page number*/,
                10/*limit per page*/,
                ['pageParameterName' => 'not_assigned_page']
            );
            $viewData['paginationNotAssigned'] = $paginationNotAssigned;


        $openedQuery = $ticketManager->getTicketList(
            $userManager,
            TicketMessage::STATUS_OPEN,
            $ticketPriority,
            true,
            false
        );

        $closedQuery = $ticketManager->getTicketList(
            $userManager,
            TicketMessage::STATUS_CLOSED,
            $ticketPriority,
            null,
            false
        );


        $paginationOpened = $this->get('knp_paginator')->paginate(
            $openedQuery->getQuery(),
            $request->query->get('opened_page', 1)/*page number*/,
            10/*limit per page*/
            ,
            ['pageParameterName' => 'opened_page']
        );

        $paginationClosed = $this->get('knp_paginator')->paginate(
            $closedQuery->getQuery(),
            $request->query->get('closed_page', 1)/*page number*/,
            5/*limit per page*/
            ,
            ['pageParameterName' => 'closed_page']
        );

        $jwt=null;
        if($this->container->getParameter('notification_client_enabled')) {
            $authentication = $this->container->get('notification.instant_notification.authenticator')->login(-1,'technical_support','channel');
            if (array_key_exists('token', $authentication)) {
                $jwt = $authentication['token'];
            }
        }

        return $this->render(
            "@HackzillaTicket/Ticket/view_all.html.twig", array_merge($viewData,
                [
                    'paginationClosed' => $paginationClosed,
                    'paginationOpened' => $paginationOpened,
                    'ticketPriority' => $ticketPriorityStr,
                    'jwt'=>$jwt
                ]
            )
        );
    }

    /**
     * Creates a new Ticket entity.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @Security("has_role('ROLE_USER')")
     */
    public function createAction(Request $request)
    {
        $ticketManager = $this->get('hackzilla_ticket.ticket_manager');

        $ticket = $ticketManager->createTicket();
        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $message = $ticket->getMessages()->current();
            $message->setStatus(TicketMessageInterface::STATUS_OPEN)
                ->setUser($this->getUserManager()->getCurrentUser());

            $ticketManager->updateTicket($ticket, $message);
            $this->dispatchTicketEvent(TicketEvents::TICKET_CREATE, $ticket);

            return $this->redirect($this->generateUrl('hackzilla_ticket_show', ['ticketId' => $ticket->getId()]));
        }

        return $this->render(
            $this->container->getParameter('hackzilla_ticket.templates')['new'],
            [
                'entity' => $ticket,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Displays a form to create a new Ticket entity.
     * @Security("has_role('ROLE_USER')")
     * @Breadcrumb({
     *   "label" = "تذكرة جديدة",
     * })
     */
    public function newAction()
    {
        $ticketManager = $this->get('hackzilla_ticket.ticket_manager');
        $entity = $ticketManager->createTicket();

        $form = $this->createForm(TicketType::class, $entity);

        return $this->render(
            $this->container->getParameter('hackzilla_ticket.templates')['new'],
            [
                'entity' => $entity,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Finds and displays a TicketInterface entity.
     *
     * @param int $ticketId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Breadcrumb({
     *   "label" = "عرض تذكرة",
     * })
     * @Security("has_role('ROLE_USER')")
     */
    public function showAction($ticketId)
    {
        $ticketManager = $this->get('hackzilla_ticket.ticket_manager');
        $ticket = $ticketManager->getTicketById($ticketId);

        if (!$ticket) {
            return $this->redirect($this->generateUrl('hackzilla_ticket'));
        }

        $currentUser = $this->getUserManager()->getCurrentUser();
        $this->getUserManager()->hasPermission($currentUser, $ticket);

        $data = ['ticket' => $ticket];

        $message = $ticketManager->createMessage($ticket);

        if ($ticketManager->canReplyToTicket($ticket, $currentUser)) {
            $data['form'] = $this->createMessageForm($message)->createView();
        }

        if (!$ticket->getAssignedToUser() || $ticket->getAssignedToUser() == $currentUser->getId()) {
            if ($currentUser && $this->getUserManager()->hasRole($currentUser, TicketRole::ADMIN)) {
                $data['delete_form'] = $this->createDeleteForm($ticket->getId())->createView();
                if (TicketMessageInterface::STATUS_CLOSED != $ticket->getStatus() && $this->canAssignTicket($ticket)) {
                    $data['assign_form'] = $this->createAssignmentForm($ticket->getId())->createView();
                }
            }
        }


        return $this->render($this->container->getParameter('hackzilla_ticket.templates')['show'], $data);
    }

    /**
     * Finds and displays a TicketInterface entity.
     *
     * @param Request $request
     * @param int $ticketId
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @Security("has_role('ROLE_USER')")
     */
    public function replyAction(Request $request, $ticketId)
    {
        $ticketManager = $this->get('hackzilla_ticket.ticket_manager');
        $ticket = $ticketManager->getTicketById($ticketId);

        if (!$ticket) {
            throw $this->createNotFoundException($this->get('translator')->trans('ERROR_FIND_TICKET_ENTITY'));
        }

        $user = $this->getUserManager()->getCurrentUser();
        $this->getUserManager()->hasPermission($user, $ticket);

        if (!$ticketManager->canReplyToTicket($ticket, $user)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(403);

        }

        $message = $ticketManager->createMessage($ticket);

        $form = $this->createMessageForm($message);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $message->setUser($user);
            $ticketManager->updateTicket($ticket, $message);

            if (!$ticket->getAssignedToUser()) {
                $ticketManager->assignTicket($ticket, $user);
            }

            $this->dispatchTicketEvent(TicketEvents::TICKET_UPDATE, $ticket);

            return $this->redirect($this->generateUrl('hackzilla_ticket_show', ['ticketId' => $ticket->getId()]));
        }

        $data = ['ticket' => $ticket, 'form' => $form->createView()];


        if ($user && $this->get('hackzilla_ticket.user_manager')->hasRole($user, TicketRole::ADMIN) && (!$ticket->getAssignedToUser() || $ticket->getAssignedToUser() == $user->getId())) {
            $data['delete_form'] = $this->createDeleteForm($ticket->getId())->createView();
        }

        return $this->render($this->container->getParameter('hackzilla_ticket.templates')['show'], $data);
    }

    /**
     * Deletes a Ticket entity.
     *
     * @param Request $request
     * @param int $ticketId
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Security("has_role('ROLE_USER')")
     */
    public function deleteAction(Request $request, $ticketId)
    {
        $userManager = $this->getUserManager();
        $user = $userManager->getCurrentUser();

        if (!\is_object($user) || !$userManager->hasRole($user, TicketRole::ADMIN)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(403);
        }

        $form = $this->createDeleteForm($ticketId);

        if ($request->isMethod('DELETE')) {
            $form->submit($request->request->get($form->getName()));

            if ($form->isValid()) {
                $ticketManager = $this->get('hackzilla_ticket.ticket_manager');
                $ticket = $ticketManager->getTicketById($ticketId);

                if (!$ticket) {
                    throw $this->createNotFoundException($this->get('translator')->trans('ERROR_FIND_TICKET_ENTITY'));
                }

                $ticketManager->deleteTicket($ticket);
                $this->dispatchTicketEvent(TicketEvents::TICKET_DELETE, $ticket);
            }
        }

        return $this->redirect($this->generateUrl('hackzilla_ticket'));
    }

    /**
     * Assign a Ticket entity to User.
     *
     * @param Request $request
     * @param int $ticketId
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Security("has_role('ROLE_USER')")
     */
    public function assignAction(Request $request, $ticketId)
    {
        $userManager = $this->getUserManager();
        $user = $userManager->getCurrentUser();

        if (!\is_object($user) || !$userManager->hasRole($user, TicketRole::ADMIN)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(403);
        }


        $form = $this->createAssignmentForm($ticketId);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $ticketManager = $this->get('hackzilla_ticket.ticket_manager');
            $ticket = $ticketManager->getTicketById($ticketId);
            if (!$ticket) {
                throw $this->createNotFoundException($this->get('translator')->trans('ERROR_FIND_TICKET_ENTITY'));
            }
            if ((TicketMessageInterface::STATUS_CLOSED == $ticket->getStatus()) || !$this->canAssignTicket($ticket)) {
                throw new \Symfony\Component\HttpKernel\Exception\HttpException(403);
            }
            $toUserId = $form->getData()['toUser'];
            $toUser = $userManager->getUserById($toUserId);
            if (!$this->get('security.authorization_checker')->isGranted('manage_tickets', $toUserId)) {
                throw new \Symfony\Component\HttpKernel\Exception\HttpException(403);
            }
            $ticketManager->assignTicket($ticket, $toUser);
            $this->dispatchTicketEvent(TicketEvents::TICKET_ASSIGN, $ticket);

            return $this->redirect($this->generateUrl('hackzilla_ticket_show', ['ticketId' => $ticket->getId()]));
        }

        return $this->redirect($this->generateUrl('hackzilla_ticket_show', ['ticketId' => $ticketId]));


    }

    private function dispatchTicketEvent($ticketEvent, TicketInterface $ticket)
    {
        $event = new TicketEvent($ticket);
        $this->get('event_dispatcher')->dispatch($ticketEvent, $event);
    }

    /**
     * Creates a form to delete a Ticket entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(['id' => $id])
            ->add('id', HiddenType::class)
            ->getForm();
    }

    public function createAssignmentForm($ticketId)
    {
        return $this->createForm(
            AssignmentType::class,
            ['ticketId' => $ticketId],
            ['to_users' => $this->get('hackzilla_ticket.admins_manager')->getAdminsAsSelect()]
        );

    }

    /**
     * @param TicketMessageInterface $message
     *
     * @return \Symfony\Component\Form\Form
     */
    private function createMessageForm(TicketMessageInterface $message)
    {
        $form = $this->createForm(
            TicketMessageType::class,
            $message,
            ['new_ticket' => false]
        );

        return $form;
    }

    /**
     * @return \Hackzilla\Bundle\TicketBundle\Manager\UserManagerInterface
     */
    private function getUserManager()
    {
        $userManager = $this->get('hackzilla_ticket.user_manager');

        return $userManager;
    }

    private function canAssignTicket(TicketInterface $ticket)
    {
        $userManager = $this->getUserManager();
        $user = $userManager->getCurrentUser();
        if ($ticket->getAssignedToUser() && $ticket->getAssignedToUser() != $user->getId()) {
            return false;
        }
        return true;
    }

}
