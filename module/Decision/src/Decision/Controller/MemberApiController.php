<?php

namespace Decision\Controller;

use Decision\Service\Member;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

class MemberApiController extends AbstractActionController
{

    /**
     * @var Member
     */
    private $memberService;

    public function __construct(Member $memberService)
    {
        $this->memberService = $memberService;
    }

    public function lidnrAction()
    {
        $lidnr = $this->params()->fromRoute('lidnr');

        $member = $this->memberService->findMemberByLidNr($lidnr);

        if ($member) {
            return new JsonModel($member->toApiArray());
        }

        return new JsonModel([]);
    }
}
