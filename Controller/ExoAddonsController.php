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
        
        public function getLessonInSessionAction(AbstractWorkspace $workspace){
             
            $em = $this->getDoctrine()->getManager();
            $session = $this->getRequest()->getSession();
            //get the first lesson
            $lesson = $this->getFirstLessonFromWorkspace($workspace);
            
      
            
            return $this->render(
                'UJMExoBundle:Partial:titleLesson.html.twig',
                array(
                    'name'     => $lesson->getResourceNode()->getName()
                )
            );
        
        }
        
        /**
         * @param AbstractWorkspace $workspace
         *
         * @return \Claroline\CoreBundle\Entity\Resource\ResourceNode
         */
        protected function getFirstLessonFromWorkspace(AbstractWorkspace $workspace)
        {
            $lessonRepository = $this->getDoctrine()->getRepository('IcapLessonBundle:Lesson');
            $resource = $this->getFirstResourceFromWorkspace($workspace, 'icap_lesson');
        
            if ($resource != null) {
                return $lessonRepository->findOneByResourceNode($resource);
            }
            return $resource;
        }
        
        /**
         * Return the first element of the required type
         *
         * @param AbstractWorkspace $workspace
         * @param string $resourceName the name of th resource
         * @param string $mimeType (optionnal) the mime type. Accept wildcards.
         *
         * @return \Claroline\CoreBundle\Entity\Resource\ResourceNode
         */
        private function getFirstResourceFromWorkspace(AbstractWorkspace $workspace, $resourceName, $mimeType = null)
        {
            $doctrine = $this->getDoctrine();
            $resourcesNodeRepository = $doctrine->getRepository('ClarolineCoreBundle:Resource\ResourceNode');
        
            $rootResource = $resourcesNodeRepository->findWorkspaceRoot($workspace);
            $current = $resourcesNodeRepository->findOneBy(array(
                'parent' => $rootResource,
                'previous' => null
            ));
            while (
                $current !== null
                && (
                    $current->getResourceType()->getName() != $resourceName
                    || (
                        ($mimeType !== null)
                        && (!preg_match('#'.$mimeType.'#', $current->getMimeType())
                        )
                    )
                )
            ) {
                $current = $current->getNext();
            }
        
        
            return $current;
        }
        
}