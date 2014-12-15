<?php

/**
 * ExoOnLine
 * Copyright or © or Copr. Université Jean Monnet (France), 2012
 * dsi.dev@univ-st-etienne.fr
 *
 * This software is a computer program whose purpose is to [describe
 * functionalities and technical features of your software].
 *
 * This software is governed by the CeCILL license under French law and
 * abiding by the rules of distribution of free software.  You can  use,
 * modify and/ or redistribute the software under the terms of the CeCILL
 * license as circulated by CEA, CNRS and INRIA at the following URL
 * "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and  rights to copy,
 * modify and redistribute granted by the license, users are provided only
 * with a limited warranty  and the software's author,  the holder of the
 * economic rights,  and the successive licensors  have only  limited
 * liability.
 *
 * In this respect, the user's attention is drawn to the risks associated
 * with loading,  using,  modifying and/or developing or reproducing the
 * software by the user in light of its specific status of free software,
 * that may mean  that it is complicated to manipulate,  and  that  also
 * therefore means  that it is reserved for developers  and  experienced
 * professionals having in-depth computer knowledge. Users are therefore
 * encouraged to load and test the software's suitability as regards their
 * requirements in conditions enabling the security of their systems and/or
 * data to be ensured and,  more generally, to use and operate it in the
 * same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
*/

namespace UJM\ExoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

use UJM\ExoBundle\Entity\Exercise;
use UJM\ExoBundle\Entity\Paper;
use UJM\ExoBundle\Form\PaperType;

use JMS\DiExtraBundle\Annotation as DI;

use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;

use Claroline\CoreBundle\Library\Resource\ResourceCollection;
use Claroline\CoreBundle\Controller\Badge\Tool;
use Claroline\CoreBundle\Entity\Badge\Badge;
use Claroline\CoreBundle\Manager\BadgeManager;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Pager\PagerFactory;
use UJM\ExoBundle\Repository\PaperRepository;
use UJM\ExoBundle\Services\classes\exerciseServices;
use UJM\ExoBundle\Entity\ExerciseUser;

/**
 * Paper controller.
 *
 */
class PaperController extends Controller
{

	/** @var exerciseServices */
	protected $exerciseServices;
	
	/** @var BadgeManager */
	private $badgeManager;
	
	/** @var PagerFactory */
    private $pagerFactory;
    
    /** @var EntityManager */
    private $em;

    /** @var PaperRepository */
    protected $paperRepository;
    
    /** @var LinkHintPaperRepository */
    protected $linkHintPaperRepository;

    /** @var ExerciseRepository */
    protected $exerciseRepository;

    /** @var UserRepository */
    protected $userRepository;

    /** @var ResponseRepository */
    protected $responseRepository;

    /** @var ExerciseUserRepository */
    protected $exerciseUserRepository;
    
	/**
	 * Constructor.
	 *
	 * @DI\InjectParams({
	 *     "exerciseServices" = @DI\Inject("ujm.exercise_services"),
	 *     "container"		  = @DI\Inject("service_container"),
     *     "pagerFactory" = @DI\Inject("claroline.pager.pager_factory"),
	 *     "badgeManager" = @DI\Inject("claroline.manager.badge")
	 * })
	 */
	public function __construct(
			$container,
			exerciseServices $exerciseServices,
			BadgeManager $badgeManager,
			PagerFactory $pagerFactory) {
		$this->setContainer($container);

		$this->em = $this->getDoctrine()->getManager();
		
		$this->exerciseServices = $exerciseServices;
		$this->badgeManager = $badgeManager;
		$this->pagerFactory = $pagerFactory;

		$this->paperRepository			= $this->em->getRepository('UJMExoBundle:Paper');
		$this->linkHintPaperRepository	= $this->em->getRepository('UJMExoBundle:LinkHintPaper');
		$this->exerciseRepository		= $this->em->getRepository('UJMExoBundle:Exercise');
		$this->userRepository			= $this->em->getRepository('ClarolineCoreBundle:User');
		$this->responseRepository		= $this->em->getRepository('UJMExoBundle:Response');
		$this->exerciseUserRepository	= $this->em->getRepository('UJMExoBundle:ExerciseUser');
	}
	
    /**
     * Lists all Paper entities.
     *
     * @EXT\ParamConverter(
     *      "exercise",
     *      class="UJMExoBundle:Exercise",
     *      options={"id" = "exoID", "strictId" = true}
     * )
     * 
     * @EXT\ParamConverter("user", options={"authenticatedUser" = true})
     */
    public function indexAction(Exercise $exercise, User $user, $page, $all) {
    	// Check rights
    	$this->checkAccess($exercise);
    	
        // Get variables from existing data
        $workspace = $exercise->getResourceNode()->getWorkspace();
        
        // Init default variables
        $maxAttemptsAchieved = false;
        $endedExercise = false;
        $startedExercise = false;
        $nbAttemptAllowed = -1;
        $arrayMarkPapers = array();
        
        // Get papers
        if ($this->exerciseServices->isExerciseAdmin($exercise)) {
            $exoAdmin = true;
            $paperQuery = $this->paperRepository->getExerciseAllPapers($exercise, true);
            $nbUserPaper = $this->exerciseServices->getNbPaper($user->getId(), $exercise->getId());
        } else {
        	$exoAdmin = false;
            $paperQuery = $this->paperRepository->getExerciseUserPapers($user->getId(), $exercise->getId(), "paper_id", "ASC", true);
        }

        $pager = $this->pagerFactory->createPager($paperQuery, $page, 10, true, false);

        $nbUserPaper = $pager->getNbResults();
        $nbPapers = $pager->getNbResults();
        if ($all == 1) {
        	$pager->setMaxPerPage($nbPapers);
        }
        
        $currentPage = $pager->getCurrentPageResults();
        if ($exoAdmin) {
        	$display = 'all';
        } else {
	        if ($nbPapers > 0) {
	            $display = $this->ctrlDisplayPaper($user, $currentPage[0]['paper']);
	        } else {
	            $display = 'all';
	        }
        }

        foreach ($currentPage as $p) {
            $arrayMarkPapers[$p['paper_id']] = $this->exerciseServices->getInfosPaper($p['paper']);
        }

        $now = new \DateTime();
        if (!$this->exerciseServices->controlMaxAttemps($exercise,
                $user, $this->exerciseServices->isExerciseAdmin($exercise))) {
            $maxAttemptsAchieved = true;
        } 
        if ($exercise->getUseDateEnd() && $exercise->getEndDate() < $now) {
        	$endedExercise = true;
        }
      	if ($exercise->getStartDate() <= $now) {
      		$startedExercise = true;
      	}
        
        if ($exercise->getMaxAttempts() > 0) {
            if ($exoAdmin === false) {
                $nbAttemptAllowed = $exercise->getMaxAttempts() - $nbPapers;
            }
        }
        
        $badgesInfoUser = $this->exerciseServices->badgesInfoUser(
                $user->getId(),
        		$exercise->getResourceNode()->getId(),
                $this->container->getParameter('locale'));

        $badgesName = array();
        $badgesNameOwned = array();

        /* Find associated badge */
        $workspace = $exercise->getResourceNode()->getWorkspace();
        
        $badgeList = $this->badgeManager->getAllBadgesForWorkspace($user, $workspace, false, true);

        foreach ($badgeList as $i => $badge) {
        	if ($badge['resource']['resource']['exercise']->getId() != $exercise->getId()) {
        		unset($badgeList[$i]);
        	}
        }

        foreach($badgeList as $result){
            $badge = $result['badge'];
            if ($result['status'] === Badge::BADGE_STATUS_OWNED) {
                $badgesNameOwned[] = $badge->getName();
            } else {
                $badgesName[] = $badge->getName();
            }

        }

        $givenUp = $user->hasGivenUpExercise($exercise);

        if ($exercise->getDispButtonInterrupt()) {
        	$currentPaper = $this->paperRepository->getCurrentPaperForUser($exercise, $user);
        } else {
        	$currentPaper = null;
        }
        
        return $this->render(
            'UJMExoBundle:Paper:index.html.twig',
            array(
                'workspace'				=> $workspace,
            	'exercise'				=> $exercise,
                //'papers'				=> $papers,
                'isAdmin'				=> $exoAdmin,
                'pager'					=> $pager,
                'exoID'					=> $exercise->getId(),
                'display'				=> $display,
                'maxAttemptsAchieved'	=> $maxAttemptsAchieved,
            	'endedExercise'			=> $endedExercise,
            	'startedExercise'		=> $startedExercise,
            	'exerciseStartDate'		=> $exercise->getStartDate(),
                'nbAttemptAllowed'		=> $nbAttemptAllowed,
                'badgesInfoUser'		=> $badgesInfoUser,
                'nbUserPaper'			=> $nbUserPaper,
                '_resource'				=> $exercise,
                'arrayMarkPapers'		=> $arrayMarkPapers,
                'badgesName'			=> $badgesName,
                'badgesNameOwned'		=> $badgesNameOwned,
            	'givenUp'		   		=> $givenUp,
            	'currentPaper'			=> $currentPaper 
            )
        );
    }

    /**
     * Finds and displays a Paper entity.
     *
     *
     * @EXT\ParamConverter(
     *      "paper",
     *      class="UJMExoBundle:Paper",
     *      options={"id" = "id", "strictId" = true}
     * )
     * @EXT\ParamConverter("user", options={"authenticatedUser" = true})
     */
    public function showAction(Paper $paper, $p = -2, User $user)
    {
        $nbAttemptAllowed = -1;
        $retryButton = false;
        
        $exercise = $paper->getExercise();

        $exoAdmin = false;
        if ($this->exerciseServices->isExerciseAdmin($exercise)) {
            $exoAdmin = true;
        }

        $nbUserPaper = 0;
        if ($exoAdmin === true) {
            
            $nbUserPaper = $this->exerciseServices->getNbPaper($user->getId(),
                                                    $exercise->getId());
        } else {
            $papers = $this->paperRepository->getExerciseUserPapers($user->getId(), $exercise->getId());
            $nbUserPaper = count($papers);
        }
        
        if ($this->exerciseServices->controlMaxAttemps($exercise,
                $user, $this->exerciseServices->isExerciseAdmin($exercise))) {
            $retryButton = true;
        }

        if ($this->exerciseServices->isExerciseAdmin($paper->getExercise())) {
            $admin = 1;
        } else {
            $admin = 0;
        }

        $workspace = $paper->getExercise()->getResourceNode()->getWorkspace();

        $display = $this->ctrlDisplayPaper($user, $paper);

        if ((($this->checkAccess($paper->getExercise())) && ($paper->getEnd() == null)) || ($display == 'none')) {
            return $this->redirect($this->generateUrl('ujm_exercise_open', array('exerciseId' => $paper->getExercise()->getId())));
        }

        $infosPaper = $this->exerciseServices->getInfosPaper($paper);

        $hintViewed = $this->linkHintPaperRepository->getHintViewed($paper->getId());

        $nbMaxQuestion = count($infosPaper['interactions']);
        
        $badgesInfoUser = $this->exerciseServices->badgesInfoUser(
                $user->getId(),
        		$exercise->getResourceNode()->getId(),
                $this->container->getParameter('locale'));
        
        if ($exercise->getMaxAttempts() > 0) {
            if (!$this->exerciseServices->isExerciseAdmin($exercise)) {
                $nbpaper = $this->exerciseServices->getNbPaper($user->getId(),
                                                    $exercise->getId());
                
                $nbAttemptAllowed = $exercise->getMaxAttempts() - $nbpaper;
            }
        }

        $badgesName = array();
        $badgesNameOwned = array();

        $workspace = $exercise->getResourceNode()->getWorkspace();
        $associatedBadge = $this->badgeManager;
        $badgeList = $associatedBadge->getAllBadgesForWorkspace($user, $workspace, false, true);

        foreach ($badgeList as $i => $badge) {
        	if ($badge['resource']['resource']['exercise']->getId() != $exercise->getId()) {
        		unset($badgeList[$i]);
        	}
        }

        foreach($badgeList as $result){
            $badge = $result['badge'];
            if($result['status'] === Badge::BADGE_STATUS_OWNED){
                $badgesNameOwned[] = $badge->getName();
            } else {
                $badgesName[] = $badge->getName();
            }

        }
        
        if ($exercise->getMaxAttempts() > 0) {
        	$currentPaper = $this->paperRepository->getCurrentPaperForUser($exercise, $user);
        	if ($currentPaper != null && $exercise->getDispButtonInterrupt() ) {
        		$retryButton = true;
        	} else {
                $currentPaper = null; // We cannot have current paper if we cannot interrupt the quiz
            }
        } else {
        	$currentPaper = null;
        }
        
        $givenUp = $user->hasGivenUpExercise($paper->getExercise());
        if ($givenUp) {
        	$retryButton = false;
        }
        
        return $this->render(
            'UJMExoBundle:Paper:show.html.twig',
            array(
                'workspace'        => $workspace,
                'exoId'            => $paper->getExercise()->getId(),
                'interactions'     => $infosPaper['interactions'],
                'responses'        => $infosPaper['responses'],
                'scorePaper'       => $infosPaper['scorePaper'],
                'scoreTemp'        => $infosPaper['scoreTemp'],
                'maxExoScore'      => $infosPaper['maxExoScore'],
                'hintViewed'       => $hintViewed,
                'correction'       => $paper->getExercise()->getCorrectionMode(),
                'display'          => $display,
                'admin'            => $admin,
                'nbAttemptAllowed' => $nbAttemptAllowed,
                'badgesInfoUser'   => $badgesInfoUser,
                '_resource'        => $paper->getExercise(),
                'p'                => $p,
                'nbMaxQuestion'    => $nbMaxQuestion,
                'paperID'          => $paper->getId(),
            	'paper'			   => $paper,
                'retryButton'      => $retryButton,
                'badgesName'       => $badgesName,
                'badgesNameOwned'  => $badgesNameOwned,
                'nbUserPaper'      => $nbUserPaper,
            	'user'			   => $user,
            	'givenUp'		   => $givenUp,
            	'currentPaper'	   => $currentPaper
            )
        );
    }


    /**
     * Finds and displays a Paper entity.
     *
     *
     * @EXT\ParamConverter(
     *      "paper",
     *      class="UJMExoBundle:Paper",
     *      options={"id" = "id", "strictId" = true}
     * )
     */
    public function deleteAction(Paper $paper)
    {
        $form = $this->createDeleteForm($paper->getId());
        $request = $this->getRequest();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Paper entity.');
            }

            $em->remove($paper);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('paper'));
    }

    public function markedOpenAction($respid, $maxScore)
    {
        return $this->render(
            'UJMExoBundle:Paper:q_open_mark.html.twig', array(
                'respid'   => $respid,
                'maxScore' => $maxScore

            )
        );
    }

    public function markedOpenRecordAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $em = $this->getDoctrine()->getManager();
            /** @var \UJM\ExoBundle\Entity\Response $response */
            $response = $this->responseRepository->find($request->get('respid'));

            $response->setMark($request->get('mark'));

            $em->persist($response);
            $em->flush();

            $this->exerciseServices->manageEndOfExercise($response->getPaper());

            return new Response($response->getId());
        } else {
            return new Response('Error');
        }
    }

    public function searchUserPaperAction()
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        
        $papersOneUser   = array();
        $papersUser      = array();
        $arrayMarkPapers = array();
        
        $display = 'none';

        $request = $this->get('request');
        $em = $this->getDoctrine()->getManager();

        $nameUser = $request->query->get('userName');
        $exoID    = $request->query->get('exoID');

        $userList = $this->userRepository->findByName($nameUser);
        $end = count($userList);

        for ($i = 0; $i < $end; $i++) {
            $papersOneUser[] = $this->paperRepository->findBy(array(
					'user' => $userList[$i]->getId(),
                    'exercise' => $exoID));
            if ($i > 0) {
                $papersUser = array_merge($papersUser, $papersOneUser[$i]);
            } else {
                $papersUser = $papersOneUser[$i];
            }
        }
        foreach ($papersUser as $p) {
            $arrayMarkPapers[$p->getId()] = $this->exerciseServices->getInfosPaper($p);
        }
        
        if(count($papersUser) > 0) {
            $display = $this->ctrlDisplayPaper($user, $papersUser[0]);
        }

        /* EDIT SII : provide isAdmin */
        $exercise = $this->exerciseRepository->find($exoID);
        $exoAdmin = $this->exerciseServices->isExerciseAdmin($exercise);
        /* EDIT SII : provide isAdmin */
        
        $divResultSearch = $this->render(
            'UJMExoBundle:Paper:userPaper.html.twig', array(
            	'exercise'		  => $exercise,
                'papers'          => $papersUser,
                'arrayMarkPapers' => $arrayMarkPapers,
                'display'         => $display,
                'isAdmin'         => $exoAdmin
            )
        );
        // If request is ajax (first display of the first search result (page = 1))
        if ($request->isXmlHttpRequest()) {
            return $divResultSearch; // Send the twig with the result
        } else {
            // Cut the header of the request to only have the twig with the result
            $divResultSearch = substr($divResultSearch, strrpos($divResultSearch, '<link'));

            // Send the form to search and the result
            return $this->render(
                'UJMExoBundle:Paper:index.html.twig', array(
                'divResultSearch' => $divResultSearch
                )
            );
        }
    }
    
    /**
     * To export results in CSV
     *
     */
    public function exportResCSVAction($exerciseId)
    {
        $em = $this->getDoctrine()->getManager();
        $exercise = $this->exerciseRepository->find($exerciseId);
        
        if ($this->exerciseServices->isExerciseAdmin($exercise)) {
            $iterableResult = $this->paperRepository->getExerciseAllPapersIterator($exerciseId);
            $handle = fopen('php://memory', 'r+');

            while (false !== ($row = $iterableResult->next())) {
                $rowCSV = array();
                $infosPaper = $this->exerciseServices->getInfosPaper($row[0]);
                $score = $infosPaper['scorePaper'] /  $infosPaper['maxExoScore'];
                $score = $score * 20;
                
                $rowCSV[] = $row[0]->getUser()->getLastName() . '-' . $row[0]->getUser()->getFirstName();
                $rowCSV[] = $row[0]->getNumPaper();
                $rowCSV[] = $row[0]->getStart()->format('Y-m-d H:i:s');
                $rowCSV[] = ($row[0]->getEnd() != NULL ? $row[0]->getEnd()->format('Y-m-d H:i:s') : '');
                $rowCSV[] = $row[0]->getInterupt();
                $rowCSV[] = $this->exerciseServices->roundUpDown($score);
                
                fputcsv($handle, $rowCSV);
                $em->detach($row[0]);
            }

            rewind($handle);
            $content = stream_get_contents($handle);
            fclose($handle);

            return new Response($content, 200, array(
                'Content-Type' => 'application/force-download',
                'Content-Disposition' => 'attachment; filename="export.csv"'
            ));
            
        } else {
            
            throw new AccessDeniedHttpException();
        }
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
                    ->add('id', 'hidden')
                    ->getForm();
    }

    private function checkAccess($exo)
    {
        $collection = new ResourceCollection(array($exo->getResourceNode()));

        if (!$this->get('security.context')->isGranted('OPEN', $collection)) {
            throw new AccessDeniedException($collection->getErrorsForDisplay());
        }
    }

    private function ctrlDisplayPaper(User $user, Paper $paper)
    {
        $display = 'none';
        $exercise = $paper->getExercise();
        $corrMode = $exercise->getCorrectionMode();
        $maxAttempts = $exercise->getMaxAttempts();
        $dateCorrection = $exercise->getDateCorrection()->format('Y-m-d H:i:s');

        if (($this->exerciseServices->isExerciseAdmin($exercise)) ||
        		($user->getId() == $paper->getUser()->getId()) &&
        		(($corrMode == 1) ||
        				(($corrMode == 3) && ($dateCorrection <= date("Y-m-d H:i:s"))) ||
        				(($corrMode == 2) && ($maxAttempts <= $this->exerciseServices->getNbPaper($user->getId(), $paper->getExercise()->getId())))
        		)
        ) {
        	$display = 'all';
        } else if (($user->getId() == $paper->getUser()->getId()) && ($paper->getExercise()->getMarkMode() == 2)) {
        	$display = 'score';
        }

        return $display;
    }
    
    /**
     * @EXT\ParamConverter(
     *      "paper",
     *      class="UJMExoBundle:Paper",
     *      options={"id" = "id", "strictId" = true})
     *     
     * @EXT\ParamConverter("user", options={"authenticatedUser" = true})
     */
    public function giveUpAction(User $user, Paper $paper) {
    	$exerciseUser = $this->exerciseUserRepository->getExerciseUser($paper->getExercise(), $user);
    	if ($exerciseUser == null) {
    		$exerciseUser = new ExerciseUser($paper->getExercise(), $user);
    	}
    	$exerciseUser->setGivenUp(true);
    	$this->em->persist($exerciseUser);
    	$this->em->flush();
    	
    	$unfinishedPapers = $this->paperRepository->getPaper($user->getId(), $paper->getExercise()->getId());
    	if ($unfinishedPapers != null) {
    		foreach ($unfinishedPapers as $unfinishedPaper) {
	    		$unfinishedPaper->setEnd(new \DateTime());
	    		$unfinishedPaper->setInterupt(0);
	    		$this->exerciseServices->manageEndOfExercise($unfinishedPaper);
    		}
    	}
    	
    	return $this->redirect($this->generateUrl("ujm_paper_show", array("id" => $paper->getId())));
    }
}