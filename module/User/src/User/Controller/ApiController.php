<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace User\Controller;

use Decision\Service\Member;
use User\Service\User;
use Zend\Mvc\Controller\AbstractActionController;

class ApiController extends AbstractActionController
{

    /**
     * @var User
     */
    private $userService;

    /**
     * @var Member
     */
    private $memberService;


    public function __construct(User $userService, Member $memberService)
    {
        $this->userService = $userService;
        $this->memberService = $memberService;
    }

    public function validateAction()
    {
        if ($this->userService->hasIdentity()) {
            $identity = $this->userService->getIdentity();
            $response = $this->getResponse();
            $response->setStatusCode(200);
            $headers = $response->getHeaders();
            $headers->addHeaderLine('GEWIS-MemberID', $identity->getLidnr());
            if ($identity->getMember() != null) {
                $member = $identity->getMember();
                $name = $member->getFullName();
                $headers->addHeaderLine('GEWIS-MemberName', $name);
                $headers->addHeaderLine('GEWIS-MemberEmail', $member->getEmail());
                $memberships = $this->memberService->getOrganMemberships($member);
                $headers->addHeaderLine('GEWIS-MemberGroups', implode(',', array_keys($memberships)));
                return $response;
            }
            $headers->addHeaderLine('GEWIS-MemberName', '');
            return $response;
        }
        $response = $this->getResponse();
        $response->setStatusCode(401);
        return $response;
    }
}
