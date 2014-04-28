<?php


namespace UJM\ExoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace;
use Claroline\CoreBundle\Entity\User;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * Description of ExoAddonsController
 *
 * @author Kevin Danezis <kdanezis@sii.fr>
 * 
 * @copyright 2014 @ sii.fr for Orange
 *           
 */
class ExoAddonsController extends Controller
{
        public function getExoInSessionAction($desc = null){
   
            $em = $this->getDoctrine()->getManager();
            $session = $this->getRequest()->getSession();
            $paper = $em->getRepository('UJMExoBundle:Paper')->find($session->get('paper'));
            $exo = $paper->getExercise();
            
            return $this->render(
                'UJMExoBundle:Partial:title.html.twig',
                array(
                    'exoTitle'     => $exo->getTitle()
                )
            );
            
            
        }
        
}