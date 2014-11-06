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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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

/**
 * Exercise controller.
 *
 */
class ExerciseController extends Controller
{

    /**
     * Displays a form to edit an existing Exercise entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $exercise = $em->getRepository('UJMExoBundle:Exercise')->find($id);
        $this->checkAccess($exercise);

        $workspace = $exercise->getResourceNode()->getWorkspace();

        $exoAdmin = $this->container->get('ujm.exercise_services')->isExerciseAdmin($exercise);

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
            return $this->redirect($this->generateUrl('ujm_exercise_open', array('exerciseId' => $id)));
        }
    }

    /**
     * Edits an existing Exercise entity.
     *
     */
    public function updateAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $exercise = $em->getRepository('UJMExoBundle:Exercise')->find($id);

        $entity = $em->getRepository('UJMExoBundle:Exercise')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Exercise entity.');
        }

        $editForm    = $this->createForm(new ExerciseType(), $entity);

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
                'entity'      => $entity,
                'edit_form'   => $editForm->createView(),
            )
        );
    }

    /**
     * Finds and displays a Exercise entity if the User is enrolled.
     *
     */
    public function openAction($exerciseId)
    {
        $exerciseSer = $this->container->get('ujm.exercise_services');
        
        $user = $this->container->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();
        $exercise = $em->getRepository('UJMExoBundle:Exercise')->find($exerciseId);
        $this->checkAccess($exercise);

        $allowToCompose = 0;
        $exoAdmin = $this->container->get('ujm.exercise_services')->isExerciseAdmin($exercise);

        $workspace = $exercise->getResourceNode()->getWorkspace();

        if (!$exercise) {
            throw $this->createNotFoundException('Unable to find Exercise entity.');
        }

        if (($this->controlDate($exoAdmin, $exercise) === true)
            && ($exerciseSer->controlMaxAttemps($exercise, $user, $exoAdmin) === true)
        ) {
            $allowToCompose = 1;
        }

        $nbQuestions = $em->getRepository('UJMExoBundle:ExerciseQuestion')->getCountQuestion($exerciseId);
        
        $nbUserPaper = $exerciseSer->getNbPaper($user->getId(),
                                                $exercise->getId());


            /** SII trouver l'info numAttempt **/
            $paper = $this->getDoctrine()
                ->getManager()
                ->getRepository('UJMExoBundle:Paper')
                ->getExerciseUserPapers($user->getId(), $exerciseId);
            $numAttempt = -1;
            if(count($paper) > 0){
                $paper = $paper[count($paper) - 1 ];
                $numAttempt = $paper['paper']->getNumPaper();    
            } 

            /** SII trouver l'info numAttempt **/

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
     */
    public function showQuestionsAction($id, $pageNow, $categoryToFind, $titleToFind, $displayAll)
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $allowEdit = array();
        $em = $this->getDoctrine()->getManager();
        $exercise = $em->getRepository('UJMExoBundle:Exercise')->find($id);
        $this->checkAccess($exercise);

        $workspace = $exercise->getResourceNode()->getWorkspace();

        $exoAdmin = $this->container->get('ujm.exercise_services')->isExerciseAdmin($exercise);

        $max = 10; // Max Per Page
        $request = $this->get('request');
        $page = $request->query->get('page', 1);

        if ($exoAdmin == 1) {
            $interactions = $this->getDoctrine()
                ->getManager()
                ->getRepository('UJMExoBundle:Interaction')
                ->getExerciseInteraction($em, $id, 0);

            if ($displayAll == 1) {
                $max = count($interactions);
            }

            $questionWithResponse = array();
            foreach ($interactions as $interaction) {
                $response = $em->getRepository('UJMExoBundle:Response')
                    ->findBy(array('interaction' => $interaction->getId()));
                if (count($response) > 0) {
                    $questionWithResponse[$interaction->getId()] = 1;
                } else {
                    $questionWithResponse[$interaction->getId()] = 0;
                }

                $share = $this->container->get('ujm.exercise_services')->controlUserSharedQuestion(
                        $interaction->getQuestion()->getId());

                if ($user->getId() == $interaction->getQuestion()->getUser()->getId()) {
                    $allowEdit[$interaction->getId()] = 1;
                } else if(count($share) > 0) {
                    $allowEdit[$interaction->getId()] = $share[0]->getAllowToModify();
                } else {
                    $allowEdit[$interaction->getId()] = 0;
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
                    'exerciseID'           => $id,
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
    *To import in this Exercise a Question of the User's bank.
    *
    */
    public function importQuestionAction($exoID, $pageGoNow, $maxPage, $nbItem, $displayAll)
    {
        $em = $this->getDoctrine()->getManager();
        $exercise = $em->getRepository('UJMExoBundle:Exercise')->find($exoID);
        $this->checkAccess($exercise);

        $workspace = $exercise->getResourceNode()->getWorkspace();

        $user = $this->container->get('security.context')->getToken()->getUser();
        $uid = $user->getId();

        $exoAdmin = $this->container->get('ujm.exercise_services')->isExerciseAdmin($exercise);

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

            $interactions = $this->getDoctrine()
                ->getManager()
                ->getRepository('UJMExoBundle:Interaction')
                ->getUserInteractionImport($this->getDoctrine()->getManager(), $uid, $exoID);

            $shared = $em->getRepository('UJMExoBundle:Share')
                    ->getUserInteractionSharedImport($exoID, $uid, $em);

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
                $sharedWithMe[] = $em->getRepository('UJMExoBundle:Interaction')
                    ->findOneBy(array('question' => $shared[$i]->getQuestion()->getId()));
            }

            $doublePagination = $this->doublePagination($interactions, $sharedWithMe, $max, $pagerMy, $pagerShared);

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
                    'exoID'        => $exoID,
                    'sharedWithMe' => $sharedWithMePager,
                    'pagerMy'      => $pagerfantaMy,
                    'pagerShared'  => $pagerfantaShared,
                    'pageToGo'     => $pageGoNow,
                    '_resource'    => $exercise,
                    'displayAll'   => $displayAll
                )
            );
        } else {
            return $this->redirect($this->generateUrl('ujm_exercise_open', array('exerciseId' => $exoID)));
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
            $pageGoNow = $request->request->get('pageGoNow');
            $qid = $request->request->get('qid');

            $em = $this->getDoctrine()->getManager();

            foreach ($qid as $q) {
                $question = $this->getDoctrine()
                    ->getManager()
                    ->getRepository('UJMExoBundle:Question')
                    ->find($q);

                if (count($question) > 0) {

                    $exo = $em->getRepository('UJMExoBundle:Exercise')->find($exoID);
                    $question = $em->getRepository('UJMExoBundle:Question')->find($q);

                    $eq = new ExerciseQuestion($exo, $question);

                    $dql = 'SELECT max(eq.ordre) FROM UJM\ExoBundle\Entity\ExerciseQuestion eq '
                         . 'WHERE eq.exercise='.$exoID;

                    $query = $em->createQuery($dql);
                    $maxOrdre = $query->getResult();

                    $eq->setOrdre((int) $maxOrdre[0][1] + 1);
                    $em->persist($eq);

                    $em->flush();
                }
            }

            $url = (string)$this->generateUrl('ujm_exercise_questions',array('id' => $exoID,'pageNow' => $pageGoNow));

            return new \Symfony\Component\HttpFoundation\Response($url);
         } else {
            return $this->redirect($this->generateUrl('ujm_exercise_import_question', array('exoID' => $exoID)));
        }
    }

    /**
     * Delete the Question of the exercise.
     *
     */
    public function deleteQuestionAction($exoID, $qid, $pageNow, $maxPage, $nbItem, $lastPage)
    {
        $em = $this->getDoctrine()->getManager();
        $exercise = $em->getRepository('UJMExoBundle:Exercise')->find($exoID);

        $this->checkAccess($exercise);

        $exoAdmin = $this->container->get('ujm.exercise_services')->isExerciseAdmin($exercise);

        if ($exoAdmin == 1) {
            $em = $this->getDoctrine()->getManager();
            $eq = $em->getRepository('UJMExoBundle:ExerciseQuestion')
                ->findOneBy(array('exercise' => $exoID, 'question' => $qid));
            $em->remove($eq);
            $em->flush();

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
                    'id' => $exoID,
                    'pageNow' => $pageNow
                )
            )
        );
    }

    /**
     * To create a paper in order to take an assessment
     *
     */
    public function exercisePaperAction($id)
    {
        $exerciseSer = $this->container->get('ujm.exercise_services');
        
        $user = $this->container->get('security.context')->getToken()->getUser();
        $uid = $user->getId();

        $em = $this->getDoctrine()->getManager();
        $exercise = $em->getRepository('UJMExoBundle:Exercise')->find($id);

        $exoAdmin = $exerciseSer->isExerciseAdmin($exercise);
        $this->checkAccess($exercise);

        $workspace = $exercise->getResourceNode()->getWorkspace();

        if ($this->controlDate($exoAdmin, $exercise) === true) {
            $session = $this->getRequest()->getSession();

            $dql = 'SELECT max(p.numPaper) FROM UJM\ExoBundle\Entity\Paper p '
                . 'WHERE p.exercise='.$id.' AND p.user='.$uid;
            $query = $em->createQuery($dql);
            $maxNumPaper = $query->getResult();

            //Verify if it exists a not finished paper
            $paper = $this->getDoctrine()
                ->getManager()
                ->getRepository('UJMExoBundle:Paper')
                ->getPaper($user->getId(), $id);

            //if not exist a paper no finished
            if (count($paper) == 0) {
                if ($exerciseSer->controlMaxAttemps($exercise, $user, $exoAdmin) === false) {
                   return $this->redirect($this->generateUrl('ujm_paper_list', array('exoID' => $id)));
                }

                $paper = new Paper();
                $paper->setNumPaper((int) $maxNumPaper[0][1] + 1);
                $paper->setExercise($exercise);
                $paper->setUser($user);
                $paper->setStart(new \Datetime());
                $paper->setArchive(0);
                $paper->setInterupt(0);

                if ( ($exercise->getNbQuestion() > 0) && ($exercise->getKeepSameQuestion()) == true ) {
                    $papers = $this->getDoctrine()
                        ->getManager()
                        ->getRepository('UJMExoBundle:Paper')
                        ->getExerciseUserPapers($user->getId(), $id);
                    if(count($papers) == 0) {
                        $tab = $this->prepareInteractionsPaper($id, $exercise);
                        $interactions  = $tab['interactions'];
                        $orderInter    = $tab['orderInter'];
                        $tabOrderInter = $tab['tabOrderInter'];
                    } else {
                        $lastPaper = $papers[count($papers) - 1];
                        $orderInter = $lastPaper->getOrdreQuestion();
                        $tabOrderInter = explode(';', $lastPaper->getOrdreQuestion());
                        unset($tabOrderInter[count($tabOrderInter) - 1]);
                        $interactions[0] = $em->getRepository('UJMExoBundle:Interaction')->find($tabOrderInter[0]);
                    }
                } else {
                    $tab = $this->prepareInteractionsPaper($id, $exercise);
                    $interactions  = $tab['interactions'];
                    $orderInter    = $tab['orderInter'];
                    $tabOrderInter = $tab['tabOrderInter'];
                }

                $paper->setOrdreQuestion($orderInter);
                $em->persist($paper);
                $em->flush();
            } else {
                $paper = $paper[0];
                $tabOrderInter = explode(';', $paper->getOrdreQuestion());
                unset($tabOrderInter[count($tabOrderInter) - 1]);
                $interactions[0] = $em->getRepository('UJMExoBundle:Interaction')->find($tabOrderInter[0]);
            }

            $session->set('tabOrderInter', $tabOrderInter);
            $session->set('paper', $paper->getId());
            $session->set('exerciseID', $id);

            $typeInter = $interactions[0]->getType();

            //To display selectioned question
            return $this->displayQuestion(1, $interactions[0], $typeInter, 
                    $exercise->getDispButtonInterrupt(),
                    $exercise->getMaxAttempts(),
                    $workspace, $paper);
        } else {
        	// Add flashbag message to tell that quizz is finished 
            return $this->redirect($this->generateUrl('ujm_paper_list', array('exoID' => $id)));
        }
    }

    /**
     * To create new paper
     *
     */
    private function prepareInteractionsPaper($id, $exercise)
    {
        $em = $this->getDoctrine()->getManager();
        $orderInter = '';
        $tabOrderInter = array();
        $tab = array();

        $interactions = $this->getDoctrine()
                        ->getManager()
                        ->getRepository('UJMExoBundle:Interaction')
                        ->getExerciseInteraction(
                            $this->getDoctrine()->getManager(), $id,
                            $exercise->getShuffle(), $exercise->getNbQuestion()
                        );

        foreach ($interactions as $interaction) {
            $orderInter = $orderInter.$interaction->getId().';';
            $tabOrderInter[] = $interaction->getId();
        }

        $tab['interactions']  = $interactions;
        $tab['orderInter']    = $orderInter;
        $tab['tabOrderInter'] = $tabOrderInter;

        return $tab;
    }

    /**
     * To navigate in the Questions of the assessment
     *
     */
    public function exercisePaperNavAction(Request $request)
    {
        $response = '';
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();
        $paper = $em->getRepository('UJMExoBundle:Paper')->find($session->get('paper'));
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
        $exerciseSer = $this->container->get('ujm.exercise_services');
        $ip = $exerciseSer->getIP();
        $interactionToValidatedID = $request->get('interactionToValidated');
        $response = $this->getDoctrine()
            ->getManager()
            ->getRepository('UJMExoBundle:Response')
            ->getAlreadyResponded($paper->getId(), $interactionToValidatedID);

        switch ($typeInterToRecorded) {
            case "InteractionQCM":
                $res = $exerciseSer->responseQCM($request, $session->get('paper'));
                break;

            case "InteractionGraphic":
                $res = $exerciseSer->responseGraphic($request, $session->get('paper'));
                break;

            case "InteractionHole":
                $res = $exerciseSer->responseHole($request, $session->get('paper'));
                break;

            case "InteractionOpen":
                $res = $exerciseSer->responseOpen($request, $session->get('paper'));
                break;
        }

        if (count($response) == 0) {
            //INSERT Response
            $response = new Response();
            $response->setNbTries(1);
            $response->setPaper($paper);
            $response->setInteraction($em->getRepository('UJMExoBundle:Interaction')->find($interactionToValidatedID));
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

        $em->persist($response);
        $em->flush();

        //To display selectioned question
        $numQuestionToDisplayed = $request->get('numQuestionToDisplayed');

        if ($numQuestionToDisplayed == 'finish') {
            return $this->finishExercise($session);
        } else if ($numQuestionToDisplayed == 'interupt') {
            return $this->interuptExercise();
        } else {
            $interactionToDisplayedID = $tabOrderInter[$numQuestionToDisplayed - 1];
            $interactionToDisplay = $em->getRepository('UJMExoBundle:Interaction')->find($interactionToDisplayedID);
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
     */
    public function docimologyAction($exerciseId, $nbPapers)
    {
        $em = $this->getDoctrine()->getManager();
        $exercise = $em->getRepository('UJMExoBundle:Exercise')->find($exerciseId);
        $this->checkAccess($exercise);

        $eqs = $em->getRepository('UJMExoBundle:ExerciseQuestion')->findBy(
            array('exercise' => $exerciseId),
            array('ordre' => 'ASC')
        );

        $papers = $em->getRepository('UJMExoBundle:Paper')->getExerciseAllPapers($exerciseId);

        if ($this->container->get('ujm.exercise_services')->isExerciseAdmin($exercise)) {

            $workspace = $exercise->getResourceNode()->getWorkspace();

            $parameters['nbPapers']  = $nbPapers;
            $parameters['workspace'] = $workspace;
            $parameters['exoID']     = $exerciseId;
            $parameters['_resource'] = $exercise;

            if ($nbPapers >= 12) {
                $histoMark = $this->histoMark($exerciseId);
                $histoSuccess = $this->histoSuccess($exerciseId, $eqs, $papers);

                if ($exercise->getNbQuestion() == 0) {
                    $histoDiscrimination = $this->histoDiscrimination($exerciseId, $eqs, $papers);
                } else {
                    $histoDiscrimination['coeffQ'] = 'none';
                }

                $histoMeasureDifficulty = $this->histoMeasureOfDifficulty($exerciseId, $eqs);

                $parameters['scoreList']          = $histoMark['scoreList'];
                $parameters['frequencyMarks']     = $histoMark['frequencyMarks'];
                $parameters['maxY']               = $histoMark['maxY'];
                $parameters['questionsList']      = $histoSuccess['questionsList'];
                $parameters['seriesResponsesTab'] = $histoSuccess['seriesResponsesTab'];
                $parameters['maxY2']              = $histoSuccess['maxY'];
                $parameters['coeffQ']             = $histoDiscrimination['coeffQ'];
                $parameters['MeasureDifficulty']  = $histoMeasureDifficulty;
            }

            return $this->render('UJMExoBundle:Exercise:docimology.html.twig', $parameters);
        } else {

            return $this->redirect($this->generateUrl('ujm_exercise_open', array('exerciseId' => $exerciseId)));
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
        $numQuestionToDisplayed, $interactionToDisplay,
        $typeInterToDisplayed, $dispButtonInterrupt, $maxAttempsAllowed, 
        $workspace, $paper
    )
    {
    	$user = $this->container->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $session = $this->getRequest()->getSession();
        $tabOrderInter = $session->get('tabOrderInter');
        $user = $this->getUser();

        switch ($typeInterToDisplayed) {
            case "InteractionQCM":

                $interactionToDisplayed = $this->getDoctrine()
                    ->getManager()
                    ->getRepository('UJMExoBundle:InteractionQCM')
                    ->getInteractionQCM($interactionToDisplay->getId());

                if ($interactionToDisplayed[0]->getShuffle()) {
                    $interactionToDisplayed[0]->shuffleChoices();
                } else {
                    $interactionToDisplayed[0]->sortChoices();
                }

                $responseGiven = $this->getDoctrine()
                    ->getManager()
                    ->getRepository('UJMExoBundle:Response')
                    ->getAlreadyResponded($session->get('paper'), $interactionToDisplay->getId());

                if (count($responseGiven) > 0) {
                    $responseGiven = $responseGiven[0]->getResponse();
                } else {
                    $responseGiven = '';
                }

                break;

            case "InteractionGraphic":

                $interactionToDisplayed = $this->getDoctrine()
                    ->getManager()
                    ->getRepository('UJMExoBundle:InteractionGraphic')
                    ->getInteractionGraphic($interactionToDisplay->getId());

                $coords = $em->getRepository('UJMExoBundle:Coords')
                    ->findBy(array('interactionGraphic' => $interactionToDisplayed[0]->getId()));

                $responseGiven = $this->getDoctrine()
                    ->getManager()
                    ->getRepository('UJMExoBundle:Response')
                    ->getAlreadyResponded($session->get('paper'), $interactionToDisplay->getId());

                if (count($responseGiven) > 0) {
                    $responseGiven = $responseGiven[0]->getResponse();
                } else {
                    $responseGiven = '';
                }

                $array['listCoords'] = $coords;

                break;

            case "InteractionHole":
                $interactionToDisplayed = $this->getDoctrine()
                    ->getManager()
                    ->getRepository('UJMExoBundle:InteractionHole')
                    ->getInteractionHole($interactionToDisplay->getId());

                $responseGiven = $this->getDoctrine()
                    ->getManager()
                    ->getRepository('UJMExoBundle:Response')
                    ->getAlreadyResponded($session->get('paper'), $interactionToDisplay->getId());

                if (count($responseGiven) > 0) {
                    $responseGiven = $responseGiven[0]->getResponse();
                } else {
                    $responseGiven = '';
                }

                break;

            case "InteractionOpen":

                $interactionToDisplayed = $this->getDoctrine()
                    ->getManager()
                    ->getRepository('UJMExoBundle:InteractionOpen')
                    ->getInteractionOpen($interactionToDisplay->getId());

                $responseGiven = $this->getDoctrine()
                                      ->getManager()
                                      ->getRepository('UJMExoBundle:Response')
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
        $paper = $em->getRepository('UJMExoBundle:Paper')->find($session->get('paper'));
        $paper->setInterupt(0);
        $paper->setEnd(new \Datetime());
        $em->persist($paper);
        $em->flush();

        $this->container->get('ujm.exercise_services')->manageEndOfExercise($paper);

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

        $paper = $em->getRepository('UJMExoBundle:Paper')->find($session->get('paper'));
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
    private function histoMark($exerciseId)
    {
        $exerciseSer = $this->container->get('ujm.exercise_services');
        $em = $this->getDoctrine()->getManager();
        $maxY = 4;
        $exercise = $em->getRepository('UJMExoBundle:Exercise')->find($exerciseId);
        if ($exercise->getNbQuestion() == 0) {
            $exoScoreMax = $this->container->get('ujm.exercise_services')->getExerciseTotalScore($exerciseId);
        }
        //$marks = $this->container->get('ujm.exercise_services')->getExerciseHistoMarks($exerciseId);
        $marks = $em->getRepository('UJMExoBundle:Exercise')->getExerciseMarks($exerciseId, 'noteExo');
        $tabMarks = array();
        $histoMark = array();

        foreach ($marks as $mark) {
            if ($exercise->getNbQuestion() > 0) {
                $exoScoreMax = $this->container->get('ujm.exercise_services')->getExercisePaperTotalScore($mark['paper']);
            }
            $scoreU = round(($mark["noteExo"] / $exoScoreMax) * 20, 2);

            $score = $exerciseSer->roundUpDown($scoreU);

            if (isset($tabMarks[(string) $score])) {
                $tabMarks[(string) $score] += 1;
            } else {
                $tabMarks[(string) $score] = 1;
            }
        }

        ksort($tabMarks);
        $scoreList = implode(",", array_keys($tabMarks));//echo $scoreList;die();

        if (max($tabMarks) > 4) {
            $maxY = max($tabMarks);
        }

        $frequencyMarks = implode(",", $tabMarks);

        $histoMark['maxY']           = $maxY;
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
        $exerciseSer = $this->container->get('ujm.exercise_services');
        $questionsResponsesTab = array();
        $seriesResponsesTab = array();
        $seriesResponsesTab[0] = '';
        $seriesResponsesTab[1] = '';
        $seriesResponsesTab[2] = '';
        $seriesResponsesTab[3] = '';
        $questionList = array();
        $histoSuccess = array();
        $maxY = 4;

        foreach ($eqs as $eq) {
            $questionList[] = $eq->getQuestion()->getTitle();

            $responsesTab = $this->getCorrectAnswer($exerciseId, $eq, $em, $exerciseSer);

            $questionsResponsesTab[$eq->getQuestion()->getId()] = $responsesTab;

        }

        //no response
        foreach ($papers as $paper) {
            $interQuestions = $paper->getOrdreQuestion();
            $interQuestions = substr($interQuestions, 0, strlen($interQuestions) - 1);

            $interQuestionsTab = explode(";", $interQuestions);
            foreach ($interQuestionsTab as $interQuestion) {
                $flag = $em->getRepository('UJMExoBundle:Response')->findOneBy(
                    array(
                        'interaction' => $interQuestion,
                        'paper' => $paper->getId()
                    )
                );

                if (!$flag || $flag->getResponse() == '') {
                    $interaction = $em->getRepository('UJMExoBundle:Interaction')->find($interQuestion);
                    $questionsResponsesTab[$interaction->getQuestion()->getId()]['noResponse'] += 1;
                }
            }
        }

        //creation serie for the graph jqplot
        foreach ($questionsResponsesTab as $responses) {
            $tot = (int) $responses['correct'] + (int) $responses['partiallyRight'] + (int) $responses['wrong'] + (int) $responses['noResponse'];
            if ($tot > $maxY ) {
                $maxY = $tot;
            }
            $seriesResponsesTab[0] .= (string) $responses['correct'].',';
            $seriesResponsesTab[1] .= (string) $responses['partiallyRight'].',';
            $seriesResponsesTab[2] .= (string) $responses['wrong'].',';
            $seriesResponsesTab[3] .= (string) $responses['noResponse'].',';
        }

        foreach ($seriesResponsesTab as $s) {
            $s = substr($s, 0, strlen($s) - 1);
        }

        $histoSuccess['questionsList'] = $questionList;
        $histoSuccess['seriesResponsesTab'] = $seriesResponsesTab;
        $histoSuccess['maxY'] = $maxY;

        return $histoSuccess;
    }

    /**
     * To draw histogram of discrimination
     *
     */
    private function histoDiscrimination($exerciseId, $eqs, $papers)
    {
        $em = $this->getDoctrine()->getManager();
        $tabScoreExo = array();
        $tabScoreQ = array();
        $tabScoreAverageQ = array();
        $productMarginMark = array();
        $tabCoeffQ = array();
        $histoDiscrimination = array();
        $scoreAverageExo = 0;
        $marks = $em->getRepository('UJMExoBundle:Exercise')->getExerciseMarks($exerciseId, 'paper');
        $exercise = $em->getRepository('UJMExoBundle:Exercise')->find($exerciseId);

        //Array of exercise's scores
        foreach ($marks as $mark) {
            $tabScoreExo[] = $mark["noteExo"];
        }
        //var_dump($tabScoreExo);die();

        //Average exercise's score
        foreach ($tabScoreExo as $se) {
            $scoreAverageExo += (float) $se;
        }

        $scoreAverageExo = $scoreAverageExo / count($tabScoreExo);

        //Array of each question's score
        foreach ($eqs as $eq) {
            $interaction = $em->getRepository('UJMExoBundle:Interaction')->getInteraction($eq->getQuestion()->getId());
            $responses = $em->getRepository('UJMExoBundle:Response')
                            ->getExerciseInterResponses($exerciseId, $interaction[0]->getId());
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

        return sqrt(array_sum(array_map(array($this, "sd_square"), $array, array_fill(0, count($array), (array_sum($array) / count($array))))) / (count($array) - 1));
    }

    /**
     *
     * @param type $exerciseId
     * @param type $eqs
     * @return type
     */
    private function histoMeasureOfDifficulty($exerciseId, $eqs)
    {
        $em = $this->getDoctrine()->getManager();
        $exerciseSer = $this->container->get('ujm.exercise_services');
        $up = array();
        $down = array();
        $measureTab = array();

        foreach ($eqs as $eq) {

            $responsesTab = $this->getCorrectAnswer($exerciseId, $eq, $em, $exerciseSer);

            $up[] = $responsesTab['correct'];
            $down[] = (int) $responsesTab['correct'] + (int) $responsesTab['partiallyRight'] + (int) $responsesTab['wrong'];
        }

        $stop = count($up);

        for ($i = 0; $i < $stop; $i++) {

            $measureTab[$i] = $exerciseSer->roundUpDown(($up[$i] / $down[$i]) * 100);
        }

        $measure = implode(",", $measureTab);

        return $measure;
    }

    /**
     * To get the number of answers with the 'correct' status
     *
     */
    private function getCorrectAnswer($exerciseId, $eq, $em, $exerciseSer)
    {
        $em = $this->getDoctrine()->getManager();

        $scoreMax = 0;

        $interaction = $em->getRepository('UJMExoBundle:Interaction')->getInteraction($eq->getQuestion()->getId());

        $responses = $em->getRepository('UJMExoBundle:Response')
                        ->getExerciseInterResponsesWithCount($exerciseId, $interaction[0]->getId());

        switch ( $interaction[0]->getType()) {
            case "InteractionQCM":
                $interQCM = $em->getRepository('UJMExoBundle:InteractionQCM')
                               ->getInteractionQCM($interaction[0]->getId());
                $scoreMax = $exerciseSer->qcmMaxScore($interQCM[0]);
                $responsesTab = $this->responseStatus($responses, $scoreMax);
                break;

            case "InteractionGraphic":
                $interGraphic = $em->getRepository('UJMExoBundle:InteractionGraphic')
                                   ->getInteractionGraphic($interaction[0]->getId());
                $scoreMax = $exerciseSer->graphicMaxScore($interGraphic[0]);
                $responsesTab = $this->responseStatus($responses, $scoreMax);
                break;

            case "InteractionHole":
                $interHole = $em->getRepository('UJMExoBundle:InteractionHole')
                                ->getInteractionHole($interaction[0]->getId());
                $scoreMax = $exerciseSer->holeMaxScore($interHole[0]);
                $responsesTab = $this->responseStatus($responses, $scoreMax);
                break;

            case "InteractionOpen":
                $interOpen = $em->getRepository('UJMExoBundle:InteractionOpen')
                                   ->getInteractionOpen($interaction[0]->getId());
                $scoreMax = $exerciseSer->openMaxScore($interOpen[0]);
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
