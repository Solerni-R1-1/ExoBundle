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

use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response as HTTPResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


use JMS\DiExtraBundle\Annotation as DI;

use Claroline\CoreBundle\Library\Resource\ResourceCollection;

use UJM\ExoBundle\Form\ExerciseType;
use UJM\ExoBundle\Form\ExerciseHandler;
use UJM\ExoBundle\Entity\Exercise;
use UJM\ExoBundle\Entity\ExerciseQuestion;
use UJM\ExoBundle\Entity\Paper;
use UJM\ExoBundle\Entity\Response;
use UJM\ExoBundle\Entity\Interaction;

use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Claroline\CoreBundle\Entity\User;
use UJM\ExoBundle\Services\classes\exerciseServices;
use Doctrine\ORM\EntityManager;
use UJM\ExoBundle\Entity\Question;
use UJM\ExoBundle\Entity\PaperQuestion;

/**
 * Exercise controller.
 *
 */
class ExerciseController extends Controller
{
	
	/* @var $exerciseServices exerciseServices */
	protected $exerciseServices;
	
	/* @var $em EntityManager */
	protected $em;
	
	/* @var $interactionRepository InteractionRepository */
	protected $interactionRepository;
	
	/* @var $exerciseRepository ExerciseRepository */
	protected $exerciseRepository;

	/* @var $paperRepository PaperRepository */
	protected $paperRepository;

	/* @var $responseRepository ResponseRepository */
	protected $responseRepository;
	
	/* @var $interactionQCMRepository InteractionQCMRepository */
	protected $interactionQCMRepository;
	
	/* @var $interactionHoleRepository InteractionHoleRepository */
	protected $interactionHoleRepository;
	
	/* @var $interactionGraphicRepository InteractionGraphicRepository */
	protected $interactionGraphicRepository;
	
	/* @var $interactionOpenRepository InteractionOpenRepository */
	protected $interactionOpenRepository;
	
	/* @var $exerciseQuestionRepository ExerciseQuestionRepository */
	protected $exerciseQuestionRepository;

	/* @var $shareRepository ShareRepository */
	protected $shareRepository;
	
	/* @var $questionRepository QuestionRepository */
	protected $questionRepository;
	
	protected $session;
	
	/**
	 * @DI\InjectParams({
	 *     "exerciseServices" = @DI\Inject("ujm.exercise_services"),
	 *     "container"		  = @DI\Inject("service_container"),
     *     "session"          = @DI\Inject("session")
	 * })
	 */
	public function __construct(
			exerciseServices $exerciseServices,
			$container,
			$session) {
		$this->setContainer($container);
		
		$this->exerciseServices = $exerciseServices;
		$this->session = $session;
		
		$this->em = $this->getDoctrine()->getManager();
		$this->paperRepository = $this->em->getRepository('UJMExoBundle:Paper');
		$this->interactionRepository = $this->em->getRepository('UJMExoBundle:Interaction');
		$this->exerciseRepository = $this->em->getRepository('UJMExoBundle:Exercise');
		$this->responseRepository = $this->em->getRepository('UJMExoBundle:Response'); 
		$this->interactionQCMRepository = $this->em->getRepository('UJMExoBundle:InteractionQCM');
		$this->interactionHoleRepository = $this->em->getRepository('UJMExoBundle:InteractionHole');
		$this->interactionGraphicRepository = $this->em->getRepository('UJMExoBundle:InteractionGraphic');
		$this->interactionOpenRepository = $this->em->getRepository('UJMExoBundle:InteractionOpen');
		$this->exerciseQuestionRepository = $this->em->getRepository('UJMExoBundle:ExerciseQuestion');
		$this->shareRepository = $this->em->getRepository('UJMExoBundle:Share');
		$this->questionRepository = $this->em->getRepository('UJMExoBundle:Question');
	}
	
    /**
     * Displays a form to edit an existing Exercise entity.
     *
     * @EXT\ParamConverter(
     *      "exercise",
     *      class="UJMExoBundle:Exercise",
     *      options={"id" = "id", "strictId" = true})
     */
    public function editAction(Exercise $exercise) {
        $this->checkAccess($exercise);
        $workspace = $exercise->getResourceNode()->getWorkspace();
        $exoAdmin = $this->exerciseServices->isExerciseAdmin($exercise);

        if ($exoAdmin == 1) {
            if (!$exercise) {
                throw $this->createNotFoundException('Unable to find Exercise entity.');
            }

            $editForm = $this->createForm(new ExerciseType(), $exercise);

            return $this->render(
                'UJMExoBundle:Exercise:edit.html.twig',
                array(
                    'workspace'   => $workspace,
                    'entity'      => $exercise,
                    'edit_form'   => $editForm->createView(),
                    '_resource'   => $exercise
                )
            );
        } else {
            return $this->redirect($this->generateUrl('ujm_exercise_open', array('exerciseId' => $exercise->getId())));
        }
    }

    /**
     * Edits an existing Exercise entity.
     *
     * @EXT\ParamConverter(
     *      "exercise",
     *      class="UJMExoBundle:Exercise",
     *      options={"id" = "id", "strictId" = true})
     */
    public function updateAction(Exercise $exercise)
    {
        $editForm = $this->createForm(new ExerciseType(), $exercise);

        $formHandler = new ExerciseHandler(
            $editForm, $this->get('request'), $this->getDoctrine()->getManager(),
            $this->container->get('security.context')->getToken()->getUser(), 'update'
        );

        if ($formHandler->process()) {
            return $this->redirect(
                $this->generateUrl(
                    'claro_resource_open', array(
                    'resourceType' => $exercise->getResourceNode()->getResourceType()->getName(),
                    'node' => $exercise->getResourceNode()->getId())
                )
            );
        }

        return $this->render(
            'UJMExoBundle:Exercise:edit.html.twig',
            array(
                'entity'      => $exercise,
                'edit_form'   => $editForm->createView(),
            )
        );
    }

    /**
     * Finds and displays a Exercise entity if the User is enrolled.
     *
     * @EXT\ParamConverter(
     *      "exercise",
     *      class="UJMExoBundle:Exercise",
     *      options={"id" = "exerciseId", "strictId" = true})
     */
    public function openAction(Exercise $exercise)
    {   
        $this->checkAccess($exercise);
        $user = $this->container->get('security.context')->getToken()->getUser();

        $allowToCompose = 0;
        $exoAdmin = $this->exerciseServices->isExerciseAdmin($exercise);

        $workspace = $exercise->getResourceNode()->getWorkspace();

        if (!$exercise) {
            throw $this->createNotFoundException('Unable to find Exercise entity.');
        }

        if (($this->controlDate($exoAdmin, $exercise) === true)
           		&& ($this->exerciseServices->controlMaxAttemps($exercise, $user, $exoAdmin) === true)) {
            $allowToCompose = 1;
        }

        $nbQuestions = $this->exerciseQuestionRepository->getCountQuestion($exercise->getId());
        
        $nbUserPaper = $this->exerciseServices->getNbPaper($user->getId(),
                                                $exercise->getId());


            /** SII trouver l'info numAttempt **/
            $paper = $this->paperRepository->getExerciseUserPapers($user->getId(), $exercise->getId());
            $numAttempt = -1;
            if(count($paper) > 0){
                $paper = $paper[count($paper) - 1 ];
                $numAttempt = $paper['paper']->getNumPaper();    
            } 

        return $this->render(
            'UJMExoBundle:Exercise:show.html.twig',
            array(
                'workspace'      => $workspace,
                'entity'         => $exercise,
                'exoAdmin'       => $exoAdmin,
                'allowToCompose' => $allowToCompose,
                'userId'         => $user->getId(),
                'nbQuestion'     => $nbQuestions['nbq'],
                'nbUserPaper'    => $nbUserPaper,
                '_resource'      => $exercise,
                'numAttempt'     => $numAttempt
            )
        );
    }

    /**
     * Finds and displays a Question entity to this Exercise.
     *
     * @EXT\ParamConverter(
     *      "exercise",
     *      class="UJMExoBundle:Exercise",
     *      options={"id" = "id", "strictId" = true})
     */
    public function showQuestionsAction(Exercise $exercise, $pageNow, $categoryToFind, $titleToFind, $displayAll)
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $allowEdit = array();
        $this->checkAccess($exercise);

        $workspace = $exercise->getResourceNode()->getWorkspace();

        $exoAdmin = $this->exerciseServices->isExerciseAdmin($exercise);

        $max = 10; // Max Per Page
        $request = $this->get('request');
        $page = $request->query->get('page', 1);

        if ($exoAdmin == 1) {
            $interactions = $this->interactionRepository->getExerciseInteraction($exercise, 0);

            if ($displayAll == 1) {
                $max = count($interactions);
            }

            $interactionIds = array();
            $questionIds = array();

            $shares = array();
            foreach ($interactions as $interaction) {
            	/* @var $interaction Interaction */
            	$interactionIds[] = $interaction->getId();
            	$questionIds[] = $interaction->getQuestion()->getId();
                
                $shares[$interaction->getId()] = $this->exerciseServices->controlUserSharedQuestion(
                        $interaction->getQuestion()->getId());
            }

            $orderedResponses = array();
            $responsesCount = $this->responseRepository->countByInteractionIn($interactions);
            foreach ($responsesCount as $responseCount) {
            	if (!array_key_exists($responseCount['interactionId'], $orderedResponses)) {
            		$orderedResponses[$responseCount['interactionId']] = array();
            	}
            	$orderedResponses[$responseCount['interactionId']][] = $responseCount['nbResponses'];
            }

            $questionWithResponse = array();
            $allowEdit = array();
            foreach ($interactions as $interaction) {
            	$id = $interaction->getId();
            	if (array_key_exists($id, $orderedResponses)) {
             		$responseCount = $orderedResponses[$id];
            	} else {
            		$responseCount = 0;
            	}
             	$share = $shares[$id];
            	if ($responseCount > 0) {
            		$questionWithResponse[$id] = true;
            	} else {
            		$questionWithResponse[$id] = false;
            	}
            	 
            	if ($user->getId() == $interaction->getQuestion()->getUser()->getId()) {
            		$allowEdit[$id] = 1;
            	} else if(count($share) > 0) {
            		$allowEdit[$id] = $share[0]->getAllowToModify();
            	} else {
            		$allowEdit[$id] = 0;
            	}
            }
            if ($categoryToFind != '' && $titleToFind != '' && $categoryToFind != 'z' && $titleToFind != 'z') {
                $i = 1 ;
                $pos = 0 ;
                $temp = 0;

                foreach ($interactions as $interaction) {
                    if ($interaction->getQuestion()->getCategory() == $categoryToFind) {
                        $temp = $i;
                    }
                    if ($interaction->getQuestion()->getTitle() == $titleToFind && $temp == $i) {
                        $pos = $i;
                        break;
                    }
                    $i++;
                }

                if ($pos % $max == 0) {
                    $pageNow = $pos / $max;
                } else {
                    $pageNow = ceil($pos / $max);
                }
            }

            $pagination = $this->paginationWithIf($interactions, $max, $page, $pageNow);

            $interactionsPager = $pagination[0];
            $pagerQuestion = $pagination[1];

            return $this->render(
                'UJMExoBundle:Question:exerciseQuestion.html.twig',
                array(
                    'workspace'            => $workspace,
                    'interactions'         => $interactionsPager,
                    'exerciseID'           => $exercise->getId(),
                    'questionWithResponse' => $questionWithResponse,
                    'pagerQuestion'        => $pagerQuestion,
                    'displayAll'           => $displayAll,
                    'allowEdit'            => $allowEdit,
                    '_resource'            => $exercise
                )
            );
        } else {
            return $this->redirect($this->generateUrl('ujm_exercise_open', array('exerciseId' => $id)));
        }
    }

    /**
     * To import in this Exercise a Question of the User's bank.
     *
     * @EXT\ParamConverter(
     *      "exercise",
     *      class="UJMExoBundle:Exercise",
     *      options={"id" = "exoID", "strictId" = true})
    */
    public function importQuestionAction($exercise, $pageGoNow, $maxPage, $nbItem, $displayAll)
    {
        $this->checkAccess($exercise);
        $em = $this->getDoctrine()->getManager();

        $workspace = $exercise->getResourceNode()->getWorkspace();
        $user = $this->container->get('security.context')->getToken()->getUser();
        $exoAdmin = $this->exerciseServices->isExerciseAdmin($exercise);

        // To paginate the result :
        $request = $this->get('request'); // Get the request which contains the following parameters :
        $page = $request->query->get('page', 1); // Get the choosen page (default 1)
        $click = $request->query->get('click', 'my'); // Get which array to change page (default 'my question')
        $pagerMy = $request->query->get('pagerMy', 1); // Get the page of the array my question (default 1)
        $pagerShared = $request->query->get('pagerShared', 1); // Get the pager of the array my shared question (default 1)
        $pageToGo = $request->query->get('pageGoNow'); // Page to go for the list of the questions of the exercise
        $max = 10; // Max of questions per page

        // If change page of my questions array
        if ($click == 'my') {
            // The choosen new page is for my questions array
            $pagerMy = $page;
        // Else if change page of my shared questions array
        } else if ($click == 'shared') {
            // The choosen new page is for my shared questions array
            $pagerShared = $page;
        }

        if ($exoAdmin == 1) {

            $interactions = $this->interactionRepository->getUserInteractionImport(
            		$this->em,
            		$user->getId(),
            		$exercise->getId());

            $shared = $this->shareRepository->getUserInteractionSharedImport(
            		$exercise->getId(),
            		$user->getId(),
            		$this->em);

            if ($displayAll == 1) {
                if (count($interactions) > count($shared)) {
                    $max = count($interactions);
                } else {
                    $max = count($shared);
                }
            }

            $sharedWithMe = array();

            $end = count($shared);

            for ($i = 0; $i < $end; $i++) {
                $sharedWithMe[] = $this->interactionRepository->findOneByQuestion(
                		$shared[$i]->getQuestion());
            }

            $doublePagination = $this->doublePagination(
            		$interactions,
            		$sharedWithMe,
            		$max,
            		$pagerMy,
            		$pagerShared);

            $interactionsPager = $doublePagination[0];
            $pagerfantaMy = $doublePagination[1];

            $sharedWithMePager = $doublePagination[2];
            $pagerfantaShared = $doublePagination[3];

            if ($pageToGo) {
                $pageGoNow = $pageToGo;
            } else {
                // If new item > max per page, display next page
                $rest = $nbItem % $maxPage;

                if ($nbItem == 0) {
                    $pageGoNow = 0;
                }

                if ($rest == 0) {
                    $pageGoNow += 1;
                }
            }


            return $this->render(
                'UJMExoBundle:Question:import.html.twig',
                array(
                    'workspace'    => $workspace,
                    'interactions' => $interactionsPager,
                    'exoID'        => $exercise->getId(),
                    'sharedWithMe' => $sharedWithMePager,
                    'pagerMy'      => $pagerfantaMy,
                    'pagerShared'  => $pagerfantaShared,
                    'pageToGo'     => $pageGoNow,
                    '_resource'    => $exercise,
                    'displayAll'   => $displayAll
                )
            );
        } else {
            return $this->redirect($this->generateUrl('ujm_exercise_open', array('exerciseId' => $exercise->getId())));
        }
    }

    /**
     * To record the question's import.
     *
     */
    public function importValidateAction()
    {
        $request = $this->container->get('request');

        if ($request->isXmlHttpRequest()) {
            $exoID = $request->request->get('exoID');
            $exo = $this->exerciseRepository->find($exoID);
            $pageGoNow = $request->request->get('pageGoNow');
            $qid = $request->request->get('qid');

            foreach ($qid as $q) {
                $question = $this->questionRepository->find($q);

                if (count($question) > 0) {
                    $eq = new ExerciseQuestion($exo, $question);
                    $maxOrdre = $this->exerciseQuestionRepository->getMaxOrder($exo);

                    $eq->setOrdre($maxOrdre + 1);
                    $this->em->persist($eq);
                    $this->em->flush();
                }
            }

            $url = $this->generateUrl('ujm_exercise_questions', array(
            		'id' => $exoID,
            		'pageNow' => $pageGoNow));

            return new HTTPResponse($url);
         } else {
            return $this->redirect($this->generateUrl('ujm_exercise_import_question', array(
            		'exoID' => $exoID)));
        }
    }

    /**
     * Delete the Question of the exercise.
     *
     * @EXT\ParamConverter(
     *      "exercise",
     *      class="UJMExoBundle:Exercise",
     *      options={"id" = "exoID", "strictId" = true})
     */
    public function deleteQuestionAction($exercise, $qid, $pageNow, $maxPage, $nbItem, $lastPage)
    {
    	$this->checkAccess($exercise);

        $exoAdmin = $this->exerciseServices->isExerciseAdmin($exercise);

        if ($exoAdmin == 1) {
            $eq = $this->exerciseQuestionRepository->findOneBy(array(
            		'exercise' => $exercise->getId(),
            		'question' => $qid));
            $this->em->remove($eq);
            $this->em->flush();

             // If delete last item of page, display the previous one
            $rest = $nbItem % $maxPage;

            if ($rest == 1 && $pageNow == $lastPage) {
                $pageNow -= 1;
            }
        }

        return $this->redirect(
            $this->generateUrl(
                'ujm_exercise_questions',
                array(
                    'id' => $exercise->getId(),
                    'pageNow' => $pageNow
                )
            )
        );
    }

    /**
     * To create a paper in order to take an assessment
     *
     * @EXT\ParamConverter("user", options={"authenticatedUser" = true})
     * @EXT\ParamConverter(
     *      "exercise",
     *      class="UJMExoBundle:Exercise",
     *      options={"id" = "id", "strictId" = true}
     * )
     */
    public function exercisePaperAction(Exercise $exercise, User $user) {
        $this->checkAccess($exercise);
        
        $exoAdmin = $this->exerciseServices->isExerciseAdmin($exercise);
        $workspace = $exercise->getResourceNode()->getWorkspace();

        if ($this->controlDate($exoAdmin, $exercise) === true) {
            $maxNumPaper = $this->paperRepository->getMaxNumPaper($exercise, $user);

            //Verify if it exists a not finished paper
            $paper = $this->paperRepository->getPaper($user->getId(), $exercise->getId());

            //if not exist a paper no finished
            if (count($paper) == 0) {
                if ($this->exerciseServices->controlMaxAttemps($exercise, $user, $exoAdmin) === false) {
                   return $this->redirect($this->generateUrl('ujm_paper_list', array('exoID' => $exercise->getId())));
                }

                $paper = new Paper();
                $paper->setNumPaper((int) $maxNumPaper + 1);
                $paper->setExercise($exercise);
                $paper->setUser($user);
                $paper->setStart(new \Datetime());
                $paper->setArchive(0);
                $paper->setInterupt(0);

                if ( ($exercise->getNbQuestion() > 0) && ($exercise->getKeepSameQuestion()) == true ) {
                    $papers = $this->paperRepository->getExerciseUserPapers($user->getId(), $exercise->getId());
                    if(count($papers) == 0) {
                        $tab = $this->prepareInteractionsPaper($exercise);
                        $interactions  = $tab['interactions'];
                        $orderInter    = $tab['orderInter'];
                        $tabOrderInter = $tab['tabOrderInter'];
                        $questions 		= $tab['tabOrderInterEntities'];
                    } else {
                        $lastPaper = $papers[count($papers) - 1]['paper'];
                        $orderInter = $lastPaper->getOrdreQuestion();
                        $tabOrderInter = explode(';', $lastPaper->getOrdreQuestion());
                        unset($tabOrderInter[count($tabOrderInter) - 1]);
                        $interactions[0] = $this->interactionRepository->find($tabOrderInter[0]);
                        
                        $questions = array();
                        foreach ($lastPaper->getPaperQuestions() as $paperQuestion) {
                        	$questions[$paperQuestion->getOrdre()] = $paperQuestion->getQuestion();
                        }
                    }
                } else {
                    $tab = $this->prepareInteractionsPaper($exercise);
                    $interactions	= $tab['interactions'];
                    $orderInter		= $tab['orderInter'];
                    $tabOrderInter	= $tab['tabOrderInter'];
                    $questions 		= $tab['tabOrderInterEntities'];
                }

                $paper->setOrdreQuestion($orderInter);
                $questionOrders = explode(';', $orderInter);
                $this->em->persist($paper);
                $this->em->flush();
                foreach ($questions as $i => $question) {
                	$paperQuestion = new PaperQuestion($paper, $question);
                	$paperQuestion->setOrdre($i);
                	$this->em->persist($paperQuestion); 
                }
                $this->em->flush();
            } else {
                $paper = $paper[0];
                $tabOrderInter = explode(';', $paper->getOrdreQuestion());
                unset($tabOrderInter[count($tabOrderInter) - 1]);
                $interactions[0] = $this->interactionRepository->find($tabOrderInter[0]);
            }

            $this->session->set('tabOrderInter', $tabOrderInter);
            $this->session->set('paper', $paper->getId());
            $this->session->set('exerciseID', $exercise->getId());

            $typeInter = $interactions[0]->getType();
			$firstInteraction = $interactions[0];
            //To display selectioned question
            return $this->redirect($this->generateUrl('ujm_exercise_paper_question', array(
            		'exerciseId' => $exercise->getId(),
            		'paperId' => $paper->getId(),
            		'interactionId' => $firstInteraction->getId())));
        } else {
        	// Add flashbag message to tell that quizz is finished 
            return $this->redirect($this->generateUrl('ujm_paper_list', array('exoID' => $exercise->getId())));
        }
    }

    /**
     * To create new paper
     */
    private function prepareInteractionsPaper(Exercise $exercise)
    {
        $em = $this->getDoctrine()->getManager();
        $orderInter = '';
        $tabOrderInter = array();
        $tab = array();

        $interactions = $this->interactionRepository->getExerciseInteraction(
        		$exercise,
				$exercise->getShuffle(),
        		$exercise->getNbQuestion());

        $questions = array();
        foreach ($interactions as $i => $interaction) {
            $orderInter = $orderInter.$interaction->getId().';';
            $tabOrderInter[] = $interaction->getId();
            $questions[$i] = $interaction->getQuestion();
        }

        $tab['interactions']  = $interactions;
        $tab['orderInter']    = $orderInter;
        $tab['tabOrderInter'] = $tabOrderInter;
        $tab['tabOrderInterEntities'] = $questions;

        return $tab;
    }
    
    /**
     * Display an answerable question to the user.
     *
     * @EXT\ParamConverter("user", options={"authenticatedUser" = true})
     * @EXT\ParamConverter(
     *      "exercise",
     *      class="UJMExoBundle:Exercise",
     *      options={"id" = "exerciseId", "strictId" = true}
     * )
     * @EXT\ParamConverter(
     *      "paper",
     *      class="UJMExoBundle:Paper",
     *      options={"id" = "paperId", "strictId" = true}
     * )
     *
     *
     * @param Exercise $exercise
     * @param Paper $paper
     */
    public function finishPaperAction(Request $request, User $user, Exercise $exercise, Paper $paper) {
    	// Check access to :
    	// - Exercise
    	// - Paper (is it mine ? is it finished ? is it in correct exercise ?)
    	if ($paper->getUser()->getId() != $user->getId()) {
    		return $this->redirect($this->generateUrl('ujm_exercise_paper', array(
    				'id' => $paper->getExercise()->getId()
    		)));
    	}
    	
    	if ($paper->getEnd() == null) { 
	    	switch ($request->getMethod()) {
	    		case 'GET':
	    			// Do nothing
	    			break;
	    		case 'POST':
	    			// Write response
	    			$this->processAnswer($request);
	    			break;
	    		default:
	    			// Throw error : not allowed
	    			break;
	    	}
	
	    	$paper->setInterupt(0);
	        $paper->setEnd(new \Datetime());
	        $this->em->persist($paper);
	        $this->em->flush();
	
	        $this->exerciseServices->manageEndOfExercise($paper);
    	}
    	
        return $this->redirect($this->generateUrl('ujm_paper_show', array('id' => $paper->getId())));//'UJMExoBundle:Paper:show', array('id' => $paper->getId()));
    }
    
    private function processAnswer(Request $request) {
    	// TODO : Check if paper exists etc...
    	$answeredInteractionId = $request->get('answeredInteractionId');
    	$answeredPaperId = $request->get('answeredPaperId');
    	$answeredExerciseId = $request->get('answeredExerciseId');
    	 
    	$answeredInteraction = $this->interactionRepository->find($answeredInteractionId);
    	$answeredPaper = $this->paperRepository->find($answeredPaperId);
    	 
    	$choices = $request->get('choice');
    	 
    	$this->exerciseServices->processInteractionAnswer($answeredPaper, $answeredInteraction, is_array($choices) ? $choices : array($choices));
    }

    /**
     * Display an answerable question to the user.
     * 
     * @EXT\ParamConverter("user", options={"authenticatedUser" = true})
     * @EXT\ParamConverter(
     *      "exercise",
     *      class="UJMExoBundle:Exercise",
     *      options={"id" = "exerciseId", "strictId" = true}
     * )
     * @EXT\ParamConverter(
     *      "paper",
     *      class="UJMExoBundle:Paper",
     *      options={"id" = "paperId", "strictId" = true}
     * )
     * @EXT\ParamConverter(
     *      "interaction",
     *      class="UJMExoBundle:Interaction",
     *      options={"id" = "interactionId", "strictId" = true}
     * )
     * 
     * 
     * @param Exercise $exercise
     * @param Paper $paper
     * @param Interaction $interaction
     */
    public function displayQuestionAction(Request $request, User $user, Exercise $exercise, Paper $paper, Interaction $interaction) {
    	// Check access to :
    	// - Paper (is it mine ? is it finished ? is it in correct exercise ?)
    	if ($paper->getUser()->getId() != $user->getId()) {
    		return $this->redirect($this->generateUrl('ujm_exercise_paper', array(
    				'id' => $paper->getExercise()->getId()
    		)));
    	}
    	if ($paper->getEnd() != null) {
    		return $this->redirect($this->generateUrl('ujm_paper_show', array('id' => $paper->getId())));
    	}
    	// Init vars
    	$workspace = $paper->getExercise()->getResourceNode()->getWorkspace();
    	switch ($request->getMethod()) {
    		case 'GET':
    			// Do nothing
    			break;
    		case 'POST':
    			// Write response
    			$this->processAnswer($request);
    			break;
    		default:
    			// Throw error : not allowed
    			break;
    	}
    	
    	switch ($interaction->getType()) {
    		case "InteractionQCM":
    			// Get real interaction (and shuffle or sort its possible answers)
    			$realInteraction = $this->interactionQCMRepository->getInteractionQCM($interaction);
                if ($realInteraction->getShuffle()) {
                    $realInteraction->shuffleChoices();
                } else {
                    $realInteraction->sortChoices();
                }

                // Get response if already answered
                $responseGiven = $this->responseRepository->getAlreadyResponded($paper, $interaction);
                if ($responseGiven != null) {
                    $responseGiven = $responseGiven->getResponse().";";
                } else {
                    $responseGiven = '';
                }
    			break;
    	}

    	$interactionsIds = explode(';', $paper->getOrdreQuestion());
    	$offsettedInteractionsIds = array();
    	foreach ($interactionsIds as $i => $interactionId) {
    		if ($interactionId != null && $interactionId != "") {
    			$offsettedInteractionsIds[$i + 1] = $interactionId;
    		}
    	}
    	$interactionPosition = array_search($interaction->getId(), $offsettedInteractionsIds);
    	// If interaction is not in the ordre question list, we redirect the user to the first question of the paper.
    	if ($interactionPosition === false) {
    		return $this->redirect($this->generateUrl('ujm_exercise_paper_question', array(
    			'exerciseId'	=> $exercise->getId(),
    			'paperId'		=> $paper->getId(),
    			'interactionId'	=> $offsettedInteractionsIds[1]
    		)));
    	}
    	$parameters = array('workspace' 				=> $workspace,
					    	'exercise'					=> $exercise,
					    	'paper'						=> $paper,
					    	'user'						=> $user,
					    	'tabOrderInter'				=> $offsettedInteractionsIds,
					    	'interaction'				=> $realInteraction,
					    	'numQ'						=> $interactionPosition,
					    	'response'					=> $responseGiven);
    	
    	return $this->render(
    			'UJMExoBundle:Exercise:paper.html.twig',
    			$parameters
    	);
    }
    
    /**
     * To navigate in the Questions of the assessment
     *
     */
    public function exercisePaperNavAction(Request $request)
    {
        $response = '';
        $session = $request->getSession();
        $paper = $this->paperRepository->find($session->get('paper'));
        $workspace = $paper->getExercise()->getResourceNode()->getWorkspace();
        $typeInterToRecorded = $request->get('typeInteraction');

        $tabOrderInter = $session->get('tabOrderInter');
        /*
         * // Edit by Kevin : break solerni user-experience
        if ($paper->getEnd()) {
            
            return $this->forward('UJMExoBundle:Paper:show', 
                                  array(
                                      'id' => $paper->getId(),
                                      'p'  => -1
                                       )
                                 );
        }*/

        //To record response
        $ip = $this->exerciseServices->getIP();
        $interactionToValidatedID = $request->get('interactionToValidated');
        $response = $this->responseRepository->getAlreadyResponded($paper->getId(), $interactionToValidatedID);

        switch ($typeInterToRecorded) {
            case "InteractionQCM":
                $res = $this->exerciseServices->responseQCM($request, $session->get('paper'));
                break;

            case "InteractionGraphic":
                $res = $this->exerciseServices->responseGraphic($request, $session->get('paper'));
                break;

            case "InteractionHole":
                $res = $this->exerciseServices->responseHole($request, $session->get('paper'));
                break;

            case "InteractionOpen":
                $res = $this->exerciseServices->responseOpen($request, $session->get('paper'));
                break;
        }

        if (count($response) == 0) {
            //INSERT Response
            $response = new Response();
            $response->setNbTries(1);
            $response->setPaper($paper);
            $response->setInteraction($this->interactionRepository->find($interactionToValidatedID));
        } else {
            //UPDATE Response
            $response = $response[0];
            $response->setNbTries($response->getNbTries() + 1);
        }

        $response->setIp($ip);
        $score = explode('/', $res['score']);
        $response->setMark($score[0]);
        $response->setResponse($res['response']);
        $response->setMarkUsedForHint($res['penalty']);

        $this->em->persist($response);
        $this->em->flush();

        //To display selectioned question
        $numQuestionToDisplayed = $request->get('numQuestionToDisplayed');

        if ($numQuestionToDisplayed == 'finish') {
            return $this->finishExercise($session);
        } else if ($numQuestionToDisplayed == 'interupt') {
            return $this->interuptExercise();
        } else {
            $interactionToDisplayedID = $tabOrderInter[$numQuestionToDisplayed - 1];
            $interactionToDisplay = $this->interactionRepository->find($interactionToDisplayedID);
            $typeInterToDisplayed = $interactionToDisplay->getType();
            return $this->displayQuestion(
                $numQuestionToDisplayed, $interactionToDisplay, $typeInterToDisplayed,
                $response->getPaper()->getExercise()->getDispButtonInterrupt(),
                $response->getPaper()->getExercise()->getMaxAttempts(),
                $workspace, $paper
            );
        }
    }

    /**
     * To change the order of the questions into an exercise
     *
     */
    public function changeQuestionOrderAction()
    {
        $request = $this->container->get('request');

        if ($request->isXmlHttpRequest()) {
            $exoID = $request->request->get('exoID');
            $order = $request->request->get('order');
            $currentPage = $request->request->get('currentPage');
            $questionMaxPerPage = $request->request->get('questionMaxPerPage');

            if ($exoID && $order && $currentPage && $questionMaxPerPage) {

                $length = count($order);

                $em = $this->getDoctrine()->getManager();
                $exoQuestions = $em->getRepository('UJMExoBundle:ExerciseQuestion')->findBy(array('exercise' => $exoID));

                foreach ($exoQuestions as $exoQuestion) {
                    for ($i = 0; $i < $length; $i++) {
                        if ($exoQuestion->getQuestion()->getId() == $order[$i]) {
                            $newOrder = $i + 1 + (((int)$currentPage - 1) * (int)$questionMaxPerPage);
                            $exoQuestion->setOrdre($newOrder);
                        }
                    }
                }
            }
        }

        $em->persist($exoQuestion);
        $em->flush();

        return $this->redirect(
            $this->generateUrl('ujm_exercise_questions', array(
                'id' => $exoID
                )
            )
        );
    }

    /**
     * To display the docimology's histogramms
     *
     * @EXT\ParamConverter(
     *      "exercise",
     *      class="UJMExoBundle:Exercise",
     *      options={"id" = "exerciseId", "strictId" = true}
     * )
     */
    public function docimologyAction(Exercise $exercise, $nbPapers)
    {
        $this->checkAccess($exercise);
        if ($this->exerciseServices->isExerciseAdmin($exercise)) {
        	// Order questions in exercise for list
        	$questions = array();
        	foreach ($exercise->getExerciseQuestions() as $eq) {
        		if (!array_key_exists($eq->getOrdre(), $questions)) {
        			$questions[$eq->getOrdre()] = array();
        		}
        		$questionData = array();
        		$questionData['title'] = $eq->getQuestion()->getTitle();
        		$questionData['interaction'] = $eq->getQuestion()->getInteractions();
        		$questions[$eq->getOrdre()][] = $questionData;
        	}
        	ksort($questions);
        	$orderedQuestions = array();
        	foreach ($questions as $ordre => $questionArray) {
        		foreach ($questionArray as $question) {
        			$orderedQuestions[] = $question['title'];
        		}
        	}

        	if ($nbPapers >= 12) {
	        	// Calculate marks histogram
	        	$marksHistogram = $this->paperRepository->getMarksHistogram($exercise);
	        	$scores = "";
	        	$frequencies = "";
	        	foreach ($marksHistogram as $markHistogram) {
	        		$scores = $scores.$markHistogram['mark'].',';
	        		$frequencies = $frequencies.$markHistogram['nbPapers'].',';
	        	}
	        	
	        	// Calculate interactions histograms
	        	$interactionsHistogram = $this->paperRepository->getInteractionsHistogram($exercise);
	        	$success = "";
	        	$partiallyRight = "";
	        	$wrong = "";
	        	$noResponse = "";
	        	$difficulty = "";
	        	foreach ($interactionsHistogram as $interaction) {
	        		$success = $success.$interaction['success'].",";
	        		$partiallyRight = $partiallyRight.$interaction['partial'].",";
	        		$wrong = $wrong.$interaction['wrong'].",";
	        		$noResponse = $noResponse.$interaction['noResponse'].",";
	        		$difficulty = $difficulty.$interaction['difficulty'].",";
	        	}
        	

                if ($exercise->getNbQuestion() == 0) {
                    $histoDiscrimination = $this->histoDiscrimination($exercise);
                } else {
                    $histoDiscrimination['coeffQ'] = 'none';
                }
            }
        	
        	
        	
        	$parameters = array(
        			"nbPapers"				=> $nbPapers,
        			"workspace"				=> $exercise->getResourceNode()->getWorkspace(),
        			"_resource"				=> $exercise,
        			"scoreList"				=> $scores,
        			"frequencyMarks"		=> $frequencies,
        			"questionsList"			=> $orderedQuestions,
        			"seriesResponsesTab"	=> array('success'			=> $success,
        											'partiallyRight'	=> $partiallyRight,
        											'wrong'				=> $wrong,
        											'noResponse'		=> $noResponse),
        			"coeffQ"				=> $histoDiscrimination['coeffQ'],
        			"MeasureDifficulty"		=> $difficulty
        	);
        	
            return $this->render('UJMExoBundle:Exercise:docimology.html.twig', $parameters);
        } else {

            return $this->redirect($this->generateUrl('ujm_exercise_open', array('exerciseId' => $exercise->getId())));
        }
    }

    /**
     * To have the status of an answer
     *
     */
    private function responseStatus($responses, $scoreMax)
    {
        $responsesTab = array();
        $responsesTab['correct']        = 0;
        $responsesTab['partiallyRight'] = 0;
        $responsesTab['wrong']          = 0;
        $responsesTab['noResponse']     = 0;

        foreach ($responses as $rep) {
            if ($rep['mark'] == $scoreMax) {
                $responsesTab['correct'] = $rep['nb'];
            } else if ($rep['mark'] == 0) {
                $responsesTab['wrong'] = $rep['nb'];
            } else {
                $responsesTab['partiallyRight'] += $rep['nb'];
            }
        }

        return $responsesTab;
    }

    /**
     * Finds and displays the question selectionned by the User in an assesment
     *
     */
    private function displayQuestion(
        	$numQuestionToDisplayed,
    		$interactionToDisplay,
        	$typeInterToDisplayed,
    		$dispButtonInterrupt,
    		$maxAttempsAllowed, 
        	$workspace,
    		$paper) {
    	$user = $this->container->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $session = $this->getRequest()->getSession();
        $tabOrderInter = $session->get('tabOrderInter');
        $user = $this->getUser();

        switch ($typeInterToDisplayed) {
            case "InteractionQCM":

                $interactionToDisplayed = $this->interactionQCMRepository->getInteractionQCM($interactionToDisplay->getId());

                if ($interactionToDisplayed[0]->getShuffle()) {
                    $interactionToDisplayed[0]->shuffleChoices();
                } else {
                    $interactionToDisplayed[0]->sortChoices();
                }

                $responseGiven = $this->responseRepository->getAlreadyResponded($session->get('paper'), $interactionToDisplay->getId());

                if (count($responseGiven) > 0) {
                    $responseGiven = $responseGiven[0]->getResponse();
                } else {
                    $responseGiven = '';
                }

                break;

            case "InteractionGraphic":

                $interactionToDisplayed = $this->interactionGraphicRepository
                    ->getInteractionGraphic($interactionToDisplay->getId());

                $coords = $em->getRepository('UJMExoBundle:Coords')
                    ->findBy(array('interactionGraphic' => $interactionToDisplayed[0]->getId()));

                $responseGiven = $this->responseRepository
                    ->getAlreadyResponded($session->get('paper'), $interactionToDisplay->getId());

                if (count($responseGiven) > 0) {
                    $responseGiven = $responseGiven[0]->getResponse();
                } else {
                    $responseGiven = '';
                }

                $array['listCoords'] = $coords;

                break;

            case "InteractionHole":
                $interactionToDisplayed = $this->interactionHoleRepository
                    ->getInteractionHole($interactionToDisplay->getId());

                $responseGiven = $this->responseRepository
                    ->getAlreadyResponded($session->get('paper'), $interactionToDisplay->getId());

                if (count($responseGiven) > 0) {
                    $responseGiven = $responseGiven[0]->getResponse();
                } else {
                    $responseGiven = '';
                }

                break;

            case "InteractionOpen":

                $interactionToDisplayed = $this->interactionOpenRepository
                    ->getInteractionOpen($interactionToDisplay->getId());

                $responseGiven = $this->responseRepository
                                      ->getAlreadyResponded($session->get('paper'), $interactionToDisplay->getId());

                if (count($responseGiven) > 0) {
                    $responseGiven = $responseGiven[0]->getResponse();
                } else {
                    $responseGiven = '';
                }

                break;
        }

        $array['workspace']              = $workspace;
        $array['tabOrderInter']          = $tabOrderInter;
        $array['interactionToDisplayed'] = $interactionToDisplayed[0];
        $array['interactionType']        = $typeInterToDisplayed;
        $array['numQ']                   = $numQuestionToDisplayed;
        $array['paper']                  = $session->get('paper');
        $array['numAttempt']             = $paper->getNumPaper();
        $array['response']               = $responseGiven;
        $array['dispButtonInterrupt']    = $dispButtonInterrupt;
        $array['maxAttempsAllowed']      = $maxAttempsAllowed;
        $array['_resource']              = $paper->getExercise();
        $array['user']					 = $user;

        return $this->render(
            'UJMExoBundle:Exercise:paper.html.twig',
            $array
        );
    }

    /**
     * To finish an assessment
     *
     */
    private function finishExercise(SessionInterface $session)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \UJM\ExoBundle\Entity\Paper $paper */
        $paper = $this->paperRepository->find($session->get('paper'));
        $paper->setInterupt(0);
        $paper->setEnd(new \Datetime());
        $em->persist($paper);
        $em->flush();

        $this->exerciseServices->manageEndOfExercise($paper);

        $session->remove('penalties');

        return $this->forward('UJMExoBundle:Paper:show', array('id' => $paper->getId()));
    }

    /**
     * To interupt an assessment
     *
     */
    private function interuptExercise()
    {
        $em = $this->getDoctrine()->getManager();
        $session = $this->getRequest()->getSession();

        $paper = $this->paperRepository->find($session->get('paper'));
        $paper->setInterupt(1);
        $em->persist($paper);
        $em->flush();

        return $this->redirect($this->generateUrl('ujm_exercise_open', array('exerciseId' => $paper->getExercise()->getId())));
    }

    /**
     * The user must be registered (and the dates must be good or the user must to be admin for the exercise)
     *
     */
    private function controlDate($exoAdmin, $exercise)
    {
        if (
            ((($exercise->getStartDate()->format('Y-m-d H:i:s') <= date('Y-m-d H:i:s'))
            && (($exercise->getUseDateEnd() == 0)
            || ($exercise->getEndDate()->format('Y-m-d H:i:s') >= date('Y-m-d H:i:s'))))
            || ($exoAdmin == 1))
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * To check the right to open exo or not
     *
     */
    private function checkAccess($exo)
    {
        $collection = new ResourceCollection(array($exo->getResourceNode()));

        if (!$this->get('security.context')->isGranted('OPEN', $collection)) {
            throw new AccessDeniedException($collection->getErrorsForDisplay());
        }
    }

    /**
     * To draw histogram of marks
     *
     */
    private function histoMark(Exercise $exercise)
    {
        if ($exercise->getNbQuestion() == 0) {
            $exoScoreMax = $this->exerciseServices->getExerciseTotalScore($exercise->getId());
        }
        
        $marks = $this->exerciseRepository->getExerciseMarks($exercise, 'noteExo');
        $tabMarks = array();
        $histoMark = array();

        foreach ($marks as $mark) {
            if ($exercise->getNbQuestion() > 0) {
                $exoScoreMax = $this->exerciseServices->getExercisePaperTotalScore($mark['paper']);
            }
            $scoreU = round(($mark["noteExo"] / $exoScoreMax) * 20, 2);

            $score = $this->exerciseServices->roundUpDown($scoreU);

            if (isset($tabMarks[(string) $score])) {
                $tabMarks[(string) $score] += 1;
            } else {
                $tabMarks[(string) $score] = 1;
            }
        }

        ksort($tabMarks);
        $scoreList = implode(",", array_keys($tabMarks));//echo $scoreList;die();

        $frequencyMarks = implode(",", $tabMarks);

        $histoMark['scoreList']      = $scoreList;
        $histoMark['frequencyMarks'] = $frequencyMarks;

        return $histoMark;
    }

    /**
     * To draw histogram of success
     *
     */
    private function histoSuccess($exerciseId, $eqs, $papers)
    {
        $em = $this->getDoctrine()->getManager();
        $questionsResponsesTab = array();
        $seriesResponsesTab = array();
        $seriesResponsesTab[0] = '';
        $seriesResponsesTab[1] = '';
        $seriesResponsesTab[2] = '';
        $seriesResponsesTab[3] = '';
        $questionList = array();
        $histoSuccess = array();

        foreach ($eqs as $eq) {
            $questionList[] = $eq->getQuestion()->getTitle();

            $responsesTab = $this->getCorrectAnswer($exerciseId, $eq);

            $questionsResponsesTab[$eq->getQuestion()->getId()] = $responsesTab;

        }
        //no response
        foreach ($papers as $paper) {
            $interQuestions = $paper['paper_ordre_question'];//->getOrdreQuestion();
            $interQuestions = substr($interQuestions, 0, strlen($interQuestions) - 1);

            $interQuestionsTab = explode(";", $interQuestions);
            foreach ($interQuestionsTab as $interQuestion) {
                $flag = $this->responseRepository->findOneBy(
                    array(
                        'interaction' => $interQuestion,
                        'paper' => $paper['paper_id']//->getId()
                    )
                );

                if (!$flag || $flag->getResponse() == '') {
                    $interaction = $this->interactionRepository->find($interQuestion);
                    $questionsResponsesTab[$interaction->getQuestion()->getId()]['noResponse'] += 1;
                }
            }
        }

        //creation serie for the graph jqplot
        foreach ($questionsResponsesTab as $responses) {
            $seriesResponsesTab[0] .= (string) $responses['correct'].',';
            $seriesResponsesTab[1] .= (string) $responses['partiallyRight'].',';
            $seriesResponsesTab[2] .= (string) $responses['wrong'].',';
            $seriesResponsesTab[3] .= (string) $responses['noResponse'].',';
        }

        $histoSuccess['questionsList'] = $questionList;
        $histoSuccess['seriesResponsesTab'] = $seriesResponsesTab;

        return $histoSuccess;
    }

    /**
     * To draw histogram of discrimination
     *
     */
    private function histoDiscrimination(Exercise $exercise)
    {
    	$papers = $exercise->getPapers();
    	$eqs = $exercise->getExerciseQuestions();
        $tabScoreExo = array();
        $tabScoreQ = array();
        $tabScoreAverageQ = array();
        $productMarginMark = array();
        $tabCoeffQ = array();
        $histoDiscrimination = array();
        $scoreAverageExo = 0;
        $marks = $this->exerciseRepository->getExerciseMarks($exercise, 'paper');

        //Array of exercise's scores
        foreach ($marks as $mark) {
        	$e = $mark["noteExo"];
            $tabScoreExo[] = $e;
            $scoreAverageExo += floatval($e);
        }

        $scoreAverageExo = $scoreAverageExo / count($tabScoreExo);

        //Array of each question's score
        foreach ($eqs as $eq) {
            $interaction = $this->interactionRepository->getInteraction($eq->getQuestion()->getId());
            $responses = $this->responseRepository->getExerciseInterResponses($exercise->getId(), $interaction[0]->getId());
            foreach ($responses as $response) {
                $tabScoreQ[$eq->getQuestion()->getId()][] = $response['mark'];
            }

            while ((count($tabScoreQ[$eq->getQuestion()->getId()])) < (count($papers))) {
                $tabScoreQ[$eq->getQuestion()->getId()][] = 0;
            }
        }
        //var_dump($tabScoreQ);die();

        //Array of average of each question's score
        foreach ($eqs as $eq) {
            $allScoreQ = $tabScoreQ[$eq->getQuestion()->getId()];
            $sm = 0;
            foreach ($allScoreQ as $sq) {
                $sm += $sq;
            }
            $sm = $sm / count($papers);
            $tabScoreAverageQ[$eq->getQuestion()->getId()] = $sm;
        }
        //var_dump($tabScoreAverageQ);die();

        //Array of (x-Mx)(y-My)
        foreach ($eqs as $eq) {
            $i = 0;
            $allScoreQ = $tabScoreQ[$eq->getQuestion()->getId()];
            foreach ($allScoreQ as $sq) {
                $productMarginMark[$eq->getQuestion()->getId()][] = ($sq - $tabScoreAverageQ[$eq->getQuestion()->getId()]) * ($tabScoreExo[$i] - $scoreAverageExo);
                $i++;
            }
        }
        //var_dump($productMarginMark);die();

        foreach ($eqs as $eq) {
            $productMarginMarkQ = $productMarginMark[$eq->getQuestion()->getId()];
            $sumPenq = 0;
            $coeff = null;
            $standardDeviationQ = null;
            $standardDeviationE = $this->sd($tabScoreExo);
            $n = count($productMarginMarkQ);
            foreach ($productMarginMarkQ as $penq) {
                $sumPenq += $penq;
            }
            $sumPenq = round($sumPenq, 3);
            $standardDeviationQ = $this->sd($tabScoreQ[$eq->getQuestion()->getId()]);
            $nSxSy = $n * $standardDeviationQ * $standardDeviationE;
            if ($nSxSy != 0) {
                $tabCoeffQ[] = round($sumPenq / ($nSxSy), 3);
            } else {
                $tabCoeffQ[] = 0;
            }
        }
        //var_dump($tabCoeffQ);die();

        $coeffQ = implode(",", $tabCoeffQ);
        $histoDiscrimination['coeffQ'] = $coeffQ;

        return $histoDiscrimination;
    }

    /**
     * Docimology, to calulate the standard deviation for the discrimination coefficient
     *
     * @param type $x
     * @param type $mean
     * @return type
     */
    private function sd_square($x, $mean)
    {
        return pow($x - $mean, 2);

    }

    /**
     *
     * Docimology, to calulate the standard deviation for the discrimination coefficient
     *
     * @param type $array
     * @return type
     */
    private function sd($array)
    {

        return sqrt(
        		array_sum(
        				array_map(
        						array($this, "sd_square"),
        						$array,
        						array_fill(
        								0,
        								count($array),
        								(array_sum($array) / count($array))
        						)
        				)
        		) / (count($array) - 1)
        );
    }

    /**
     *
     * @param type $exerciseId
     * @param type $eqs
     * @return type
     */
    private function histoMeasureOfDifficulty($exerciseId, $eqs)
    {
        $up = array();
        $down = array();
        $measureTab = array();

        foreach ($eqs as $eq) {

            $responsesTab = $this->getCorrectAnswer($exerciseId, $eq);

            $up[] = $responsesTab['correct'];
            $down[] = (int) $responsesTab['correct'] + (int) $responsesTab['partiallyRight'] + (int) $responsesTab['wrong'];
        }

        $stop = count($up);

        for ($i = 0; $i < $stop; $i++) {

            $measureTab[$i] = $this->exerciseServices->roundUpDown(($up[$i] / $down[$i]) * 100);
        }

        $measure = implode(",", $measureTab);

        return $measure;
    }

    /**
     * To get the number of answers with the 'correct' status
     *
     */
    private function getCorrectAnswer($exerciseId, $eq)
    {
        $scoreMax = 0;

        $interaction = $this->interactionRepository->getInteraction($eq->getQuestion()->getId());
        $responses = $this->responseRepository->getExerciseInterResponsesWithCount(
        		$exerciseId,
        		$interaction[0]->getId());

        switch ( $interaction[0]->getType()) {
            case "InteractionQCM":
                $interQCM = $this->em->getRepository('UJMExoBundle:InteractionQCM')
                               ->getInteractionQCM($interaction[0]);
                $scoreMax = $this->exerciseServices->qcmMaxScore($interQCM);
                $responsesTab = $this->responseStatus($responses, $scoreMax);
                break;

            case "InteractionGraphic":
                $interGraphic = $this->em->getRepository('UJMExoBundle:InteractionGraphic')
                                   ->getInteractionGraphic($interaction[0]->getId());
                $scoreMax = $this->exerciseServices->graphicMaxScore($interGraphic[0]);
                $responsesTab = $this->responseStatus($responses, $scoreMax);
                break;

            case "InteractionHole":
                $interHole = $this->em->getRepository('UJMExoBundle:InteractionHole')
                                ->getInteractionHole($interaction[0]->getId());
                $scoreMax = $this->exerciseServices->holeMaxScore($interHole[0]);
                $responsesTab = $this->responseStatus($responses, $scoreMax);
                break;

            case "InteractionOpen":
                $interOpen = $this->em->getRepository('UJMExoBundle:InteractionOpen')
                                   ->getInteractionOpen($interaction[0]->getId());
                $scoreMax = $this->exerciseServices->openMaxScore($interOpen[0]);
                $responsesTab = $this->responseStatus($responses, $scoreMax);
                break;
        }

        return $responsesTab;
    }

    /**
     * To paginate two tables on one page
     *
     */
    private function doublePagination($entityToPaginateOne, $entityToPaginateTwo, $max, $pageOne, $pageTwo)
    {
        $adapterOne = new ArrayAdapter($entityToPaginateOne);
        $pagerOne = new Pagerfanta($adapterOne);

        $adapterTwo = new ArrayAdapter($entityToPaginateTwo);
        $pagerTwo = new Pagerfanta($adapterTwo);

        try {
            $entityPaginatedOne = $pagerOne
                ->setMaxPerPage($max)
                ->setCurrentPage($pageOne)
                ->getCurrentPageResults();

            $entityPaginatedTwo = $pagerTwo
                ->setMaxPerPage($max)
                ->setCurrentPage($pageTwo)
                ->getCurrentPageResults();
        } catch (\Pagerfanta\Exception\NotValidCurrentPageException $e) {
            throw $this->createNotFoundException("Cette page n'existe pas.");
        }


        $doublePagination[0] = $entityPaginatedOne;
        $doublePagination[1] = $pagerOne;

        $doublePagination[2] = $entityPaginatedTwo;
        $doublePagination[3] = $pagerTwo;

        return $doublePagination;
    }

    /**
     * To paginate table
     *
     */
    private function paginationWithIf($entityToPaginate, $max, $page, $pageNow)
    {
        $adapter = new ArrayAdapter($entityToPaginate);
        $pager = new Pagerfanta($adapter);

        try {
            if ($pageNow == 0) {
                $entityPaginated = $pager
                    ->setMaxPerPage($max)
                    ->setCurrentPage($page)
                    ->getCurrentPageResults();
            } else {
                $entityPaginated = $pager
                    ->setMaxPerPage($max)
                    ->setCurrentPage($pageNow)
                    ->getCurrentPageResults();
            }
        } catch (\Pagerfanta\Exception\NotValidCurrentPageException $e) {
            throw $this->createNotFoundException("Cette page n'existe pas.");
        }

        $pagination[0] = $entityPaginated;
        $pagination[1] = $pager;

        return $pagination;
    }
}
